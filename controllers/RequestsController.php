<?php

namespace app\controllers;

use app\models\Aircrafts;
use app\models\Airports;
use app\models\AoRequestsApplications;
use app\models\Certificates;
use app\models\Chat;
use app\models\Conversations;
use app\models\Feedback;
use app\models\MroAircraftCertificate;
use Yii;
use app\models\Requests;
use app\models\MroprofileAirport;

use app\models\MroProfile;
use app\models\MroNotificationsPreferences;
use app\models\MroRequestApply;
use app\models\RepairReport;
use app\models\RequestChangeEvent;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use app\models\MroInsuranceDocuments;
use app\components\UrlIdHelper;
use app\components\MroRequestEligibility;

class RequestsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            /*
             * PROTECTION DES DEMANDES : les pages et actions de ce contrôleur
             * manipulent des informations métier AO/MRO et ne doivent jamais être
             * rendues sans identité Yii valide. Lorsqu'une session expire, Yii
             * redirige maintenant vers la connexion avant d'atteindre le layout.
             * Les contrôles métier plus précis déjà présents dans chaque action
             * restent inchangés et continuent de limiter les données accessibles.
             */
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    /*
                     * CORRECTIF CSRF : « deny » supprime définitivement une réponse
                     * MRO. Elle n'accepte plus de GET (un simple lien ou une image
                     * piégée suffisait à la déclencher). Les boutons des vues
                     * check-applications et view-application envoient déjà un POST
                     * avec jeton CSRF (data-method="post").
                     */
                    'deny' => ['POST'],
                ],
            ],
        ];
    }

    /* =====================================================================
     * CONTRÔLE DE PROPRIÉTÉ DES DEMANDES (correctif sécurité IDOR, 2026-11)
     * =====================================================================
     *
     * PROBLÈME CORRIGÉ :
     * La règle AccessControl de ce contrôleur accepte tout utilisateur connecté
     * ('@'), qu'il soit AO, MRO ou admin. Plusieurs actions chargeaient ensuite
     * une demande, une réponse MRO, un PO ou un rapport CRS uniquement à partir
     * de l'identifiant reçu dans l'URL, sans vérifier à qui il appartenait.
     * Un MRO (ou un autre AO) pouvait donc supprimer, clôturer, approuver ou
     * télécharger les documents d'une demande qui n'était pas la sienne.
     *
     * Les identifiants signés (UrlIdHelper) empêchent seulement de deviner les
     * numéros : ce n'est PAS une autorisation, car un MRO reçoit légitimement
     * des liens signés vers les demandes qu'il consulte.
     *
     * PRINCIPE DE LA CORRECTION :
     * Toute action AO passe désormais par une des méthodes ci-dessous. Elles
     * remontent systématiquement jusqu'à la demande (requests.ao_id) et la
     * comparent à l'AO connecté, lu uniquement dans la session serveur.
     * Les messages d'erreur restent génériques pour ne rien révéler.
     */

    /**
     * Retourne l'identifiant de l'AO connecté, ou null si l'utilisateur
     * connecté n'est pas un AO (MRO, admin…).
     * La source est exclusivement la session serveur, jamais la requête HTTP.
     */
    private function currentAoId(): ?int
    {
        if (Yii::$app->session->get('user_type') !== 'ao') {
            return null;
        }

        $aoId = (int) Yii::$app->session->get('ao_id');
        return $aoId > 0 ? $aoId : null;
    }

    /**
     * Exige que l'utilisateur connecté soit un AO et retourne son identifiant.
     * Utilisé par les actions réservées à l'exploitant (création, PO, CRS…).
     *
     * @throws ForbiddenHttpException si l'utilisateur n'est pas un AO
     */
    private function requireAoId(): int
    {
        $aoId = $this->currentAoId();
        if ($aoId === null) {
            throw new ForbiddenHttpException('This action is reserved for aircraft operators.');
        }

        return $aoId;
    }

    /**
     * Charge une demande et vérifie qu'elle appartient à l'AO connecté.
     * C'est le point de contrôle central : les autres méthodes « findOwned* »
     * finissent toutes par l'appeler.
     *
     * @param int $requestId identifiant interne déjà décodé
     * @throws NotFoundHttpException si la demande n'existe pas
     * @throws ForbiddenHttpException si elle appartient à un autre AO ou si l'utilisateur n'est pas AO
     */
    protected function findOwnedRequest($requestId): Requests
    {
        $aoId = $this->requireAoId();
        $request = Requests::findOne((int) $requestId);

        if ($request === null) {
            throw new NotFoundHttpException('Request not found.');
        }

        // Comparaison stricte en entier : '12' et 12 doivent être équivalents.
        if ((int) $request->ao_id !== $aoId) {
            throw new ForbiddenHttpException('You are not allowed to access this request.');
        }

        return $request;
    }

    /**
     * Charge une réponse MRO (mro_request_apply) et vérifie que la demande
     * à laquelle elle répond appartient à l'AO connecté.
     */
    private function findOwnedApplication($applicationId): MroRequestApply
    {
        $application = MroRequestApply::findOne((int) $applicationId);
        if ($application === null) {
            throw new NotFoundHttpException('Application not found.');
        }

        // Le contrôle porte sur la demande parente, seule détentrice de ao_id.
        $this->findOwnedRequest($application->request_id);

        return $application;
    }

    /**
     * Charge un enregistrement de PO (ao_requests_applications) et vérifie
     * que sa demande appartient à l'AO connecté.
     */
    private function findOwnedPoRecord($poRecordId): AoRequestsApplications
    {
        $poRecord = AoRequestsApplications::findOne((int) $poRecordId);
        if ($poRecord === null) {
            throw new NotFoundHttpException('PO document not found.');
        }

        $this->findOwnedRequest($poRecord->request_id);

        return $poRecord;
    }

    /**
     * Charge un rapport CRS (repair_report) et vérifie la chaîne complète :
     * rapport → réponse MRO → demande → AO connecté.
     * Un rapport orphelin (réponse MRO supprimée) est traité comme introuvable.
     */
    private function findOwnedReport($reportId): RepairReport
    {
        $report = RepairReport::findOne((int) $reportId);
        if ($report === null) {
            throw new NotFoundHttpException('Report not found.');
        }

        $application = $report->getMroRequestApply()->one();
        if ($application === null) {
            throw new NotFoundHttpException('Report not found.');
        }

        $this->findOwnedRequest($application->request_id);

        return $report;
    }

    /**
     * Indique si l'utilisateur connecté peut CONSULTER une demande (lecture seule).
     *
     * La page requests/view est partagée : les MRO y accèdent depuis leurs listes
     * (mro-requests, mro-applications, view-po, view-feedback) et depuis les
     * notifications temps réel. La lecture est donc autorisée à :
     *  - l'AO propriétaire ;
     *  - l'admin (supervision, comportement existant conservé) ;
     *  - un MRO qui a déjà répondu à la demande ;
     *  - un MRO à qui la demande est proposée dans « New requests ».
     * Toutes les actions de MODIFICATION restent réservées à l'AO propriétaire.
     */
    private function canViewRequest(Requests $request): bool
    {
        $userType = Yii::$app->session->get('user_type');

        if ($userType === 'admin') {
            return true;
        }

        if ($userType === 'ao') {
            return (int) $request->ao_id === $this->currentAoId();
        }

        if ($userType === 'mro') {
            $mroId = (int) Yii::$app->session->get('mro_id');
            if ($mroId <= 0) {
                return false;
            }

            // MRO ayant déjà répondu, ou à qui la demande est proposée
            // (règle partagée : components/MroRequestEligibility.php).
            return MroRequestEligibility::isConcerned($request, $mroId);
        }

        return false;
    }




    /**
     * Reads the search keyword from the new "q" GET parameter.
     * The old "search" parameter is still supported to avoid breaking existing links.
     *
     * @param string|null $search
     * @return string
     */
    protected function getSearchKeyword($search = null)
    {
        return trim((string) Yii::$app->request->get(
            'q',
            Yii::$app->request->get('search', $search)
        ));
    }

    /**
     * Applies the search filter to requests listing queries.
     * Important: all OR conditions are grouped inside one andWhere() block,
     * so the AO/status filters remain protected and cannot be bypassed by search.
     *
     * @param \yii\db\ActiveQuery $query
     * @param string $search
     * @param string $alias
     * @return \yii\db\ActiveQuery
     */
    protected function applyRequestsSearchFilter($query, $search, $alias = 'r')
    {
        /*
         * FILTRE DE PRIORITÉ : il est appliqué même lorsque la recherche texte est
         * vide. La valeur GET n'est acceptée que si elle appartient au vocabulaire
         * défini par Requests ; toute autre valeur est simplement ignorée.
         */
        $query = $this->applyRequestsPriorityFilter($query, $alias);

        if ($search === '') {
            return $query;
        }

        $prefix = $alias !== '' ? $alias . '.' : '';

        // Join the aircraft relation only for search, without changing the rendered data.
        $query->joinWith([
            'aircraft' => function ($aircraftQuery) {
                $aircraftQuery->alias('aircraftSearch');
            }
        ], false);

        return $query->andWhere([
            'or',
            ['like', $prefix . 'request_id', $search],
            ['like', $prefix . 'request_details', $search],
            ['like', $prefix . 'aircraft_registration', $search],
            ['like', $prefix . 'serial_number', $search],
            ['like', $prefix . 'location', $search],
            ['like', $prefix . 'status', $search],
            ['like', 'aircraftSearch.manufacturer', $search],
            ['like', 'aircraftSearch.model', $search],
        ]);
    }

    /**
     * Applique le filtre Priority avant le comptage et la pagination.
     *
     * Ce filtre réduit uniquement le jeu de résultats visible. Il ne modifie ni
     * la priorité enregistrée, ni le statut, ni les règles d'accès AO/MRO.
     */
    protected function applyRequestsPriorityFilter($query, $alias = 'r')
    {
        $priority = strtolower(trim((string) Yii::$app->request->get('priority', '')));
        $allowedPriorities = array_keys(Requests::getOperationalPriorityOptions());

        if (!in_array($priority, $allowedPriorities, true)) {
            return $query;
        }

        $prefix = $alias !== '' ? $alias . '.' : '';
        return $query->andWhere([$prefix . 'operational_priority' => $priority]);
    }

    /**
     * Rend la page AO complete lors d'une navigation normale et uniquement son
     * tableau lors d'une actualisation demandee par le gestionnaire central.
     * L'en-tete technique explicite evite de modifier le comportement d'autres
     * appels AJAX eventuellement utilises par Yii2 sur les memes actions.
     *
     * @param array $params Donnees deja calculees par l'action metier courante.
     * @return string
     */
    protected function renderRequestsIndex(array $params)
    {
        if (
            Yii::$app->request->isAjax
            && Yii::$app->request->headers->get('X-CAN-Fragment') === 'request-list'
        ) {
            return $this->renderPartial('_request-list', $params);
        }

        return $this->render('index', $params);
    }

    /**
     * Lists all Requests models.
     * @param string|null $search
     * @return mixed
     */
    public function actionIndex($search = null)
    {
        /*
         * CURSEUR DE SYNCHRONISATION : il est capturé avant toute lecture de la
         * liste. Ainsi, un changement publié pendant la construction de la page
         * reste postérieur à ce curseur et sera récupéré au premier contrôle AJAX.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Read the search keyword from GET parameter "q" while keeping old "search" support.
        $search = $this->getSearchKeyword($search);

        // Get the AO ID from session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Build the base query for the connected AO only.
        $query = Requests::find()
            ->alias('r')
            ->where(['r.ao_id' => $ao_id])
            ->joinWith(['destinationAirport'])
            /*
             * TRI OPÉRATIONNEL AVANT PAGINATION : AOG, Urgent, puis Routine.
             * L'identifiant décroissant conserve l'ordre actuel dans chaque groupe.
             */
            ->orderBy(Requests::getOperationalPriorityOrderExpression('r'))
            ->addOrderBy(['r.request_id' => SORT_DESC]);

        // Apply grouped search conditions without breaking the AO filter.
        $this->applyRequestsSearchFilter($query, $search, 'r');

        // Clone the query to get the total count for pagination.
        $countQuery = clone $query;
        $totalCount = $countQuery->select('r.request_id')->distinct()->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 7,
            'pageSizeParam' => 'per-page',
        ]);

        // Fetch the requests with pagination.
        $requests = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $title = "Requests";

        return $this->renderRequestsIndex([
            'requests' => $requests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => $title,
            'syncCursor' => $syncCursor,
        ]);
    }

    public function actionNewRequests1($search = null)
    {
        /*
         * CURSEUR DE SYNCHRONISATION : la valeur précède la requête SQL afin de
         * ne perdre aucun événement reçu pendant le rendu initial de cette liste.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Read the search keyword from GET parameter "q" while keeping old "search" support.
        $search = $this->getSearchKeyword($search);

        // Get the AO ID from session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Build the query to fetch only new requests for the connected AO.
        $query = Requests::find()
            ->alias('r')
            ->distinct()
            ->where(['r.ao_id' => $ao_id])
            ->andWhere(['r.status' => ['created', 'answered', 'po_loaded']])
            ->joinWith(['destinationAirport'])
            ->joinWith(['mroApplication'])
            /*
             * TRI OPÉRATIONNEL AVANT PAGINATION : AOG, Urgent, puis Routine.
             * L'identifiant décroissant conserve l'ordre actuel dans chaque groupe.
             */
            ->orderBy(Requests::getOperationalPriorityOrderExpression('r'))
            ->addOrderBy(['r.request_id' => SORT_DESC]);

        // Apply grouped search conditions without breaking AO/status filters.
        $this->applyRequestsSearchFilter($query, $search, 'r');

        // Clone the query to get the total count for pagination.
        $countQuery = clone $query;
        $totalCount = $countQuery->select('r.request_id')->distinct()->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 8,
            'pageSizeParam' => 'per-page',
        ]);

        // Fetch the requests with pagination.
        $requests = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $title = "New requests";

        return $this->renderRequestsIndex([
            'requests' => $requests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => $title,
            'syncCursor' => $syncCursor,
        ]);
    }

    public function actionNewRequests($search = null)
    {
        /*
         * CURSEUR DE SYNCHRONISATION : la liste et le navigateur partagent ce
         * point de départ, même si l'onglet est masqué lors de son chargement.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Read the search keyword from GET parameter "q" while keeping old "search" support.
        $search = $this->getSearchKeyword($search);

        // Get the AO ID from session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Build the query to fetch only new requests for the connected AO.
        $query = Requests::find()
            ->alias('r')
            ->distinct()
            ->where(['r.ao_id' => $ao_id])
            ->andWhere(['r.status' => ['created', 'answered', 'po_loaded']])
            ->joinWith(['destinationAirport'])
            ->joinWith(['mroApplication'])
            /*
             * TRI OPÉRATIONNEL AVANT PAGINATION : AOG, Urgent, puis Routine.
             * L'identifiant décroissant conserve l'ordre actuel dans chaque groupe.
             */
            ->orderBy(Requests::getOperationalPriorityOrderExpression('r'))
            ->addOrderBy(['r.request_id' => SORT_DESC]);

        // Apply grouped search conditions without breaking AO/status filters.
        $this->applyRequestsSearchFilter($query, $search, 'r');

        // Clone the query to get total count for pagination.
        $countQuery = clone $query;

        // Count distinct request IDs to avoid duplicated rows caused by joinWith.
        $totalCount = $countQuery->select('r.request_id')->distinct()->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 7,
            'pageSizeParam' => 'per-page',
        ]);

        // Fetch the requests with pagination.
        $requests = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $title = "New requests";

        return $this->renderRequestsIndex([
            'requests' => $requests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => $title,
            'syncCursor' => $syncCursor,
        ]);
    }

    public function actionOpenRequests($search = null)
    {
        /*
         * CURSEUR DE SYNCHRONISATION : capturé avant les données pour garantir
         * que toute transition concurrente sera détectée au prochain contrôle.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Read the search keyword from GET parameter "q" while keeping old "search" support.
        $search = $this->getSearchKeyword($search);

        // Get the AO ID from session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Build the query to fetch only open requests for the connected AO.
        $query = Requests::find()
            ->alias('r')
            ->where(['r.ao_id' => $ao_id])
            ->andWhere(['r.status' => [
                'work_accepted',
                'work_started',
                'report_submitted',
                'update_request',
                'update_request_accepted',
                'update_request_denied'
            ]])
            ->joinWith(['destinationAirport'])
            /*
             * TRI OPÉRATIONNEL AVANT PAGINATION : AOG, Urgent, puis Routine.
             * L'identifiant décroissant conserve l'ordre actuel dans chaque groupe.
             */
            ->orderBy(Requests::getOperationalPriorityOrderExpression('r'))
            ->addOrderBy(['r.request_id' => SORT_DESC]);

        // Apply grouped search conditions without breaking AO/status filters.
        $this->applyRequestsSearchFilter($query, $search, 'r');

        // Clone the query to get the total count for pagination.
        $countQuery = clone $query;
        $totalCount = $countQuery->select('r.request_id')->distinct()->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 7,
            'pageSizeParam' => 'per-page',
        ]);

        // Fetch the requests with pagination.
        $requests = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $title = "Open requests";

        return $this->renderRequestsIndex([
            'requests' => $requests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => $title,
            'syncCursor' => $syncCursor,
        ]);
    }

    public function actionClosedRequests($search = null)
    {
        /*
         * CURSEUR DE SYNCHRONISATION : ce repère serveur empêche le premier appel
         * AJAX de considérer comme ancien un événement arrivé dans un onglet caché.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Read the search keyword from GET parameter "q" while keeping old "search" support.
        $search = $this->getSearchKeyword($search);

        // Get the AO ID from session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Build the query to fetch only closed requests for the connected AO.
        $query = Requests::find()
            ->alias('r')
            ->where(['r.ao_id' => $ao_id])
            ->andWhere(['r.status' => ['closed']])
            ->joinWith(['destinationAirport'])
            /*
             * TRI OPÉRATIONNEL AVANT PAGINATION : AOG, Urgent, puis Routine.
             * L'identifiant décroissant conserve l'ordre actuel dans chaque groupe.
             */
            ->orderBy(Requests::getOperationalPriorityOrderExpression('r'))
            ->addOrderBy(['r.request_id' => SORT_DESC]);

        // Apply grouped search conditions without breaking AO/status filters.
        $this->applyRequestsSearchFilter($query, $search, 'r');

        // Clone the query to get the total count for pagination.
        $countQuery = clone $query;
        $totalCount = $countQuery->select('r.request_id')->distinct()->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 7,
            'pageSizeParam' => 'per-page',
        ]);

        // Fetch the requests with pagination.
        $requests = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $title = "Closed requests";

        return $this->renderRequestsIndex([
            'requests' => $requests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => $title,
            'syncCursor' => $syncCursor,
        ]);
    }

