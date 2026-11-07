<?php

namespace app\controllers;

use app\components\CsvCellSanitizer;
use app\components\UrlIdHelper;
use app\models\PrelaunchSubscriber;
use app\models\PrelaunchSubscriberSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Private administration of early-access registrations.
 */
class AdminPrelaunchSubscribersController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'view' => ['GET'],
                    'export' => ['GET'],
                    'resend' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => static function () {
                        return Yii::$app->session->get('user_type') === 'admin';
                    },
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new PrelaunchSubscriberSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'stats' => $this->loadStats(),
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'subscriber' => $this->findSubscriber($id),
        ]);
    }

    /**
     * Exports the current filtered result while neutralizing spreadsheet formulas.
     */
    public function actionExport()
    {
        $searchModel = new PrelaunchSubscriberSearch();
        $query = $searchModel
            ->buildQuery(Yii::$app->request->queryParams)
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(10000);

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'ID', 'First Name', 'Last Name', 'Company', 'Email', 'Website',
            'Company Type', 'Subscription Status', 'Confirmation Email Status',
            'Created At', 'Confirmed At', 'Confirmation Sent At',
            'Email Attempts', 'Last Delivery Error',
        ]);

        foreach ($query->each(500) as $subscriber) {
            fputcsv($stream, array_map([CsvCellSanitizer::class, 'sanitize'], [
                (string) $subscriber->id,
                $subscriber->first_name,
                $subscriber->last_name,
                $subscriber->company_name,
                $subscriber->business_email,
                $subscriber->company_website,
                $subscriber->company_type,
                $subscriber->subscription_status,
                $subscriber->confirmation_email_status,
                $subscriber->created_at,
                $subscriber->confirmed_at,
                $subscriber->confirmation_sent_at,
                (string) $subscriber->confirmation_attempt_count,
                $subscriber->confirmation_last_error,
            ]));
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return Yii::$app->response->sendContentAsFile(
            $content,
            'can-prelaunch-subscribers-' . gmdate('Ymd-His') . '.csv',
            ['mimeType' => 'text/csv; charset=UTF-8', 'inline' => false]
        );
    }

    public function actionResend($id)
    {
        $subscriber = $this->findSubscriber($id);
        $returnUrl = ['/admin-prelaunch-subscribers/index'];
        $filters = Yii::$app->request->post('filters', []);
        if (is_array($filters)) {
            foreach (['search', 'company_type', 'subscription_status', 'confirmation_email_status', 'date_from', 'date_to', 'page_size', 'page', 'sort'] as $key) {
                if (isset($filters[$key]) && is_scalar($filters[$key])) { $returnUrl[$key] = $filters[$key]; }
            }
        }
        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() - 60);
        $confirmationToken = Yii::$app->security->generateRandomString(48);
        $unsubscribeToken = Yii::$app->security->generateRandomString(48);

        // Claim the attempt atomically so simultaneous clicks cannot send twice.
        $claimed = PrelaunchSubscriber::updateAll([
            'confirmation_token_hash' => hash('sha256', $confirmationToken),
            'confirmation_token_expires_at' => date('Y-m-d H:i:s', time() + 86400),
            'unsubscribe_token_hash' => hash('sha256', $unsubscribeToken),
            'confirmation_attempted_at' => $now,
            'confirmation_attempt_count' => new \yii\db\Expression('confirmation_attempt_count + 1'),
            'confirmation_email_status' => PrelaunchSubscriber::EMAIL_PENDING,
            'confirmation_last_error' => null,
        ], ['and',
            ['id' => $subscriber->id, 'subscription_status' => PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION],
            ['or', ['confirmation_attempted_at' => null], ['<=', 'confirmation_attempted_at', $cutoff]],
            ['or', ['confirmation_sent_at' => null], ['<=', 'confirmation_sent_at', $cutoff]],
        ]);
        if (!$claimed) {
            Yii::$app->session->setFlash('warning', 'Only pending registrations can be resent. Please allow 60 seconds between attempts.');
            return $this->redirect($returnUrl);
        }
        $subscriber->refresh();
        $delivery = \app\components\PrelaunchConfirmationMailer::send($subscriber, $confirmationToken, $unsubscribeToken);
        $attributes = [
            'confirmation_email_status' => $delivery['sent'] ? PrelaunchSubscriber::EMAIL_SENT : PrelaunchSubscriber::EMAIL_FAILED,
            'confirmation_last_error' => $delivery['error'],
        ];
        if ($delivery['sent']) { $attributes['confirmation_sent_at'] = date('Y-m-d H:i:s'); }
        // Do not overwrite diagnostics if a newer public signup replaced this token.
        PrelaunchSubscriber::updateAll($attributes, [
            'id' => $subscriber->id,
            'confirmation_token_hash' => hash('sha256', $confirmationToken),
        ]);
        Yii::$app->session->setFlash(
            $delivery['sent'] ? 'success' : 'error',
            $delivery['sent'] ? 'Confirmation email resent.' : 'Confirmation email could not be sent. Please check the delivery details before trying again.'
        );
        return $this->redirect($returnUrl);
    }

    private function loadStats()
    {
        return [
            'total' => (int) PrelaunchSubscriber::find()->count(),
            'mro' => (int) PrelaunchSubscriber::find()->where([
                'company_type' => PrelaunchSubscriber::TYPE_MRO,
            ])->count(),
            'aircraft_operator' => (int) PrelaunchSubscriber::find()->where([
                'company_type' => PrelaunchSubscriber::TYPE_AIRCRAFT_OPERATOR,
            ])->count(),
            'confirmed' => (int) PrelaunchSubscriber::find()->where([
                'subscription_status' => PrelaunchSubscriber::STATUS_CONFIRMED,
            ])->count(),
            'pending' => (int) PrelaunchSubscriber::find()->where([
                'subscription_status' => PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION,
            ])->count(),
            'delivery_failed' => (int) PrelaunchSubscriber::find()->where([
                'confirmation_email_status' => PrelaunchSubscriber::EMAIL_FAILED,
            ])->count(),
        ];
    }

    private function findSubscriber($encodedId)
    {
        $id = UrlIdHelper::decodeOrFail($encodedId, 'Invalid early access subscriber link.');
        $subscriber = PrelaunchSubscriber::findOne($id);
        if ($subscriber === null) {
            throw new NotFoundHttpException('Early access subscriber not found.');
        }
        return $subscriber;
    }

}
