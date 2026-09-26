<?php

namespace tests\unit\models;

use app\models\PrelaunchSubscriberSearch;

class PrelaunchSubscriberSearchTest extends \Codeception\Test\Unit
{
    public function testDefaultSortUsesDeclaredStableAttributes()
    {
        $provider = (new PrelaunchSubscriberSearch())->search([]);
        $sort = $provider->getSort();

        verify(array_keys($sort->getOrders()))->equals(['created_at', 'id']);
        verify(array_key_exists('id', $sort->attributes))->true();
    }
}
