<?php

use yii\db\Migration;

/**
 * Ajoute la priorité opérationnelle sans modifier le workflow des demandes.
 *
 * Les statuts existants restent inchangés. La priorité sert uniquement à qualifier
 * l'urgence opérationnelle, à calculer une échéance de réponse et à conserver un
 * historique métier distinct du journal technique de synchronisation AJAX.
 */
class m260910_190000_add_operational_priority_to_requests extends Migration
{
    /**
     * Installe les nouveaux champs avec « routine » comme valeur sûre pour toutes
     * les demandes existantes. Les échéances sont stockées en UTC afin de rester
     * comparables indépendamment du fuseau de l'AO, du MRO ou de l'aéroport.
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%requests}}',
            'operational_priority',
            $this->string(16)->notNull()->defaultValue('routine')->after('status')
        );
        $this->addColumn(
            '{{%requests}}',
            'response_required_minutes',
            $this->smallInteger()->null()->after('operational_priority')
        );
        $this->addColumn(
            '{{%requests}}',
            'response_due_at_utc',
            $this->dateTime()->null()->after('response_required_minutes')
        );

        /*
         * INDEX DE PILOTAGE : cet ordre accélère les filtres par priorité et la
         * détection des AOG dont l'échéance approche, sans remplacer les index de
         * statut et de propriétaire déjà utilisés par les listes AO/MRO.
         */
        $this->createIndex(
            'idx_requests_operational_priority_due',
            '{{%requests}}',
            ['operational_priority', 'response_due_at_utc', 'request_id']
        );

        /*
         * HISTORIQUE MÉTIER PERMANENT : contrairement au journal de polling qui
         * pourra être purgé, cette table garde la décision de l'utilisateur, son
         * auteur et les anciennes/nouvelles valeurs pour assurer la traçabilité.
         */
        $this->createTable('{{%request_priority_history}}', [
            'id' => $this->bigPrimaryKey(),
            'request_id' => $this->integer()->notNull(),
            'previous_priority' => $this->string(16)->null(),
            'new_priority' => $this->string(16)->notNull(),
            'previous_response_minutes' => $this->smallInteger()->null(),
            'new_response_minutes' => $this->smallInteger()->null(),
            'previous_due_at_utc' => $this->dateTime()->null(),
            'new_due_at_utc' => $this->dateTime()->null(),
            'actor_type' => $this->string(16)->null(),
            'actor_id' => $this->integer()->null(),
            'changed_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx_request_priority_history_request_changed',
            '{{%request_priority_history}}',
            ['request_id', 'changed_at', 'id']
        );
    }

    /**
     * Annule uniquement cette fonctionnalité. L'historique est retiré avant les
     * colonnes sources afin de garantir un retour de migration propre et prévisible.
     */
    public function safeDown()
    {
        $this->dropIndex('idx_request_priority_history_request_changed', '{{%request_priority_history}}');
        $this->dropTable('{{%request_priority_history}}');
        $this->dropIndex('idx_requests_operational_priority_due', '{{%requests}}');
        $this->dropColumn('{{%requests}}', 'response_due_at_utc');
        $this->dropColumn('{{%requests}}', 'response_required_minutes');
        $this->dropColumn('{{%requests}}', 'operational_priority');
    }
}
