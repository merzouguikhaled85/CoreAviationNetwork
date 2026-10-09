<?php

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\Application;

/** Invalidates an authenticated session when its account auth version changes. */
class SessionSecurityBootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, static function () use ($app) {
            $identity = $app->user->identity;
            if ($identity === null) {
                return;
            }

            $currentVersion = (int) ($identity->auth_version ?? 1);
            $sessionVersion = $app->session->get('auth_version');

            // A session restored by a valid identity cookie adopts its current version.
            if ($sessionVersion === null) {
                $app->session->set('auth_version', $currentVersion);
                return;
            }

            if ((int) $sessionVersion !== $currentVersion) {
                $app->user->logout(true);
                $app->session->setFlash('error', 'Your session has been revoked. Please sign in again.');
                $app->response->redirect(['/site/login']);
                $app->end();
            }
        });
    }
}
