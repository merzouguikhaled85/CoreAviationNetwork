<?php

use yii\db\Migration;

/**
 * Ajoute le suivi independant de l'accuse de reception adresse au demandeur.
 *
 * L'e-mail interne et la confirmation utilisateur peuvent ainsi etre retentes
 * separement sans dupliquer un message deja envoye avec succes.
 */
class m260912_130000_add_acknowledgement_to_support_ticket extends Migration
{
    /**
     * Ajoute uniquement des metadonnees d'envoi au ticket Support existant.
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%support_ticket}}',
            'acknowledgement_status',
            $this->string(24)->notNull()->defaultValue('pending')->after('email_error')
        );
        $this->addColumn(
            '{{%support_ticket}}',
            'acknowledgement_attempted_at',
            $this->dateTime()->null()->after('acknowledgement_status')
        );
        $this->addColumn(
            '{{%support_ticket}}',
            'acknowledgement_error',
            $this->text()->null()->after('acknowledgement_attempted_at')
        );

        /*
         * INDEX DE RELANCE :
         * il permettra a une tache future de retrouver rapidement les confirmations
         * en attente ou echouees sans parcourir tous les tickets.
         */
        $this->createIndex(
            'idx_support_ticket_ack_created',
            '{{%support_ticket}}',
            ['acknowledgement_status', 'created_at', 'id']
        );
    }

    /**
     * Annule seulement les champs de confirmation ajoutes par cette migration.
     */
    public function safeDown()
    {
        $this->dropIndex('idx_support_ticket_ack_created', '{{%support_ticket}}');
        $this->dropColumn('{{%support_ticket}}', 'acknowledgement_error');
        $this->dropColumn('{{%support_ticket}}', 'acknowledgement_attempted_at');
        $this->dropColumn('{{%support_ticket}}', 'acknowledgement_status');
    }
}
