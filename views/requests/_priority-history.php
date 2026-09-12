<?php

use app\models\Requests;
use yii\helpers\Html;

/**
 * HISTORIQUE DE PRIORITÉ — AFFICHAGE UNIQUEMENT
 *
 * Ce fragment reçoit une demande déjà autorisée par le contrôleur et ses entrées
 * historisées. Il ne propose aucune écriture et ne modifie aucun état métier.
 *
 * @var app\models\Requests $requestModel
 * @var app\models\RequestPriorityHistory[] $priorityHistory
 * @var array $operationalPriorityIcons
 */
?>

<!--
    CHRONOLOGIE DE PRIORITÉ : ce panneau est replié par défaut pour
    conserver une page légère. Il affiche les décisions enregistrées
    sans proposer d'action et sans recalculer les valeurs historiques.
-->
<details class="priority-history-panel">
    <summary class="priority-history-summary">
        <span class="priority-history-summary-icon">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
        </span>
        <span class="priority-history-summary-copy">
            <span>Priority History</span>
            <small>
                <?= count($priorityHistory) ?>
                <?= count($priorityHistory) === 1 ? 'recorded change' : 'recorded changes' ?>
            </small>
        </span>
        <i class="bi bi-chevron-down priority-history-chevron" aria-hidden="true"></i>
    </summary>

    <div class="priority-history-content">
        <?php if (empty($priorityHistory)): ?>
            <div class="priority-history-empty">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>
                    No priority change recorded yet. Current priority:
                    <?= Html::encode($requestModel->getOperationalPriorityLabel()) ?>.
                </span>
            </div>
        <?php else: ?>
            <div class="priority-history-timeline">
                <?php foreach ($priorityHistory as $historyEntry): ?>
                    <?php
                        /*
                         * LIBELLÉS D'UNE ENTRÉE : les valeurs inconnues
                         * reçoivent un texte neutre. L'auteur provient
                         * exclusivement de l'identité stockée côté serveur.
                         */
                        $historyOptions = Requests::getOperationalPriorityOptions();
                        $previousPriorityLabel = $historyEntry->previous_priority
                            ? ($historyOptions[$historyEntry->previous_priority] ?? ucfirst($historyEntry->previous_priority))
                            : null;
                        $newPriorityLabel = $historyOptions[$historyEntry->new_priority]
                            ?? ucfirst((string) $historyEntry->new_priority);
                        $historyIcon = $operationalPriorityIcons[$historyEntry->new_priority]
                            ?? 'bi-calendar-check';
                        $actorType = strtoupper(trim((string) $historyEntry->actor_type));
                        $actorLabel = $actorType !== '' ? $actorType : 'System';
                        if ($historyEntry->actor_id) {
                            $actorLabel .= ' #' . $historyEntry->actor_id;
                        }
                        $historyChangedAt = $historyEntry->changed_at
                            ? date('d M Y H:i', strtotime($historyEntry->changed_at))
                            : 'Date unavailable';
                        $historyDueTimestamp = $historyEntry->new_due_at_utc
                            ? strtotime($historyEntry->new_due_at_utc . ' UTC')
                            : false;
                    ?>
                    <article class="priority-history-entry">
                        <span class="priority-history-marker priority-<?= Html::encode($historyEntry->new_priority) ?>">
                            <i class="bi <?= Html::encode($historyIcon) ?>" aria-hidden="true"></i>
                        </span>

                        <div class="priority-history-entry-body">
                            <div class="priority-history-entry-head">
                                <span class="priority-history-transition">
                                    <?php if ($previousPriorityLabel === null): ?>
                                        Initial priority:
                                        <strong><?= Html::encode($newPriorityLabel) ?></strong>
                                    <?php else: ?>
                                        <?= Html::encode($previousPriorityLabel) ?>
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        <strong><?= Html::encode($newPriorityLabel) ?></strong>
                                    <?php endif; ?>
                                </span>
                                <time><?= Html::encode($historyChangedAt) ?></time>
                            </div>

                            <div class="priority-history-meta">
                                <span>
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <?= Html::encode($actorLabel) ?>
                                </span>
                                <?php if ($historyEntry->new_response_minutes): ?>
                                    <span>
                                        <i class="bi bi-stopwatch" aria-hidden="true"></i>
                                        <?= Html::encode((string) $historyEntry->new_response_minutes) ?> min response
                                    </span>
                                <?php endif; ?>
                                <?php if ($historyDueTimestamp !== false): ?>
                                    <span>
                                        <i class="bi bi-globe2" aria-hidden="true"></i>
                                        Due <?= Html::encode(gmdate('d M Y H:i', $historyDueTimestamp)) ?> UTC
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</details>

