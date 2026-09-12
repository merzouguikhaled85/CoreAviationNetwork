<?php

namespace app\components;

use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\web\Application as WebApplication;

/**
 * Centralise la publication des changements visibles dans les listes.
 *
 * Le service reste independant du transport : le polling lit aujourd'hui la
 * table SQL, mais un futur SSE ou WebSocket pourra consommer les memes evenements
 * sans modifier les actions metier qui les produisent.
 */
final class RequestChangePublisher
{
    /**
     * Enregistre un evenement minimal sur la connexion Yii2 courante.
     *
     * Si l'appelant a ouvert une transaction, cet INSERT participe naturellement
     * a cette transaction. L'evenement ne sera donc visible qu'apres le commit et
     * disparaitra avec le rollback en cas d'echec de l'operation principale.
     *
     * @return int identifiant/curseur du nouvel evenement
     */
    public static function publish(
        int $requestId,
        string $eventType,
        string $sourceTable = 'requests',
        ?int $sourceId = null,
        array $payload = []
    ): int {
        /*
         * VALIDATION DEFENSIVE : ces valeurs deviennent des cles de filtrage.
         * On refuse donc les identifiants invalides et les libelles libres afin
         * de garder un journal coherent et exploitable par l'endpoint AJAX.
         */
        if ($requestId <= 0) {
            throw new InvalidArgumentException('Un identifiant de demande positif est obligatoire.');
        }

        if (!preg_match('/^[a-z0-9._-]{1,64}$/', $eventType)) {
            throw new InvalidArgumentException("Le type d'evenement de synchronisation est invalide.");
        }

        if (!preg_match('/^[a-z0-9_]{1,64}$/', $sourceTable)) {
            throw new InvalidArgumentException('La table source de synchronisation est invalide.');
        }

        /*
         * ACTEUR COURANT : l'identite est derivee exclusivement de la session
         * serveur. Elle ne provient jamais du formulaire ou de l'URL, ce qui
         * empeche un client de falsifier l'auteur d'un changement.
         */
        [$actorType, $actorId] = self::resolveActor();

        /*
         * CHARGE UTILE LIMITEE : seuls des indicateurs techniques non sensibles
         * doivent etre places ici. Le HTML et les informations autorisees seront
         * recalcules ensuite par le controleur de synchronisation selon le role.
         */
        $payloadJson = $payload === []
            ? null
            : Json::encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Yii::$app->db->createCommand()->insert('{{%request_change_event}}', [
            'request_id' => $requestId,
            'event_type' => $eventType,
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'payload_json' => $payloadJson,
        ])->execute();

        return (int) Yii::$app->db->getLastInsertID();
    }

    /**
     * Determine le profil connecte sans supposer que l'identifiant global User
     * est egal a la cle AO ou MRO. En console, aucune session web n'est ouverte :
     * l'acteur reste alors volontairement vide pour les scripts d'administration.
     */
    private static function resolveActor(): array
    {
        if (!(Yii::$app instanceof WebApplication)) {
            return [null, null];
        }

        $session = Yii::$app->session;
        $actorType = strtolower((string) $session->get('user_type', ''));

        if ($actorType === 'ao') {
            return ['ao', self::positiveIntegerOrNull($session->get('ao_id'))];
        }

        if ($actorType === 'mro') {
            return ['mro', self::positiveIntegerOrNull($session->get('mro_id'))];
        }

        if ($actorType === 'admin') {
            $identityId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
            return ['admin', self::positiveIntegerOrNull($identityId)];
        }

        return [null, null];
    }

    /**
     * Normalise un identifiant de session et remplace toute valeur absente ou
     * non positive par null pour respecter le schema nullable du journal.
     */
    private static function positiveIntegerOrNull($value): ?int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? null : (int) $value;
    }
}
