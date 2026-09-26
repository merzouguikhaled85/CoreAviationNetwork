<?php

namespace app\controllers;

use app\models\AoRequestsApplications;
use app\components\UrlIdHelper;
use app\models\Currency;
use app\models\MroRequestApply;
use app\models\RepairReport;
use app\models\RequestChange;
use app\models\RequestChangeDocument;
use app\models\Requests;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Orchestre les avenants sans modifier le statut opérationnel de la Request.
 *
 * Chaque action vérifie le propriétaire côté serveur. Les identifiants AO/MRO
 * envoyés par le navigateur ne sont jamais utilisés pour déterminer les droits.
 */
class RequestChangesController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function () {
                            return in_array(Yii::$app->session->get('user_type'), ['ao', 'mro'], true);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'accept' => ['POST'],
                    'reject' => ['POST'],
                    'accept-po' => ['POST'],
                    'reject-po' => ['POST'],
                    'accept-quote' => ['POST'],
                    'reject-quote' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * L'AO décrit l'avenant. La Request garde son statut et son MRO actuels.
     */
    public function actionCreate($requestId)
    {
        $this->requireRole('ao');
        $requestId = UrlIdHelper::decodeOrFail($requestId, 'Invalid Request link.');
        $request = Requests::findOne($requestId);

        if (!$request || (int) $request->ao_id !== (int) Yii::$app->session->get('ao_id')) {
            throw new NotFoundHttpException('Request not found.');
        }

        if (!in_array($request->status, [Requests::STATUS_WORK_ACCEPTED, Requests::STATUS_WORK_STARTED], true)) {
            throw new ForbiddenHttpException('A change order is allowed only after the work has been accepted or started.');
        }

        $existing = RequestChange::find()
            ->where(['request_id' => $request->request_id, 'status' => RequestChange::activeStatuses()])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($existing) {
            return $this->redirect(['view', 'id' => UrlIdHelper::encode($existing->id)]);
        }

        $application = $this->findAssignedApplication($request->request_id);
        /*
         * TRAVAIL EN COURS : le dernier PO et le dernier rapport de l'application
         * affectée sont chargés uniquement pour renseigner le résumé en lecture seule.
         */
        $currentPo = AoRequestsApplications::find()
            ->where([
                'request_id' => $request->request_id,
                'application_id' => $application->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $latestReport = RepairReport::find()
            ->where(['mro_request_apply_id' => $application->id])
            ->orderBy(['repair_report_id' => SORT_DESC])
            ->one();
        $model = new RequestChange();

        $payload = Yii::$app->request->post($model->formName());
        if (is_array($payload)) {
            /* LISTE BLANCHE : le navigateur ne peut renseigner que le contenu métier proposé. */
            $model->reason = $payload['reason'] ?? null;
            $model->added_tasks = $payload['added_tasks'] ?? null;
            $model->removed_tasks = $payload['removed_tasks'] ?? null;

            /* Les clés de rattachement sont toujours dérivées de la session et de la base. */
            $model->request_id = (int) $request->request_id;
            $model->mro_request_apply_id = (int) $application->id;
            $model->ao_id = (int) $request->ao_id;
            $model->mro_id = (int) $application->mro_id;
            $model->version = ((int) RequestChange::find()->where(['request_id' => $request->request_id])->max('version')) + 1;
            $model->status = RequestChange::STATUS_PENDING_MRO;
            $model->original_request_status = (string) $request->status;

            if ($model->save()) {
                $this->notify('mro', $model->mro_id, 'New change request for Request #' . $request->request_id, $model);
                Yii::$app->session->setFlash('success', 'The change request was sent to the assigned MRO.');
                return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'request' => $request,
            'application' => $application,
            'currentPo' => $currentPo,
            'latestReport' => $latestReport,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findAuthorizedChange($id)]);
    }

    /** Le MRO accepte le principe de l'avenant, pas encore ses conditions financières. */
    public function actionAccept($id)
    {
        $this->requireRole('mro');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_MRO]);

        $model->status = RequestChange::STATUS_AWAITING_UPDATED_PO;
        $model->mro_reviewed_at = gmdate('Y-m-d H:i:s');
        $model->mro_rejection_reason = null;
        $model->save(false);

        $this->notify('ao', $model->ao_id, 'Change accepted: please upload the revised PO for Request #' . $model->request_id, $model);
        Yii::$app->session->setFlash('success', 'The change was accepted. The revised PO is now required.');
        return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
    }