/**
     * Displays a single Request model.
     * @param integer $id
     * @return mixed
     */
    public function actionViewOld($id)
    {
        /*
         * CORRECTIF IDOR : ancienne action encore routable (requests/view-old)
         * qui accepte un identifiant numérique brut. Elle n'est plus utilisée
         * par les vues, mais tant qu'elle existe elle est réservée à l'AO
         * propriétaire de la demande.
         */
        $id = (int) $this->findOwnedRequest($id)->request_id;

        // Attempt to find the AoRequest by request_id
        $AoRequest = AoRequestsApplications::find()
            ->where(['request_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
            
        // If no AoRequest is found, set $poAttachment to an empty string
        $poAttachment = $AoRequest ? $AoRequest->po : '';
    
        $aoRequestApplication = AoRequestsApplications::find()
    ->where(['request_id' => $id])
    ->orderBy(['id' => SORT_DESC]) // Sort by the ID of AoRequestsApplications
    ->one();

        // Attempt to find the AoRequest by request_id
        $MroApplication = $aoRequestApplication && $aoRequestApplication->application
        ? $aoRequestApplication->application->id
        : null; // Return null if no related application exists

                
        // If no AoRequest is found, set $poAttachment to an empty string
        $invoice = $MroApplication ? $MroApplication : '';


        // Get all mro request application
        $allRepairReports = RepairReport::find()
        ->orderBy(['updated_at' => SORT_DESC])
        ->all();

        $repairReports = [];
        foreach ($allRepairReports as $report) {
            if ( $report->getRequest()->request_id == $id && $report->CRSed == 0) {
                $repairReports[] = $report->CRS_attachment;
            }
        }

        // Get all mro request application
        $CRSReports = RepairReport::find()
        ->where(['CRSed' => 1])
        ->orderBy(['updated_at' => SORT_DESC])
        ->all();

        $CrsReports = [];
        foreach ($CRSReports as $report) {
            if ( $report->getRequest()->request_id == $id) {
                $CrsReports[] = $report->CRS_attachment;
            }
        }


        $crs = $CrsReports ? $CrsReports[0] : '';

        // Render the view with the found request model and the PO attachment (or empty)
        return $this->render('view', [
            'request' => $this->findModel($id),
            'poAttachment' => $poAttachment, // This will be empty if no AoRequest is found
            'invoice' => $invoice, // This will be empty if no AoRequest is found
            'repairReports' => $repairReports,
            'crs' => $crs,
        ]);
    }
    
    
    public function actionView($id)
{
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }

    /*
     * CORRECTIF IDOR : la page est partagée entre l'AO propriétaire, l'admin
     * et les MRO concernés par la demande (voir canViewRequest()). Un autre AO
     * ou un MRO non concerné reçoit désormais une erreur 403.
     */
    $requestModel = $this->findModel($id);
    if (!$this->canViewRequest($requestModel)) {
        throw new ForbiddenHttpException('You are not allowed to view this request.');
    }

    // Attempt to find the AoRequest by request_id
    $AoRequest = AoRequestsApplications::find()
        ->where(['request_id' => $id])
        ->orderBy(['created_at' => SORT_DESC])
        ->one();

    // If no AoRequest is found, set $poAttachment to an empty string
    $poAttachment = $AoRequest ? $AoRequest->po : '';

    $aoRequestApplication = AoRequestsApplications::find()
        ->where(['request_id' => $id])
        ->orderBy(['id' => SORT_DESC])
        ->one();

    // Attempt to find the related MRO application
    $MroApplication = $aoRequestApplication && $aoRequestApplication->application
        ? $aoRequestApplication->application->id
        : null;

    /*
     * IDENTIFIANT D'APPLICATION MRO OPTIONNEL : une demande nouvellement creee peut
     * legitimement ne posseder encore aucune reponse MRO. Dans ce cas, nous conservons
     * la valeur null au lieu d'envoyer une chaine vide a la vue. Cette normalisation
     * permet a la vue de distinguer clairement « aucun lien disponible » d'un vrai ID.
     */
    $invoice = $MroApplication;

    /*
     * REQUEST VIEW PERFORMANCE 2026: fetch only reports linked to this request.
     * This replaces two full-table loops while returning the same attachment data.
     */
    $applicationIds = MroRequestApply::find()
        ->select('id')
        ->where(['request_id' => $id])
        ->column();

    $repairReports = [];
    $crs = '';

    if (!empty($applicationIds)) {
        $reportBaseQuery = RepairReport::find()
            ->where(['mro_request_apply_id' => $applicationIds])
            ->andWhere(['not', ['CRS_attachment' => null]])
            ->andWhere(['<>', 'CRS_attachment', '']);

        $repairReports = (clone $reportBaseQuery)
            ->select('CRS_attachment')
            ->andWhere(['CRSed' => 0])
            ->orderBy(['updated_at' => SORT_DESC])
            ->column();

        $crs = (clone $reportBaseQuery)
            ->select('CRS_attachment')
            ->andWhere(['CRSed' => 1])
            ->orderBy(['updated_at' => SORT_DESC])
            ->scalar() ?: '';
    }

    /*
     * DEVIS MRO DANS LA PAGE DE LA DEMANDE (2026-11) :
     * l'onglet « MRO Quotes » reprend la liste de requests/check-applications.
     * Il n'est fourni QU'À l'AO propriétaire : la page view est aussi ouverte par
     * des MRO, qui ne doivent jamais voir les devis de leurs concurrents.
     * null = onglet non affiché ; [] = onglet affiché, aucune réponse reçue.
     */
    $mroQuotes = null;
    if ($this->currentAoId() !== null && (int) $requestModel->ao_id === $this->currentAoId()) {
        $mroQuotes = MroRequestApply::find()
            ->where(['request_id' => $requestModel->request_id])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    // Render the view with the found request model and attachments
    return $this->render('view', [
        'request' => $requestModel, // déjà chargée et contrôlée plus haut
        'poAttachment' => $poAttachment,
        'invoice' => $invoice,
        'repairReports' => $repairReports,
        'crs' => $crs,
        'mroQuotes' => $mroQuotes,
    ]);
}

    /**
     * Creates a new Request model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
public function actionCreate1()
{
    $request = new Requests();

    /*
     * CORRECTIF IDOR : ancienne action encore routable (requests/create1).
     * Seul un AO peut créer une demande ; son identifiant vient de la session.
     */
    $ao_id = $this->requireAoId();
    $request->ao_id = $ao_id;

    $airports = Airports::find()
        ->orderBy('icao')
        ->all();

    if ($request->load(Yii::$app->request->post())) {

        /**
         * REQUIRED CERTIFICATE
         */
        $aircraft = Aircrafts::findOne($request->aircraft_id);

        $request->required_certificates = $aircraft
            ->getCertificateType()
            ->one()
            ->type;

        /**
         * FILE UPLOAD
         */
        $request->attachment = UploadedFile::getInstance($request, 'attachment');

        if ($request->attachment) {

            $uploadPath = 'uploads/' .
                Yii::$app->security->generateRandomString() .
                '.' .
                $request->attachment->extension;

            if ($request->attachment->saveAs($uploadPath)) {
                $request->attachment = $uploadPath;
            } else {

                Yii::$app->session->setFlash(
                    'error',
                    'Failed to upload attachment.'
                );

                return $this->render('create', [
                    'request' => $request,
                    'airports' => $airports,
                ]);
            }
        }

        $request->location =
            Airports::findOne($request->destination)->airport_name;

        /**
         * DATE VALIDATION
         */
        $request->eta = date(
            'Y-m-d H:i:s',
            strtotime($request->eta)
        );

        $request->etd = date(
            'Y-m-d H:i:s',
            strtotime($request->etd)
        );

        if (strtotime($request->etd) <= strtotime($request->eta)) {

            Yii::$app->session->setFlash(
                'error',
                'ETD must be after ETA.'
            );

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
            ]);
        }

        if ($request->save()) {

            /**
             * =========================
             * COVERAGE CALCULATION
             * =========================
             */

            $requiredCertificates = $aircraft
                ->getCertificateType()
                ->one()
                ->type;

            $requiredAircraftModel = $aircraft
                ->getAircraftModel()
                ->one()
                ->aircraft_model_id;
                

            /**
             * MROs WITH CERTIFICATES
             */
            $mrosWithCertificates = MroProfile::find()
                ->joinWith('certificates')
                ->where([
                    'IN',
                    'certificates.type',
                    $requiredCertificates
                ])
                ->all();

            /**
             * MROs WITH AIRCRAFT
             */
            $mrosWithAircraft = MroProfile::find()
                ->joinWith('aircraftCertificates')
                ->where([
                    'mro_aircraft_certificate.aircraft_model_id'
                        => $requiredAircraftModel
                ])
                ->all();

            /**
             * ALL MROS
             */
            $allMros = MroProfile::find()->all();

            if (empty($allMros)) {

                Yii::$app->session->setFlash(
                    'message',
                    'Request created, but no MROs exist.'
                );

                return $this->redirect(['new-requests']);
            }

            /**
             * ARRAYS OF IDS
             */
            $mroIdsWithCertificates =
                array_column($mrosWithCertificates, 'mro_id');

            $mroIdsWithAircraft =
                array_column($mrosWithAircraft, 'mro_id');

            /**
             * LOOP THROUGH ALL MROS
             */
            foreach ($allMros as $mro) {
            $hasInsurance = MroInsuranceDocuments::find()
                ->where(['mro_id' => $mro->mro_id])
                ->exists();
                $notificationPreferences =
                    MroNotificationsPreferences::findOne([
                        'mro_id' => $mro->mro_id
                    ]);

                $url = Url::to(['mro-requests/index'], true);

                /**
                 * =========================
                 * COVER CHECKS
                 * =========================
                 */

                // AIRPORT COVER
                $hasAirportAccess = MroprofileAirport::find()
                    ->where([
                        'mro_id' => $mro->mro_id,
                        'airport_id' => $request->destination,
                    ])
                    ->exists();

                // CERTIFICATE COVER
                $hasCertificateAccess =
                    in_array(
                        $mro->mro_id,
                        $mroIdsWithCertificates
                    );

                // AIRCRAFT COVER
                $hasAircraftAccess =
                    in_array(
                        $mro->mro_id,
                        $mroIdsWithAircraft
                    );

                // FULL COVER
                $covered =
                    $hasAirportAccess &&
                    $hasCertificateAccess &&
                    $hasAircraftAccess &&    
                    $hasInsurance;

                /**
                 * =========================
                 * BUILD REASON MESSAGE
                 * =========================
                 */

                $reason = [];

                // ONLY BUILD REASON
                // IF AIRPORT IS COVERED
                if ($hasAirportAccess) {

                    if (!$hasAircraftAccess) {

                        $reason[] =
                            "Aircraft not covered: " .
                            $aircraft->manufacturer .
                            " : " .
                            $aircraft->model;
                    }

                    if (!$hasCertificateAccess) {

                        $reason[] =
                            "Certificate not covered: " .
                            $requiredCertificates;
                    }
                }

                $reasonText = implode(" | ", $reason);

                /**
                 * =========================
                 * CERTIFIED NOTIFICATION
                 * =========================
                 */

                if (
                    $covered &&
                    $notificationPreferences &&
                    $notificationPreferences
                        ->notify_for_certified_aircraft
                ) {

                    /**
                     * EMAIL
                     */
                    if (
                        $notificationPreferences
                            ->notify_by_email
                    ) {

                        Yii::$app->mailer
                            ->compose(
                                'notification_certified',
                                [
                                    /*
                                     * CONTENU MÉTIER DU MAIL « NOUVELLE DEMANDE » : les modèles déjà validés
                                     * sont transmis à la vue afin d'afficher un résumé utile au MRO autorisé.
                                     * Cette présentation ne modifie aucun critère de couverture ou d'éligibilité.
                                     */
                                    'url' => $url,
                                    'request' => $request,
                                    'aircraft' => $aircraft,
                                ]
                            )
                            ->setTo($mro->email)
                            ->setSubject('New Request Notification')
                            ->send();
                    }

                    /**
                     * PLATFORM
                     */
                    if (
                        $notificationPreferences
                            ->notify_by_platform
                    ) {

                        Yii::$app->runAction(
                            'notification/save-notification',
                            [
                                'recipientType' => 'mro',
                                'recipientId' => $mro->mro_id,
                                'message' =>
    'New request #' . $request->request_id . ' matches your scope of work.',
                                'actions' =>
                                    Yii::$app->request->baseUrl .
                                    '/mro-requests',
                            ]
                        );
                    }
                }

                /**
                 * =========================
                 * NOT COVERED NOTIFICATION
                 * ONLY IF AIRPORT IS COVERED
                 * =========================
                 */

                if (
                    $hasAirportAccess &&
                    !$covered &&
                    $notificationPreferences &&
                    $notificationPreferences
                        ->notify_for_non_certified_aircraft
                ) {

                    /**
                     * EMAIL
                     */
                    if (
                        $notificationPreferences
                            ->notify_by_email
                    ) {

                        Yii::$app->mailer
                            ->compose(
                                'notification_non_certified',
                                [
                                    'manufacturer'
                                        => $aircraft->manufacturer,

                                    'model'
                                        => $aircraft->model,
                                ]
                            )
                            ->setTo($mro->email)
                            ->setSubject('New Request Notification')
                            ->send();
                    }

                    /**
                     * PLATFORM
                     */
                    if (
                        $notificationPreferences
                            ->notify_by_platform
                    ) {

                        Yii::$app->runAction(
                            'notification/save-notification',
                            [
                                'recipientType' => 'mro',

                                'recipientId' => $mro->mro_id,

                                'message' =>
                                    'A new request is NOT fully covered: '
                                    . $reasonText,

                                'actions' =>
                                    Yii::$app->request->baseUrl .
                                    '/mro-requests',
                            ]
                        );
                    }
                }
            }

            Yii::$app->session->setFlash(
                'message',
                'Request created successfully.'
            );

            return $this->redirect(['new-requests']);
        }
    }

    return $this->render('create', [
        'request' => $request,
        'airports' => $airports,
    ]);
}

