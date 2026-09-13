<?php

namespace tests\unit\controllers;

use app\controllers\RequestSyncController;
use app\models\User;
use Codeception\Test\Unit;
use Yii;

class RequestSyncAuthenticationTest extends Unit
{
    private $previousIdentity;
    private $previousRole;
    private $previousMroId;

    protected function _before(): void
    {
        $this->previousIdentity = Yii::$app->user->identity;
        $this->previousRole = Yii::$app->session->get('user_type');
        $this->previousMroId = Yii::$app->session->get('mro_id');
    }

    protected function _after(): void
    {
        Yii::$app->user->setIdentity($this->previousIdentity);
        Yii::$app->session->set('user_type', $this->previousRole);
        Yii::$app->session->set('mro_id', $this->previousMroId);
    }

    public function testRememberedIdentityRestoresMissingSynchronizationSession(): void
    {
        Yii::$app->user->setIdentity(new User([
            'mro_id' => 104,
            'username' => 'remembered-mro',
        ]));
        Yii::$app->session->remove('user_type');
        Yii::$app->session->remove('mro_id');

        $method = new \ReflectionMethod(RequestSyncController::class, 'resolveAuthenticatedRole');
        $method->setAccessible(true);

        $this->assertSame('mro', $method->invoke(null));
        $this->assertSame('mro', Yii::$app->session->get('user_type'));
        $this->assertSame(104, Yii::$app->session->get('mro_id'));
    }
}
