<?php

namespace app\components;

use Yii;
use yii\base\Component;

class FirebaseMessagingSender extends Component
{
    private $accessToken;
    private $accessTokenExpires = 0;

    protected function credentials()
    {
        $config = Yii::$app->params['firebase'] ?? [];
        $path = realpath($config['serviceAccountPath'] ?? '');
        $base = str_replace('\\', '/', realpath(Yii::$app->basePath));
        $normalized = $path ? str_replace('\\', '/', $path) : '';
        // Le JSON privé ne doit jamais se trouver dans le projet ou public_html.
        if (!$path || !is_readable($path) || !is_file($path)
            || stripos($normalized, $base . '/') === 0
            || preg_match('~/public_html(?:/|$)~i', $normalized) || filesize($path) > 65536) {
            throw new \RuntimeException('SERVICE_ACCOUNT_UNAVAILABLE');
        }
        $credentials = json_decode(file_get_contents($path), true);
        if (!is_array($credentials) || ($credentials['type'] ?? '') !== 'service_account'
            || ($credentials['project_id'] ?? '') !== ($config['web']['projectId'] ?? '')
            || empty($credentials['private_key']) || empty($credentials['client_email'])) {
            throw new \RuntimeException('SERVICE_ACCOUNT_INVALID');
        }
        return $credentials;
    }

    public function isConfigured()
    {
        try {
            $this->credentials();
            return extension_loaded('curl') && extension_loaded('openssl');
        } catch (\Throwable $error) {
            return false;
        }
    }

    private function base64Url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function getAccessToken()
    {
        if ($this->accessToken && $this->accessTokenExpires > time() + 60) {
            return $this->accessToken;
        }
        $credentials = $this->credentials();
        $now = time();
        $jwt = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.'
            . $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ]));
        if (!openssl_sign($jwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('SERVICE_ACCOUNT_INVALID');
        }
        $jwt .= '.' . $this->base64Url($signature);
        $response = $this->post('https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]));
        if ($response['status'] !== 200 || empty($response['body']['access_token'])) {
            throw new \RuntimeException('OAUTH_FAILED');
        }
        $this->accessToken = $response['body']['access_token'];
        $this->accessTokenExpires = $now + min(3600, (int) ($response['body']['expires_in'] ?? 3600));
        return $this->accessToken;
    }

    public function send(array $message)
    {
        try {
            $accessToken = $this->getAccessToken();
            $project = Yii::$app->params['firebase']['web']['projectId'];
            if (!preg_match('/^[a-z0-9-]+$/D', $project)) {
                throw new \RuntimeException('PROJECT_INVALID');
            }
            $response = $this->post('https://fcm.googleapis.com/v1/projects/' . $project . '/messages:send',
                ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'],
                json_encode(['message' => $message], JSON_THROW_ON_ERROR));
            $status = $response['status'];
            $code = 'HTTP_' . $status;
            foreach ($response['body']['error']['details'] ?? [] as $detail) {
                if (($detail['@type'] ?? '') === 'type.googleapis.com/google.firebase.fcm.v1.FcmError') {
                    $candidate = $detail['errorCode'] ?? '';
                    if (preg_match('/^[A-Z_]{1,64}$/D', $candidate)) {
                        $code = $candidate;
                    }
                }
            }
            if ($status === 401) {
                $this->accessToken = null;
            }
            return ['success' => $status >= 200 && $status < 300,
                'invalidToken' => $code === 'UNREGISTERED',
                'retryable' => $status === 401 || $status === 429 || $status >= 500,
                'retryAfter' => $response['retryAfter'], 'error' => $code];
        } catch (\Throwable $error) {
            // Ne jamais propager le contenu HTTP ni une exception contenant un secret.
            return ['success' => false, 'invalidToken' => false,
                'retryable' => true, 'retryAfter' => 120, 'error' => 'TRANSPORT_OR_CONFIG_ERROR'];
        }
    }

    protected function post($url, array $headers, $body)
    {
        $retryAfter = 0;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($handle, $line) use (&$retryAfter) {
                if (stripos($line, 'Retry-After:') === 0) {
                    $value = trim(substr($line, 12));
                    $retryAfter = ctype_digit($value) ? (int) $value : max(0, (int) strtotime($value) - time());
                }
                return strlen($line);
            },
        ]);
        $result = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($result === false) {
            throw new \RuntimeException('TRANSPORT_FAILED');
        }
        return ['status' => $status, 'body' => json_decode($result, true) ?: [], 'retryAfter' => $retryAfter];
    }
}
