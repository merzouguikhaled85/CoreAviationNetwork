<?php

namespace app\components;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

// Seule une création réussie déclenche le push, jamais une simple modification.
class AogPushBehavior extends Behavior
{
    public function events()
    {
        return [ActiveRecord::EVENT_AFTER_INSERT => 'queueNotification'];
    }

    public function queueNotification()
    {
        if (Yii::$app && Yii::$app->has('aogPush')) {
            Yii::$app->aogPush->enqueueRequest($this->owner);
        }
    }
}
