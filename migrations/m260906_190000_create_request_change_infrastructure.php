<?php

use yii\db\Migration;

/**
 * Met en place le socle SQL utilise par la synchronisation automatique des listes.
 *
 * Cette migration ne modifie aucune regle metier : elle ajoute uniquement les
 * informations techniques necessaires pour dater les changements, retrouver
 * rapidement les lignes visibles et publier un curseur commun aux ecrans AO/MRO.
 */
class m260906_190000_create_request_change_infrastructure extends Migration
{
    /**
     * Ajoute les colonnes et index dans un ordre compatible avec MySQL partage.
     * Les timestamps sont geres par MySQL afin que les appels save(false) et les
     * mises a jour SQL directes restent eux aussi detectables.
     */
    public function safeUp()
    {
        /*
         * HORODATAGE DES DEMANDES : la table requests ne possedait aucun repere
         * temporel. created_at date la creation et updated_at evolue a chaque
         * UPDATE, meme lorsque l'ecriture ne passe pas par la validation Yii2.
         */
        $this->addColumn(
            '{{%requests}}',
            'created_at',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
        );
        $this->addColumn(
            '{{%requests}}',
            'updated_at',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP')
        );

        /*
         * HORODATAGE DES PROPOSITIONS MRO : une modification de prix, devise,
         * description ou piece jointe doit pouvoir invalider la ligne affichee
         * sans attendre un changement de statut de la demande principale.
         */
        $this->addColumn(
            '{{%mro_request_apply}}',
            'created_at',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
        );
        $this->addColumn(
            '{{%mro_request_apply}}',
            'updated_at',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP')
        );

        /*
         * JOURNAL CENTRAL : chaque operation metier publiera une ligne courte.
         * Il n'y a volontairement pas de cle etrangere vers requests : un
         * evenement de suppression doit rester lisible apres la disparition de
         * la demande afin que les navigateurs puissent retirer la ligne.
         */
        $this->createTable('{{%request_change_event}}', [
            'id' => $this->bigPrimaryKey(),
            'request_id' => $this->integer()->notNull(),
            'event_type' => $this->string(64)->notNull(),
            'source_table' => $this->string(64)->notNull(),
            'source_id' => $this->integer()->null(),
            'actor_type' => $this->string(32)->null(),
            'actor_id' => $this->integer()->null(),
            'payload_json' => $this->text()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        /*
         * INDEX DU JOURNAL : le premier accelere la lecture incrementale par
         * demande et curseur. Le second permet une purge future par anciennete
         * sans parcourir toute la table sur l'hebergement partage.
         */
        $this->createIndex(
            'idx_request_change_event_request_cursor',
            '{{%request_change_event}}',
            ['request_id', 'id']
        );
        $this->createIndex(
            'idx_request_change_event_created_at',
            '{{%request_change_event}}',
            'created_at'
        );

        /*
         * INDEX DES LISTES : ils correspondent aux filtres les plus frequents
         * des ecrans AO et MRO. Ils reduisent le cout du rafraichissement sans
         * changer l'ordre, la pagination ou les conditions fonctionnelles.
         */
        $this->createIndex('idx_requests_ao_status_request', '{{%requests}}', ['ao_id', 'status', 'request_id']);
        $this->createIndex('idx_mro_request_apply_mro_request_id', '{{%mro_request_apply}}', ['mro_id', 'request_id', 'id']);
        $this->createIndex('idx_ao_requests_applications_application_updated', '{{%ao_requests_applications}}', ['application_id', 'updated_at']);
        $this->createIndex('idx_repair_report_application_updated', '{{%repair_report}}', ['mro_request_apply_id', 'updated_at']);
        $this->createIndex('idx_appointment_ao_status_updated', '{{%appointment}}', ['ao_id', 'status', 'updated_at']);
        $this->createIndex('idx_appointment_mro_status_updated', '{{%appointment}}', ['mro_id', 'status', 'updated_at']);
        $this->createIndex('idx_conversations_chat_timestamp', '{{%conversations}}', ['chat_id', 'timestamp']);
    }

    /**
     * Retire uniquement les elements techniques ajoutes par safeUp().
     * L'ordre inverse evite de laisser des index qui referencent des colonnes
     * deja supprimees pendant un retour de migration.
     */
    public function safeDown()
    {
        $this->dropIndex('idx_conversations_chat_timestamp', '{{%conversations}}');
        $this->dropIndex('idx_appointment_mro_status_updated', '{{%appointment}}');
        $this->dropIndex('idx_appointment_ao_status_updated', '{{%appointment}}');
        $this->dropIndex('idx_repair_report_application_updated', '{{%repair_report}}');
        $this->dropIndex('idx_ao_requests_applications_application_updated', '{{%ao_requests_applications}}');
        $this->dropIndex('idx_mro_request_apply_mro_request_id', '{{%mro_request_apply}}');
        $this->dropIndex('idx_requests_ao_status_request', '{{%requests}}');

        $this->dropTable('{{%request_change_event}}');

        $this->dropColumn('{{%mro_request_apply}}', 'updated_at');
        $this->dropColumn('{{%mro_request_apply}}', 'created_at');
        $this->dropColumn('{{%requests}}', 'updated_at');
        $this->dropColumn('{{%requests}}', 'created_at');
    }
}
