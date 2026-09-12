<?php

namespace app\controllers;

use app\components\UrlIdHelper;
use app\models\AoRequestsApplications;
use app\models\Appointment;
use app\models\Certificates;
use app\models\Chat;
use app\models\Dispute;
use app\models\MroAircraftCertificate;
use app\models\MroprofileAirport;
use app\models\MroRequestApply;
use app\models\RepairReport;
use app\models\RequestChangeEvent;
use app\models\Requests;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Fournit le curseur securise utilise par les listes rafraichies en AJAX.
 *
 * Ce controleur ne retourne jamais le contenu d'une demande. Il indique seulement
 * si le conteneur courant est devenu obsolete et fournit des identifiants publics
 * signes lorsque leur visibilite a ete confirmee cote serveur.
 */
class RequestSyncController extends Controller
{
    /**
     * CONTEXTES AUTORISES : chaque role ne peut interroger que ses propres familles
     * d'ecrans. Cette liste blanche empeche un client de transformer le parametre
     * context en acces indirect a une liste reservee a un autre profil.
     */
    private const CONTEXTS_BY_ROLE = [
        'ao' => ['ao-requests', 'ao-appointments', 'conversations', 'disputes'],
        'mro' => ['mro-requests', 'mro-applications', 'mro-appointments', 'conversations', 'disputes'],
        'admin' => ['admin-disputes', 'awaiting-responses'],
    ];

