<?php

namespace tests\unit\models;

use app\models\RequestChange;
use Codeception\Test\Unit;

class RequestChangeTest extends Unit
{
    public function testActiveStatusesExcludeTerminalDecisions(): void
    {
        $this->assertContains(RequestChange::STATUS_PENDING_MRO, RequestChange::activeStatuses());
        $this->assertContains(RequestChange::STATUS_PO_REJECTED, RequestChange::activeStatuses());
        $this->assertContains(RequestChange::STATUS_QUOTE_REJECTED, RequestChange::activeStatuses());
        $this->assertNotContains(RequestChange::STATUS_REJECTED, RequestChange::activeStatuses());
        $this->assertNotContains(RequestChange::STATUS_COMPLETED, RequestChange::activeStatuses());
    }

    public function testScopeRequiresAtLeastOneAddedOrRemovedTask(): void
    {
        /* Le double léger évite de dépendre du schéma MySQL dans ce test de règle pure. */
        $model = new class extends RequestChange {
            public function attributes()
            {
                return ['added_tasks', 'removed_tasks'];
            }
        };
        $model->added_tasks = '   ';
        $model->removed_tasks = '';
        $model->validateScopeChange('added_tasks');

        $this->assertTrue($model->hasErrors('added_tasks'));
    }

    public function testStatusLabelsCoverEveryWorkflowState(): void
    {
        $labels = RequestChange::statusLabels();

        $this->assertArrayHasKey(RequestChange::STATUS_PENDING_MRO, $labels);
        $this->assertArrayHasKey(RequestChange::STATUS_PENDING_PO_REVIEW, $labels);
        $this->assertArrayHasKey(RequestChange::STATUS_PENDING_QUOTE_REVIEW, $labels);
        $this->assertArrayHasKey(RequestChange::STATUS_COMPLETED, $labels);
    }

    public function testSubmitQuoteScenarioRequiresCommercialFields(): void
    {
        /* Le scénario du formulaire active les contrôles sans modifier les autres étapes. */
        $model = new class extends RequestChange {
            public function attributes()
            {
                return ['quote_description', 'quote_price', 'quote_currency'];
            }
        };
        $model->scenario = 'submitQuote';

        $model->validate(['quote_description', 'quote_price', 'quote_currency']);

        $this->assertTrue($model->hasErrors('quote_description'));
        $this->assertTrue($model->hasErrors('quote_price'));
        $this->assertTrue($model->hasErrors('quote_currency'));
    }

    public function testUploadPoScenarioRequiresDocument(): void
    {
        /* Le PO est obligatoire uniquement pendant son étape de téléversement. */
        $model = new class extends RequestChange {
            public function attributes()
            {
                return ['revisedPoUpload'];
            }
        };
        $model->scenario = 'uploadPo';

        $model->validate(['revisedPoUpload']);

        $this->assertTrue($model->hasErrors('revisedPoUpload'));
    }
}
