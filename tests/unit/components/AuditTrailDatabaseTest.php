<?php

namespace tests\unit\components;

use app\models\AuditLog;
use app\models\Terms;
use Codeception\Test\Unit;
use Yii;

/**
 * Ces tests utilisent une transaction et ne conservent aucune donnee metier.
 * La migration Audit Trail doit etre appliquee a la base de test au prealable.
 */
class AuditTrailDatabaseTest extends Unit
{
    private $transaction;

    protected function _before(): void
    {
        try {
            $auditTable = Yii::$app->db->schema->getTableSchema('audit_log', true);
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Create and migrate the configured yii2basic_test database first.');
            return;
        }
        if ($auditTable === null) {
            $this->markTestSkipped('Apply the audit_log migration to the test database first.');
            return;
        }
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    protected function _after(): void
    {
        if ($this->transaction && $this->transaction->isActive) {
            $this->transaction->rollBack();
        }
    }

    public function testCreateUpdateAndDeleteAreAuditedWithChangedFieldsOnly(): void
    {
        $term = new Terms();
        $term->content = 'audit-create-' . bin2hex(random_bytes(4));
        $term->created_at = gmdate('Y-m-d H:i:s');
        $this->assertTrue($term->save(false));

        $created = $this->findEvent('CREATE', $term->id);
        $this->assertNotNull($created);
        $this->assertSame($term->content, $created->newValuesArray['content']);

        $before = $term->content;
        $term->content = 'audit-update-' . bin2hex(random_bytes(4));
        $this->assertTrue($term->save(false));

        $updated = $this->findEvent('UPDATE', $term->id);
        $this->assertSame(['content'], array_keys($updated->oldValuesArray));
        $this->assertSame($before, $updated->oldValuesArray['content']);
        $this->assertSame($term->content, $updated->newValuesArray['content']);

        $id = $term->id;
        $deletedContent = $term->content;
        $this->assertNotFalse($term->delete());
        $deleted = $this->findEvent('DELETE', $id);
        $this->assertSame($deletedContent, $deleted->oldValuesArray['content']);
    }

    public function testSensitiveValuesAndAuditRecursionAreBlocked(): void
    {
        Yii::$app->auditService->record('LOGIN_FAILED', [
            'username' => 'unknown-user',
            'new_values' => ['password' => 'never-store-this', 'reason' => 'INVALID_CREDENTIALS'],
        ]);
        $failed = AuditLog::find()->where(['action' => 'LOGIN_FAILED'])->orderBy(['id' => SORT_DESC])->one();
        $this->assertSame('[REDACTED]', $failed->newValuesArray['password']);
        $this->assertStringNotContainsString('never-store-this', (string) $failed->new_values);

        $before = (int) AuditLog::find()->count();
        $manual = new AuditLog([
            'action' => 'CREATE',
            'request_id' => Yii::$app->auditContext->requestId,
            'created_at' => gmdate('Y-m-d H:i:s.000000'),
        ]);
        $this->assertTrue($manual->insert(false));
        $this->assertSame($before + 1, (int) AuditLog::find()->count());
    }

    public function testSeveralOperationsShareOneRequestId(): void
    {
        foreach (['one', 'two'] as $suffix) {
            $term = new Terms();
            $term->content = 'audit-request-' . $suffix . '-' . bin2hex(random_bytes(3));
            $term->created_at = gmdate('Y-m-d H:i:s');
            $this->assertTrue($term->save(false));
        }

        $ids = AuditLog::find()->select('request_id')->where(['action' => 'CREATE', 'model' => Terms::class])->column();
        $this->assertNotEmpty($ids);
        $this->assertCount(1, array_unique($ids));
    }

    private function findEvent(string $action, $recordId): AuditLog
    {
        return AuditLog::find()
            ->where(['action' => $action, 'model' => Terms::class, 'record_id' => (string) $recordId])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }
}
