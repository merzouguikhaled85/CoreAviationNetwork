<?php

use yii\db\Migration;

/**
 * Stores early-access registrations and their double opt-in state.
 */
class m260926_200000_create_prelaunch_subscriber_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%prelaunch_subscriber}}', [
            'id' => $this->bigPrimaryKey(),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'company_name' => $this->string(200)->notNull(),
            // 190 remains compatible with older shared-hosting utf8mb4 indexes.
            'business_email' => $this->string(190)->notNull(),
            'company_website' => $this->string(255)->null(),
            'company_type' => $this->string(30)->notNull(),
            'subscription_status' => $this->string(30)->notNull()->defaultValue('PENDING_CONFIRMATION'),
            'consent' => $this->boolean()->notNull()->defaultValue(false),
            'consent_at' => $this->dateTime()->notNull(),
            'consent_text_version' => $this->string(40)->notNull(),
            'source' => $this->string(100)->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'confirmed_at' => $this->dateTime()->null(),
            'unsubscribed_at' => $this->dateTime()->null(),
            'confirmation_token_hash' => $this->char(64)->null(),
            'confirmation_token_expires_at' => $this->dateTime()->null(),
            'confirmation_sent_at' => $this->dateTime()->null(),
            'unsubscribe_token_hash' => $this->char(64)->notNull(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->string(500)->null(),
        ], $tableOptions);

        $this->createIndex(
            'uq_prelaunch_subscriber_email',
            '{{%prelaunch_subscriber}}',
            'business_email',
            true
        );
        $this->createIndex(
            'uq_prelaunch_subscriber_confirmation_token',
            '{{%prelaunch_subscriber}}',
            'confirmation_token_hash',
            true
        );
        $this->createIndex(
            'uq_prelaunch_subscriber_unsubscribe_token',
            '{{%prelaunch_subscriber}}',
            'unsubscribe_token_hash',
            true
        );
        $this->createIndex(
            'idx_prelaunch_subscriber_status_created',
            '{{%prelaunch_subscriber}}',
            ['subscription_status', 'created_at', 'id']
        );
        $this->createIndex(
            'idx_prelaunch_subscriber_type_created',
            '{{%prelaunch_subscriber}}',
            ['company_type', 'created_at', 'id']
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%prelaunch_subscriber}}');
    }
}
