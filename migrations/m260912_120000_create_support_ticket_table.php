<?php

use yii\db\Migration;

/**
 * Cree le stockage durable des demandes envoyees depuis la bulle Support.
 *
 * Le ticket est enregistre avant toute tentative SMTP. Cette separation garantit
 * qu'une indisponibilite du serveur mail ne peut ni perdre le message utilisateur
 * ni annuler une operation metier en cours sur la plateforme.
 */
class m260912_120000_create_support_ticket_table extends Migration
{
    /**
     * Installe la table, les informations de suivi du mail et les index utiles au
     * traitement futur d'une boite de support ou d'une file d'attente.
     */
    public function safeUp()
    {
        $this->createTable('{{%support_ticket}}', [
            'id' => $this->bigPrimaryKey(),
            'user_type' => $this->string(16)->null(),
            'user_id' => $this->integer()->null(),
            'name' => $this->string(120)->notNull(),
            'email' => $this->string(190)->notNull(),
            'category' => $this->string(32)->notNull(),
            'priority' => $this->string(16)->notNull()->defaultValue('normal'),
            'subject' => $this->string(190)->notNull(),
            'message' => $this->text()->notNull(),
            'request_reference' => $this->string(100)->null(),
            'attachment_path' => $this->string(500)->null(),
            'attachment_original_name' => $this->string(255)->null(),
            'attachment_mime_type' => $this->string(100)->null(),
            'attachment_size' => $this->integer()->null(),
            'status' => $this->string(24)->notNull()->defaultValue('new'),
            'email_status' => $this->string(24)->notNull()->defaultValue('pending'),
            'email_dispatch_token_hash' => $this->char(64)->notNull(),
            'email_attempted_at' => $this->dateTime()->null(),
            'email_error' => $this->text()->null(),
            'ip_hash' => $this->char(64)->null(),
            'user_agent' => $this->string(255)->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        /*
         * INDEX DE TRAITEMENT :
         * le premier sert a retrouver les mails en attente ou en echec ; le second
         * prepare une future liste support par statut et date sans modifier le
         * fonctionnement des requests, applications ou rendez-vous.
         */
        $this->createIndex(
            'idx_support_ticket_email_created',
            '{{%support_ticket}}',
            ['email_status', 'created_at', 'id']
        );
        $this->createIndex(
            'idx_support_ticket_status_created',
            '{{%support_ticket}}',
            ['status', 'created_at', 'id']
        );
    }

    /**
     * Retire uniquement la fonctionnalite Support. Les autres tables metier ne
     * possedent volontairement aucune cle etrangere vers cette table.
     */
    public function safeDown()
    {
        $this->dropIndex('idx_support_ticket_status_created', '{{%support_ticket}}');
        $this->dropIndex('idx_support_ticket_email_created', '{{%support_ticket}}');
        $this->dropTable('{{%support_ticket}}');
    }
}
