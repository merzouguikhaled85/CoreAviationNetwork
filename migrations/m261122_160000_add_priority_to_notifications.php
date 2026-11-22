<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * PRIORITÉ DES NOTIFICATIONS (2026-11)
 *
 * Ajoute la colonne FACULTATIVE `notifications.priority` : priorité opérationnelle
 * de la demande concernée (`aog`, `urgent`, `routine`), NULL si la notification
 * ne concerne pas une demande identifiable. L'interface (cloche, toast, son)
 * l'utilise au lieu de chercher le mot « AOG » dans le texte du message.
 *
 * Reprise de l'existant : les notifications déjà enregistrées reçoivent la
 * priorité de la demande citée dans leur message (« request #123 »). Le calcul
 * est fait en PHP pour rester compatible MySQL / MariaDB quelle que soit la version.
 *
 * Compatibilité : `notifications` n'a pas de migration de création (schéma
 * historique) ; la colonne est ajoutée seulement si elle manque (rejouable).
 * Retour arrière : safeDown() supprime la colonne.
 */
class m261122_160000_add_priority_to_notifications extends Migration
{
    private const TABLE = '{{%notifications}}';

    public function safeUp()
    {
        $schema = $this->db->getTableSchema(self::TABLE, true);
        if ($schema === null) {
            echo "    > table notifications introuvable, migration ignorée.\n";
            return true;
        }

        if ($schema->getColumn('priority') === null) {
            $this->addColumn(self::TABLE, 'priority', $this->string(16)->null()->after('status'));
        }

        $this->backfillPriorities();
        return true;
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema(self::TABLE, true);
        if ($schema !== null && $schema->getColumn('priority') !== null) {
            $this->dropColumn(self::TABLE, 'priority');
        }
    }

    /**
     * Renseigne la priorité des notifications existantes à partir du numéro de
     * demande présent dans le message. Les messages sans numéro restent à NULL.
     */
    private function backfillPriorities(): void
    {
        $updated = 0;
        $rows = (new Query())
            ->select(['id', 'content'])
            ->from(self::TABLE)
            ->where(['priority' => null]);

        foreach ($rows->batch(500, $this->db) as $batch) {
            $requestIdsByNotification = [];
            foreach ($batch as $row) {
                if (preg_match('/request\s*#\s*(\d+)/i', (string) $row['content'], $match)) {
                    $requestIdsByNotification[(int) $row['id']] = (int) $match[1];
                }
            }
            if ($requestIdsByNotification === []) {
                continue;
            }

            $priorities = (new Query())
                ->select(['operational_priority', 'request_id'])
                ->from('{{%requests}}')
                ->where(['request_id' => array_values(array_unique($requestIdsByNotification))])
                ->indexBy('request_id')
                ->column($this->db);

            foreach ($requestIdsByNotification as $notificationId => $requestId) {
                $priority = strtolower((string) ($priorities[$requestId] ?? ''));
                if (in_array($priority, ['aog', 'urgent', 'routine'], true)) {
                    $this->db->createCommand()
                        ->update(self::TABLE, ['priority' => $priority], ['id' => $notificationId])
                        ->execute();
                    $updated++;
                }
            }
        }

        echo "    > priorité renseignée pour {$updated} notification(s) existante(s).\n";
    }
}
