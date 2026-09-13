<?php

namespace tests\unit\models;

use app\models\LoginForm;
use app\models\User;
use Codeception\Test\Unit;
use Yii;

class LoginFormTest extends Unit
{
    protected function _after(): void
    {
        Yii::$app->user->logout();
    }

    public function testLoginFailsWhenUserDoesNotExist(): void
    {
        $model = $this->createLoginForm(null, 'mot-de-passe-test');

        $this->assertFalse($model->login());
        $this->assertTrue(Yii::$app->user->isGuest);
        $this->assertArrayHasKey('password', $model->errors);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $user = $this->createUser('mot-de-passe-valide');
        $model = $this->createLoginForm($user, 'mot-de-passe-incorrect');

        $this->assertFalse($model->login());
        $this->assertTrue(Yii::$app->user->isGuest);
        $this->assertArrayHasKey('password', $model->errors);
    }

    public function testLoginSucceedsWithCorrectPassword(): void
    {
        $user = $this->createUser('mot-de-passe-valide');
        $model = $this->createLoginForm($user, 'mot-de-passe-valide');

        $this->assertTrue($model->login());
        $this->assertFalse(Yii::$app->user->isGuest);
        $this->assertSame('admin:42', Yii::$app->user->id);
        $this->assertArrayNotHasKey('password', $model->errors);
    }

    private function createUser(string $plainPassword): User
    {
        return new User([
            'admin_id' => 42,
            'username' => 'utilisateur-test',
            'password' => Yii::$app->security->generatePasswordHash($plainPassword),
        ]);
    }

    private function createLoginForm(?User $user, string $password): LoginForm
    {
        /*
         * La recherche SQL est remplacée uniquement dans le test afin de ne
         * jamais dépendre de la base locale, de développement ou de production.
         */
        return new class($user, [
            'username' => 'utilisateur-test',
            'password' => $password,
            'rememberMe' => false,
        ]) extends LoginForm {
            private $testUser;

            public function __construct(?User $testUser, array $config = [])
            {
                $this->testUser = $testUser;
                parent::__construct($config);
            }

            protected function getUser()
            {
                return $this->testUser;
            }
        };
    }
}
