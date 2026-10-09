<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$identity = Yii::$app->user->identity;
if (!$identity || !preg_match('/^mro:[1-9][0-9]*$/D', (string) $identity->getId())
    || !Yii::$app->aogPush->isAvailable()) {
    return;
}
$settings = [
    'config' => Yii::$app->params['firebase']['web'],
    'vapidKey' => Yii::$app->params['firebase']['vapidKey'],
    'account' => (string) $identity->getId(),
    'registerUrl' => Url::to(['/push/register']),
    'unregisterUrl' => Url::to(['/push/unregister']),
    'workerUrl' => Url::to(['/push/worker']),
    'scope' => Yii::$app->request->baseUrl . '/',
    'requestsUrl' => Url::to(['/mro-requests/index']),
];
$this->registerJsFile('https://www.gstatic.com/firebasejs/13.0.0/firebase-app-compat.js', [], 'firebase-app');
$this->registerJsFile('https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging-compat.js', [], 'firebase-messaging');
$this->registerJsFile(Url::to('@web/js/aog-push.js'), [], 'aog-push');
?>
<div class="px-2" id="aog-push-settings" data-settings="<?= Html::encode(Json::encode($settings)) ?>">
    <button type="button" class="btn btn-link" id="aog-push-toggle" disabled>Enable AOG notifications</button>
    <small class="d-block text-muted" id="aog-push-status" role="status" aria-live="polite"></small>
</div>