    /**
     * Applique l'authentification Yii2 et limite cet endpoint de lecture a GET.
     * Le role fonctionnel sera ensuite controle avec la session serveur, jamais
     * avec une valeur actor_id fournie par JavaScript.
     */
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
                            return in_array(
                                strtolower((string) Yii::$app->session->get('user_type')),
                                ['ao', 'mro', 'admin'],
                                true
                            );
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'changes' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Retourne les changements autorises situes apres le curseur du navigateur.
     * Le traitement est borne a 250 evenements pour proteger PHP/MySQL sur un
     * hebergement partage. hasMore permet au futur client de continuer aussitot
     * sans attendre le prochain intervalle lorsque ce lot est depasse.
     */
    public function actionChanges($cursor = null, $context = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        $role = strtolower((string) Yii::$app->session->get('user_type'));
        $context = strtolower(trim((string) $context));

        $this->assertContextAllowed($role, $context);

        /*
         * INITIALISATION DU NAVIGATEUR : lorsqu'une page appelle l'endpoint pour
         * la premiere fois, aucun curseur n'est encore memorise. On lui remet le
         * dernier numero technique existant sans declarer de changement. Ainsi,
         * une nouvelle ouverture ne rejoue pas tout l'historique et n'occasionne
         * pas un rafraichissement inutile du conteneur visible.
         */
        if ($cursor === null || $cursor === '') {
            $currentCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

            return [
                'cursor' => $currentCursor,
                'changed' => false,
                'requestIds' => [],
                'alerts' => [],
                'refreshMode' => 'none',
                'hasMore' => false,
                'pollAfterMs' => 10000,
            ];
        }

        $cursor = $this->normalizeCursor($cursor);

        /*
         * LECTURE INCREMENTALE : seules les colonnes necessaires sont chargees.
         * Le tri ascendant garantit que le dernier id du lot devient un curseur
         * fiable, meme si plusieurs utilisateurs modifient des demandes ensemble.
         */
        $events = RequestChangeEvent::find()
            /*
             * AUTEUR DE L'EVENEMENT : actor_type et actor_id servent uniquement à
             * ne pas avertir l'utilisateur de sa propre action. Ces deux valeurs
             * sont comparées côté serveur et ne sont jamais renvoyées au navigateur.
             */
            ->select(['id', 'request_id', 'event_type', 'actor_type', 'actor_id', 'payload_json'])
            ->where(['>', 'id', $cursor])
            ->orderBy(['id' => SORT_ASC])
            ->limit(250)
            ->asArray()
            ->all();

        if ($events === []) {
            return [
                'cursor' => $cursor,
                'changed' => false,
                'requestIds' => [],
                'alerts' => [],
                'refreshMode' => 'none',
                'hasMore' => false,
                'pollAfterMs' => 10000,
            ];
        }

        /*
         * CURSEUR DU LOT : end() manipule le tableau par reference. Conserver son
         * resultat dans une variable rend le code compatible avec les versions PHP
         * supportees par l'application et evite une dereference ambigue.
         */
        $lastEvent = end($events);
        $lastCursor = (int) $lastEvent['id'];
        $requestIds = array_values(array_unique(array_map('intval', array_column($events, 'request_id'))));
        $visibleIds = $this->resolveVisibleRequestIds($context, $role, $requestIds);

        /*
         * SUPPRESSION DE DEMANDE : apres DELETE, la ligne requests n'existe plus
         * pour verifier son proprietaire. Aucun identifiant n'est alors expose ;
         * on demande seulement un nouveau rendu du conteneur. Cette strategie
         * retire la ligne pour les utilisateurs concernes sans divulguer son objet.
         */
        $requiresContainerRefresh = false;
        foreach ($events as $event) {
            if ($event['event_type'] === 'request.deleted') {
                $requiresContainerRefresh = true;
                break;
            }

            /*
             * SUPPRESSION D'UNE APPLICATION MRO : la ligne source n'existe plus
             * pour la requête de visibilité habituelle. Le périmètre enregistré
             * avant suppression permet de rafraîchir uniquement le MRO propriétaire,
             * sans retourner cet identifiant ni celui de la candidature au client.
             */
            if ($context === 'mro-applications' && $event['event_type'] === 'application.deleted') {
                $payload = json_decode((string) ($event['payload_json'] ?? ''), true);
                $eventMroId = is_array($payload) ? (int) ($payload['scope_mro_id'] ?? 0) : 0;

                if ($eventMroId === $this->requireSessionId('mro_id')) {
                    $requiresContainerRefresh = true;
                    break;
                }
            }

            /*
             * SUPPRESSION D'UN RENDEZ-VOUS : apres suppression, la requete de
             * visibilite ne peut plus retrouver la ligne Appointment. Les identifiants
             * de perimetre captures avant DELETE permettent de rafraichir uniquement
             * la liste du AO ou du MRO proprietaire, sans exposer ces identifiants.
             */
            if (
                in_array($context, ['ao-appointments', 'mro-appointments'], true)
                && $event['event_type'] === 'appointment.deleted'
            ) {
                $payload = json_decode((string) ($event['payload_json'] ?? ''), true);
                $scopeKey = $context === 'ao-appointments' ? 'scope_ao_id' : 'scope_mro_id';
                $sessionKey = $context === 'ao-appointments' ? 'ao_id' : 'mro_id';
                $eventOwnerId = is_array($payload) ? (int) ($payload[$scopeKey] ?? 0) : 0;

                if ($eventOwnerId === $this->requireSessionId($sessionKey)) {
                    $requiresContainerRefresh = true;
                    break;
                }
            }
        }

        /*
         * CONFIDENTIALITE DE LA LISTE MRO : une demande peut devenir non affichable
         * apres le changement qui doit justement retirer sa ligne. Dans ce contexte,
         * on ordonne seulement le nouveau rendu et aucun identifiant n'est transmis.
         * Les autres contextes conservent leurs identifiants publics signes.
         */
        $encodedIds = [];
        if ($context !== 'mro-requests') {
            foreach ($visibleIds as $requestId) {
                $encodedIds[] = UrlIdHelper::encode((int) $requestId);
            }
        }

        /*
         * LOT SUIVANT : cette verification ne renvoie pas le curseur global et
         * n'expose donc aucune information metier. Elle dit seulement au client
         * qu'un autre lot technique est deja disponible.
         */
        $hasMore = RequestChangeEvent::find()->where(['>', 'id', $lastCursor])->exists();
        $changed = $requiresContainerRefresh || $visibleIds !== [];

        /*
         * ALERTES DE STATUT : les messages sont construits après le contrôle de
         * visibilité. Le navigateur reçoit donc uniquement un texte prédéfini, un
         * identifiant de route signé et aucune information d'auteur interne.
         */
        $alerts = $this->buildStatusAlerts($events, $visibleIds, $role, $context);

        return [
            'cursor' => $lastCursor,
            'changed' => $changed,
            'requestIds' => $encodedIds,
            'alerts' => $alerts,
            'refreshMode' => $changed ? 'container' : 'none',
            'hasMore' => $hasMore,
            'pollAfterMs' => $changed ? 5000 : 10000,
        ];
    }

