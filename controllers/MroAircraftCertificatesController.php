<?php

namespace app\controllers;

use Yii;
use app\models\MroProfile;
use app\models\MroAircraftCertificate;
use app\models\AircraftModel;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use yii\data\Pagination;

class MroAircraftCertificatesController extends Controller
{
    /*
     * LIMITES DU FICHIER CERTIFICAT (correctif 2026-11)
     * 10 Mo, comme les autres documents de la plateforme (devis MRO, avenants).
     * Cette valeur doit rester inférieure à post_max_size (12 Mo actuellement),
     * sinon PHP vide la requête avant même que ce contrôle puisse s'exécuter.
     * Les vues create/update lisent ces constantes pour le contrôle navigateur.
     */
    public const MAX_CERTIFICATE_BYTES = 10 * 1024 * 1024;
    public const ALLOWED_CERTIFICATE_EXTENSIONS = ['pdf', 'doc', 'docx'];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to MRO users.
                            return Yii::$app->session->get('user_type') === 'mro';
                        },
                    ],
                ],
            ],
            // Une suppression ne doit jamais être déclenchée par une URL GET.
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ];
    }

    /**
     * Displays all aircraft certificates associated with the logged-in MRO profile.
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $search = trim((string) Yii::$app->request->get('search', ''));

        $certificateTable = MroAircraftCertificate::tableName();
        $aircraftModelTable = AircraftModel::tableName();

        $query = MroAircraftCertificate::find()
            ->where([$certificateTable . '.mro_id' => $mroId])
            ->joinWith('aircraftModel');

        // Apply search filter only when the search value is not empty.
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', $certificateTable . '.certificate', $search],
                ['like', $aircraftModelTable . '.manufacturer', $search],
                ['like', $aircraftModelTable . '.model', $search],
            ]);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
            'params' => Yii::$app->request->queryParams,
        ]);

        $certificates = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy([
                $aircraftModelTable . '.manufacturer' => SORT_ASC,
                $aircraftModelTable . '.model' => SORT_ASC,
            ])
            ->all();

        return $this->render('index', [
            'title' => 'MRO Aircraft Certificates',
            'mroProfile' => $mroProfile,
            'certificates' => $certificates,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    /**
     * Displays a specific aircraft certificate.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id = null)
    {
        $certificate = $this->findCertificateModel($id);

        return $this->render('view', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * Valide puis enregistre le fichier certificat envoyé.
     *
     * Contrôles (avant toute écriture sur le disque) :
     *  - erreur d'envoi PHP (ex. upload_max_filesize dépassé) ;
     *  - taille maximale MAX_CERTIFICATE_BYTES ;
     *  - extension parmi ALLOWED_CERTIFICATE_EXTENSIONS.
     * Le nom de fichier est aléatoire et l'extension est reprise en minuscules.
     *
     * @return bool true si le fichier est valide et enregistré ; sinon l'erreur
     *              est ajoutée sur l'attribut « certificate » du modèle.
     */
    private function storeCertificateFile(MroAircraftCertificate $certificate, UploadedFile $file): bool
    {
        $validator = new FileValidator([
            'extensions' => self::ALLOWED_CERTIFICATE_EXTENSIONS,
            // Le type MIME des .doc/.docx varie selon les serveurs : on s'en tient à l'extension.
            'checkExtensionByMimeType' => false,
            'maxSize' => self::MAX_CERTIFICATE_BYTES,
            'tooBig' => 'The certificate file is too large. Maximum size: 10 MB.',
            'wrongExtension' => 'Only PDF, DOC and DOCX files are accepted.',
        ]);

        $error = null;
        if (!$validator->validate($file, $error)) {
            $certificate->addError('certificate', $error);
            // Le champ natif est masqué par la vue : le message est aussi affiché en bandeau.
            Yii::$app->session->setFlash('error', $error);
            return false;
        }

        $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . strtolower($file->extension);
        if (!$file->saveAs($uploadPath)) {
            $certificate->addError('certificate', 'The certificate file could not be saved. Please try again.');
            return false;
        }

        $certificate->certificate = $uploadPath;
        return true;
    }

    /**
     * Creates a new aircraft certificate associated with an MRO profile.
     *
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $certificate = new MroAircraftCertificate();
        $certificate->mro_id = $mroId;

        if ($certificate->load(Yii::$app->request->post())) {
            /*
             * CORRECTIF IDOR : mro_id est un attribut « safe » du modèle (règle
             * integer). Il était fixé AVANT load(), donc un POST forgé pouvait
             * rattacher le certificat à un autre MRO. Il est réimposé ici depuis
             * la session serveur.
             */
            $certificate->mro_id = $mroId;

            // Save uploaded certificate file if provided.
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');

            /*
             * CORRECTIF : le fichier était écrit sur le disque AVANT toute
             * vérification (ni taille, ni type). Il est maintenant validé puis
             * enregistré par storeCertificateFile() ; en cas d'erreur, le
             * formulaire est réaffiché avec le message, sans rien enregistrer.
             */
            $fileStored = $certificateFile === null || $this->storeCertificateFile($certificate, $certificateFile);

            if ($fileStored && $certificate->validate() && $certificate->save()) {
                Yii::$app->session->setFlash('success', 'Aircraft certificate added successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'certificate' => $certificate,
            'mroProfile' => $mroProfile,
            'aircraftModels' => AircraftModel::find()->all(),
        ]);
    }

    /**
     * Updates an existing aircraft certificate.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id = null)
    {
        $certificate = $this->findCertificateModel($id);

        $mroId = Yii::$app->session->get('mro_id');
        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        // Get the selected aircraft model without relying on lazy loading in the form.
        $aircraftModel = AircraftModel::find()
            ->where(['aircraft_model_id' => $certificate->aircraft_model_id])
            ->one();

        // Propriétaire d'origine (déjà contrôlé par findCertificateModel()).
        $ownerMroId = (int) $certificate->mro_id;
        // Fichier actuel, conservé si aucun nouveau fichier n'est envoyé.
        $currentCertificatePath = $certificate->certificate;

        if ($certificate->load(Yii::$app->request->post())) {
            // CORRECTIF IDOR : le certificat ne peut pas être transféré à un autre MRO par un POST forgé.
            $certificate->mro_id = $ownerMroId;

            // Keep existing certificate file unless a new file is uploaded.
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');

            /*
             * CORRECTIF : fileInput() de Yii envoie toujours un champ caché vide
             * « certificate ». load() remplaçait donc le chemin du fichier existant
             * par une chaîne vide lorsqu'aucun nouveau fichier n'était choisi.
             * On restaure le fichier actuel avant de traiter un éventuel remplacement.
             */
            $certificate->certificate = $currentCertificatePath;

            // CORRECTIF : même validation qu'à la création (taille et type avant écriture).
            $fileStored = $certificateFile === null || $this->storeCertificateFile($certificate, $certificateFile);

            if ($fileStored && $certificate->validate() && $certificate->save()) {
                Yii::$app->session->setFlash('success', 'Aircraft certificate updated successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'certificate' => $certificate,
            'mroProfile' => $mroProfile,
            'aircraftModel' => $aircraftModel,
        ]);
    }

    /**
     * Deletes an aircraft certificate.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id = null)
    {
        $certificate = $this->findCertificateModel($id);
        $certificate->delete();

        Yii::$app->session->setFlash('success', 'Aircraft certificate deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Returns aircraft models by manufacturer for dependent dropdowns.
     *
     * @param string $manufacturer
     * @return array
     */
    public function actionGetModelsByManufacturer($manufacturer)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return AircraftModel::find()
            ->where(['manufacturer' => $manufacturer])
            ->orderBy(['model' => SORT_ASC])
            ->select(['aircraft_model_id as id', 'model as name'])
            ->asArray()
            ->all();
    }

    /**
     * Finds certificate model and ensures it belongs to the logged-in MRO.
     *
     * @param int $id
     * @return MroAircraftCertificate
     * @throws NotFoundHttpException
     */
    protected function findCertificateModel($id)
    {
        if ($id === null || $id === '') {
            throw new NotFoundHttpException('Aircraft certificate ID is missing.');
        }

        $mroId = Yii::$app->session->get('mro_id');

        // Use findOne($id) so Yii resolves the real primary key of the model.
        // This works whether the primary key is named id, certificate_id,
        // mro_aircraft_certificate_id, or another configured PK.
        $certificate = MroAircraftCertificate::findOne($id);

        if (!$certificate || (string) $certificate->mro_id !== (string) $mroId) {
            throw new NotFoundHttpException('Aircraft certificate not found.');
        }

        return $certificate;
    }
}
