<?php

use yii\db\Migration;

/** Adds a revocable authentication generation to every identity table. */
class m261008_190000_add_auth_version_to_profiles extends Migration
{
    private const TABLES = ['admin_profiles', 'mro_profiles', 'ao_profiles'];

    public function safeUp()
    {
        foreach (self::TABLES as $table) {
            $this->addColumn("{{%{$table}}}", 'auth_version', $this->integer()->notNull()->defaultValue(1));
        }
    }

    public function safeDown()
    {
        foreach (self::TABLES as $table) {
            $this->dropColumn("{{%{$table}}}", 'auth_version');
        }
    }
}
