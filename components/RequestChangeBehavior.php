<?php

namespace app\components;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;

/**
 * Observe un ActiveRecord lie a une demande et publie ses changements.
 *
 * Le comportement evite de disperser le meme INSERT technique dans tous les
 * controleurs. Les formulaires, statuts, notifications et redirections restent
 * inchanges : seuls les evenements ActiveRecord deja reussis sont observes.
 */
class RequestChangeBehavior extends Behavior
{
    /** @var string attribut contenant directement l'identifiant de la demande */
    public $requestIdAttribute = 'request_id';

    /** @var string prefixe fonctionnel utilise dans event_type */
    public $eventPrefix = 'request';

    /** @var callable|null resolution speciale lorsque request_id n'existe pas sur le modele */
    public $requestIdResolver;

    /**
     * Branche les trois moments qui peuvent rendre une liste obsolete.
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Une creation peut ajouter une ligne, un bouton, un PO, un rapport ou un
     * rendez-vous dans une liste deja ouverte par un autre utilisateur.
     */
    public function afterInsert(AfterSaveEvent $event): void
    {
        $this->publish($this->eventPrefix . '.created');
    }

    /**
     * Une mise a jour de statut est identifiee explicitement car elle peut faire
     * passer une demande d'une liste a une autre. Pour les autres modifications,
     * la liste connait seulement les noms de champs modifies, jamais leur contenu
     * potentiellement sensible.
     */
    public function afterUpdate(AfterSaveEvent $event): void
    {
        $changedFields = array_keys($event->changedAttributes);
        $payload = ['changed_fields' => $changedFields];
        $eventType = $this->eventPrefix . '.updated';

        if (array_key_exists('status', $event->changedAttributes)) {
            $eventType = $this->eventPrefix . '.status_changed';
            $payload['status_before'] = $event->changedAttributes['status'];
            $payload['status_after'] = $this->owner->getAttribute('status');
        }

        $this->publish($eventType, $payload);

        /*
         * PRIORITÉ INDÉPENDANTE DU STATUT : lorsqu'un même enregistrement modifie
         * aussi son statut, un second événement conserve la variation AOG/Urgent/
         * Routine. Les listes et futurs compteurs peuvent ainsi réagir sans déduire
         * la priorité depuis un événement de statut qui répond à un autre métier.
         */
        if (
            $this->eventPrefix === 'request'
            && array_key_exists('operational_priority', $event->changedAttributes)
        ) {
            $this->publish('request.priority_changed', [
                'priority_before' => $event->changedAttributes['operational_priority'],
                'priority_after' => $this->owner->getAttribute('operational_priority'),
                'response_required_minutes' => $this->owner->getAttribute('response_required_minutes'),
                'response_due_at_utc' => $this->owner->getAttribute('response_due_at_utc'),
            ]);
        }
    }

    /**
     * L'evenement est emis apres une suppression reussie. L'identifiant de la
     * demande reste disponible dans l'objet ActiveRecord meme si sa ligne SQL
     * vient de disparaitre, ce qui permet aux navigateurs de retirer leur ligne.
     */
    public function afterDelete(): void
    {
        $this->publish($this->eventPrefix . '.deleted');
    }

    /**
     * Construit et transmet l'evenement au journal central. Une panne du module
     * de synchronisation est journalisee mais ne doit jamais annuler a posteriori
     * une operation metier deja enregistree en dehors d'une transaction.
     */
    private function publish(string $eventType, array $payload = []): void
    {
        try {
            $requestId = $this->resolveRequestId();

            if ($requestId === null) {
                Yii::warning([
                    'message' => "Evenement de synchronisation ignore : demande introuvable.",
                    'model' => get_class($this->owner),
                    'event_type' => $eventType,
                ], __METHOD__);
                return;
            }

            /*
             * PÉRIMÈTRE INTERNE DE PROPRIÉTÉ : certains enregistrements doivent
             * encore être attribués après leur suppression SQL. Les identifiants AO
             * et MRO sont lus exclusivement depuis l'ActiveRecord serveur, ajoutés
             * au journal interne et ne sont jamais renvoyés par l'API au navigateur.
             */
            foreach (['ao_id' => 'scope_ao_id', 'mro_id' => 'scope_mro_id'] as $attribute => $payloadKey) {
                if (!$this->owner->hasAttribute($attribute)) {
                    continue;
                }

                $scopeId = filter_var(
                    $this->owner->getAttribute($attribute),
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if ($scopeId !== false) {
                    $payload[$payloadKey] = (int) $scopeId;
                }
            }

            RequestChangePublisher::publish(
                $requestId,
                $eventType,
                $this->normalizeSourceTable(),
                $this->resolveSourceId(),
                $payload
            );
        } catch (\Throwable $exception) {
            Yii::error([
                'message' => "Echec de publication d'un evenement de synchronisation.",
                'model' => get_class($this->owner),
                'event_type' => $eventType,
                'exception' => $exception->getMessage(),
            ], __METHOD__);
        }
    }

    /**
     * Recupere request_id directement ou appelle le resolver configure par le
     * modele. Le resultat est valide pour ne jamais publier un curseur orphelin.
     */
    private function resolveRequestId(): ?int
    {
        $value = is_callable($this->requestIdResolver)
            ? call_user_func($this->requestIdResolver, $this->owner)
            : $this->owner->getAttribute($this->requestIdAttribute);

        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? null : (int) $value;
    }

    /**
     * Retourne la cle primaire de la ressource source lorsqu'elle est simple.
     * Une cle composite reste volontairement nulle plutot que d'etre serialisee
     * dans un format ambigu pour les futurs endpoints.
     */
    private function resolveSourceId(): ?int
    {
        $primaryKey = $this->owner->getPrimaryKey();

        if (is_array($primaryKey)) {
            return null;
        }

        $primaryKey = filter_var($primaryKey, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $primaryKey === false ? null : (int) $primaryKey;
    }

    /**
     * Nettoie la notation Yii2 {{%table}} pour stocker un nom stable et court
     * qui pourra etre compare sans connaitre le prefixe SQL de l'hebergement.
     */
    private function normalizeSourceTable(): string
    {
        return trim(str_replace(['{{%', '{{', '}}'], '', $this->owner::tableName()), '%');
    }
}
