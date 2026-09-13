<?php

namespace tests\unit\components;

use app\components\AuditDataRedactor;
use Codeception\Test\Unit;

class AuditDataRedactorTest extends Unit
{
    public function testSensitiveValuesAreRedactedRecursively(): void
    {
        $redactor = new AuditDataRedactor();
        $result = $redactor->redactArray([
            'username' => 'pilot',
            'password' => 'never-store-this',
            'nested' => ['verification_token' => 'secret-token', 'status' => 'active'],
            'card_number' => '4111111111111111',
        ]);

        $this->assertSame('pilot', $result['username']);
        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('[REDACTED]', $result['nested']['verification_token']);
        $this->assertSame('active', $result['nested']['status']);
        $this->assertSame('[REDACTED]', $result['card_number']);
    }

    public function testSensitiveQueryParametersAreRedacted(): void
    {
        $redactor = new AuditDataRedactor();
        $url = $redactor->redactUrl('/reset?token=abc123&return=dashboard');

        $this->assertStringNotContainsString('abc123', $url);
        $this->assertStringContainsString('return=dashboard', $url);
    }

    public function testDateNormalizationDoesNotModifyBusinessObject(): void
    {
        $date = new \DateTime('2026-09-13 10:00:00', new \DateTimeZone('Africa/Tunis'));
        $redactor = new AuditDataRedactor();

        $result = $redactor->redactArray(['scheduled_at' => $date]);

        $this->assertSame('Africa/Tunis', $date->getTimezone()->getName());
        $this->assertSame('2026-09-13 09:00:00.000000', $result['scheduled_at']);
    }
}
