<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%terms}}`.
 */
class m240528_154637_create_terms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%terms}}', [
            'id' => $this->primaryKey(),
            'content' => $this->text()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%terms}}');
    }
}
