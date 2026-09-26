<?php

use yii\db\Migration;

/**
 * Crée le workflow contractuel des avenants à une Request déjà attribuée.
 *
 * La Request principale conserve son identifiant, son MRO et son statut
 * opérationnel. Toutes les décisions et tous les documents révisés sont
 * historisés ici afin de ne jamais écraser le périmètre, le PO ou le devis
 * qui restent applicables tant que l'avenant n'est pas entièrement accepté.
 */
class m260923_120000_create_request_change_workflow extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%request_change}}', [
            'id' => $this->primaryKey(),
            'request_id' => $this->integer()->notNull(),
            'mro_request_apply_id' => $this->integer()->notNull(),
            'ao_id' => $this->integer()->notNull(),
            'mro_id' => $this->integer()->notNull(),
            'version' => $this->integer()->notNull(),
            'status' => $this->string(40)->notNull(),
            'original_request_status' => $this->string(40)->notNull(),
            'reason' => $this->text()->notNull(),
            'added_tasks' => $this->text()->null(),
            'removed_tasks' => $this->text()->null(),
            'mro_rejection_reason' => $this->text()->null(),
            'revised_po' => $this->string(255)->null(),
            'po_rejection_reason' => $this->text()->null(),
            'quote_description' => $this->text()->null(),
            'quote_price' => $this->decimal(15, 2)->null(),
            'quote_currency' => $this->string(3)->null(),
            'revised_quote' => $this->string(255)->null(),
            'quote_rejection_reason' => $this->text()->null(),
            'requested_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'mro_reviewed_at' => $this->timestamp()->null(),
            'po_uploaded_at' => $this->timestamp()->null(),
            'po_reviewed_at' => $this->timestamp()->null(),
            'quote_submitted_at' => $this->timestamp()->null(),
            'quote_reviewed_at' => $this->timestamp()->null(),
            'completed_at' => $this->timestamp()->null(),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        /* Une version ne peut exister qu'une seule fois pour une même Request. */
        $this->createIndex(
            'ux_request_change_request_version',
            '{{%request_change}}',
            ['request_id', 'version'],
            true
        );
        $this->createIndex(
            'idx_request_change_mro_status',
            '{{%request_change}}',
            ['mro_id', 'status', 'updated_at']
        );
        $this->createIndex(
            'idx_request_change_application',
            '{{%request_change}}',
            ['mro_request_apply_id', 'id']
        );

        $this->addForeignKey(
            'fk_request_change_request',
            '{{%request_change}}',
            'request_id',
            '{{%requests}}',
            'request_id',
            'CASCADE',
            'RESTRICT'
        );
        $this->addForeignKey(
            'fk_request_change_application',
            '{{%request_change}}',
            'mro_request_apply_id',
            '{{%mro_request_apply}}',
            'id',
            'RESTRICT',
            'RESTRICT'
        );

        /*
         * HISTORIQUE DES VERSIONS : une resoumission ne remplace jamais la preuve
         * précédente. Le PO ou le devis rejeté reste donc consultable avec sa
         * décision et son motif, même après le dépôt d'une version corrigée.
         */
        $this->createTable('{{%request_change_document}}', [
            'id' => $this->primaryKey(),
            'request_change_id' => $this->integer()->notNull(),
            'document_type' => $this->string(16)->notNull(),
            'version' => $this->integer()->notNull(),
            'file_path' => $this->string(255)->null(),
            'description' => $this->text()->null(),
            'price' => $this->decimal(15, 2)->null(),
            'currency' => $this->string(3)->null(),
            'uploaded_by_type' => $this->string(8)->notNull(),
            'uploaded_by_id' => $this->integer()->notNull(),
            'review_status' => $this->string(16)->notNull()->defaultValue('pending'),
            'rejection_reason' => $this->text()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'reviewed_at' => $this->timestamp()->null(),
        ]);
        $this->createIndex(
            'ux_request_change_document_version',
            '{{%request_change_document}}',
            ['request_change_id', 'document_type', 'version'],
            true
        );
        $this->addForeignKey(
            'fk_request_change_document_change',
            '{{%request_change_document}}',
            'request_change_id',
            '{{%request_change}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_request_change_document_change', '{{%request_change_document}}');
        $this->dropTable('{{%request_change_document}}');
        $this->dropForeignKey('fk_request_change_application', '{{%request_change}}');
        $this->dropForeignKey('fk_request_change_request', '{{%request_change}}');
        $this->dropTable('{{%request_change}}');
    }
}
