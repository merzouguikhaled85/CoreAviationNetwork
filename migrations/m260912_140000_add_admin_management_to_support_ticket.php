<?php

use yii\db\Migration;

/**
 * Ajoute l'assignation administrative et la piste d'audit des tickets Support.
 *
 * Cette migration reste limitee au module Support : aucune relation n'est creee
 * vers les requests de maintenance, les applications, les PO ou les rapports CRS.
 */
class m260912_140000_add_admin_management_to_support_ticket extends Migration
{
    /**
     * Installe le responsable courant sur le ticket et une table d'evenements
     * immuable pour conserver qui a modifie quel statut et a quel moment.
     */
    public function safeUp()
    {
        /*
         * REPRISE APRES ECHEC DDL MYSQL : MySQL valide immediatement ADD COLUMN et
         * CREATE INDEX meme dans safeUp(). Ces verifications rendent donc la migration
         * relancable si une installation a ete interrompue entre deux instructions.
         */
        $ticketSchema = $this->db->schema->getTableSchema('{{%support_ticket}}', true);
        if ($ticketSchema->getColumn('assigned_admin_id') === null) {
            $this->addColumn(
                '{{%support_ticket}}',
                'assigned_admin_id',
                $this->integer()->null()->after('user_id')
            );
        }
        if (!$this->indexExists('{{%support_ticket}}', 'idx_support_ticket_assigned_status')) {
            $this->createIndex(
                'idx_support_ticket_assigned_status',
                '{{%support_ticket}}',
                ['assigned_admin_id', 'status', 'created_at']
            );
        }

        /*
         * COMPATIBILITE AVEC LE SCHEMA HISTORIQUE : admin_profiles utilise MyISAM,
         * moteur qui ne prend pas en charge les cles etrangeres. L'integrite de
         * l'affectation est donc verifiee dans le controleur, sans convertir une table
         * d'authentification existante et sans changer le comportement des comptes.
         */

        $this->createTable('{{%support_ticket_history}}', [
            'id' => $this->bigPrimaryKey(),
            'support_ticket_id' => $this->bigInteger()->notNull(),
            'admin_id' => $this->integer()->null(),
            'action' => $this->string(40)->notNull(),
            'old_status' => $this->string(24)->null(),
            'new_status' => $this->string(24)->null(),
            'old_assigned_admin_id' => $this->integer()->null(),
            'new_assigned_admin_id' => $this->integer()->null(),
            'comment' => $this->string(500)->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        /*
         * INDEX ET INTEGRITE :
         * l'historique est lu du plus recent au plus ancien par ticket. La suppression
         * exceptionnelle d'un ticket supprime sa piste, celle d'un admin garde l'action.
         */
        $this->createIndex(
            'idx_support_ticket_history_ticket_created',
            '{{%support_ticket_history}}',
            ['support_ticket_id', 'created_at', 'id']
        );
        $this->addForeignKey(
            'fk_support_ticket_history_ticket',
            '{{%support_ticket_history}}',
            'support_ticket_id',
            '{{%support_ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Retire uniquement les structures ajoutees pour la gestion administrative.
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_support_ticket_history_ticket', '{{%support_ticket_history}}');
        $this->dropTable('{{%support_ticket_history}}');
        $this->dropIndex('idx_support_ticket_assigned_status', '{{%support_ticket}}');
        $this->dropColumn('{{%support_ticket}}', 'assigned_admin_id');
    }

    /**
     * Detecte un index par son nom dans la base courante. Cette methode sert uniquement
     * a rendre la migration sure apres une interruption partielle de MySQL.
     */
    private function indexExists($table, $indexName)
    {
        $rawTable = $this->db->schema->getRawTableName($table);
        return (bool) $this->db->createCommand(
            'SELECT COUNT(*) FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index',
            [':table' => $rawTable, ':index' => $indexName]
        )->queryScalar();
    }
}
