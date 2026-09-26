<?php

namespace app\components;

use Yii;

/**
 * Central access point for the reversible Early Access launch mode.
 */
final class PrelaunchMode
{
    public static function isEnabled(): bool
    {
        return (bool) (Yii::$app->params['prelaunchMode'] ?? true);
    }

    public static function earlyAccessRoute(): array
    {
        return ['/site/index', '#' => 'early-access'];
    }
}
