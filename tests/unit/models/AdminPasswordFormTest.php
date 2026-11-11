<?php

namespace tests\unit\models;

use app\controllers\AdminController;
use app\models\AdminPasswordForm;
use app\models\AdminProfile;
use app\models\User;
use PHPUnit\Framework\TestCase;
use Yii;

Yii::setAlias('@app', dirname(__DIR__, 3));

/** Uses no database and never changes an existing administrator account. */
class AdminPasswordFormTest extends TestCase
{
    private $previousApp;
    private $previousMethod;

    protected function setUp(): void
    {
        $this->previousApp = Yii::$app;
        $this->previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        new \yii\web\Application([
            'id' => 'admin-password-test',
            'basePath' => dirname(__DIR__, 3),
            'components' => [
                'security' => ['class' => \yii\base\Security::class, 'passwordHashCost' => 4],
                'request' => ['class' => \yii\web\Request::class, 'cookieValidationKey' => 'test-only', 'baseUrl' => '', 'scriptUrl' => '/index.php'],
                'response' => ['class' => \yii\web\Response::class],
                'urlManager' => ['class' => \yii\web\UrlManager::class, 'enablePrettyUrl' => true, 'showScriptName' => false],
                'user' => ['class' => \yii\web\User::class, 'identityClass' => User::class, 'enableSession' => false],
                'session' => ['class' => PasswordTestSession::class],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Yii::$app = $this->previousApp;
        if ($this->previousMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->previousMethod;
        }
    }

    private function admin(): PasswordTestAdmin
    {
        $admin = new PasswordTestAdmin();
        $admin->setAttributes([
            'admin_id' => 23,
            'password' => Yii::$app->security->generatePasswordHash('Current123!'),
            'auth_version' => 5,
        ], false);
        $admin->setOldAttributes($admin->getAttributes());
        return $admin;
    }

    private function form(PasswordTestAdmin $admin, array $attributes = []): AdminPasswordForm
    {
        $form = new AdminPasswordForm($admin);
        $form->setAttributes(array_merge([
            'currentPassword' => 'Current123!',
            'newPassword' => 'Replacement456!',
            'confirmPassword' => 'Replacement456!',
        ], $attributes));
        return $form;
    }

    public function testValidPasswordIsHashedAndRevokesExistingSessions(): void
    {
        $admin = $this->admin();
        $oldHash = $admin->password;
        self::assertTrue($this->form($admin)->updatePassword());
        self::assertNotSame($oldHash, $admin->password);
        self::assertTrue(Yii::$app->security->validatePassword('Replacement456!', $admin->password));
        self::assertFalse(Yii::$app->security->validatePassword('Current123!', $admin->password));
        self::assertSame(6, $admin->auth_version);
        self::assertSame(['password', 'auth_version'], $admin->savedColumns);
    }

    /** @dataProvider invalidPasswords */
    public function testInvalidInputNeverChangesStoredCredentials(array $attributes): void
    {
        $admin = $this->admin();
        $oldHash = $admin->password;
        $form = $this->form($admin, $attributes);
        self::assertFalse($form->updatePassword());
        self::assertTrue($form->hasErrors());
        self::assertSame($oldHash, $admin->password);
        self::assertSame(5, $admin->auth_version);
        self::assertSame(0, $admin->saveCalls);
    }

    public function invalidPasswords(): array
    {
        return [
            'wrong current password' => [['currentPassword' => 'Wrong123!']],
            'missing current password' => [['currentPassword' => '']],
            'confirmation mismatch' => [['confirmPassword' => 'Other123!']],
            'missing confirmation' => [['confirmPassword' => '']],
            'too short' => [['newPassword' => 'New1', 'confirmPassword' => 'New1']],
            'no number' => [['newPassword' => 'OnlyLetters', 'confirmPassword' => 'OnlyLetters']],
            'reused password' => [['newPassword' => 'Current123!', 'confirmPassword' => 'Current123!']],
            'array input' => [['currentPassword' => ['Current123!']]],
            'array new password' => [['newPassword' => ['Replacement456!']]],
            'bcrypt truncation' => [['newPassword' => str_repeat('é', 36) . 'A1', 'confirmPassword' => str_repeat('é', 36) . 'A1']],
        ];
    }

    public function testSaveFailureIsReported(): void
    {
        $admin = $this->admin();
        $admin->saveSucceeds = false;
        $form = $this->form($admin);
        self::assertFalse($form->updatePassword());
        self::assertTrue($form->hasErrors('newPassword'));
        self::assertSame(5, $admin->auth_version);
    }

    public function testGetRendersTheFormWithoutChangingThePassword(): void
    {
        $admin = $this->admin();
        $hash = $admin->password;
        $controller = new PasswordTestController('admin', Yii::$app);
        $controller->testAdmin = $admin;
        self::assertSame('password form', $controller->actionUpdatePassword());
        self::assertSame($hash, $admin->password);
        self::assertSame(0, $admin->saveCalls);
        self::assertInstanceOf(AdminPasswordForm::class, $controller->renderedModel);
        self::assertTrue($controller->enableCsrfValidation);
        self::assertSame(['GET', 'POST'], $controller->behaviors()['verbs']['actions']['update-password']);
    }

    public function testOnlyAnAdminIdentityWithTheAdminRoleIsAllowed(): void
    {
        $controller = new AdminController('admin', Yii::$app);
        $check = $controller->behaviors()['access']['rules'][0]['matchCallback'];
        Yii::$app->session->set('user_type', 'admin');
        Yii::$app->user->setIdentity(new User(['admin_id' => 23]));
        self::assertTrue($check());
        Yii::$app->session->set('user_type', 'mro');
        self::assertFalse($check());
        Yii::$app->session->set('user_type', 'admin');
        Yii::$app->user->setIdentity(new User(['mro_id' => 23]));
        self::assertFalse($check());
        Yii::$app->user->setIdentity(null);
        self::assertFalse($check());
    }

    public function testSuccessfulPostSignsOutAndRedirectsToLogin(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $admin = $this->admin();
        Yii::$app->user->setIdentity(new User(['admin_id' => 23]));
        Yii::$app->request->setBodyParams(['AdminPasswordForm' => [
            'currentPassword' => 'Current123!',
            'newPassword' => 'Replacement456!',
            'confirmPassword' => 'Replacement456!',
            'admin_id' => 999, // Target account IDs are never loaded from the form.
        ]]);
        $controller = new PasswordTestController('admin', Yii::$app);
        $controller->testAdmin = $admin;
        $response = $controller->actionUpdatePassword();
        self::assertSame(302, $response->statusCode);
        self::assertSame('/site/login', $response->headers->get('Location'));
        self::assertTrue(Yii::$app->user->isGuest);
        self::assertSame(23, $admin->admin_id);
        self::assertSame(6, $admin->auth_version);
        self::assertNotEmpty(Yii::$app->session->get('success'));
    }

    public function testWrongCurrentPasswordDoesNotSignOutOrSave(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $admin = $this->admin();
        Yii::$app->user->setIdentity(new User(['admin_id' => 23]));
        Yii::$app->request->setBodyParams(['AdminPasswordForm' => [
            'currentPassword' => 'Wrong123!',
            'newPassword' => 'Replacement456!',
            'confirmPassword' => 'Replacement456!',
        ]]);
        $controller = new PasswordTestController('admin', Yii::$app);
        $controller->testAdmin = $admin;
        self::assertSame('password form', $controller->actionUpdatePassword());
        self::assertFalse(Yii::$app->user->isGuest);
        self::assertSame(0, $admin->saveCalls);
        self::assertTrue($controller->renderedModel->hasErrors('currentPassword'));
    }

    public function testPostWithoutCsrfTokenIsRejectedBeforeSaving(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->user->setIdentity(new User(['admin_id' => 23]));
        Yii::$app->session->set('user_type', 'admin');
        $controller = new PasswordTestController('admin', Yii::$app);
        $controller->testAdmin = $this->admin();
        $this->expectException(\yii\web\BadRequestHttpException::class);
        try {
            $controller->beforeAction(new \yii\base\Action('update-password', $controller));
        } finally {
            self::assertSame(0, $controller->testAdmin->saveCalls);
        }
    }

    public function testFormRendersCsrfAndNeverRepopulatesPasswords(): void
    {
        Yii::$app->set('assetManager', [
            'class' => \yii\web\AssetManager::class,
            'basePath' => sys_get_temp_dir(),
            'baseUrl' => '/assets',
            'bundles' => [
                \yii\bootstrap5\BootstrapAsset::class => false,
                \yii\web\JqueryAsset::class => false,
                \yii\web\YiiAsset::class => false,
                \yii\widgets\ActiveFormAsset::class => false,
                \yii\validators\ValidationAsset::class => false,
            ],
        ]);
        $html = Yii::$app->view->renderFile(
            dirname(__DIR__, 3) . '/views/admin/update-password.php',
            ['model' => $this->form($this->admin())]
        );
        self::assertStringContainsString('name="_csrf"', $html);
        self::assertSame(3, substr_count($html, 'type="password"'));
        self::assertStringNotContainsString('Current123!', $html);
        self::assertStringNotContainsString('Replacement456!', $html);
        self::assertStringContainsString('action="/admin/update-password"', $html);
        self::assertStringContainsString('Password Guidelines', $html);
        self::assertStringContainsString('class="form-card"', $html);
        self::assertStringContainsString('Reset Form', $html);
        self::assertStringContainsString('data-no-spinner', $html);
        // No Yii ActiveForm resubmit may bypass the asynchronous confirmation.
        $scripts = implode("\n", Yii::$app->view->js[\yii\web\View::POS_READY] ?? []);
        self::assertStringNotContainsString('yiiActiveForm(', $scripts);
    }

    public function testPasswordPayloadIsMaskedInApplicationLogs(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/web.php';
        $maskVars = $config['components']['log']['targets'][0]['maskVars'];
        self::assertContains('_POST.AdminPasswordForm.*', $maskVars);
        $previousPost = $_POST;
        try {
            $_POST = ['AdminPasswordForm' => ['currentPassword' => 'NeverLogCurrent123', 'newPassword' => 'NeverLogNew123']];
            $target = new class(['logVars' => ['_POST'], 'maskVars' => $maskVars]) extends \yii\log\FileTarget {
                public function contextForTest() { return parent::getContextMessage(); }
            };
            $context = $target->contextForTest();
            self::assertStringNotContainsString('NeverLogCurrent123', $context);
            self::assertStringNotContainsString('NeverLogNew123', $context);
        } finally {
            $_POST = $previousPost;
        }
    }
}

class PasswordTestAdmin extends AdminProfile
{
    public int $saveCalls = 0;
    public bool $saveSucceeds = true;
    public array $savedColumns = [];

    public function attributes()
    {
        return ['admin_id', 'password', 'auth_version'];
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        ++$this->saveCalls;
        $this->savedColumns = $attributeNames;
        if (!$this->saveSucceeds) {
            return false;
        }
        // Run the real session-revocation hook, without SQL or an existing account.
        return $this->beforeSave(false);
    }
}

class PasswordTestSession extends \yii\base\Component
{
    private array $values = [];
    public function get($key, $defaultValue = null) { return $this->values[$key] ?? $defaultValue; }
    public function set($key, $value) { $this->values[$key] = $value; }
    public function setFlash($key, $value = true, $removeAfterAccess = true) { $this->set($key, $value); }
    public function hasFlash($key) { return array_key_exists($key, $this->values); }
    public function getFlash($key, $defaultValue = null, $delete = false) { return $this->get($key, $defaultValue); }
}

class PasswordTestController extends AdminController
{
    public PasswordTestAdmin $testAdmin;
    public $renderedModel;
    protected function findCurrentAdmin(): AdminProfile { return $this->testAdmin; }
    public function render($view, $params = [])
    {
        $this->renderedModel = $params['model'];
        return 'password form';
    }
}
