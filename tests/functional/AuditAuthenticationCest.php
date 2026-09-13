<?php

use app\models\AdminProfile;
use app\models\AuditLog;

class AuditAuthenticationCest
{
    public function authenticationEventsAreAuditedWithoutPassword(FunctionalTester $I): void
    {
        try {
            $auditTable = \Yii::$app->db->schema->getTableSchema('audit_log', true);
            $adminTable = \Yii::$app->db->schema->getTableSchema('admin_profiles', true);
        } catch (\Throwable $exception) {
            $I->markTestSkipped('Create and migrate the configured yii2basic_test database first.');
            return;
        }
        if ($auditTable === null || $adminTable === null) {
            $I->markTestSkipped('Apply all migrations to the test database first.');
            return;
        }

        $username = 'audit-admin-' . bin2hex(random_bytes(4));
        $password = 'Audit-Test-Password-2026';
        $admin = new AdminProfile([
            'username' => $username,
            'password' => \Yii::$app->security->generatePasswordHash($password),
            'email' => $username . '@example.test',
        ]);
        $I->assertTrue($admin->save(false));

        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => $username,
            'LoginForm[password]' => 'wrong-password-never-store',
        ]);

        $failed = AuditLog::find()
            ->where(['action' => 'LOGIN_FAILED', 'username' => $username])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $I->assertNotNull($failed);
        $I->assertStringNotContainsString('wrong-password-never-store', (string) $failed->new_values);

        $I->submitForm('#login-form', [
            'LoginForm[username]' => $username,
            'LoginForm[password]' => $password,
        ]);
        $I->assertNotNull(AuditLog::find()
            ->where(['action' => 'LOGIN', 'username' => $username])
            ->orderBy(['id' => SORT_DESC])
            ->one());

        /* Le vrai formulaire garantit que le controleur photographie l'auteur avant logout. */
        $I->submitForm('form.logout-form', []);
        $logout = AuditLog::find()
            ->where(['action' => 'LOGOUT', 'username' => $username])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $I->assertNotNull($logout);
        $I->assertSame('admin', $logout->user_role);
    }
}
