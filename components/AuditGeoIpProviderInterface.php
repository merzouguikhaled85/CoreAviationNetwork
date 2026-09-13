<?php

namespace app\components;

/**
 * Contrat non bloquant de geolocalisation approximative pour l'audit.
 */
interface AuditGeoIpProviderInterface
{
    /**
     * @return array{country_code:?string,country_name:?string,city:?string}
     */
    public function locate(?string $ipAddress): array;
}
