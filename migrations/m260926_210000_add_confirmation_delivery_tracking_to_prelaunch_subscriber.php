<?php

use yii\db\Migration;
use yii\db\Expression;

/**
 * Adds private delivery diagnostics for the early-access confirmation email.
 */
class m260926_210000_add_confirmation_delivery_tracking_to_prelaunch_subscriber extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%prelaunch_subscriber}}',
            'confirmation_email_status',
            $this->string(20)->notNull()->defaultValue('PENDING')
        );
        $this->addColumn(
            '{{%prelaunch_subscriber}}',
            'confirmation_attempt_count',
            $this->integer()->notNull()->defaultValue(0)
        );
        $this->addColumn(
            '{{%prelaunch_subscriber}}',
            'confirmation_attempted_at',
            $this->dateTime()->null()
        );
        $this->addColumn(
            '{{%prelaunch_subscriber}}',
            'confirmation_last_error',
            $this->text()->null()
        );

        // Records created before delivery tracking already have a sent timestamp.
        $this->update(
            '{{%prelaunch_subscriber}}',
            [
                'confirmation_email_status' => 'SENT',
                'confirmation_attempt_count' => 1,
                'confirmation_attempted_at' => new Expression('confirmation_sent_at'),
            ],
            ['not', ['confirmation_sent_at' => null]]
        );

        $this->createIndex(
            'idx_prelaunch_confirmation_delivery',
            '{{%prelaunch_subscriber}}',
            ['confirmation_email_status', 'created_at', 'id']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx_prelaunch_confirmation_delivery', '{{%prelaunch_subscriber}}');
        $this->dropColumn('{{%prelaunch_subscriber}}', 'confirmation_last_error');
        $this->dropColumn('{{%prelaunch_subscriber}}', 'confirmation_attempted_at');
        $this->dropColumn('{{%prelaunch_subscriber}}', 'confirmation_attempt_count');
        $this->dropColumn('{{%prelaunch_subscriber}}', 'confirmation_email_status');
    }
}