public function actionCreate()
{
    $request = new Requests();

    /*
     * CORRECTIF IDOR : la règle AccessControl laisse entrer les MRO et les
     * admins. Seul un AO peut créer une demande, et ao_id provient uniquement
     * de la session (jamais du formulaire).
     */
    $ao_id = $this->requireAoId();
    $request->ao_id = $ao_id;

    // IMPORTANT:
    // Do not load all airports here.
    // Airports must be loaded by AJAX search in Select2.
    $airports = [];

    if ($request->load(Yii::$app->request->post())) {

        /**
         * AIRCRAFT VALIDATION
         *
         * CORRECTIF IDOR : l'avion doit appartenir à la flotte de l'AO connecté.
         * Sans ce filtre, un AO pouvait créer une demande sur l'avion d'un
         * autre exploitant en modifiant aircraft_id dans le formulaire.
         * Un avion d'un autre AO est traité comme « introuvable ».
         */
        $aircraft = Aircrafts::findOne([
            'aircraft_id' => $request->aircraft_id,
            'ao_id' => $ao_id,
        ]);

        if (!$aircraft) {
            Yii::$app->session->setFlash('error', 'Selected aircraft not found.');

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
                'selectedAirportName' => null,
            ]);
        }

        $certificateType = $aircraft->getCertificateType()->one();

        if (!$certificateType) {
            Yii::$app->session->setFlash('error', 'Aircraft certificate type not found.');

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
                'selectedAirportName' => null,
            ]);
        }

        $request->required_certificates = $certificateType->type;

        /**
         * FILE UPLOAD
         */
        $uploadedFile = UploadedFile::getInstance($request, 'attachment');

        if ($uploadedFile) {
            $uploadPath = 'uploads/' .
                Yii::$app->security->generateRandomString() .
                '.' .
                $uploadedFile->extension;

            if ($uploadedFile->saveAs($uploadPath)) {
                $request->attachment = $uploadPath;
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload attachment.');

                return $this->render('create', [
                    'request' => $request,
                    'airports' => $airports,
                    'selectedAirportName' => null,
                ]);
            }
        }

        /**
         * DESTINATION AIRPORT
         */
        $destinationAirport = Airports::findOne($request->destination);

        if (!$destinationAirport) {
            Yii::$app->session->setFlash('error', 'Selected airport not found.');

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
                'selectedAirportName' => null,
            ]);
        }

        $request->location = $destinationAirport->airport_name;

        /**
         * DATE VALIDATION
         */
        $eta = strtotime($request->eta);
        $etd = strtotime($request->etd);

        if (!$eta || !$etd) {
            Yii::$app->session->setFlash('error', 'Invalid ETA or ETD date.');

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
                'selectedAirportName' => null,
            ]);
        }

        $request->eta = date('Y-m-d H:i:s', $eta);
        $request->etd = date('Y-m-d H:i:s', $etd);

        if ($etd <= $eta) {
            Yii::$app->session->setFlash('error', 'ETD must be after ETA.');

            return $this->render('create', [
                'request' => $request,
                'airports' => $airports,
                'selectedAirportName' => null,
            ]);
        }

        $request->status = $request->status ?: 'created';

        if ($request->save()) {

            /**
             * COVERAGE CALCULATION
             */
            $requiredCertificates = $certificateType->type;
            $aircraftModel = $aircraft->getAircraftModel()->one();

            if ($aircraftModel) {
                $requiredAircraftModel = $aircraftModel->aircraft_model_id;

                /*
                 * IMPORTANT FIX:
                 * Use alias('mro') and select('mro.mro_id')
                 * instead of select('mro_profile.mro_id').
                 */
                $mrosWithCertificates = MroProfile::find()
                    ->alias('mro')
                    ->joinWith('certificates')
                    ->where(['certificates.type' => $requiredCertificates])
                    ->select('mro.mro_id')
                    ->distinct()
                    ->column();

                $mrosWithAircraft = MroProfile::find()
                    ->alias('mro')
                    ->joinWith('aircraftCertificates')
                    ->where([
                        'mro_aircraft_certificate.aircraft_model_id' => $requiredAircraftModel
                    ])
                    ->select('mro.mro_id')
                    ->distinct()
                    ->column();

                /**
                 * Use each() instead of all() to reduce memory usage.
                 */
                foreach (MroProfile::find()->each(50) as $mro) {

                    $hasInsurance = MroInsuranceDocuments::find()
                        ->where(['mro_id' => $mro->mro_id])
                        ->exists();

                    $notificationPreferences = MroNotificationsPreferences::findOne([
                        'mro_id' => $mro->mro_id
                    ]);

                    $url = Url::to(['mro-requests/index'], true);

                    $hasAirportAccess = MroprofileAirport::find()
                        ->where([
                            'mro_id' => $mro->mro_id,
                            'airport_id' => $request->destination,
                        ])
                        ->exists();

                    $hasCertificateAccess = in_array($mro->mro_id, $mrosWithCertificates);
                    $hasAircraftAccess = in_array($mro->mro_id, $mrosWithAircraft);

                    $covered =
                        $hasAirportAccess &&
                        $hasCertificateAccess &&
                        $hasAircraftAccess &&
                        $hasInsurance;

                    $reason = [];

                    if ($hasAirportAccess) {
                        if (!$hasAircraftAccess) {
                            $reason[] = 'Aircraft not covered: ' .
                                $aircraft->manufacturer .
                                ' : ' .
                                $aircraft->model;
                        }

                        if (!$hasCertificateAccess) {
                            $reason[] = 'Certificate not covered: ' . $requiredCertificates;
                        }
                    }

                    $reasonText = implode(' | ', $reason);

                    /**
                     * CERTIFIED NOTIFICATION
                     */
                    if (
                        $covered &&
                        $notificationPreferences &&
                        $notificationPreferences->notify_for_certified_aircraft
                    ) {
                        if ($notificationPreferences->notify_by_email) {
                            Yii::$app->mailer
                                ->compose('notification_certified', [
                                    /*
                                     * SECOND PARCOURS DE CRÉATION : il reçoit exactement les mêmes données pour
                                     * produire un e-mail identique au parcours principal, sans changer le métier.
                                     */
                                    'url' => $url,
                                    'request' => $request,
                                    'aircraft' => $aircraft,
                                ])
                                ->setTo($mro->email)
                                ->setSubject('New Request Notification')
                                ->send();
                        }

                        if ($notificationPreferences->notify_by_platform) {
                            Yii::$app->runAction('notification/save-notification', [
                                'recipientType' => 'mro',
                                'recipientId' => $mro->mro_id,
                                'message' => 'New request #' . $request->request_id . ' matches your scope of work.',
                                'actions' => Yii::$app->request->baseUrl . '/mro-requests',
                            ]);
                        }
                    }

                    /**
                     * NON CERTIFIED NOTIFICATION
                     */
                    if (
                        $hasAirportAccess &&
                        !$covered &&
                        $notificationPreferences &&
                        $notificationPreferences->notify_for_non_certified_aircraft
                    ) {
                        if ($notificationPreferences->notify_by_email) {
                            Yii::$app->mailer
                                ->compose('notification_non_certified', [
                                    'manufacturer' => $aircraft->manufacturer,
                                    'model' => $aircraft->model,
                                ])
                                ->setTo($mro->email)
                                ->setSubject('New Request Notification')
                                ->send();
                        }

                        if ($notificationPreferences->notify_by_platform) {
                            Yii::$app->runAction('notification/save-notification', [
                                'recipientType' => 'mro',
                                'recipientId' => $mro->mro_id,
                                'message' => 'A new request is available but is not fully covered: ' . $reasonText,
                                'actions' => Yii::$app->request->baseUrl . '/mro-requests',
                            ]);
                        }
                    }
                }
            }

            Yii::$app->session->setFlash('success', 'Request created successfully.');

            return $this->redirect(['new-requests']);
        }

        Yii::$app->session->setFlash('error', 'Failed to create request.');
    }

    return $this->render('create', [
        'request' => $request,
        'airports' => $airports,
        'selectedAirportName' => null,
    ]);
}