    /** Le rejet conserve automatiquement le périmètre, le PO et le devis précédents. */
    public function actionReject($id)
    {
        $this->requireRole('mro');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_MRO]);
        $reason = trim((string) Yii::$app->request->post('reason'));
        if ($reason === '') {
            Yii::$app->session->setFlash('error', 'A rejection reason is required.');
            return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
        }

        $model->status = RequestChange::STATUS_REJECTED;
        $model->mro_rejection_reason = $reason;
        $model->mro_reviewed_at = gmdate('Y-m-d H:i:s');
        $model->save(false);

        $this->notify('ao', $model->ao_id, 'Change rejected for Request #' . $model->request_id . '. The original scope remains active.', $model);
        Yii::$app->session->setFlash('success', 'The change was rejected without modifying the active Request.');
        return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
    }

    /** L'AO charge ou remplace le PO révisé après accord de principe du MRO. */
    public function actionUploadPo($id)
    {
        $this->requireRole('ao');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_AWAITING_UPDATED_PO, RequestChange::STATUS_PO_REJECTED]);
        $model->scenario = 'uploadPo';

        if (Yii::$app->request->isPost) {
            $model->revisedPoUpload = UploadedFile::getInstance($model, 'revisedPoUpload');
            if (!$model->revisedPoUpload) {
                $model->addError('revisedPoUpload', 'Select the revised PO.');
            } elseif ($model->validate(['revisedPoUpload'])) {
                $path = $this->storeUpload($model->revisedPoUpload, 'po');
                $model->revised_po = $path;
                $model->po_rejection_reason = null;
                $model->po_uploaded_at = gmdate('Y-m-d H:i:s');
                $model->status = RequestChange::STATUS_PENDING_PO_REVIEW;
                $this->archiveDocument(
                    $model,
                    RequestChangeDocument::TYPE_PO,
                    $path,
                    null,
                    null,
                    null,
                    'ao',
                    $model->ao_id
                );
                $model->save(false);

                $this->notify('mro', $model->mro_id, 'Revised PO awaiting approval for Request #' . $model->request_id, $model);
                Yii::$app->session->setFlash('success', 'The revised PO was sent to the MRO.');
                return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
            }
        }

        return $this->render('upload-po', ['model' => $model]);
    }

    public function actionAcceptPo($id)
    {
        $this->requireRole('mro');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_PO_REVIEW]);

        $model->status = RequestChange::STATUS_AWAITING_UPDATED_QUOTE;
        $model->po_reviewed_at = gmdate('Y-m-d H:i:s');
        $model->po_rejection_reason = null;
        $this->reviewLatestDocument($model, RequestChangeDocument::TYPE_PO, RequestChangeDocument::REVIEW_ACCEPTED);
        $model->save(false);

        Yii::$app->session->setFlash('success', 'The PO was accepted. You can now submit the revised quote.');
        return $this->redirect(['submit-quote', 'id' => UrlIdHelper::encode($model->id)]);
    }

    public function actionRejectPo($id)
    {
        $this->requireRole('mro');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_PO_REVIEW]);
        $reason = trim((string) Yii::$app->request->post('reason'));
        if ($reason === '') {
            Yii::$app->session->setFlash('error', 'A PO rejection reason is required.');
            return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
        }

        $model->status = RequestChange::STATUS_PO_REJECTED;
        $model->po_rejection_reason = $reason;
        $model->po_reviewed_at = gmdate('Y-m-d H:i:s');
        $this->reviewLatestDocument($model, RequestChangeDocument::TYPE_PO, RequestChangeDocument::REVIEW_REJECTED, $reason);
        $model->save(false);

        $this->notify('ao', $model->ao_id, 'Revised PO rejected for Request #' . $model->request_id, $model);
        Yii::$app->session->setFlash('success', 'The PO was rejected. The AO can upload a corrected version.');
        return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
    }

    /** Le MRO crée son devis seulement après validation du PO révisé. */
    public function actionSubmitQuote($id)
    {
        $this->requireRole('mro');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_AWAITING_UPDATED_QUOTE, RequestChange::STATUS_QUOTE_REJECTED]);
        $model->scenario = 'submitQuote';

        /* DEVISES DE LA PLATEFORME : une seule source pour les devis initiaux et révisés. */
        $currencyList = [];
        foreach (Currency::find()->orderBy(['code' => SORT_ASC])->all() as $currency) {
            $currencyList[$currency->code] = $currency->name . ' ' . $currency->symbol . ' (' . $currency->code . ')';
        }

        $payload = Yii::$app->request->post($model->formName());
        if (is_array($payload)) {
            /* LISTE BLANCHE : aucun statut ou identifiant interne n'est accepté du formulaire. */
            $model->quote_description = $payload['quote_description'] ?? null;
            $model->quote_price = $payload['quote_price'] ?? null;
            $model->quote_currency = $payload['quote_currency'] ?? null;
            $model->revisedQuoteUpload = UploadedFile::getInstance($model, 'revisedQuoteUpload');
            $attributes = ['quote_description', 'quote_price', 'quote_currency', 'revisedQuoteUpload'];
            foreach (['quote_description', 'quote_price', 'quote_currency'] as $required) {
                if (trim((string) $model->$required) === '') {
                    $model->addError($required, 'This field is required.');
                }
            }
            if ($model->quote_currency !== null && !isset($currencyList[strtoupper((string) $model->quote_currency)])) {
                $model->addError('quote_currency', 'Select a currency available on the platform.');
            }

            if (!$model->hasErrors() && $model->validate($attributes)) {
                if ($model->revisedQuoteUpload) {
                    $model->revised_quote = $this->storeUpload($model->revisedQuoteUpload, 'quote');
                }
                $model->quote_rejection_reason = null;
                $model->quote_submitted_at = gmdate('Y-m-d H:i:s');
                $model->status = RequestChange::STATUS_PENDING_QUOTE_REVIEW;
                $this->archiveDocument(
                    $model,
                    RequestChangeDocument::TYPE_QUOTE,
                    $model->revised_quote,
                    $model->quote_description,
                    $model->quote_price,
                    $model->quote_currency,
                    'mro',
                    $model->mro_id
                );
                $model->save(false);

                $this->notify('ao', $model->ao_id, 'Revised quote awaiting approval for Request #' . $model->request_id, $model);
                Yii::$app->session->setFlash('success', 'The revised quote was sent to the AO.');
                return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
            }
        }

        return $this->render('submit-quote', [
            'model' => $model,
            'currencyList' => $currencyList,
        ]);
    }

    /** La validation AO clôt l'avenant ; le statut opérationnel reste inchangé. */
    public function actionAcceptQuote($id)
    {
        $this->requireRole('ao');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_QUOTE_REVIEW]);

        $model->status = RequestChange::STATUS_COMPLETED;
        $model->quote_reviewed_at = gmdate('Y-m-d H:i:s');
        $model->completed_at = gmdate('Y-m-d H:i:s');
        $model->quote_rejection_reason = null;
        $this->reviewLatestDocument($model, RequestChangeDocument::TYPE_QUOTE, RequestChangeDocument::REVIEW_ACCEPTED);
        $model->save(false);

        $this->notify('mro', $model->mro_id, 'Change order accepted for Request #' . $model->request_id, $model);
        Yii::$app->session->setFlash('success', 'The revised quote and change order were accepted.');
        return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
    }

    public function actionRejectQuote($id)
    {
        $this->requireRole('ao');
        $model = $this->findAuthorizedChange($id);
        $this->requireStatus($model, [RequestChange::STATUS_PENDING_QUOTE_REVIEW]);
        $reason = trim((string) Yii::$app->request->post('reason'));
        if ($reason === '') {
            Yii::$app->session->setFlash('error', 'A quote rejection reason is required.');
            return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
        }

        $model->status = RequestChange::STATUS_QUOTE_REJECTED;
        $model->quote_rejection_reason = $reason;
        $model->quote_reviewed_at = gmdate('Y-m-d H:i:s');
        $this->reviewLatestDocument($model, RequestChangeDocument::TYPE_QUOTE, RequestChangeDocument::REVIEW_REJECTED, $reason);
        $model->save(false);

        $this->notify('mro', $model->mro_id, 'Revised quote rejected for Request #' . $model->request_id, $model);
        Yii::$app->session->setFlash('success', 'The quote was rejected. The MRO can submit a new version.');
        return $this->redirect(['view', 'id' => UrlIdHelper::encode($model->id)]);
    }

    private function findAssignedApplication(int $requestId): MroRequestApply
    {
        /* Le PO accepté identifie le MRO contractuel ; le dernier candidat n'est qu'un repli historique. */
        $application = MroRequestApply::find()
            ->alias('mra')
            ->innerJoin('{{%ao_requests_applications}} ara', 'ara.application_id = mra.id')
            ->where(['mra.request_id' => $requestId])
            ->orderBy(['ara.id' => SORT_DESC])
            ->one();

        if (!$application) {
            $application = MroRequestApply::find()->where(['request_id' => $requestId])->orderBy(['id' => SORT_DESC])->one();
        }
        if (!$application) {
            throw new NotFoundHttpException('No MRO is assigned to this Request.');
        }

        return $application;
    }

    private function findAuthorizedChange(string $encodedId): RequestChange
    {
        $id = UrlIdHelper::decodeOrFail($encodedId, 'Invalid change order link.');
        $model = RequestChange::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Change order not found.');
        }

        $role = Yii::$app->session->get('user_type');
        $ownerId = $role === 'ao' ? $model->ao_id : $model->mro_id;
        $sessionId = Yii::$app->session->get($role === 'ao' ? 'ao_id' : 'mro_id');
        if ((int) $ownerId !== (int) $sessionId) {
            throw new NotFoundHttpException('Change order not found.');
        }

        return $model;
    }

    private function requireRole(string $expected): void
    {
        if (Yii::$app->session->get('user_type') !== $expected) {
            throw new ForbiddenHttpException('This action is not allowed for your role.');
        }
    }

    private function requireStatus(RequestChange $model, array $allowed): void
    {
        if (!in_array($model->status, $allowed, true)) {
            throw new ForbiddenHttpException('This action is no longer available at the current stage.');
        }
    }

    private function storeUpload(UploadedFile $file, string $prefix): string
    {
        $directory = Yii::getAlias('@webroot/uploads/request-changes');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to prepare the change-order upload directory.');
        }

        $name = $prefix . '-' . Yii::$app->security->generateRandomString(24) . '.' . strtolower($file->extension);
        if (!$file->saveAs($directory . DIRECTORY_SEPARATOR . $name)) {
            throw new \RuntimeException('The document could not be saved.');
        }

        return 'uploads/request-changes/' . $name;
    }

    /**
     * Archive chaque dépôt comme une nouvelle version. Le chemin courant conservé
     * sur RequestChange sert à l'affichage rapide, tandis que cette ligne constitue
     * la preuve historique qui ne sera jamais écrasée par une resoumission.
     */
    private function archiveDocument(
        RequestChange $change,
        string $type,
        ?string $path,
        ?string $description,
        $price,
        ?string $currency,
        string $actorType,
        int $actorId
    ): void {
        $document = new RequestChangeDocument();
        $document->request_change_id = (int) $change->id;
        $document->document_type = $type;
        $document->version = ((int) RequestChangeDocument::find()
            ->where(['request_change_id' => $change->id, 'document_type' => $type])
            ->max('version')) + 1;
        $document->file_path = $path;
        $document->description = $description;
        $document->price = $price;
        $document->currency = $currency;
        $document->uploaded_by_type = $actorType;
        $document->uploaded_by_id = $actorId;
        $document->review_status = RequestChangeDocument::REVIEW_PENDING;
        $document->save(false);
    }

    /** Enregistre la décision sur la version effectivement présentée au relecteur. */
    private function reviewLatestDocument(RequestChange $change, string $type, string $decision, ?string $reason = null): void
    {
        $document = RequestChangeDocument::find()
            ->where([
                'request_change_id' => $change->id,
                'document_type' => $type,
                'review_status' => RequestChangeDocument::REVIEW_PENDING,
            ])
            ->orderBy(['version' => SORT_DESC, 'id' => SORT_DESC])
            ->one();

        if (!$document) {
            throw new \RuntimeException('The document version to review could not be found.');
        }

        $document->review_status = $decision;
        $document->rejection_reason = $reason;
        $document->reviewed_at = gmdate('Y-m-d H:i:s');
        $document->save(false);
    }

    private function notify(string $recipientType, int $recipientId, string $message, RequestChange $model): void
    {
        try {
            Yii::$app->runAction('notification/save-notification', [
                'recipientType' => $recipientType,
                'recipientId' => $recipientId,
                'message' => $message,
                'actions' => Yii::$app->request->baseUrl . '/request-changes/view?id=' . UrlIdHelper::encode($model->id),
            ]);
        } catch (\Throwable $exception) {
            /* Une notification indisponible ne doit jamais annuler une décision métier validée. */
            Yii::warning(['message' => 'Change-order notification was not sent.', 'exception' => $exception->getMessage()], __METHOD__);
        }
    }
}
