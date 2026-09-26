<?php

namespace tests\unit\components;

use app\components\PrelaunchMode;
use Yii;

class PrelaunchModeTest extends \Codeception\Test\Unit
{
    public function testModeFollowsTheCentralApplicationParameter()
    {
        $previousValue = Yii::$app->params['prelaunchMode'] ?? null;

        try {
            Yii::$app->params['prelaunchMode'] = true;
            verify(PrelaunchMode::isEnabled())->true();

            Yii::$app->params['prelaunchMode'] = false;
            verify(PrelaunchMode::isEnabled())->false();
        } finally {
            Yii::$app->params['prelaunchMode'] = $previousValue;
        }
    }

    public function testEarlyAccessRouteTargetsThePublicForm()
    {
        verify(PrelaunchMode::earlyAccessRoute())->equals([
            '/site/index',
            '#' => 'early-access',
        ]);
    }
}