public function actionSearchAirports($term)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    if (strlen($term) < 3) {
        return [];
    }

    $airports = Airports::find()
        ->filterWhere(['or',
            ['like', 'airport_name', $term],
            ['like', 'icao', $term],
            ['like', 'city_name', $term],
            ['like', 'country_name', $term]
        ])
        ->orderBy('icao')
        ->limit(20) // Limit the number of results to prevent overload
        ->all();

    /* AIRPORT SELECT DISPLAY: expose presentation metadata without changing the selected ID. */
    return array_map(static function ($airport) {
        $location = implode(', ', array_filter([
            trim((string) $airport->city_name),
            trim((string) $airport->country_name),
        ]));

        return [
            'id' => $airport->airport_id,
            'text' => trim((string) $airport->icao) . ' · ' . trim((string) $airport->airport_name)
                . ($location !== '' ? ' — ' . $location : ''),
            'icao' => trim((string) $airport->icao),
            'airport' => trim((string) $airport->airport_name),
            'city' => trim((string) $airport->city_name),
            'country' => trim((string) $airport->country_name),
        ];
    }, $airports);
}

    /**
     * Updates an existing Request model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */

public function actionUpdate($id, $status = null)
{
   
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }

    /*
     * CORRECTIF IDOR : avant, n'importe quel utilisateur connecté pouvait
     * modifier la demande, et la ligne « $request->ao_id = session ao_id »
     * permettait même à un autre AO de s'approprier la demande.
     * Désormais seule la demande de l'AO connecté est chargée.
     */
    $request = $this->findOwnedRequest($id);

    $oldDestination = $request->destination;
    $oldAircraftId = $request->aircraft_id;

    // Valeur identique à celle déjà en base (contrôlée ci-dessus) : aucun transfert possible.
    $ao_id = (int) $request->ao_id;
    $request->ao_id = $ao_id;

    /*
     * IMPORTANT:
     * Do not load all airports with Airports::find()->all().
     * It can cause memory exhausted errors.
     * Use AJAX airport search instead.
     */
    $airports = [];

    $selectedAirport = Airports::findOne($request->destination);
    $selectedAirportName = $selectedAirport
        ? trim((string) $selectedAirport->icao) . ' · ' . trim((string) $selectedAirport->airport_name)
            . (trim(implode(', ', array_filter([
                trim((string) $selectedAirport->city_name),
                trim((string) $selectedAirport->country_name),
            ]))) !== ''
                ? ' — ' . implode(', ', array_filter([
                    trim((string) $selectedAirport->city_name),
                    trim((string) $selectedAirport->country_name),
                ]))
                : '')
        : null;

    if (!empty($request->eta)) {
        $request->eta = date('Y-m-d H:i:s', strtotime($request->eta));
    }

    if (!empty($request->etd)) {
        $request->etd = date('Y-m-d H:i:s', strtotime($request->etd));
    }

    if ($request->load(Yii::$app->request->post())) {

        /*
         * CORRECTIF IDOR : ao_id ne doit jamais venir du formulaire, et un
         * nouvel avion choisi doit appartenir à la flotte de l'AO connecté.
         */
        $request->ao_id = $ao_id;
        if (
            (int) $request->aircraft_id !== (int) $oldAircraftId
            && !Aircrafts::find()->where(['aircraft_id' => $request->aircraft_id, 'ao_id' => $ao_id])->exists()
        ) {
            throw new ForbiddenHttpException('Selected aircraft does not belong to your fleet.');
        }

        $oldAttachment = $request->getOldAttribute('attachment');

        /*
         * File upload
         */
        $uploadedFile = UploadedFile::getInstance($request, 'attachment');

        if ($uploadedFile) {
            $uploadPath =
                'uploads/' .
                Yii::$app->security->generateRandomString() .
                '.' .
                $uploadedFile->extension;

            if ($uploadedFile->saveAs($uploadPath)) {
                $request->attachment = $uploadPath;
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload attachment.');

                return $this->render('update', [
                    'request' => $request,
                    'airports' => $airports,
                    'statuss' => $status,
                    'selectedAirportName' => $selectedAirportName,
                ]);
            }
        } else {
            /*
             * Keep old attachment if no new file uploaded
             */
            $request->attachment = $oldAttachment;
        }

        /*
         * Destination airport validation
         */
        $destinationAirport = Airports::findOne($request->destination);

        if (!$destinationAirport) {
            Yii::$app->session->setFlash('error', 'Selected airport not found.');

            return $this->render('update', [
                'request' => $request,
                'airports' => $airports,
                'statuss' => $status,
                'selectedAirportName' => $selectedAirportName,
            ]);
        }

        $request->location = $destinationAirport->airport_name;

        /*
         * Date validation
         */
        $eta = strtotime($request->eta);
        $etd = strtotime($request->etd);

        if (!$eta || !$etd) {
            Yii::$app->session->setFlash('error', 'Invalid ETA or ETD date.');

            return $this->render('update', [
                'request' => $request,
                'airports' => $airports,
                'statuss' => $status,
                'selectedAirportName' => $selectedAirportName,
            ]);
        }

        if ($etd <= $eta) {
            Yii::$app->session->setFlash('error', 'ETD must be after ETA.');

            return $this->render('update', [
                'request' => $request,
                'airports' => $airports,
                'statuss' => $status,
                'selectedAirportName' => $selectedAirportName,
            ]);
        }

        /*
         * Status update notification
         */
        if ($status !== null) {
            $request->status = $status;

            $lastMroRequestApply = MroRequestApply::find()
                ->where(['request_id' => $request->request_id])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($lastMroRequestApply) {
                $mro = MroProfile::findOne($lastMroRequestApply->mro_id);

                if ($mro) {
                    $mroNotificationsPreferences = MroNotificationsPreferences::findOne([
                        'mro_id' => $mro->mro_id
                    ]);

                    if ($mroNotificationsPreferences && $mroNotificationsPreferences->notify_by_email) {
                        Yii::$app->mailer
                            ->compose('update_request_recived', [
                                'request' => $request
                            ])
                            ->setTo($mro->email)
                            ->setSubject('Update Request Received')
                            ->send();
                    }

                    if ($mroNotificationsPreferences && $mroNotificationsPreferences->notify_by_platform) {
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'mro',
                            'recipientId' => $mro->mro_id,
                            'message' => 'Update request received for request #' . $id,
                            'actions' => Yii::$app->request->baseUrl . '/mro-applications/',
                        ]);
                    }
                }
            }
        }

        if ($request->save()) {

            /*
             * Only recalculate coverage if destination or aircraft changed.
             */
            $changed =
                $oldDestination != $request->destination ||
                $oldAircraftId != $request->aircraft_id;

            if ($changed) {
                $aircraft = Aircrafts::findOne($request->aircraft_id);

                if ($aircraft) {
                    $certificateType = $aircraft->getCertificateType()->one();
                    $aircraftModel = $aircraft->getAircraftModel()->one();

                    if ($certificateType && $aircraftModel) {
                        $requiredCertificates = $certificateType->type;
                        $requiredAircraftModel = $aircraftModel->aircraft_model_id;

                        $mrosWithCertificates = MroProfile::find()
                            ->joinWith('certificates')
                            ->where(['IN', 'certificates.type', $requiredCertificates])
                            ->all();

                        $mrosWithAircraft = MroProfile::find()
                            ->joinWith('aircraftCertificates')
                            ->where([
                                'mro_aircraft_certificate.aircraft_model_id' => $requiredAircraftModel
                            ])
                            ->all();

                        $mroIdsWithCertificates = array_column($mrosWithCertificates, 'mro_id');
                        $mroIdsWithAircraft = array_column($mrosWithAircraft, 'mro_id');

                        /*
                         * Use each() instead of all() to reduce memory usage.
                         */
                        foreach (MroProfile::find()->each(50) as $mro) {
                            $notificationPreferences = MroNotificationsPreferences::findOne([
                                'mro_id' => $mro->mro_id
                            ]);

                            $url = Url::to(['mro-requests/index'], true);

                            $hasInsurance = MroInsuranceDocuments::find()
                                ->where(['mro_id' => $mro->mro_id])
                                ->exists();

                            $hasAirportAccess = MroprofileAirport::find()
                                ->where([
                                    'mro_id' => $mro->mro_id,
                                    'airport_id' => $request->destination,
                                ])
                                ->exists();

                            $hasCertificateAccess = in_array($mro->mro_id, $mroIdsWithCertificates);
                            $hasAircraftAccess = in_array($mro->mro_id, $mroIdsWithAircraft);

                            $covered =
                                $hasAirportAccess &&
                                $hasCertificateAccess &&
                                $hasAircraftAccess &&
                                $hasInsurance;

                            $reason = [];

                            if ($hasAirportAccess) {
                                if (!$hasAircraftAccess) {
                                    $reason[] =
                                        'Aircraft not covered: ' .
                                        $aircraft->manufacturer .
                                        ' : ' .
                                        $aircraft->model;
                                }

                                if (!$hasCertificateAccess) {
                                    $reason[] =
                                        'Certificate not covered: ' .
                                        $requiredCertificates;
                                }
                            }

                            $reasonText = implode(' | ', $reason);

                            if (
                                $covered &&
                                $notificationPreferences &&
                                $notificationPreferences->notify_for_certified_aircraft
                            ) {
                                if ($notificationPreferences->notify_by_email) {
                                    Yii::$app->mailer
                                        ->compose('notification_certified', [
                                            'url' => $url
                                        ])
                                        ->setTo($mro->email)
                                        ->setSubject('Request Updated')
                                        ->send();
                                }

                                if ($notificationPreferences->notify_by_platform) {
                                    Yii::$app->runAction('notification/save-notification', [
                                        'recipientType' => 'mro',
                                        'recipientId' => $mro->mro_id,
                                        'message' => 'A request #' . $id . ' has been updated and matches your scope of work.',
                                        'actions' => Yii::$app->request->baseUrl . '/mro-requests',
                                    ]);
                                }
                            }

                            if (
                                $hasAirportAccess &&
                                !$covered &&
                                $notificationPreferences &&
                                $notificationPreferences->notify_for_non_certified_aircraft
                            ) {
                                if ($notificationPreferences->notify_by_email) {
                                    Yii::$app->mailer
                                        ->compose('notification_non_certified', [
                                            'manufacturer' => $aircraft->manufacturer,
                                            'model' => $aircraft->model,
                                        ])
                                        ->setTo($mro->email)
                                        ->setSubject('Request Updated')
                                        ->send();
                                }

                                if ($notificationPreferences->notify_by_platform) {
                                    Yii::$app->runAction('notification/save-notification', [
                                        'recipientType' => 'mro',
                                        'recipientId' => $mro->mro_id,
                                        'message' => 'A request has been updated and is NOT fully covered: ' . $reasonText,
                                        'actions' => Yii::$app->request->baseUrl . '/mro-requests',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            Yii::$app->session->setFlash('message', 'Request updated successfully.');

            return $this->redirect(['new-requests']);
        }

        Yii::$app->session->setFlash('error', 'Failed to update request.');
    }

    return $this->render('update', [
        'request' => $request,
        'airports' => $airports,
        'statuss' => $status,
        'selectedAirportName' => $selectedAirportName,
    ]);
}

public function actionUpdate1($id, $status = null)
{
    /*
     * CORRECTIF IDOR : ancienne action encore routable (requests/update1) qui
     * accepte un identifiant numérique brut. Réservée à l'AO propriétaire.
     */
    $request = $this->findOwnedRequest($id);

    $oldDestination = $request->destination;
    $oldAircraftId = $request->aircraft_id;

    $ao_id = Yii::$app->session->get('ao_id');
    $request->ao_id = $ao_id;

    $airports = Airports::find()->all();

    $selectedAirport = Airports::findOne($request->destination);
    $selectedAirportName = $selectedAirport
        ? $selectedAirport->airport_name
        : null;

    if (!empty($request->eta)) {
        $request->eta = date(
            'Y-m-d H:i:s',
            strtotime($request->eta)
        );
    }

    if (!empty($request->etd)) {
        $request->etd = date(
            'Y-m-d H:i:s',
            strtotime($request->etd)
        );
    }

    if ($request->load(Yii::$app->request->post())) {
        // CORRECTIF IDOR : ao_id ne doit jamais être modifié par le formulaire.
        $request->ao_id = (int) $request->getOldAttribute('ao_id');

        /**
         * FILE UPLOAD
         */
        $request->attachment =
            UploadedFile::getInstance($request, 'attachment');

        if ($request->attachment) {

            $uploadPath =
                'uploads/' .
                Yii::$app->security->generateRandomString() .
                '.' .
                $request->attachment->extension;

            if ($request->attachment->saveAs($uploadPath)) {

                $request->attachment = $uploadPath;

            } else {

                Yii::$app->session->setFlash(
                    'error',
                    'Failed to upload attachment.'
                );

                return $this->render('update', [
                    'request' => $request,
                    'airports' => $airports,
                ]);
            }
        }

        $request->location =
            Airports::findOne($request->destination)->airport_name;

        /**
         * DATE VALIDATION
         */
        $eta = strtotime($request->eta);
        $etd = strtotime($request->etd);

        if ($etd <= $eta) {

            Yii::$app->session->setFlash(
                'error',
                'ETD must be after ETA.'
            );

            return $this->render('update', [
                'request' => $request,
                'airports' => $airports,
            ]);
        }

        /**
         * STATUS UPDATE NOTIFICATION
         */
        if ($status !== null) {

            $request->status = $status;

            $lastMroRequestApply = MroRequestApply::find()
                ->where([
                    'request_id' => $request->request_id
                ])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($lastMroRequestApply) {

                $mro = MroProfile::findOne(
                    $lastMroRequestApply->mro_id
                );

                if ($mro) {

                    $aoNotificationsPreferences =
                        MroNotificationsPreferences::findOne([
                            'mro_id' => $mro->mro_id
                        ]);

                    if (
                        $aoNotificationsPreferences &&
                        $aoNotificationsPreferences->notify_by_email
                    ) {

                        Yii::$app->mailer
                            ->compose(
                                'update_request_recived',
                                ['request' => $request]
                            )
                            ->setTo($mro->email)
                            ->setSubject(
                                'Update Request Recived'
                            )
                            ->send();
                    }

                    /**
                     * PLATFORM NOTIFICATION
                     */
                    if (
                        $aoNotificationsPreferences &&
                        $aoNotificationsPreferences
                            ->notify_by_platform
                    ) {

                        Yii::$app->runAction(
                            'notification/save-notification',
                            [
                                'recipientType' => 'mro',

                                'recipientId' => $mro->mro_id,

'message' => 'Update request received for request #' . $id,

                                'actions' =>
                                    Yii::$app->request->baseUrl .
                                    '/mro-applications/',
                            ]
                        );
                    }
                }
            }
        }

        if ($request->save()) {

            /**
             * ONLY CHECK IF IMPORTANT DATA CHANGED
             */
            $changed =
                $oldDestination != $request->destination ||
                $oldAircraftId != $request->aircraft_id;

            if ($changed) {

                /**
                 * REQUIRED DATA
                 */
                $aircraft =
                    Aircrafts::findOne($request->aircraft_id);

                $requiredCertificates = $aircraft
                    ->getCertificateType()
                    ->one()
                    ->type;

                $requiredAircraftModel = $aircraft
                    ->getAircraftModel()
                    ->one()
                    ->aircraft_model_id;

                /**
                 * MROs WITH CERTIFICATES
                 */
                $mrosWithCertificates = MroProfile::find()
                    ->joinWith('certificates')
                    ->where([
                        'IN',
                        'certificates.type',
                        $requiredCertificates
                    ])
                    ->all();

                /**
                 * MROs WITH AIRCRAFT
                 */
                $mrosWithAircraft = MroProfile::find()
                    ->joinWith('aircraftCertificates')
                    ->where([
                        'mro_aircraft_certificate.aircraft_model_id'
                            => $requiredAircraftModel
                    ])
                    ->all();

                /**
                 * IDS ARRAYS
                 */
                $mroIdsWithCertificates =
                    array_column(
                        $mrosWithCertificates,
                        'mro_id'
                    );

                $mroIdsWithAircraft =
                    array_column(
                        $mrosWithAircraft,
                        'mro_id'
                    );

                /**
                 * ALL MROS
                 */
                $allMros = MroProfile::find()->all();

                foreach ($allMros as $mro) {

                    $notificationPreferences =
                        MroNotificationsPreferences::findOne([
                            'mro_id' => $mro->mro_id
                        ]);

                    $url = Url::to(
                        ['mro-requests/index'],
                        true
                    );

                    /**
                     * =========================
                     * COVER CHECKS
                     * =========================
                     */
                    $hasInsurance = MroInsuranceDocuments::find()
                        ->where(['mro_id' => $mro->mro_id])
                        ->exists();
                    // AIRPORT COVER
                    $hasAirportAccess =
                        MroprofileAirport::find()
                            ->where([
                                'mro_id' => $mro->mro_id,
                                'airport_id' =>
                                    $request->destination,
                            ])
                            ->exists();

                    // CERTIFICATE COVER
                    $hasCertificateAccess =
                        in_array(
                            $mro->mro_id,
                            $mroIdsWithCertificates
                        );

                    // AIRCRAFT COVER
                    $hasAircraftAccess =
                        in_array(
                            $mro->mro_id,
                            $mroIdsWithAircraft
                        );

                    // FULL COVER
                    $covered =
                        $hasAirportAccess &&
                        $hasCertificateAccess &&
                        $hasAircraftAccess &&
                        $hasInsurance;

                    /**
                     * =========================
                     * BUILD REASON MESSAGE
                     * =========================
                     */

                    $reason = [];

                    // ONLY BUILD REASON
                    // IF AIRPORT IS COVERED
                    if ($hasAirportAccess) {

                        if (!$hasAircraftAccess) {

                            $reason[] =
                                "Aircraft not covered: " .
                                $aircraft->manufacturer .
                                " : " .
                                $aircraft->model;
                        }

                        if (!$hasCertificateAccess) {

                            $reason[] =
                                "Certificate not covered: " .
                                $requiredCertificates;
                        }
                    }

                    $reasonText = implode(
                        " | ",
                        $reason
                    );

                    /**
                     * =========================
                     * CERTIFIED NOTIFICATION
                     * =========================
                     */

                    if (
                        $covered &&
                        $notificationPreferences &&
                        $notificationPreferences
                            ->notify_for_certified_aircraft
                    ) {

                        /**
                         * EMAIL
                         */
                        if (
                            $notificationPreferences
                                ->notify_by_email
                        ) {

                            Yii::$app->mailer
                                ->compose(
                                    'notification_certified',
                                    ['url' => $url]
                                )
                                ->setTo($mro->email)
                                ->setSubject(
                                    'Request Updated'
                                )
                                ->send();
                        }

                        /**
                         * PLATFORM
                         */
                        if (
                            $notificationPreferences
                                ->notify_by_platform
                        ) {

                            Yii::$app->runAction(
                                'notification/save-notification',
                                [
                                    'recipientType' => 'mro',

                                    'recipientId' => $mro->mro_id,

'message' => 'A request #' . $id . ' has been updated and matches your scope of work.',

                                    'actions' =>
                                        Yii::$app->request->baseUrl .
                                        '/mro-requests',
                                ]
                            );
                        }
                    }

                    /**
                     * =========================
                     * NON COVERED NOTIFICATION
                     * ONLY IF AIRPORT IS COVERED
                     * =========================
                     */

                    if (
                        $hasAirportAccess &&
                        !$covered &&
                        $notificationPreferences &&
                        $notificationPreferences
                            ->notify_for_non_certified_aircraft
                    ) {

                        /**
                         * EMAIL
                         */
                        if (
                            $notificationPreferences
                                ->notify_by_email
                        ) {

                            Yii::$app->mailer
                                ->compose(
                                    'notification_non_certified',
                                    [
                                        'manufacturer' =>
                                            $aircraft->manufacturer,

                                        'model' =>
                                            $aircraft->model,
                                    ]
                                )
                                ->setTo($mro->email)
                                ->setSubject(
                                    'Request Updated'
                                )
                                ->send();
                        }

                        /**
                         * PLATFORM
                         */
                        if (
                            $notificationPreferences
                                ->notify_by_platform
                        ) {

                            Yii::$app->runAction(
                                'notification/save-notification',
                                [
                                    'recipientType' => 'mro',

                                    'recipientId' => $mro->mro_id,

                                    'message' =>
                                        'A request has been updated and is NOT fully covered: '
                                        . $reasonText,

                                    'actions' =>
                                        Yii::$app->request->baseUrl .
                                        '/mro-requests',
                                ]
                            );
                        }
                    }
                }
            }

            Yii::$app->session->setFlash(
                'message',
                'Request updated successfully.'
            );

            return $this->redirect(['new-requests']);
        }
    }

    return $this->render('update', [
        'request' => $request,
        'airports' => $airports,
        'statuss' => $status,
        'selectedAirportName' => $selectedAirportName,
    ]);
}

    public function actionCheckApplications($id)
{
    
     // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    /*
     * CORRECTIF IDOR : la liste des devis MRO (prix, documents) d'une demande
     * n'est visible que par l'AO propriétaire. Avant, tout utilisateur
     * connecté pouvait lire les devis concurrents d'une demande.
     */
    $request = $this->findOwnedRequest($id);

    // Fetch all applications for the specific request
    $applications = MroRequestApply::find()->where(['request_id' => $id])->all();

    // Render the view with the applications data
    return $this->render('check-applications', [
        'request' => $request,
        'applications' => $applications,
    ]);
}

/**
 * Display one MRO application with the same business actions as the list row.
 * The public URL uses the signed identifier handled by UrlIdHelper.
 */
public function actionViewApplication($id)
{
    $applicationId = UrlIdHelper::decode($id);

    if (!$applicationId) {
        throw new NotFoundHttpException('Invalid application link.');
    }

    $application = MroRequestApply::findOne($applicationId);

    if (!$application) {
        throw new NotFoundHttpException('Application not found.');
    }

    $request = Requests::findOne($application->request_id);

    if (!$request) {
        throw new NotFoundHttpException('Request not found.');
    }

    // SECURITY: an AO may only inspect applications linked to its own request.
    $aoId = (int) (Yii::$app->session->get('ao_id') ?: Yii::$app->user->id);
    if ((int) $request->ao_id !== $aoId) {
        throw new ForbiddenHttpException('You are not allowed to view this application.');
    }

    return $this->render('view-application', [
        'application' => $application,
        'request' => $request,
    ]);
}

public function actionDeny($id)
{
    // SIGNED APPLICATION ID: decode at the controller boundary before any lookup.
    $id = UrlIdHelper::decodeOrFail($id, 'Invalid application link.');

    /*
     * CORRECTIF IDOR : refuser (et supprimer) une réponse MRO est réservé à
     * l'AO propriétaire de la demande. Avant, n'importe quel utilisateur
     * connecté pouvait supprimer le devis d'un concurrent.
     */
    $application = $this->findOwnedApplication($id);

    $requestId = $application->request_id;
    if ($application) {
        // Load the associated MRO
        $mro = $application->mro;
        
        // Send email notification to the MRO including application details
        Yii::$app->mailer->compose('application_denied', ['application' => $application])
        ->setTo($mro->email)
        
        ->setSubject('Request Application Denied')
        ->send();

                                                //platform notification
                                                    // Call the action to save the notification
                                                        Yii::$app->runAction('notification/save-notification', [
                                                            'recipientType' => 'mro',
                                                            'recipientId' => $mro->mro_id,
'message' =>
    'Your application for request #' . $requestId . ' has been denied.',
                                                         'actions' =>  Yii::$app->request->baseUrl .'/mro-requests', // Or any route you want

                                                        ]);
                                                    
        
        // Delete the application
        $application->delete();
        $applicationsCount = MroRequestApply::find()->where(['request_id' => $requestId])->count();
        if ($applicationsCount === 0) {
            // Update request status to 'created'
            $request = Requests::findOne($requestId);
            if ($request) {
                $request->status = 'created';
                $request->save();
            }
        }

        // Set flash message
        Yii::$app->session->setFlash('success', 'Application denied and notification sent.');
    } else {
        Yii::$app->session->setFlash('error', 'Application not found.');
    }

    // Redirect back to the check-applications page or any other relevant page
    return $this->redirect(['check-applications', 'id' => UrlIdHelper::encode($requestId)]);
}




public function actionLoadPo($id)
{
    
     // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    /*
     * Load the MRO application safely.
     */
    $application = MroRequestApply::findOne($id);

    if (!$application) {
        Yii::$app->session->setFlash('error', 'Application not found.');
        return $this->redirect(['new-requests']);
    }

    /*
     * CORRECTIF IDOR : émettre un PO engage contractuellement l'AO. Seul le
     * propriétaire de la demande peut le faire. Avant, un utilisateur
     * connecté quelconque pouvait déposer un PO sur la demande d'un autre AO.
     */
    $this->findOwnedRequest($application->request_id);

    /*
     * Load related MRO safely.
     */
    $mro = $application->mro;

    if (!$mro) {
        Yii::$app->session->setFlash('error', 'MRO profile not found.');
        return $this->redirect(['check-applications', 'id' => UrlIdHelper::encode($application->request_id)]);
    }

    /*
     * Load related request safely.
     */
    $request = Requests::findOne($application->request_id);

    if (!$request) {
        Yii::$app->session->setFlash('error', 'Request not found.');
        return $this->redirect(['new-requests']);
    }

    /*
     * Check if AO request application already exists.
     */
    $aoRequest = AoRequestsApplications::find()
        ->where([
            'application_id' => $id,
            'request_id' => $application->request_id
        ])
        ->one();

    /*
     * If not exists, create a new one.
     */
    if (!$aoRequest) {
        $aoRequest = new AoRequestsApplications();
        $aoRequest->application_id = $id;
        $aoRequest->request_id = $application->request_id;
    }

    /*
     * Load MRO insurance documents.
     */
    $mroInsurance = MroInsuranceDocuments::find()
        ->where(['mro_id' => $mro->mro_id])
        ->all();

    /*
     * Load MRO certificate for the required certificate type.
     */
    $mroCertificate = Certificates::find()
        ->where([
            'mro_id' => $mro->mro_id,
            'type' => $request->required_certificates
        ])
        ->one();

    /*
     * Load aircraft safely.
     * This fixes:
     * Attempt to read property "aircraft_model_id" on null.
     */
    $aircraft = $request->getAircraft()->one();

    $mroAircraftCertificate = null;

    if ($aircraft && !empty($aircraft->aircraft_model_id)) {
        $mroAircraftCertificate = MroAircraftCertificate::find()
            ->where([
                'mro_id' => $mro->mro_id,
                'aircraft_model_id' => $aircraft->aircraft_model_id
            ])
            ->one();
    } else {
        Yii::$app->session->setFlash(
            'error',
            'Aircraft model not found for this request. Please check the aircraft configuration.'
        );
    }

    /*
     * Handle POST request.
     */
    if (Yii::$app->request->isPost) {

        /*
         * Handle the "Check Certificate" button click.
         */
        if (Yii::$app->request->post('check-certificate')) {
            return $this->render('load-po', [
                'aoRequest' => $aoRequest,
                // LOAD PO UX 2026: expose existing context for the read-only summary.
                'application' => $application,
                'request' => $request,
                'mro' => $mro,
                'aircraft' => $aircraft,
                'mroCertificate' => $mroCertificate,
                'mroInsurance' => $mroInsurance,
                'mroAircraftCertificate' => $mroAircraftCertificate,
                'certificateModal' => true,
            ]);
        }

        /*
         * Upload PO file.
         */
        $uploadedFile = UploadedFile::getInstance($aoRequest, 'po');

        if ($uploadedFile) {
            $filename = 'po_' .
                Yii::$app->security->generateRandomString(8) .
                '.' .
                $uploadedFile->extension;

            if ($uploadedFile->saveAs('uploads/' . $filename)) {

                /*
                 * Delete old PO file if updating.
                 */
                if (!$aoRequest->isNewRecord && !empty($aoRequest->po)) {
                    $oldFile = 'uploads/' . $aoRequest->po;

                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                /*
                 * Assign new PO filename.
                 */
                $aoRequest->po = $filename;

                if ($aoRequest->save()) {

                    /*
                     * Update request status.
                     */
                    $request->status = 'po_loaded';
                    $request->save(false);

                    /*
                     * Notify other MROs that their application was not selected.
                     */
                    $allApplications = MroRequestApply::find()
                        ->where(['request_id' => $application->request_id])
                        ->all();

                    $acceptedMroId = $mro->mro_id;

                    foreach ($allApplications as $app) {
                        if ($app->mro_id == $acceptedMroId) {
                            continue;
                        }

                        $otherMro = MroProfile::findOne($app->mro_id);

                        if (!$otherMro) {
                            continue;
                        }

                        $prefs = MroNotificationsPreferences::findOne([
                            'mro_id' => $otherMro->mro_id
                        ]);

                        /*
                         * Email notification to non-selected MROs.
                         */
                        if ($prefs && $prefs->notify_by_email) {
                            Yii::$app->mailer
                                ->compose('application_rejected', [
                                    'application' => $app,
                                    'request' => $request
                                ])
                                ->setTo($otherMro->email)
                                ->setSubject('Application Not Accepted')
                                ->send();
                        }

                        /*
                         * Platform notification to non-selected MROs.
                         */
                        if ($prefs && $prefs->notify_by_platform) {
                            Yii::$app->runAction('notification/save-notification', [
                                'recipientType' => 'mro',
                                'recipientId' => $otherMro->mro_id,
                                'message' => 'Your application for request #' . $request->request_id . ' was not selected.',
                                'actions' => Yii::$app->request->baseUrl . '/mro-applications',
                            ]);
                        }
                    }

                    /*
                     * Notify accepted MRO.
                     */
                    $notificationPreferences = MroNotificationsPreferences::findOne([
                        'mro_id' => $mro->mro_id
                    ]);

                    if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                        /*
                         * ACCÈS AU PO POUR LE MRO SÉLECTIONNÉ : l'identifiant de candidature est encodé avant
                         * d'être placé dans l'URL. Le fichier reste sur la plateforme authentifiée et n'est pas
                         * joint au message, ce qui évite de diffuser le document hors de l'espace sécurisé.
                         */
                        $poUrl = Url::to([
                            'mro-applications/view-po',
                            'id' => UrlIdHelper::encode((int) $application->id),
                        ], true);

                        Yii::$app->mailer
                            ->compose('application_accepted', [
                                /*
                                 * RÉSUMÉ OPÉRATIONNEL COMPLET : la demande et l'enregistrement du PO sont passés
                                 * explicitement pour éviter une résolution implicite fragile dans la vue mail.
                                 */
                                'application' => $application,
                                'request' => $request,
                                'aoRequest' => $aoRequest,
                                'url' => $poUrl,
                            ])
                            ->setTo($mro->email)
                            ->setSubject('PO loaded - Request #' . $request->request_id)
                            ->send();
                    }

                    if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'mro',
                            'recipientId' => $mro->mro_id,
                            'message' => 'Your application for the request #' . $request->request_id . ' has been accepted.',
                            'actions' => Yii::$app->request->baseUrl . '/mro-applications',
                        ]);
                    }

                    Yii::$app->session->setFlash('success', 'PO file uploaded successfully.');
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save PO file.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload PO file.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'No file uploaded.');
        }

        return $this->redirect(['new-requests']);
    }

    /*
     * Render the PO loading page.
     */
    return $this->render('load-po', [
        'aoRequest' => $aoRequest,
        // LOAD PO UX 2026: expose existing context for the read-only summary.
        'application' => $application,
        'request' => $request,
        'mro' => $mro,
        'aircraft' => $aircraft,
        'mroCertificate' => $mroCertificate,
        'mroInsurance' => $mroInsurance,
        'mroAircraftCertificate' => $mroAircraftCertificate,
        'certificateModal' => false,
    ]);
}

public function actionLoadPo1($id)
{
    /*
     * CORRECTIF IDOR : ancienne action encore routable (requests/load-po1) avec
     * identifiant brut. Réservée à l'AO propriétaire de la demande.
     */
    $application = $this->findOwnedApplication($id);
    $mro = $application->mro;

// CHECK IF AO REQUEST APPLICATION ALREADY EXISTS
$aoRequest = AoRequestsApplications::find()
    ->where([
        'application_id' => $id,
        'request_id' => $application->request_id
    ])
    ->one();

// IF NOT EXISTS → CREATE NEW
if (!$aoRequest) {
    $aoRequest = new AoRequestsApplications();
    $aoRequest->application_id = $id;
    $aoRequest->request_id = $application->request_id;
}

    $request = Requests::findOne($application->request_id);
$mroInsurance = MroInsuranceDocuments::find()
    ->where(['mro_id' => $mro->mro_id])
    ->all(); // or ->one() if only one file
    $mroCertificate = Certificates::find()
        ->where([
            'mro_id' => $mro->mro_id,
            'type' => $request->required_certificates
        ])
        ->one();

        $mroAircraftCertificate = MroAircraftCertificate::find()
        ->where([
            'mro_id' => $mro->mro_id,
            'aircraft_model_id' => $request->getAircraft()->one()->aircraft_model_id
        ])
        ->one();

    // Check if the form is submitted and the model is validated
    if (Yii::$app->request->isPost) {
        if (Yii::$app->request->post('check-certificate')) {
            // Handle the "Check Certificate" button click
            return $this->render('load-po', [
                'aoRequest' => $aoRequest,
                'mroCertificate' => $mroCertificate,
                'mroAircraftCertificate' =>$mroAircraftCertificate,
                'certificateModal' => true // Flag to show the modal
            ]);
        }

        $uploadedFile = UploadedFile::getInstance($aoRequest, 'po');
        if ($uploadedFile) {
            // Generate a unique filename
            $filename = 'po_' . Yii::$app->security->generateRandomString(8) . '.' . $uploadedFile->extension;

            // Save the uploaded file
            if ($uploadedFile->saveAs('uploads/' . $filename)) {
              // OPTIONAL: delete old PO file if updating
if (!$aoRequest->isNewRecord && !empty($aoRequest->po)) {

    $oldFile = 'uploads/' . $aoRequest->po;

    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Assign new filename
$aoRequest->po = $filename;

// Save (UPDATE if exists / INSERT if new)
if ($aoRequest->save()) {
                    // Update the request status to "answered"
                    $request->status = 'po_loaded';
                    $request->save();

$allApplications = MroRequestApply::find()
    ->where(['request_id' => $application->request_id])
    ->all();
$acceptedMroId = $mro->mro_id;
foreach ($allApplications as $app) {

    if ($app->mro_id == $acceptedMroId) {
        continue; // skip accepted one
    }

    $otherMro = MroProfile::findOne($app->mro_id);

    if (!$otherMro) {
        continue;
    }

    $prefs = MroNotificationsPreferences::findOne([
        'mro_id' => $otherMro->mro_id
    ]);

    /**
     * EMAIL NOTIFICATION
     */
    if ($prefs && $prefs->notify_by_email) {

        Yii::$app->mailer
            ->compose('application_rejected', [
                'application' => $app,
                'request' => $request
            ])
            ->setTo($otherMro->email)
            ->setSubject('Application Not Accepted')
            ->send();
    }

    /**
     * PLATFORM NOTIFICATION
     */
    if ($prefs && $prefs->notify_by_platform) {

        Yii::$app->runAction('notification/save-notification', [
            'recipientType' => 'mro',
            'recipientId' => $otherMro->mro_id,
            'message' => 'Your application for request #' . $request->request_id . ' was not selected.',
            'actions' => Yii::$app->request->baseUrl . '/mro-applications',
        ]);
    }
}
                    $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
                    if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                        /*
                         * SECOND PARCOURS DE CHARGEMENT : même URL encodée et même contenu que le traitement
                         * principal afin que l'expérience du MRO ne dépende pas de l'écran utilisé par l'AO.
                         */
                        $poUrl = Url::to([
                            'mro-applications/view-po',
                            'id' => UrlIdHelper::encode((int) $application->id),
                        ], true);

                        Yii::$app->mailer->compose('application_accepted', [
                            'application' => $application,
                            'request' => $request,
                            'aoRequest' => $aoRequest,
                            'url' => $poUrl,
                        ])
                            ->setTo($mro->email)
                            ->setSubject('PO loaded - Request #' . $request->request_id)
                            ->send();
                    }

                    // Platform notification
                    if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                        // Call the action to save the notification
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'mro',
                            'recipientId' => $mro->mro_id,
                            'message' => 'Your application for the request #'.$request->request_id .' has been accepted.',
                            'actions' =>  Yii::$app->request->baseUrl .'/mro-applications', // Or any route you want

                        ]);
                    }

                    Yii::$app->session->setFlash('success', 'PO file uploaded successfully.');
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save PO file.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload PO file.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'No file uploaded.');
        }

        // Redirect to a relevant page
        return $this->redirect(['new-requests']);
    }

    // Render the view with the AO request model
    return $this->render('load-po', [
        'aoRequest' => $aoRequest,
        'mroCertificate' => $mroCertificate,
            'mroInsurance' => $mroInsurance,
        'mroAircraftCertificate' =>$mroAircraftCertificate,
        'certificateModal' => false // Flag to hide the modal initially
    ]);
}



public function actionViewPo($id)
{
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    /*
     * CORRECTIF IDOR : la liste des PO d'une demande est réservée à l'AO
     * propriétaire (le MRO consulte ses PO via mro-applications/view-po).
     */
    $request = $this->findOwnedRequest($id);

    // Find all AoRequestsApplications related to the request_id
    $aoRequestsApplications = AoRequestsApplications::find()->where(['request_id' => $id])->all();
    
    // Pass the request data and AoRequestsApplications data to the view
    return $this->render('view-po', [
        'request' => $request,
        'aoRequestsApplications' => $aoRequestsApplications,
    ]);
}
public function actionDownloadPo($id)
{
    /* REQUEST PO DOCUMENT 2026: decode the PO record ID before serving the file. */
    $poRecordId = UrlIdHelper::decode($id);
    if (!$poRecordId) {
        throw new NotFoundHttpException('Invalid PO document link.');
    }

    /*
     * CORRECTIF IDOR : le fichier PO est un document contractuel. Il n'est
     * téléchargeable ici que par l'AO propriétaire de la demande.
     */
    $aoRequest = $this->findOwnedPoRecord($poRecordId);

    if ($aoRequest && !empty($aoRequest->po)) {
        // Define the file path
        $safeFileName = basename($aoRequest->po);
        $filePath = Yii::getAlias('@webroot/uploads/' . $safeFileName);

        // Check if the file exists
        if (file_exists($filePath)) {
            // Send the file to the browser for download
            return Yii::$app->response->sendFile($filePath, $safeFileName, ['inline' => false]);
        } else {
            // File not found, display error message
            Yii::$app->session->setFlash('error', 'File not found.');
        }
    } else {
        // No PO file associated with this request, display error message
        Yii::$app->session->setFlash('error', 'No PO file associated with this request.');
    }

    // Redirect back with the encoded request ID when the document is unavailable.
    return $aoRequest
        ? $this->redirect(['view-po', 'id' => UrlIdHelper::encode($aoRequest->request_id)])
        : $this->redirect(['index']);
}

/**
 * REQUEST PO DOCUMENT 2026: displays the PO inline without changing download logic.
 */
public function actionViewPoDocument($id)
{
    $poRecordId = UrlIdHelper::decode($id);
    if (!$poRecordId) {
        throw new NotFoundHttpException('Invalid PO document link.');
    }

    // CORRECTIF IDOR : même contrôle que download-po (AO propriétaire uniquement).
    $aoRequest = $this->findOwnedPoRecord($poRecordId);
    if (empty($aoRequest->po)) {
        throw new NotFoundHttpException('PO document not found.');
    }

    $safeFileName = basename($aoRequest->po);
    $filePath = Yii::getAlias('@webroot/uploads/' . $safeFileName);
    if (!is_file($filePath)) {
        throw new NotFoundHttpException('PO file not found.');
    }

    return Yii::$app->response->sendFile(
        $filePath,
        $safeFileName,
        ['inline' => true]
    );
}


    /**
     * Deletes an existing Request model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
public function actionDelete($id)
{
    // SIGNED REQUEST ID: destructive actions must never accept a raw database key.
    $id = UrlIdHelper::decodeOrFail($id, 'Invalid request link.');

    /*
     * CORRECTIF IDOR : la suppression est définitive (hard delete). Avant, un MRO
     * ou un autre AO disposant du lien signé pouvait supprimer la demande.
     * Désormais seul l'AO propriétaire peut la supprimer.
     */
    $request = $this->findOwnedRequest($id);

    // ðŸ”¥ Safely get MRO BEFORE deleting
    $mro = null;

    if ($request->mroApplication && $request->mroApplication->mro) {
        $mro = $request->mroApplication->mro;
    }

    // delete request first or after notification (we'll send before delete for safety)
    if ($mro) {

        Yii::$app->runAction('notification/save-notification', [
            'recipientType' => 'mro',
            'recipientId' => $mro->mro_id,
            'message' => 'Request #' . $request->request_id . ' has been removed by the AO.',
        ]);
    }

    // now delete
    $request->delete();

    Yii::$app->getSession()->setFlash('success', 'Request deleted successfully!');
    return $this->redirect(['new-requests']);
}

    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Request the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Requests::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

public function actionViewReports1($id)
{
    /*
     * CORRECTIF IDOR : ancienne action encore routable (requests/view-reports1)
     * avec identifiant brut et capable de clôturer la demande. Réservée à l'AO
     * propriétaire.
     */
    $request = $this->findOwnedRequest($id);

    // Handle form submission (CRS approval/rejection)
    if (Yii::$app->request->isPost) {
        $post = Yii::$app->request->post();
        $reportId = $post['report_id'] ?? null; // Expect this from hidden input
        $decision = $post['approve'] ?? null;

        if ($reportId && in_array($decision, ['yes', 'no'])) {
            $report = RepairReport::findOne($reportId);
            // CORRECTIF IDOR : le rapport posté doit appartenir à CETTE demande.
            $reportApplication = $report ? $report->getMroRequestApply()->one() : null;
            if ($reportApplication === null || (int) $reportApplication->request_id !== (int) $request->request_id) {
                $report = null;
            }
            if (!$report) {
                Yii::$app->session->setFlash('error', 'CRS report not found.');
                return $this->redirect(['view-reports', 'id' => $id]);
            }

            $lastMroRequestApply = MroRequestApply::find()
                ->where(['id' => $report->mro_request_apply_id])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            $mro = MroProfile::findOne($lastMroRequestApply->mro_id);
            $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);

            if ($decision === 'yes') {
                $report->quote_approved = true;
                $report->save();

                $request->status = 'closed';
                $request->save();

                if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                    /*
                     * ACCÈS SÉCURISÉ AU CRS ACCEPTÉ : le lien cible la candidature concernée avec un identifiant
                     * encodé. Le rapport et la demande sont fournis à la vue pour composer le résumé autorisé.
                     */
                    $crsUrl = Url::to([
                        'mro-applications/view-reports',
                        'id' => UrlIdHelper::encode((int) $lastMroRequestApply->id),
                    ], true);

                    Yii::$app->mailer->compose('CRS_report_accepted', [
                        'report' => $report,
                        'mro' => $mro,
                        'request' => $request,
                        'url' => $crsUrl,
                    ])
                        ->setTo($mro->email)
                        ->setSubject('CRS report accepted - Request #' . $request->request_id)
                        ->send();
                }

                if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'mro',
                        'recipientId' => $mro->mro_id,
'message' => 'CRS report for request #' . $id . ' has been accepted.',                        'actions' =>  Yii::$app->request->baseUrl .'/mro-applications/closed-requests', // Or any route you want

                    ]);
                }

                Yii::$app->session->setFlash('success', 'CRS approved successfully.');
            } else {
                $report->quote_approved = false;
                $report->save();

                if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                    Yii::$app->mailer->compose('CRS_report_rejected', ['report' => $report, 'mro' => $mro])
                        ->setTo($mro->email)
                        ->setSubject('CRS report rejected')
                        ->send();
                }

                if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'mro',
                        'recipientId' => $mro->mro_id,
