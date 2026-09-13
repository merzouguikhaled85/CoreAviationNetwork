<?php

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;

/**
 * Enregistre un observateur ActiveRecord global. On evite ainsi de modifier
 * l'heritage ou les behaviors de chaque modele metier existant.
 */
class AuditTrailBootstrap implements BootstrapInterface
{
    /** Evite plusieurs inscriptions lorsque Codeception recree l'application. */
    private static $registered = false;

    private $deleteSnapshots = [];

    public function bootstrap($app)
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        Event::on(ActiveRecord::class, BaseActiveRecord::EVENT_AFTER_INSERT, [$this, 'afterInsert']);
        Event::on(ActiveRecord::class, BaseActiveRecord::EVENT_AFTER_UPDATE, [$this, 'afterUpdate']);
        Event::on(ActiveRecord::class, BaseActiveRecord::EVENT_BEFORE_DELETE, [$this, 'beforeDelete']);
        Event::on(ActiveRecord::class, BaseActiveRecord::EVENT_AFTER_DELETE, [$this, 'afterDelete']);
    }

    public function afterInsert(AfterSaveEvent $event): void
    {
        $model = $event->sender;
        if ($this->canAudit($model)) {
            Yii::$app->auditService->recordModel(AuditService::CREATE, $model, [], $model->getAttributes());
        }
    }

    public function afterUpdate(AfterSaveEvent $event): void
    {
        $model = $event->sender;
        if (!$this->canAudit($model) || !$event->changedAttributes) {
            return;
        }

        $newValues = [];
        foreach (array_keys($event->changedAttributes) as $attribute) {
            $newValues[$attribute] = $model->getAttribute($attribute);
        }

        Yii::$app->auditService->recordModel(
            AuditService::UPDATE,
            $model,
            $event->changedAttributes,
            $newValues
        );
    }

    public function beforeDelete(Event $event): void
    {
        $model = $event->sender;
        if ($this->canAudit($model)) {
            $this->deleteSnapshots[spl_object_id($model)] = $model->getAttributes();
        }
    }

    public function afterDelete(Event $event): void
    {
        $model = $event->sender;
        $objectId = spl_object_id($model);
        if (!$this->canAudit($model) || !isset($this->deleteSnapshots[$objectId])) {
            return;
        }

        $snapshot = $this->deleteSnapshots[$objectId];
        unset($this->deleteSnapshots[$objectId]);
        Yii::$app->auditService->recordModel(AuditService::DELETE, $model, $snapshot, []);
    }

    private function canAudit($model): bool
    {
        return $model instanceof ActiveRecord
            && Yii::$app->has('auditService')
            && !Yii::$app->auditService->isWriting()
            && Yii::$app->auditService->shouldAudit($model);
    }
}
