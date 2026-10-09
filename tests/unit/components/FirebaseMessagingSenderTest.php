<?php

namespace tests\unit\components;

use app\components\FirebaseMessagingSender;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;

Yii::setAlias('@app', dirname(__DIR__, 3));

class FirebaseMessagingSenderTest extends TestCase
{
    private $previous;
    private $sender;

    protected function setUp(): void
    {
        $this->previous = Yii::$app;
        new Application(['id' => 'firebase-transport-tests', 'basePath' => dirname(__DIR__, 3),
            'params' => ['firebase' => ['web' => ['projectId' => 'test-project']]]]);
        $this->sender = new SimulatedFirebaseSender();
        // Clé éphémère réservée au test, jamais envoyée à Google ni enregistrée dans Git.
        $this->sender->key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($this->sender->key === false) {
            throw new \RuntimeException('Configure OPENSSL_CONF for the ephemeral RSA test key.');
        }
    }

    protected function tearDown(): void
    {
        Yii::$app = $this->previous;
    }

    public function testSignedOauthAssertionAndAccessTokenReuse(): void
    {
        $this->assertTrue($this->sender->send(['token' => 'device-one'])['success']);
        $this->assertTrue($this->sender->send(['token' => 'device-two'])['success']);
        $this->assertCount(3, $this->sender->calls);
        $this->assertSame('https://oauth2.googleapis.com/token', $this->sender->calls[0]['url']);
        parse_str($this->sender->calls[0]['body'], $form);
        $jwt = explode('.', $form['assertion']);
        $claims = json_decode(base64_decode(strtr($jwt[1], '-_', '+/')), true);
        $this->assertSame('https://www.googleapis.com/auth/firebase.messaging', $claims['scope']);
        $this->assertSame('https://oauth2.googleapis.com/token', $claims['aud']);
        $this->assertSame(3600, $claims['exp'] - $claims['iat']);
        $public = openssl_pkey_get_details($this->sender->key)['key'];
        $this->assertSame(1, openssl_verify($jwt[0] . '.' . $jwt[1],
            base64_decode(strtr($jwt[2], '-_', '+/')), $public, OPENSSL_ALGO_SHA256));
        $this->assertSame('https://fcm.googleapis.com/v1/projects/test-project/messages:send', $this->sender->calls[1]['url']);
        $this->assertSame('device-two', json_decode($this->sender->calls[2]['body'], true)['message']['token']);
    }

    /** @dataProvider responseCodes */
    public function testOnlyUnregisteredErrorsInvalidateTokens($status, $code, $invalid, $retryable): void
    {
        $this->sender->response = ['status' => $status, 'retryAfter' => 300, 'body' => ['error' => [
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => $code]],
        ]]];
        $result = $this->sender->send(['token' => 'device-one']);
        $this->assertFalse($result['success']);
        $this->assertSame($invalid, $result['invalidToken']);
        $this->assertSame($retryable, $result['retryable']);
        $this->assertSame(300, $result['retryAfter']);
    }

    public function responseCodes(): array
    {
        return [[404, 'UNREGISTERED', true, false], [400, 'INVALID_ARGUMENT', false, false],
            [429, 'QUOTA_EXCEEDED', false, true], [503, 'UNAVAILABLE', false, true],
            [403, 'SENDER_ID_MISMATCH', false, false]];
    }

    public function testFailureDoesNotExposeSecretOrTransportBody(): void
    {
        $this->sender->fail = true;
        $result = $this->sender->send(['token' => 'secret-device-token']);
        $this->assertFalse($result['success']);
        $this->assertSame('TRANSPORT_OR_CONFIG_ERROR', $result['error']);
        $this->assertStringNotContainsString('secret', json_encode($result));
    }

    public function testPrivateAccountInsideTheProjectIsRejected(): void
    {
        Yii::$app->params['firebase']['serviceAccountPath'] = dirname(__DIR__, 3) . '/config/firebase.php';
        $this->assertFalse((new FirebaseMessagingSender())->isConfigured());
    }
}

class SimulatedFirebaseSender extends FirebaseMessagingSender
{
    public $key;
    public $calls = [];
    public $fail = false;
    public $response = ['status' => 200, 'body' => ['name' => 'test-message'], 'retryAfter' => 0];

    protected function credentials()
    {
        return ['client_email' => 'test@example.test', 'private_key' => $this->key];
    }

    protected function post($url, array $headers, $body)
    {
        $this->calls[] = compact('url', 'headers', 'body');
        if ($this->fail) {
            throw new \RuntimeException('secret-device-token must never appear in logs');
        }
        return $url === 'https://oauth2.googleapis.com/token'
            ? ['status' => 200, 'body' => ['access_token' => 'test-access-token', 'expires_in' => 3600], 'retryAfter' => 0]
            : $this->response;
    }
}