    /**
     * Prépare au maximum une alerte par demande et conserve seulement son dernier
     * changement de statut dans le lot. Les libellés proviennent d'une liste blanche
     * afin qu'une valeur inattendue en base ne devienne jamais un message libre.
     *
     * Le contexte mro-requests est volontairement exclu : sa requête de détection
     * reconnaît aussi une demande qui vient de quitter la liste. Retourner son statut
     * ou son URL à ce moment contournerait la confidentialité prévue pour ce contexte.
     *
     * @param array $events Evénements techniques déjà lus dans l'ordre du curseur.
     * @param array $visibleIds Demandes dont la visibilité a été validée côté serveur.
     * @param string $role Rôle issu exclusivement de la session Yii2.
     * @param string $context Liste actuellement ouverte par l'utilisateur.
     * @return array Messages sûrs destinés au composant SweetAlert.
     */
    private function buildStatusAlerts(array $events, array $visibleIds, string $role, string $context): array
    {
        if ($visibleIds === [] || $context === 'mro-requests') {
            return [];
        }

        $visibleMap = array_fill_keys(array_map('intval', $visibleIds), true);
        $currentActorId = $role === 'ao'
            ? $this->requireSessionId('ao_id')
            : ($role === 'mro' ? $this->requireSessionId('mro_id') : 0);

        /*
         * VOCABULAIRE MÉTIER AUTORISÉ : ces intitulés harmonisent le statut technique
         * avec un texte lisible sans modifier les valeurs enregistrées dans requests.
         */
        $statusLabels = [
            'created' => 'Created',
            'answered' => 'Answered',
            'po_loaded' => 'Purchase Order Loaded',
            'update_request' => 'Update Requested',
            'work_accepted' => 'Work Accepted',
            'work_started' => 'Work Started',
            'report_submitted' => 'Report Submitted',
            'closed' => 'Closed',
        ];
        $importantStatuses = ['po_loaded', 'update_request', 'work_accepted', 'report_submitted', 'closed'];
        $latestByRequest = [];

        foreach ($events as $event) {
            $requestId = (int) ($event['request_id'] ?? 0);
            if (($event['event_type'] ?? '') !== 'request.status_changed' || !isset($visibleMap[$requestId])) {
                continue;
            }

            /*
             * ACTION LOCALE : une transition créée par le même profil connecté est
             * déjà confirmée par son formulaire. Elle rafraîchit la liste mais ne
             * déclenche pas une seconde alerte redondante dans ses autres onglets.
             */
            if (
                ($event['actor_type'] ?? null) === $role
                && (int) ($event['actor_id'] ?? 0) === $currentActorId
            ) {
                continue;
            }

            $payload = json_decode((string) ($event['payload_json'] ?? ''), true);
            $statusAfter = is_array($payload) ? strtolower((string) ($payload['status_after'] ?? '')) : '';
            $statusBefore = is_array($payload) ? strtolower((string) ($payload['status_before'] ?? '')) : '';

            if (!isset($statusLabels[$statusAfter])) {
                continue;
            }

            $afterLabel = $statusLabels[$statusAfter];
            $beforeLabel = $statusLabels[$statusBefore] ?? null;
            $message = $beforeLabel === null
                ? 'The request status is now ' . $afterLabel . '.'
                : 'The request status changed from ' . $beforeLabel . ' to ' . $afterLabel . '.';

            $latestByRequest[$requestId] = [
                'eventId' => (int) $event['id'],
                'title' => 'Request #' . $requestId . ': ' . $afterLabel,
                'message' => $message,
                'icon' => $statusAfter === 'update_request'
                    ? 'warning'
                    : (in_array($statusAfter, ['work_accepted', 'closed'], true) ? 'success' : 'info'),
                'presentation' => in_array($statusAfter, $importantStatuses, true) ? 'modal' : 'toast',
                'actionUrl' => Yii::$app->urlManager->createUrl([
                    '/requests/view',
                    'id' => UrlIdHelper::encode($requestId),
                ]),
            ];
        }

        /*
         * VOLUME BORNÉ : dix demandes suffisent pour informer sans gonfler la réponse.
         * Le client regroupera plusieurs éléments dans une seule alerte récapitulative.
         */
        return array_slice(array_values($latestByRequest), -10);
    }

    /**
     * Refuse les valeurs negatives, decimales ou non numeriques afin d'eviter des
     * scans complets provoques par un parametre de curseur volontairement invalide.
     */
    private function normalizeCursor($cursor): int
    {
        if (!is_scalar($cursor) || !preg_match('/^\d+$/', (string) $cursor)) {
            throw new BadRequestHttpException('Invalid synchronization cursor.');
        }

        return (int) $cursor;
    }

