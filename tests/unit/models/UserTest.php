<?php

namespace tests\unit\models;

use app\models\User;
use Codeception\Test\Unit;
use Yii;

class UserTest extends Unit
{
    public function testIdentityIdIncludesTheUserRole(): void
    {
        $this->assertSame('admin:12', (new User(['admin_id' => 12]))->getId());
        $this->assertSame('mro:12', (new User(['mro_id' => 12]))->getId());
        $this->assertSame('ao:12', (new User(['ao_id' => 12]))->getId());
    }

    public function testIdentityWithoutRoleHasNoId(): void
    {
        $this->assertNull((new User())->getId());
    }

    public function testUnsupportedAccessTokenIsRejected(): void
    {
        $this->assertNull(User::findIdentityByAccessToken('token-inconnu'));
    }

    public function testMalformedIdentityIsRejectedWithoutDatabaseLookup(): void
    {
        $this->assertNull(User::findIdentity('12'));
        $this->assertNull(User::findIdentity('invalid:12'));
        $this->assertNull(User::findIdentity('admin:0'));
    }

    public function testAuthenticationKeyDependsOnRoleIdAndPasswordHash(): void
    {
        $passwordHash = Yii::$app->security->generatePasswordHash('mot-de-passe-test');
        $admin = new User(['admin_id' => 7, 'password' => $passwordHash]);
        $mro = new User(['mro_id' => 7, 'password' => $passwordHash]);

        $this->assertNotNull($admin->getAuthKey());
        $this->assertTrue($admin->validateAuthKey($admin->getAuthKey()));
        $this->assertFalse($admin->validateAuthKey($mro->getAuthKey()));
        $this->assertFalse($admin->validateAuthKey('cle-invalide'));
    }

    public function testPasswordValidationUsesTheStoredHash(): void
    {
        $user = new User([
            'admin_id' => 7,
            'password' => Yii::$app->security->generatePasswordHash('mot-de-passe-test'),
        ]);

        $this->assertTrue($user->validatePassword('mot-de-passe-test'));
        $this->assertFalse($user->validatePassword('mot-de-passe-incorrect'));
    }
}
