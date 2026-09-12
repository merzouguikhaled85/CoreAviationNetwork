<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property string $content
 * @property int $recipient_id
 * @property string $recipient_type
 * @property string $status
 * @property string $created_at
 * @property string|null $actions
 */

class Notification extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * {@inheritdoc}
     */
  public function rules()
{
    return [
        [['content', 'recipient_id', 'recipient_type'], 'required'],
        [['content'], 'string'],
        [['recipient_id'], 'integer'],
        [['recipient_type'], 'string', 'max' => 255],
        [['status'], 'string', 'max' => 10],
        [['created_at'], 'safe'],
        [['actions'], 'string', 'max' => 100],  // Increase max length to 100 (if needed)
        [['actions'], 'safe'], // <-- Add this line to allow mass assignment if needed
    ];
}


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
            'recipient_id' => 'Recipient ID',
            'recipient_type' => 'Recipient Type',
            'status' => 'Status',
            'created_at' => 'Created At',
        'actions' => 'Actions',

        ];
    }
}

