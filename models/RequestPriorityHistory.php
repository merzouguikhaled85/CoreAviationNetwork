<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Représente une modification historisée de la priorité opérationnelle.
 *
 * Ce modèle ne décide d'aucune transition : il conserve seulement les valeurs
 * réellement enregistrées sur la demande et l'identité serveur de leur auteur.
 */
class RequestPriorityHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%request_priority_history}}';
    }

    /**
     * Les règles protègent la cohérence de l'historique si ce modèle est réutilisé
     * ultérieurement par une commande console ou une interface d'administration.
     */
    public function rules()
    {
        return [
            [['request_id', 'new_priority'], 'required'],
            [['request_id', 'previous_response_minutes', 'new_response_minutes', 'actor_id'], 'integer'],
            [['previous_due_at_utc', 'new_due_at_utc', 'changed_at'], 'safe'],
            [['previous_priority', 'new_priority', 'actor_type'], 'string', 'max' => 16],
        ];
    }
}
