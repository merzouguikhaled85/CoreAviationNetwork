<?php

namespace tests\unit\components;

use Codeception\Test\Unit;
use yii\web\Request;

class ClientIpResolutionTest extends Unit
{
    private $serverBackup;

    protected function _before(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function _after(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testDirectRequestUsesRemoteAddressAndIgnoresSpoofedHeader(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.77';

        $this->assertSame('203.0.113.10', $this->request()->userIP);
    }

    public function testTrustedCloudflareProxyReturnsClientAddress(): void
    {
        $_SERVER['REMOTE_ADDR'] = '173.245.48.10';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.77';

        $this->assertSame('198.51.100.77', $this->request()->userIP);
    }

    public function testTrustedCloudflareProxySupportsIpv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2400:cb00::10';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '2001:db8:85a3::8a2e:370:7334';

        $this->assertSame('2001:db8:85a3::8a2e:370:7334', $this->request()->userIP);
    }

    private function request(): Request
    {
        return new Request([
            'trustedHosts' => [
                '173.245.48.0/20' => ['CF-Connecting-IP'],
                '2400:cb00::/32' => ['CF-Connecting-IP'],
            ],
            'secureHeaders' => ['CF-Connecting-IP'],
            'ipHeaders' => ['CF-Connecting-IP'],
        ]);
    }
}