'message' => 'CRS report rejected. Request #' . $id,                        'actions' =>  Yii::$app->request->baseUrl .'/mro-applications', // Or any route you want

                    ]);
                }

                Yii::$app->session->setFlash('error', 'CRS rejected. MRO should upload new CRS.');
            }

            return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($id)]);
        }

        Yii::$app->session->setFlash('error', 'Invalid form submission.');
    }

    // Fetch reports linked to this request
        $allRepairReports = RepairReport::find()->all();

        $repairReports = [];
        foreach ($allRepairReports as $report) {
            if ($report->getRequest()->request_id == $id) {
                $repairReports[] = $report;
            }
        }

    return $this->render('view-reports', [
        'request' => $request,
        'repairReports' => $repairReports,
    ]);
}


public function actionViewReports($id)
{
$encryptedId = $id;
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }

    /*
     * CORRECTIF IDOR : cette page affiche les CRS et permet de les accepter
     * (ce qui clôture la demande). Elle est réservée à l'AO propriétaire ;
     * le MRO dispose de sa propre page mro-applications/view-reports.
     */
    $request = $this->findOwnedRequest($id);

    /*
     * Handle form submission:
     * approve = yes => CRS accepted
     * approve = no  => CRS rejected
     */
    if (Yii::$app->request->isPost) {
        $post = Yii::$app->request->post();

        $reportId = $post['report_id'] ?? null;
        $decision = $post['approve'] ?? null;

        if (!$reportId || !in_array($decision, ['yes', 'no'], true)) {
            Yii::$app->session->setFlash('error', 'Invalid form submission.');
            return $this->redirect(['view-reports', 'id' => $encryptedId]);
        }

        $report = RepairReport::findOne($reportId);

        /*
         * CORRECTIF IDOR : report_id vient d'un champ caché du formulaire, donc
         * modifiable. On vérifie que ce rapport concerne bien la demande
         * contrôlée ci-dessus ; sinon il est traité comme introuvable.
         */
        $reportApplication = $report ? $report->getMroRequestApply()->one() : null;
        if ($reportApplication === null || (int) $reportApplication->request_id !== (int) $request->request_id) {
            $report = null;
        }

        if (!$report) {
            Yii::$app->session->setFlash('error', 'CRS report not found.');
            return $this->redirect(['view-reports', 'id' => $encryptedId]);
        }

        $lastMroRequestApply = MroRequestApply::find()
            ->where(['id' => $report->mro_request_apply_id])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$lastMroRequestApply) {
            Yii::$app->session->setFlash('error', 'MRO application not found.');
            return $this->redirect(['view-reports', 'id' => $encryptedId]);
        }

        $mro = MroProfile::findOne($lastMroRequestApply->mro_id);

        if (!$mro) {
            Yii::$app->session->setFlash('error', 'MRO profile not found.');
            return $this->redirect(['view-reports', 'id' => $encryptedId]);
        }

        $notificationPreferences = MroNotificationsPreferences::findOne([
            'mro_id' => $mro->mro_id,
        ]);

        if ($decision === 'yes') {
            /*
             * CRS accepted
             */
            $report->quote_approved = true;
            $report->save(false);

            $request->status = 'closed';
            $request->save(false);

            if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                /*
                 * DEUXIÈME PARCOURS D'ACCEPTATION : il génère la même destination encodée et transmet les mêmes
                 * informations métier afin que la notification reste uniforme quel que soit le point d'entrée.
                 */
                $crsUrl = Url::to([
                    'mro-applications/view-reports',
                    'id' => UrlIdHelper::encode((int) $lastMroRequestApply->id),
                ], true);

                Yii::$app->mailer
                    ->compose('CRS_report_accepted', [
                        'report' => $report,
                        'mro' => $mro,
                        'request' => $request,
                        'url' => $crsUrl,
                    ])
                    ->setTo($mro->email)
                    ->setSubject('CRS report accepted - Request #' . $request->request_id)
                    ->send();
            }

            if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $mro->mro_id,
                    'message' => 'CRS report for request #' . $id . ' has been accepted.',
                    'actions' => Yii::$app->request->baseUrl . '/mro-applications/closed-requests',
                ]);
            }

            Yii::$app->session->setFlash('success', 'CRS approved successfully.');
        } else {
            /*
             * CRS rejected
             */
            $report->quote_approved = false;
            $report->save(false);

            if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                Yii::$app->mailer
                    ->compose('CRS_report_rejected', [
                        'report' => $report,
                        'mro' => $mro,
                    ])
                    ->setTo($mro->email)
                    ->setSubject('CRS report rejected')
                    ->send();
            }

            if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $mro->mro_id,
                    'message' => 'CRS report rejected. Request #' . $id,
                    'actions' => Yii::$app->request->baseUrl . '/mro-applications',
                ]);
            }

            Yii::$app->session->setFlash('error', 'CRS rejected. MRO should upload new CRS.');
        }

        return $this->redirect(['view-reports', 'id' => $encryptedId]);
    }

    /*
     * Fetch all CRS reports linked to this request.
     * Important: use all(), not one().
     */
    $reports = RepairReport::find()
        ->joinWith('mroRequestApply')
        ->where(['mro_request_apply.request_id' => $id])
        ->orderBy(['repair_report_id' => SORT_DESC])
        ->all();

    return $this->render('view-reports', [
        'request' => $request,

        /*
         * Use reports in the view.
         */
        'reports' => $reports,

        /*
         * Optional compatibility if your old view still uses repairReports.
         */
        'repairReports' => $reports,
    ]);
}

    public function actionDownload($id)
    {
        // SIGNED REPORT ID: document downloads use the same protected public identifier.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid report link.');

        /*
         * CORRECTIF IDOR : le CRS est un document réglementaire de l'avion.
         * Ici, seul l'AO propriétaire de la demande peut le télécharger.
         */
        $report = $this->findOwnedReport($id);

        if (!$report->CRS_attachment) {
            throw new NotFoundHttpException('Report not found or attachment not available.');
        }
        $filePath = Yii::getAlias('@webroot/uploads/' . $report->CRS_attachment);

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Attachment file not found.');
        }

        return Yii::$app->response->sendFile($filePath, null, ['inline' => false]);
    }

    public function actionSubmitDescription($id)
    {
        // SIGNED REPORT ID: protect the report identifier used by this form.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid report link.');

        /*
         * CORRECTIF IDOR : le commentaire AO sur un CRS ne peut être saisi que
         * par l'AO propriétaire de la demande concernée.
         */
        $report = $this->findOwnedReport($id);
    
        if ($report->load(Yii::$app->request->post())) {
            $report->AO_description = Yii::$app->request->post('RepairReport')['AO_description'];

            if ($report->save()) {

                Yii::$app->session->setFlash('success', 'Description submitted successfully.');
                return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]);
            } else {
                Yii::$app->session->setFlash('error', 'Failed to submit description.');
            }
        }
    
        return $this->render('submit-description', [
            'report' => $report,
        ]);
    }

    public function actionAcceptCrs($id)
    {
        /*
         * CORRECTIF IDOR : accepter un CRS clôture la demande. Avant, n'importe
         * quel utilisateur connecté pouvait le faire avec un simple numéro.
         * findOwnedReport() vérifie rapport → réponse MRO → demande → AO connecté
         * (et lève une 404 propre au lieu d'une erreur PHP si le rapport n'existe pas).
         */
        $report = $this->findOwnedReport($id);
        $request = $report->getRequest();
        $requestId = $request ? $request->request_id : 'N/A';
        $lastMroRequestApply = MroRequestApply::find()
        ->where(['id' => $report->mro_request_apply_id]) // Assuming 'id' is the correct column name
        ->orderBy(['id' => SORT_DESC]) // Assuming 'created_at' is the timestamp column
        ->one();

        $mro =  MroProfile::findOne($lastMroRequestApply->mro_id);
        // Check if the request is a POST request and if the quote is approved
        if (Yii::$app->request->isPost && Yii::$app->request->post('approve') === 'yes') {

            // Set the quote_approved field to true
            $report->quote_approved = true;
            $report->save();

            // Set the request status to 'closed'
            $request = $report->getRequest();
            $request->status = 'closed';
            $request->save();

            $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
            if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                /*
                 * PARCOURS HISTORIQUE D'ACCEPTATION : même contenu et même URL encodée que les actions récentes,
                 * sans modifier le changement de statut ni les préférences de notification existantes.
                 */
                $crsUrl = Url::to([
                    'mro-applications/view-reports',
                    'id' => UrlIdHelper::encode((int) $lastMroRequestApply->id),
                ], true);

                Yii::$app->mailer->compose('CRS_report_accepted', [
                    'report' => $report,
                    'mro' => $mro,
                    'request' => $request,
                    'url' => $crsUrl,
                ])
                ->setTo($mro->email)
                ->setSubject('CRS report accepted - Request #' . $request->request_id)
                ->send();
            }
              //platform notification
              if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                // Call the action to save the notification
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $mro->mro_id,
'message' => "CRS report accepted for request #$requestId",
                    'actions' => Yii::$app->request->baseUrl . '/mro-applications/closed-requests',
                ]);
                }

            Yii::$app->session->setFlash('success', 'CRS approved successfully.');

            return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]);

        } elseif (Yii::$app->request->isPost && Yii::$app->request->post('approve') === 'no') {
            // Set the quote_approved field to true
            $report->quote_approved = false;
            $report->save();
            $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
            if ($notificationPreferences && $notificationPreferences->notify_by_email) {
                Yii::$app->mailer->compose('CRS_report_rejected', ['report' => $report , 'mro' => $mro])
                ->setTo($mro->email)
                
                ->setSubject('CRS report Rejected')
                ->send();
            }
              //platform notification
              if ($notificationPreferences && $notificationPreferences->notify_by_platform) {
                // Call the action to save the notification
Yii::$app->runAction('notification/save-notification', [
    'recipientType' => 'mro',
    'recipientId' => $mro->mro_id,
'message' => "CRS report rejected for request #$requestId",
    'actions' => Yii::$app->request->baseUrl . '/mro-applications',
]);
                }
  

            Yii::$app->session->setFlash('error', 'CRS rejected. MRO should upload new CRS.');
            return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]);

        } else {
            Yii::$app->session->setFlash('error', 'Invalid request.');

        }
 // Render the approve quote view
 return $this->render('approve-quote', [
    'report' => $report,
]);
    }

    public function actionApproveQuote($id)
    {
        // SIGNED REPORT ID: protect the CRS identifier used by this decision form.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid report link.');

        /*
         * CORRECTIF IDOR : approuver le devis clôture la demande et engage
         * financièrement l'AO. Seul l'AO propriétaire peut le faire ; avant,
         * un MRO pouvait approuver son propre devis et clôturer la demande.
         */
        $report = $this->findOwnedReport($id);

        // Check if the request is a POST request and if the quote is approved
        if (Yii::$app->request->isPost && Yii::$app->request->post('approve') === 'yes') {

            // Set the quote_approved field to true
            $report->quote_approved = true;
            $report->save();

            // Set the request status to 'closed'
            $request = $report->getRequest();
            $request->status = 'closed';
            $request->save();

          
            Yii::$app->session->setFlash('success', 'Quote approved successfully.');

            return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->getRequest()->request_id)]);

        } elseif (Yii::$app->request->isPost && Yii::$app->request->post('approve') === 'no') {

            // Find the AO application and delete it
            $ao_application = AoRequestsApplications::find()->where(['application_id' => $report->mro_request_apply_id])->one();
            if ($ao_application) {
                $ao_application->delete();
            }
          // Set the request status to 'answered'
          $request = $report->getRequest();
          $request->status = 'answered';
          $request->save();
          // Remove the report
          $report->delete();

         
            Yii::$app->session->setFlash('error', 'Quote rejected. Request cancelled.');
            return $this->redirect(['index']);

        } else {
            Yii::$app->session->setFlash('error', 'Invalid request.');

        }
 // Render the approve quote view
 return $this->render('approve-quote', [
    'report' => $report,
]);
    }
    public function actionProvideFeedback($id)
    {
        /* FEEDBACK ENCODED ID 2026: public URL contains an encoded CRS report ID. */
        $reportId = UrlIdHelper::decode($id);
        if (!$reportId) {
            throw new NotFoundHttpException('Invalid feedback link.');
        }

        /*
         * CORRECTIF IDOR : l'évaluation d'un MRO ne peut être déposée que par
         * l'AO propriétaire de la demande (sinon un tiers pourrait noter ou
         * dénigrer un MRO sur une mission qui ne le concerne pas).
         */
        $report = $this->findOwnedReport($reportId);
        $feedback = new Feedback();

        /* FEEDBACK REVIEW 2026: load the linked request once for context and existing processing. */
        $request = $report->getRequest();
        if (!$request) {
            throw new NotFoundHttpException('Request not found.');
        }
    
        if (Yii::$app->request->isPost) {


            $feedback->request_id = $request->request_id;
            $feedback->ao_id = $request->ao_id;

            $feedback->kept_to_agreed_schedule_rating = Yii::$app->request->post('Feedback')['kept_to_agreed_schedule_rating'];
            $feedback->kept_to_agreed_cost_rating = Yii::$app->request->post('Feedback')['kept_to_agreed_cost_rating'];
            $feedback->overall_communication_rating = Yii::$app->request->post('Feedback')['overall_communication_rating'];
            $feedback->rating =round(($feedback->kept_to_agreed_schedule_rating + $feedback->kept_to_agreed_cost_rating + $feedback->overall_communication_rating)/3) ;
            $feedback->mro_id = $report->getMro()->mro_id;
            $feedback->feedback_text = Yii::$app->request->post('Feedback')['feedback_text'];
          //  VarDumper::dump($feedback); die();

            $feedback->save();
    
            Yii::$app->session->setFlash('success', 'Feedback submitted successfully.');
            $mro = $report->getMro();
            $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);
            if ($notificationPreferences && $notificationPreferences->notify_by_email && $notificationPreferences->notify_for_appointment_acceptance) {
                Yii::$app->mailer->compose('feedback_sent', ['feedback' => $feedback , 'mro' => $mro])
                ->setTo($mro->email)
                
                ->setSubject('FeedBack sent')
                ->send();
            }
              //platform notification
              if ($notificationPreferences && $notificationPreferences->notify_by_platform && $notificationPreferences->notify_for_appointment_acceptance) {
                  $requestId = $request ? $request->request_id : 'N/A';

                // Call the action to save the notification
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'mro',
                        'recipientId' => $mro->mro_id,
                        'message' => 'Feedback has been provided for request #' . $requestId,
                                        'actions' =>  Yii::$app->request->baseUrl .'/mro-applications/closed-requests', // Or any route you want

                    ]);
                }
            return $this->redirect(['closed-requests']);
        }
    
        return $this->render('provide-feedback', [
            'report' => $report,
            'feedback'=>$feedback,
            'request' => $request,

        ]);
    }

    public function actionUpdateRequest($id)
    {
        $requestId = UrlIdHelper::decodeOrFail($id, 'Invalid request link.');
        $request = $this->findModel($requestId);

        /*
         * AVENANT SANS RÉGRESSION DE STATUT : l'ancien flux remplaçait
         * work_started par update_request puis answered. Le nouveau formulaire
         * crée une entité RequestChange et laisse la Request opérationnelle intacte.
         */
        if (
            Yii::$app->session->get('user_type') !== 'ao'
            || (int) $request->ao_id !== (int) Yii::$app->session->get('ao_id')
        ) {
            throw new ForbiddenHttpException('You are not allowed to amend this request.');
        }

        return $this->redirect([
            '/request-changes/create',
            'requestId' => UrlIdHelper::encode($request->request_id),
        ]);
    }


    public function actionContact($id)
    {
        // SIGNED APPLICATION ID: conversations are opened from a protected application URL.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid application link.');

        /*
         * CORRECTIF IDOR : cette action crée une conversation au nom de l'AO
         * connecté (chat.ao_id = session). Elle est donc réservée à l'AO
         * propriétaire de la demande ; le MRO utilise mro-applications/contact.
         */
        $mroRequestApplication = $this->findOwnedApplication($id);

        // Check if a conversation already exists for the given request
        $conversation = Conversations::findOne(['request_id' => $mroRequestApplication->request_id]);
    
        // If conversation does not exist, create a new one
        if (!$conversation) {
            // Create a new chat
            $chat = new Chat();
            $chat->mro_id =  $mroRequestApplication->mro_id;
            $chat->ao_id = Yii::$app->session->get('ao_id');
            $chat->request_id =  $mroRequestApplication->request_id;

            $chat->save();
    
            // Create a new conversation
            $conversation = new Conversations();
            $conversation->request_id =  $mroRequestApplication->request_id;
            $conversation->chat_id = $chat->chat_id;
            $conversation->sender_id = $chat->ao_id;
            $conversation->sender_type ='ao';
            $conversation->receiver_id = $chat->mro_id;
            $conversation->receiver_type ='mro';
            // Additional attributes initialization if needed
            $conversation->save();


        }
    
        // Redirect to view the conversation
        // SECURITY: keep the chat identifier signed in browser-facing URLs.
        return $this->redirect([
            'conversations/view',
            'chat_id' => UrlIdHelper::encode($conversation->chat_id),
        ]);
    }
    
}
