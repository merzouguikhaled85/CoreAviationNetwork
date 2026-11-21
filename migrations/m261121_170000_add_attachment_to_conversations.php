<?php

use yii\db\Migration;

/**
 * PIÈCES JOINTES DANS LES CONVERSATIONS (2026-11)
 *
 * Ajoute à la table `conversations` quatre colonnes FACULTATIVES décrivant un
 * fichier joint à un message. Aucune donnée existante n'est modifiée : les
 * anciens messages gardent ces colonnes à NULL.
 *
 * Le fichier lui-même n'est PAS stocké en base ni dans web/uploads (public) :
 * il est enregistré dans @runtime/chat-attachments (bloqué par .htaccess et
 * ignoré par Git), puis servi uniquement par ConversationsController::actionAttachment
 * après vérification que l'utilisateur participe à la conversation.
 *
 * Compatibilité : la table `conversations` n'a pas de migration de création
 * (schéma historique). La migration vérifie donc l'existence de chaque colonne
 * pour pouvoir être rejouée sans erreur.
 *
 * Retour arrière : safeDown() supprime les 4 colonnes. Les fichiers déjà
 * stockés dans @runtime/chat-attachments restent sur le disque.
 */
class m261121_170000_add_attachment_to_conversations extends Migration
{
    private const TABLE = '{{%conversations}}';

    public function safeUp()
    {
        $columns = [
            // Nom du fichier stocké (aléatoire), relatif à @runtime/chat-attachments.
            'attachment_path' => $this->string(255)->null(),
            // Nom d'origine, affiché à l'utilisateur et proposé au téléchargement.
            'attachment_name' => $this->string(255)->null(),
            // Type MIME détecté côté serveur (pas celui annoncé par le navigateur).
            'attachment_mime' => $this->string(100)->null(),
            // Taille en octets.
            'attachment_size' => $this->integer()->null(),
        ];

        $schema = $this->db->getTableSchema(self::TABLE, true);
        foreach ($columns as $name => $type) {
            if ($schema === null || $schema->getColumn($name) === null) {
                $this->addColumn(self::TABLE, $name, $type);
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema(self::TABLE, true);
        foreach (['attachment_size', 'attachment_mime', 'attachment_name', 'attachment_path'] as $name) {
            if ($schema !== null && $schema->getColumn($name) !== null) {
                $this->dropColumn(self::TABLE, $name);
            }
        }
    }
}
