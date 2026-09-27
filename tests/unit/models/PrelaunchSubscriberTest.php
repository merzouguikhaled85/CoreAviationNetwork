<?php

namespace tests\unit\models;

use app\models\PrelaunchSubscriber;

class PrelaunchSubscriberTest extends \Codeception\Test\Unit
{
    public function testEmailNormalization()
    {
        verify(PrelaunchSubscriber::normalizeEmail(' Operations@Example.COM '))
            ->equals('operations@example.com');
    }

    public function testCompanyTypesAreRestrictedToThePublicOptions()
    {
        verify(array_keys(PrelaunchSubscriber::companyTypeOptions()))->equals([
            PrelaunchSubscriber::TYPE_MRO,
            PrelaunchSubscriber::TYPE_AIRCRAFT_OPERATOR,
        ]);
    }

    public function testPublicRulesContainConsentAndHoneypotValidation()
    {
        $model = new PrelaunchSubscriber();
        $rules = $model->rules();
        $validators = array_column($rules, 1);

        $this->assertContains('compare', $validators);
        $this->assertContains('validateHoneypot', $validators);
    }

    public function testHoneypotRejectsAutomatedSubmission()
    {
        $model = new PrelaunchSubscriber();
        $model->website = 'https://spam.example';
        $model->validateHoneypot('website');

        verify($model->hasErrors('website'))->true();
    }
}
