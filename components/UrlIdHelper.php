<?php

namespace app\components;

use Yii;
use yii\web\NotFoundHttpException;

class UrlIdHelper
{
    public static function encode($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new \InvalidArgumentException('A positive numeric identifier is required.');
        }

        $idPart = strtoupper(base_convert($id, 10, 36));

        $signature = strtoupper(substr(
            hash_hmac(
                'sha256',
                (string)$id,
                Yii::$app->params['urlIdSecret']
            ),
            0,
            8
        ));

        return $idPart . $signature;
    }

    public static function decode($code)
    {
        $code = strtoupper(trim($code));

        // SIGNED URL IDENTIFIER: one base-36 ID part followed by an 8-character signature.
        if (!preg_match('/^[0-9A-Z]+[0-9A-F]{8}$/', $code) || strlen($code) < 9) {
            return null;
        }

        $idPart = substr($code, 0, -8);
        $signature = substr($code, -8);

        $id = (int) base_convert(strtolower($idPart), 36, 10);

        if ($id <= 0) {
            return null;
        }

        $expectedSignature = strtoupper(substr(
            hash_hmac(
                'sha256',
                (string)$id,
                Yii::$app->params['urlIdSecret']
            ),
            0,
            8
        ));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $id;
    }

    /**
     * Decode a signed browser identifier or stop with a consistent 404 response.
     */
    public static function decodeOrFail($code, string $message = 'Invalid or expired link.'): int
    {
        $id = self::decode($code);

        if ($id === null) {
            throw new NotFoundHttpException($message);
        }

        return $id;
    }
}
