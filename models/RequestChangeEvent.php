<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Represente un changement technique susceptible de modifier une liste.
 *
 * @property int $id
 * @property int $request_id
 * @property string $event_type
 * @property string $source_table
 * @property int|null $source_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $payload_json
 * @property string $created_at
 */
class RequestChangeEvent extends ActiveRecord
{
    /**
     * Associe explicitement ce modele au journal central de synchronisation.
     */
    public static function tableName()
    {
        return '{{%request_change_event}}';
    }

    /**
     * Valide uniquement des metadonnees techniques courtes. Le contenu metier
     * complet n'est jamais copie dans le journal afin de limiter son volume et
     * d'eviter l'exposition accidentelle de donnees sensibles dans les reponses.
     */
    public function rules()
    {
        return [
            [['request_id', 'event_type', 'source_table'], 'required'],
            [['request_id', 'source_id', 'actor_id'], 'integer'],
            [['payload_json'], 'string'],
            [['event_type', 'source_table'], 'string', 'max' => 64],
            [['actor_type'], 'string', 'max' => 32],
            [['created_at'], 'safe'],
        ];
    }
}
