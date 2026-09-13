<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Application as WebApplication;

/**
 * Contexte technique d'une requete. L'identifiant de correlation est genere une
 * seule fois puis reutilise par toutes les traces produites durant la requete HTTP.
 */
class AuditRequestContext extends Component
{
    private $requestId;

    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = bin2hex(random_bytes(16));
        }

        return $this->requestId;
    }

    public function getContext(): array
    {
        $context = [
            'request_id' => $this->getRequestId(),
            'ip_address' => null,
            'country_code' => null,
            'country_name' => null,
            'city' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_url' => null,
        ];

        if (!(Yii::$app instanceof WebApplication)) {
            return $context;
        }

        $request = Yii::$app->request;
        $context['ip_address'] = $request->userIP ?: null;
        $context['user_agent'] = $request->userAgent ?: null;
        $context['request_method'] = $request->method ?: null;
        $context['request_url'] = Yii::$app->auditRedactor->redactUrl($request->url);

        if (Yii::$app->has('auditGeoIp')) {
            $location = Yii::$app->auditGeoIp->locate($context['ip_address']);
            $context['country_code'] = $location['country_code'] ?? null;
            $context['country_name'] = $location['country_name'] ?? null;
            $context['city'] = $location['city'] ?? null;
        }

        return $context;
    }
}
