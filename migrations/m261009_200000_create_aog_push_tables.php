<?php

use yii\db\Migration;

// Les horodatages Unix évitent les différences de fuseau entre PHP et MySQL.
class m261009_200000_create_aog_push_tables extends Migration
{
    public function safeUp()
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;
        $this->createTable('{{%push_subscription}}', [
            'id' => $this->primaryKey(),
            'mro_id' => $this->integer()->notNull(),
            'installation_hash' => $this->char(64)->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'token' => $this->text()->notNull(),
            'account_key_hash' => $this->char(64)->notNull(),
            'active' => $this->boolean()->notNull()->defaultValue(true),
            'updated_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('uq_push_installation', '{{%push_subscription}}', 'installation_hash', true);
        $this->createIndex('uq_push_token', '{{%push_subscription}}', 'token_hash', true);
        $this->createIndex('idx_push_mro', '{{%push_subscription}}', ['mro_id', 'active']);

        $this->createTable('{{%aog_push_job}}', [
            'id' => $this->primaryKey(),
            'request_id' => $this->integer()->notNull(),
            'mro_id' => $this->integer()->notNull(),
            'subscription_id' => $this->integer()->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'available_at' => $this->integer()->notNull(),
            'started_at' => $this->integer()->notNull()->defaultValue(0),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'last_error' => $this->string(64)->null(),
        ], $options);
        $this->createIndex('uq_aog_push_delivery', '{{%aog_push_job}}', ['request_id', 'subscription_id'], true);
        $this->createIndex('idx_aog_push_pending', '{{%aog_push_job}}', ['status', 'available_at']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%aog_push_job}}');
        $this->dropTable('{{%push_subscription}}');
    }
}
