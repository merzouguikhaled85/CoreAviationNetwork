<?php

use yii\db\Migration;

/**
 * Cree le stockage d'audit denormalise et alimente uniquement par ajout.
 *
 * L'absence de cles etrangeres est volontaire : les preuves doivent survivre
 * a la suppression des utilisateurs, societes et enregistrements metier.
 */
class m260913_120000_create_audit_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%audit_log}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->integer()->null(),
            'username' => $this->string(255)->null(),
            'company_id' => $this->integer()->null(),
            'company_name' => $this->string(255)->null(),
            'user_role' => $this->string(32)->null(),
            'action' => $this->string(64)->notNull(),
            'model' => $this->string(255)->null(),
            'record_id' => $this->string(255)->null(),
            // LONGTEXT est utilise explicitement pour rester compatible avec Yii 2.0.49.
            'old_values' => 'LONGTEXT NULL',
            'new_values' => 'LONGTEXT NULL',
            'ip_address' => $this->string(45)->null(),
            'country_code' => $this->string(2)->null(),
            'country_name' => $this->string(100)->null(),
            'city' => $this->string(100)->null(),
            'user_agent' => $this->string(1000)->null(),
            'request_method' => $this->string(10)->null(),
            'request_url' => $this->text()->null(),
            'request_id' => $this->char(32)->null(),
            'created_at' => $this->dateTime(6)->notNull(),
        ]);

        $this->createIndex('idx_audit_log_user', '{{%audit_log}}', ['user_role', 'user_id']);
        $this->createIndex('idx_audit_log_action_created', '{{%audit_log}}', ['action', 'created_at']);
        $this->createIndex('idx_audit_log_created', '{{%audit_log}}', ['created_at', 'id']);
        $this->createIndex('idx_audit_log_request', '{{%audit_log}}', ['request_id']);
        $this->createIndex('idx_audit_log_resource', '{{%audit_log}}', ['model', 'record_id']);
        $this->createIndex('idx_audit_log_company', '{{%audit_log}}', ['company_id']);
        $this->createIndex('idx_audit_log_ip', '{{%audit_log}}', ['ip_address']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%audit_log}}');
    }
}