    /**
     * Controle la combinaison role/contexte avant toute interrogation metier.
     */
    private function assertContextAllowed(string $role, string $context): void
    {
        if (!isset(self::CONTEXTS_BY_ROLE[$role]) || !in_array($context, self::CONTEXTS_BY_ROLE[$role], true)) {
            throw new ForbiddenHttpException('You are not authorized to synchronize this list.');
        }
    }

    /**
     * Calcule l'intersection entre les demandes modifiees et les demandes visibles
     * dans le contexte courant. Les conditions reprennent les proprietaires et les
     * relations deja utilises par les listes ; elles ne font confiance ni au DOM,
     * ni a une cle AO/MRO envoyee dans la requete AJAX.
     */
    private function resolveVisibleRequestIds(string $context, string $role, array $requestIds): array
    {
        if ($requestIds === []) {
            return [];
        }

        switch ($context) {
            case 'ao-requests':
                return Requests::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds])
                    ->andWhere(['ao_id' => $this->requireSessionId('ao_id')])
                    ->column();

            case 'mro-requests':
                return $this->buildPotentialMroRequestsQuery($this->requireSessionId('mro_id'))
                    ->select('requests.request_id')
                    ->andWhere(['requests.request_id' => $requestIds])
                    ->column();

            case 'mro-applications':
                return MroRequestApply::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds, 'mro_id' => $this->requireSessionId('mro_id')])
                    ->distinct()
                    ->column();

            case 'ao-appointments':
                return Appointment::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds, 'ao_id' => $this->requireSessionId('ao_id')])
                    ->distinct()
                    ->column();

            case 'mro-appointments':
                return Appointment::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds, 'mro_id' => $this->requireSessionId('mro_id')])
                    ->distinct()
                    ->column();

            case 'conversations':
                $column = $role === 'ao' ? 'ao_id' : 'mro_id';
                return Chat::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds, $column => $this->requireSessionId($column)])
                    ->distinct()
                    ->column();

            case 'disputes':
                $column = $role === 'ao' ? 'ao_id' : 'mro_id';
                return Dispute::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds, $column => $this->requireSessionId($column)])
                    ->distinct()
                    ->column();

            case 'admin-disputes':
                return Dispute::find()
                    ->select('request_id')
                    ->where(['request_id' => $requestIds])
                    ->distinct()
                    ->column();

            case 'awaiting-responses':
                return RepairReport::find()
                    ->alias('rr')
                    ->select('mra.request_id')
                    ->innerJoin(['mra' => MroRequestApply::tableName()], 'mra.id = rr.mro_request_apply_id')
                    ->where(['mra.request_id' => $requestIds])
                    ->distinct()
                    ->column();
        }

        return [];
    }

    /**
     * Calcule les demandes susceptibles d'appartenir au perimetre technique du MRO
     * a partir de ses certificats, aeroports et modeles approuves. Le statut et le
     * PO sont volontairement exclus : leur changement peut retirer une ligne, et
     * doit donc encore provoquer le rendu du conteneur apres cette transition.
     */
    private function buildPotentialMroRequestsQuery(int $mroId)
    {
        return Requests::find()
            ->where([
                'IN',
                'requests.required_certificates',
                Certificates::find()->select('type')->where(['mro_id' => $mroId]),
            ])
            ->andWhere([
                'requests.destination' => MroprofileAirport::find()
                    ->select('airport_id')
                    ->where(['mro_id' => $mroId]),
            ])
            ->joinWith('aircraft')
            ->andWhere([
                'EXISTS',
                MroAircraftCertificate::find()
                    ->where('mro_aircraft_certificate.aircraft_model_id = aircrafts.aircraft_model_id')
                    ->andWhere(['mro_aircraft_certificate.mro_id' => $mroId])
                    ->andWhere(['aircrafts.aircraft_id' => new Expression('requests.aircraft_id')]),
            ]);
    }

    /**
     * Lit une cle de profil uniquement dans la session authentifiee. Une session
     * incoherente est refusee immediatement plutot que de produire une requete SQL
     * non filtree susceptible de retourner les demandes d'un autre utilisateur.
     */
    private function requireSessionId(string $key): int
    {
        $value = filter_var(
            Yii::$app->session->get($key),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($value === false) {
            throw new ForbiddenHttpException('Your authenticated profile is incomplete.');
        }

        return (int) $value;
    }
}
