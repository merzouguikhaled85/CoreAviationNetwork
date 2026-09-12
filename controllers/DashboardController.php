<?php 
namespace app\controllers;

use app\models\Requests;
use app\models\SupportTicket;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\db\Query;

class DashboardController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['home'],
                'rules' => [
                    [
                        'actions' => ['home'],
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                    ],
                ],
            ],
        ];
    }

    public function actionHome()
    {
        $adminDashboard = null;

        /*
         * TABLEAU DE BORD ADMINISTRATEUR : les autres profils continuent de
         * recevoir exactement leur écran actuel. Les indicateurs ne sont
         * calculés que pour l'administrateur afin de ne pas ajouter de requêtes
         * inutiles aux parcours AO et MRO et de ne modifier aucune règle métier.
         */
        if (strtolower((string) Yii::$app->session->get('user_type')) === 'admin') {
            $adminDashboard = $this->buildAdminDashboard();
        }

        return $this->render('home', [
            'adminDashboard' => $adminDashboard,
        ]);
    }

    /**
     * Construit une photographie en lecture seule de l'activité de la plateforme.
     * Le cache court évite de répéter les agrégations à chaque rafraîchissement,
     * tout en gardant des chiffres suffisamment récents pour le pilotage quotidien.
     */
    private function buildAdminDashboard()
    {
        return Yii::$app->cache->getOrSet('dashboard.admin.operational.v1', function () {
            $db = Yii::$app->db;
            $activeRequestStatuses = [Requests::STATUS_CLOSED, Requests::STATUS_CANCELLED];
            $openSupportStatuses = ['new', 'in_progress', 'waiting_user'];

            /*
             * INDICATEURS PRINCIPAUX : chaque compteur correspond à une action
             * administrative concrète. Les demandes closes ou annulées sont
             * exclues des charges actives, mais restent naturellement conservées.
             */
            $activeRequests = (int) (new Query())
                ->from(Requests::tableName())
                ->where(['not in', 'status', $activeRequestStatuses])
                ->count('*', $db);

            $activeAog = (int) (new Query())
                ->from(Requests::tableName())
                ->where(['operational_priority' => Requests::PRIORITY_AOG])
                ->andWhere(['not in', 'status', $activeRequestStatuses])
                ->count('*', $db);

            $openDisputes = (int) (new Query())
                ->from('disputes')
                ->where(['status' => 'open'])
                ->count('*', $db);

            $supportWorkload = (int) (new Query())
                ->from(SupportTicket::tableName())
                ->where(['status' => $openSupportStatuses])
                ->count('*', $db);

            $unverifiedAo = (int) (new Query())
                ->from('ao_profiles')
                ->where(['email_verified' => 0])
                ->count('*', $db);

            $unverifiedMro = (int) (new Query())
                ->from('mro_profiles')
                ->where(['email_verified' => 0])
                ->count('*', $db);

            $mailFailures = (int) (new Query())
                ->from(SupportTicket::tableName())
                ->where(['or', ['email_status' => 'failed'], ['acknowledgement_status' => 'failed']])
                ->count('*', $db);

            /*
             * ACTIVITÉ RÉCENTE : les dates sont comparées côté base pour limiter
             * le volume transféré. Les organisations actives et vérifiées sont
             * additionnées séparément pour respecter les deux tables existantes.
             */
            $requests7Days = (int) (new Query())
                ->from(Requests::tableName())
                ->where(['>=', 'created_at', date('Y-m-d H:i:s', strtotime('-7 days'))])
                ->count('*', $db);

            $requests30Days = (int) (new Query())
                ->from(Requests::tableName())
                ->where(['>=', 'created_at', date('Y-m-d H:i:s', strtotime('-30 days'))])
                ->count('*', $db);

            $activeAo = (int) (new Query())->from('ao_profiles')->where(['status' => 'active'])->count('*', $db);
            $activeMro = (int) (new Query())->from('mro_profiles')->where(['status' => 'active'])->count('*', $db);
            $verifiedAo = (int) (new Query())->from('ao_profiles')->where(['status' => 'active', 'email_verified' => 1])->count('*', $db);
            $verifiedMro = (int) (new Query())->from('mro_profiles')->where(['status' => 'active', 'email_verified' => 1])->count('*', $db);

            /*
             * RÉPARTITION DES PRIORITÉS : COALESCE protège les anciennes lignes
             * créées avant l'introduction de la priorité opérationnelle en les
             * considérant comme Routine, conformément au modèle Requests.
             */
            $priorityRows = (new Query())
                ->select([
                    'priority' => "COALESCE(operational_priority, 'routine')",
                    'total' => 'COUNT(*)',
                ])
                ->from(Requests::tableName())
                ->where(['not in', 'status', $activeRequestStatuses])
                /*
                 * EXPRESSION SQL EXPLICITE : Yii ne doit pas interpréter la
                 * valeur littérale 'routine' comme un nom de colonne lors du
                 * GROUP BY, notamment avec MariaDB.
                 */
                ->groupBy(new \yii\db\Expression("COALESCE(operational_priority, 'routine')"))
                ->all($db);

            $priorityDistribution = [
                Requests::PRIORITY_AOG => 0,
                Requests::PRIORITY_URGENT => 0,
                Requests::PRIORITY_ROUTINE => 0,
            ];
            foreach ($priorityRows as $row) {
                $priority = strtolower((string) $row['priority']);
                if (array_key_exists($priority, $priorityDistribution)) {
                    $priorityDistribution[$priority] = (int) $row['total'];
                }
            }

            /*
             * FILES OPÉRATIONNELLES : elles sont volontairement courtes. L'AOG
             * est trié par échéance de réponse, le support par urgence puis âge,
             * et les litiges par ancienneté pour faire remonter les dossiers oubliés.
             */
            $aogQueue = (new Query())
                ->select(['request_id', 'status', 'response_due_at_utc', 'created_at'])
                ->from(Requests::tableName())
                ->where(['operational_priority' => Requests::PRIORITY_AOG])
                ->andWhere(['not in', 'status', $activeRequestStatuses])
                ->orderBy(new \yii\db\Expression(
                    'response_due_at_utc IS NULL ASC, response_due_at_utc ASC, created_at ASC'
                ))
                ->limit(5)
                ->all($db);

            $supportQueue = (new Query())
                ->select(['id', 'subject', 'priority', 'status', 'created_at'])
                ->from(SupportTicket::tableName())
                ->where(['status' => $openSupportStatuses])
                ->orderBy(new \yii\db\Expression(
                    "CASE WHEN priority = 'urgent' THEN 0 ELSE 1 END ASC, created_at ASC"
                ))
                ->limit(5)
                ->all($db);

            $disputeQueue = (new Query())
                ->select(['dispute_id', 'request_id', 'created_by', 'timestamp'])
                ->from('disputes')
                ->where(['status' => 'open'])
                ->orderBy(['timestamp' => SORT_ASC, 'dispute_id' => SORT_ASC])
                ->limit(5)
                ->all($db);

            return [
                'summary' => [
                    'activeAog' => $activeAog,
                    'activeRequests' => $activeRequests,
                    'openDisputes' => $openDisputes,
                    'supportWorkload' => $supportWorkload,
                    'unverifiedProfiles' => $unverifiedAo + $unverifiedMro,
                    'mailFailures' => $mailFailures,
                ],
                'activity' => [
                    'requests7Days' => $requests7Days,
                    'requests30Days' => $requests30Days,
                    'activeOrganizations' => $activeAo + $activeMro,
                    'verifiedOrganizations' => $verifiedAo + $verifiedMro,
                ],
                'priorityDistribution' => $priorityDistribution,
                'aogQueue' => $aogQueue,
                'supportQueue' => $supportQueue,
                'disputeQueue' => $disputeQueue,
                'generatedAt' => gmdate('Y-m-d H:i:s'),
            ];
        }, 45);
    }
}
