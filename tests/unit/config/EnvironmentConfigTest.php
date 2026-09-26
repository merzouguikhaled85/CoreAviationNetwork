<?php

namespace tests\unit\config;

use Codeception\Test\Unit;

/**
 * Protège le chargement des paramètres sensibles contre les différences entre
 * PHP CLI et Apache/cPanel. Les valeurs sont comparées en mémoire et ne sont
 * jamais incluses dans le message du test.
 */
class EnvironmentConfigTest extends Unit
{
    public function testPrelaunchModeCanBeDisabledExplicitly(): void
    {
        $previousValue = getenv('CAN_PRELAUNCH_MODE');

        try {
            putenv('CAN_PRELAUNCH_MODE=0');
            $environment = require dirname(__DIR__, 3) . '/config/env.php';

            $this->assertSame('0', $environment['prelaunchMode']);
        } finally {
            if ($previousValue === false) {
                putenv('CAN_PRELAUNCH_MODE');
            } else {
                putenv('CAN_PRELAUNCH_MODE=' . $previousValue);
            }
        }
    }

    public function testEmptyTurnstileEnvironmentValuesFallBackToLocalFile(): void
    {
        $localFile = dirname(__DIR__, 3) . '/config/env-local.php';
        if (!is_file($localFile)) {
            $this->markTestSkipped('config/env-local.php is required for this local regression test.');
        }

        $localValues = require $localFile;
        $variableNames = [
            'CAN_TURNSTILE_SITE_KEY',
            'CAN_TURNSTILE_SECRET_KEY',
            'CAN_TURNSTILE_EXPECTED_HOSTNAME',
        ];
        $previousValues = [];

        try {
            foreach ($variableNames as $variableName) {
                $previousValues[$variableName] = getenv($variableName);
                putenv($variableName . '=');
            }

            $environment = require dirname(__DIR__, 3) . '/config/env.php';

            $this->assertSame(
                trim((string) $localValues['turnstileSiteKey']),
                $environment['turnstileSiteKey']
            );
            $this->assertSame(
                trim((string) $localValues['turnstileSecretKey']),
                $environment['turnstileSecretKey']
            );
            $this->assertSame(
                trim((string) $localValues['turnstileExpectedHostname']),
                $environment['turnstileExpectedHostname']
            );
        } finally {
            foreach ($previousValues as $variableName => $previousValue) {
                if ($previousValue === false) {
                    putenv($variableName);
                } else {
                    putenv($variableName . '=' . $previousValue);
                }
            }
        }
    }
}
