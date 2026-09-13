<?php

namespace tests\unit\models;

use app\models\AuditLogSearch;
use Codeception\Test\Unit;

class AuditLogSearchTest extends Unit
{
    public function testDefaultSortUsesDeclaredStableAttributes(): void
    {
        $provider = (new AuditLogSearch())->search([]);

        $this->assertSame([
            'created_at' => SORT_DESC,
            'id' => SORT_DESC,
        ], $provider->sort->getOrders());
    }
}
