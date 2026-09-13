<?php

namespace tests\unit\models;

use app\models\AuditLog;
use Codeception\Test\Unit;

class AuditLogTest extends Unit
{
    public function testExistingAuditRecordCannotBeUpdated(): void
    {
        $model = new AuditLog();
        $this->assertFalse($model->beforeSave(false));
    }

    public function testAuditRecordCannotBeDeleted(): void
    {
        $model = new AuditLog();
        $this->assertFalse($model->beforeDelete());
    }
}
