<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * PRÉFÉRENCES DE NOTIFICATION PAR DÉFAUT POUR LES COMPTES EXISTANTS (2026-11)
 *
 * Depuis le 2026-11-22, les préférences (tout activé) sont créées dès
 * l'inscription (AoProfile / MroProfile::afterSave). Les comptes plus anciens
 * qui n'ont jamais ouvert leur page de préférences n'ont aucune ligne : ils ne
 * recevaient donc aucune notification de demande.
 *
 * Cette migration ajoute une ligne « tout activé » UNIQUEMENT pour les profils
 * qui n'en ont pas. Les préférences déjà enregistrées ne sont jamais modifiées.
 * Rejouable : un second passage n'insère rien.
 *
 * Retour arrière : safeDown() ne supprime rien, car on ne peut pas distinguer
 * les lignes créées ici de celles enregistrées ensuite par les utilisateurs.
 */
class m261122_190000_create_default_notification_preferences extends Migration
{
    public function safeUp()
    {
        $aoCount = $this->insertMissing(
            '{{%ao_profiles}}',
            'ao_id',
            '{{%ao_notifications_preferences}}',
            \app\models\AoNotificationsPreferences::DEFAULTS
        );
        $mroCount = $this->insertMissing(
            '{{%mro_profiles}}',
            'mro_id',
            '{{%mro_notifications_preferences}}',
            \app\models\MroNotificationsPreferences::DEFAULTS
        );

        echo "    > préférences par défaut créées : {$aoCount} AO, {$mroCount} MRO.\n";
        return true;
    }

    public function safeDown()
    {
        echo "    > rien à annuler : les préférences créées restent en place.\n";
        return true;
    }

    private function insertMissing(string $profileTable, string $key, string $preferencesTable, array $defaults): int
    {
        if ($this->db->getTableSchema($profileTable, true) === null
            || $this->db->getTableSchema($preferencesTable, true) === null) {
            echo "    > table {$profileTable} ou {$preferencesTable} introuvable, ignorée.\n";
            return 0;
        }

        $missingIds = (new Query())
            ->select(['p.' . $key])
            ->from(['p' => $profileTable])
            ->leftJoin(['n' => $preferencesTable], 'n.' . $key . ' = p.' . $key)
            ->where(['n.' . $key => null])
            ->column($this->db);

        if ($missingIds === []) {
            return 0;
        }

        $columns = array_merge([$key], array_keys($defaults));
        $rows = [];
        foreach ($missingIds as $id) {
            $rows[] = array_merge([(int) $id], array_values($defaults));
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->batchInsert($preferencesTable, $columns, $chunk);
        }

        return count($rows);
    }
}
