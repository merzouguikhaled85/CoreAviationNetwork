<?php

namespace app\components;

use Yii;
use yii\db\ActiveRecord;

/** Issues and validates one-hour, single-use password reset tokens. */
final class PasswordResetTokenService
{
    private const PREFIX = 'v2';
    private const TTL = 3600;

    /** @return array{publicToken:string, storedToken:string} */
    public static function issue(): array
    {
        $selector = bin2hex(random_bytes(16));
        $verifier = Yii::$app->security->generateRandomString(43);
        $issuedAt = time();

        return [
            'publicToken' => $selector . '.' . $verifier . '.' . $issuedAt,
            'storedToken' => implode(':', [
                self::PREFIX,
                $selector,
                $issuedAt,
                self::fingerprint($verifier),
            ]),
        ];
    }

    /** @param class-string<ActiveRecord> $modelClass */
    public static function findForModel(string $publicToken, string $modelClass): ?ActiveRecord
    {
        $parts = self::parsePublicToken($publicToken);
        if ($parts === null) {
            return null;
        }

        [$selector, $verifier, $issuedAt] = $parts;
        if ($issuedAt > time() + 60 || $issuedAt < time() - self::TTL) {
            return null;
        }

        $storedPrefix = self::PREFIX . ':' . $selector . ':' . $issuedAt . ':';
        $model = $modelClass::find()
            ->where(['like', 'password_reset_token', $storedPrefix . '%', false])
            ->one();
        if ($model === null || !is_string($model->password_reset_token)) {
            return null;
        }

        $storedParts = explode(':', $model->password_reset_token, 4);
        if (
            count($storedParts) !== 4
            || $storedParts[0] !== self::PREFIX
            || !hash_equals($selector, $storedParts[1])
            || !hash_equals((string) $issuedAt, $storedParts[2])
            || !hash_equals($storedParts[3], self::fingerprint($verifier))
        ) {
            return null;
        }

        return $model;
    }

    private static function parsePublicToken(string $token): ?array
    {
        if (strlen($token) > 160) {
            return null;
        }

        $parts = explode('.', $token, 3);
        if (
            count($parts) !== 3
            || !preg_match('/^[a-f0-9]{32}$/', $parts[0])
            || !preg_match('/^[A-Za-z0-9_-]{40,64}$/', $parts[1])
            || !ctype_digit($parts[2])
        ) {
            return null;
        }

        return [$parts[0], $parts[1], (int) $parts[2]];
    }

    private static function fingerprint(string $verifier): string
    {
        $key = Yii::$app->params['passwordResetTokenKey'] ?? null;
        if (!is_string($key) || strlen($key) < 32) {
            throw new \RuntimeException('Password reset token key is not configured.');
        }

        return hash_hmac(
            'sha256',
            $verifier,
            $key
        );
    }
}
