<?php

use yii\db\Migration;

/**
 * Cree les reponses administratives associees aux tickets Support.
 *
 * Une reponse est enregistree avant l'appel SMTP. Cette conception garantit que le
 * texte redige n'est jamais perdu si le serveur mail est momentanement indisponible.
 */
class m260912_150000_create_support_ticket_reply_table extends Migration
{
    /**
     * Installe la conversation sortante et ses informations de livraison. Le statut
     * demande n'est applique au ticket qu'apres confirmation reelle de l'envoi.
     */
    public function safeUp()
    {
        $this->createTable('{{%support_ticket_reply}}', [
            'id' => $this->bigPrimaryKey(),
            'support_ticket_id' => $this->bigInteger()->notNull(),
            'admin_id' => $this->integer()->null(),
            'message' => $this->text()->notNull(),
            'status_after_send' => $this->string(24)->notNull()->defaultValue('waiting_user'),
            'email_status' => $this->string(24)->notNull()->defaultValue('pending'),
            'email_error' => $this->text()->null(),
            'dispatch_token_hash' => $this->char(64)->notNull(),
            'sent_at' => $this->dateTime()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        /*
         * INTEGRITE ET PERFORMANCE : la relation vers le ticket InnoDB est garantie par
         * MySQL. admin_profiles reste volontairement sans cle etrangere car cette table
         * historique utilise MyISAM ; l'existence de l'admin est controlee en PHP.
         */
        $this->createIndex(
            'idx_support_ticket_reply_ticket_created',
            '{{%support_ticket_reply}}',
            ['support_ticket_id', 'created_at', 'id']
        );
        $this->createIndex(
            'idx_support_ticket_reply_delivery',
            '{{%support_ticket_reply}}',
            ['email_status', 'created_at']
        );
        $this->addForeignKey(
            'fk_support_ticket_reply_ticket',
            '{{%support_ticket_reply}}',
            'support_ticket_id',
            '{{%support_ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /** Retire uniquement la conversation Support ajoutee par cette migration. */
    public function safeDown()
    {
        $this->dropForeignKey('fk_support_ticket_reply_ticket', '{{%support_ticket_reply}}');
        $this->dropTable('{{%support_ticket_reply}}');
    }
}
