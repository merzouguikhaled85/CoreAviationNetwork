<?php

namespace app\components;

use app\models\AuditLog;
use Yii;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\Application as WebApplication;

/**
 * Enrichit et persiste tous les evenements d'audit techniques et semantiques.
 */
class AuditService extends Component
{
    public const CREATE = 'CREATE';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';
    public const LOGIN = 'LOGIN';
    public const LOGIN_FAILED = 'LOGIN_FAILED';
    public const LOGOUT = 'LOGOUT';

    public $excludedTables = [
        'audit_log',
        'migration',
        'request_change_event',
        'request_priority_history',
        'support_ticket_history',
    ];

    private $writing = false;

    public function isWriting(): bool
    {
        return $this->writing;
    }

    public function shouldAudit(ActiveRecord $model): bool
    {
        if ($model instanceof AuditLog) {
            return false;
        }

        return !in_array($model::tableName(), $this->excludedTables, true);
    }

    public function recordModel(string $action, ActiveRecord $model, array $oldValues, array $newValues): ?AuditLog
    {
        return $this->record($action, [
            'model' => get_class($model),
            'record_id' => $this->resolveRecordId($model),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Enregistre un evenement semantique ou d'authentification dans le meme flux.
     * Les valeurs explicites de l'auteur servent quand une connexion echoue avant
     * que Yii ne dispose d'une identite authentifiee.
     */
    public function record(string $action, array $event = []): ?AuditLog
    {
        if ($this->writing) {
            return null;
        }

        $this->writing = true;
        try {
            $actor = $this->resolveActor();
            $requestContext = Yii::$app->has('auditContext')
                ? Yii::$app->auditContext->getContext()
                : [];

            $oldValues = $event['old_values'] ?? [];
            $newValues = $event['new_values'] ?? [];
            $oldValues = is_array($oldValues) ? Yii::$app->auditRedactor->redactArray($oldValues) : [];
            $newValues = is_array($newValues) ? Yii::$app->auditRedactor->redactArray($newValues) : [];

            $attributes = array_merge($actor, $requestContext, [
                'action' => strtoupper($action),
                'model' => $event['model'] ?? null,
                'record_id' => isset($event['record_id']) ? (string) $event['record_id'] : null,
                'old_values' => $oldValues ? Json::encode($oldValues) : null,
                'new_values' => $newValues ? Json::encode($newValues) : null,
                'created_at' => $this->utcNow(),
            ]);

            foreach (['user_id', 'username', 'company_id', 'company_name', 'user_role'] as $key) {
                if (array_key_exists($key, $event)) {
                    $attributes[$key] = $event[$key];
                }
            }

            $audit = new AuditLog();
            $audit->setAttributes($attributes, false);
            if (!$audit->insert(false)) {
                Yii::error('Audit event could not be inserted.', 'audit');
                return null;
            }

            return $audit;
        } catch (\Throwable $exception) {
            Yii::error([
                'message' => 'Audit subsystem failure.',
                'exception' => get_class($exception),
                'detail' => $exception->getMessage(),
            ], 'audit');
            return null;
        } finally {
            $this->writing = false;
        }
    }

    private function resolveActor(): array
    {
        $actor = [
            'user_id' => null,
            'username' => null,
            'company_id' => null,
            'company_name' => null,
            'user_role' => null,
        ];

        if (!(Yii::$app instanceof WebApplication) || !Yii::$app->has('user') || Yii::$app->user->isGuest) {
            return $actor;
        }

        $identity = Yii::$app->user->identity;
        if ($identity === null) {
            return $actor;
        }

        $role = $this->inferRole($identity);
        $idProperty = $role === 'admin' ? 'admin_id' : ($role === 'mro' ? 'mro_id' : ($role === 'ao' ? 'ao_id' : null));

        $actor['user_role'] = $role;
        $actor['user_id'] = $idProperty !== null && isset($identity->{$idProperty})
            ? (int) $identity->{$idProperty}
            : ($identity->getId() !== null ? (int) $identity->getId() : null);
        $actor['username'] = !empty($identity->username)
            ? (string) $identity->username
            : (!empty($identity->email) ? (string) $identity->email : null);

        if (in_array($role, ['ao', 'mro'], true)) {
            $actor['company_id'] = $actor['user_id'];
            $actor['company_name'] = !empty($identity->company_name) ? (string) $identity->company_name : null;
        } elseif ($role === 'admin') {
            $actor['company_name'] = 'Core Aviation Network';
        }

        return $actor;
    }

    private function inferRole($identity): ?string
    {
        if (!empty($identity->admin_id)) {
            return 'admin';
        }
        if (!empty($identity->mro_id)) {
            return 'mro';
        }
        if (!empty($identity->ao_id)) {
            return 'ao';
        }

        if (Yii::$app instanceof WebApplication && Yii::$app->has('session')) {
            $role = strtolower((string) Yii::$app->session->get('user_type'));
            return in_array($role, ['admin', 'mro', 'ao'], true) ? $role : null;
        }

        return null;
    }

    private function resolveRecordId(ActiveRecord $model): ?string
    {
        $primaryKey = $model->getPrimaryKey(true);
        if (!$primaryKey) {
            return null;
        }
        if (count($primaryKey) === 1) {
            $value = reset($primaryKey);
            return $value === null ? null : (string) $value;
        }

        return Json::encode($primaryKey);
    }

    private function utcNow(): string
    {
        $now = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
        if ($now === false) {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }

        return $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }
}
