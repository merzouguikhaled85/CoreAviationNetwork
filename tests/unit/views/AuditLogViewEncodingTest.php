<?php

namespace tests\unit\views;

use Codeception\Test\Unit;
use Yii;
use yii\data\ArrayDataProvider;

class AuditLogViewEncodingTest extends Unit
{
    public function testAuditedHtmlIsEncodedInDetailView(): void
    {
        $payload = '<script>alert("audit-xss")</script>';
        /*
         * Un objet de presentation suffit ici et garde ce test independant de la
         * base : la vue ne doit jamais faire confiance au contenu persiste.
         */
        $model = (object) [
            'id' => 1,
            'action' => 'UPDATE',
            'model' => 'app\\models\\Terms',
            'record_id' => '42',
            'oldValuesArray' => ['description' => 'safe'],
            'newValuesArray' => ['description' => $payload],
            'created_at' => '2026-09-13 12:00:00.000000',
            'request_id' => str_repeat('a', 32),
            'user_id' => null,
            'username' => null,
            'company_name' => null,
            'user_role' => null,
            'ip_address' => null,
            'city' => null,
            'country_name' => null,
            'country_code' => null,
            'request_method' => null,
            'request_url' => null,
            'user_agent' => null,
        ];
        $relatedProvider = new ArrayDataProvider([
            'allModels' => [],
            'pagination' => ['pageSize' => 25],
        ]);
        $controller = new \app\controllers\AdminAuditLogController(
            'admin-audit-log',
            Yii::$app
        );
        $previousController = Yii::$app->controller;
        Yii::$app->controller = $controller;

        try {
            $html = Yii::$app->view->renderFile(
                Yii::getAlias('@app/views/admin-audit-log/view.php'),
                compact('model', 'relatedProvider'),
                $controller
            );
        } finally {
            Yii::$app->controller = $previousController;
        }

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString(
            '&lt;script&gt;alert(&quot;audit-xss&quot;)&lt;/script&gt;',
            $html
        );
    }
}
