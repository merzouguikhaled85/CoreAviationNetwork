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
    'logoUrl' => Url::to('@web/logo/can-logo-main.png'),
];
$this->registerCssFile(Url::to('@web/css/aog-push.css'), [], 'aog-push');
$this->registerJsFile('https://www.gstatic.com/firebasejs/13.0.0/firebase-app-compat.js', [], 'firebase-app');
$this->registerJsFile('https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging-compat.js', [], 'firebase-messaging');
$this->registerJsFile(Url::to('@web/js/aog-push.js'), [], 'aog-push');
?>
<div class="can-push-preferences" id="aog-push-settings" data-state="loading" data-settings="<?= Html::encode(Json::encode($settings)) ?>">
    <div class="can-push-preferences__heading">
        <span class="can-push-preferences__icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
        <span class="can-push-preferences__title">AOG alerts</span>
        <span class="can-push-preferences__badge" id="aog-push-state">Loading</span>
    </div>
    <p class="can-push-preferences__status" id="aog-push-status" role="status" aria-live="polite">Checking this device…</p>
    <button type="button" class="can-push-preferences__toggle" id="aog-push-toggle"
            aria-label="Enable AOG notifications" aria-describedby="aog-push-status" aria-pressed="false" disabled>Enable alerts</button>
</div>
