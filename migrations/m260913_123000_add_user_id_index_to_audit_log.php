<?php

use yii\db\Migration;

/**
 * Ajoute l'index simple necessaire aux recherches par utilisateur sans filtre de role.
 */
class m260913_123000_add_user_id_index_to_audit_log extends Migration
{
    public function safeUp()
    {
        $this->createIndex('idx_audit_log_user_id', '{{%audit_log}}', ['user_id']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_audit_log_user_id', '{{%audit_log}}');
    }
}
