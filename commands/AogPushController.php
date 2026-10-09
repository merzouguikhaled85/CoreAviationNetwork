<?php

namespace app\commands;

use app\components\AogPushService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

class AogPushController extends Controller
{
    // GoDaddy lance cette commande chaque minute ; aucun processus permanent requis.
    public function actionSend($limit = 20)
    {
        if (!Yii::$app->aogPush->isAvailable() || !Yii::$app->firebaseSender->isConfigured()) {
            $this->stderr("AOG push unavailable: check activation, migration and private service account path.\n");
            return ExitCode::CONFIG;
        }
        $lock = fopen(Yii::getAlias('@runtime/aog-push.lock'), 'c');
        if (!$lock) {
            $this->stderr("Cannot open the AOG push lock.\n");
            return ExitCode::CANTCREAT;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return ExitCode::OK;
        }
        $counts = ['sent' => 0, 'pending' => 0, 'failed' => 0, 'discarded' => 0];
        try {
            $db = Yii::$app->db;
            $now = time();
            $db->createCommand()->update(AogPushService::JOBS, ['status' => 'pending'], [
                'and', ['status' => 'processing'], ['<', 'started_at', $now - 300],
            ])->execute();
            $jobs = (new Query())->from(AogPushService::JOBS)->where(['status' => 'pending'])
                ->andWhere(['<=', 'available_at', $now])->orderBy('id')
                ->limit(max(1, min(200, (int) $limit)))->all();
            foreach ($jobs as $job) {
                $claimed = $db->createCommand()->update(AogPushService::JOBS, [
                    'status' => 'processing', 'started_at' => time(), 'attempts' => (int) $job['attempts'] + 1,
                ], ['id' => $job['id'], 'status' => 'pending'])->execute();
                if (!$claimed) {
                    continue;
                }
                $job['attempts'] = (int) $job['attempts'] + 1;
                try {
                    $result = Yii::$app->aogPush->deliver($job);
                } catch (\Throwable $error) {
                    $result = ['status' => $job['attempts'] < 5 ? 'pending' : 'failed',
                        'error' => 'DELIVERY_FAILED', 'delay' => 120];
                }
                $db->createCommand()->update(AogPushService::JOBS, [
                    'status' => $result['status'], 'last_error' => $result['error'],
                    'available_at' => time() + (int) ($result['delay'] ?? 0),
                ], ['id' => $job['id'], 'status' => 'processing'])->execute();
                $counts[$result['status']]++;
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        $this->stdout('AOG push: ' . json_encode($counts) . "\n");
        return ExitCode::OK;
    }
}
