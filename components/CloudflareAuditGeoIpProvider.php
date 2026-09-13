<?php

namespace app\components;

use app\models\Countries;
use Yii;
use yii\base\Component;
use yii\web\Application as WebApplication;

/**
 * Exploite uniquement le pays fourni par un pair Cloudflare deja valide par Yii.
 * Aucune API externe n'est appelee pendant la requete utilisateur.
 */
class CloudflareAuditGeoIpProvider extends Component implements AuditGeoIpProviderInterface
{
    public function locate(?string $ipAddress): array
    {
        $location = ['country_code' => null, 'country_name' => null, 'city' => null];
        if (!(Yii::$app instanceof WebApplication)) {
            return $location;
        }

        $countryCode = strtoupper(trim((string) Yii::$app->request->headers->get('CF-IPCountry', '')));
        if (!preg_match('/^[A-Z]{2}$/', $countryCode) || in_array($countryCode, ['XX', 'T1'], true)) {
            return $location;
        }

        $location['country_code'] = $countryCode;
        try {
            $countryName = Countries::find()
                ->select('country_name')
                ->where(['country_code' => $countryCode])
                ->scalar();
            $location['country_name'] = $countryName !== false ? (string) $countryName : null;
        } catch (\Throwable $exception) {
            Yii::warning('La resolution du pays pour Audit Trail a echoue.', 'audit');
        }

        return $location;
    }
}
