<?php

/** @var yii\web\View $this */
/** @var \app\models\Appointment[] $appointments */
/** @var \yii\data\Pagination|null $pagination */
/** @var array<int,string> $mroMap */
/** @var array<int,\app\models\Requests> $requestMap */

use app\components\UrlIdHelper;
use app\models\MroProfile;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/*
 * RAFRAICHISSEMENT CIBLE DES RENDEZ-VOUS AO : ce fragment contient uniquement
 * la carte de liste susceptible d'etre remplacee par AJAX. Les valeurs par
 * defaut garantissent aussi un rendu fiable lors d'un test ou d'une page vide.
 */
$appointments = $appointments ?? [];
$pagination = $pagination ?? null;
$mroMap = $mroMap ?? [];
$requestMap = $requestMap ?? [];

/*
 * PRESENTATION DU STATUT : cette fonction reste locale au fragment pour que le
 * rendu AJAX produise les memes badges que le premier chargement, sans dependre
 * d'une variable definie dans la vue parente.
 */
$statusBadge = static function ($status): string {
    $status = (string) $status;
    $statusLower = strtolower(trim($status));
    $statusKey = str_replace(' ', '_', $statusLower);
    $formattedStatus = ucwords(str_replace('_', ' ', $status));
    $class = 'status-badge status-default';
    $icon = 'bi-info-circle';

    if ($statusLower === 'confirmed') {
        $class = 'status-badge status-confirmed';
        $icon = 'bi-check-circle';
    } elseif ($statusLower === 'waiting response' || $statusLower === 'waiting_response') {
        $class = 'status-badge status-waiting_response';
        $icon = 'bi-hourglass-split';
    } elseif ($statusLower === 'reschedule' || $statusLower === 'rescheduled') {
        $class = 'status-badge status-reschedule';
        $icon = 'bi-arrow-repeat';
    } elseif ($statusLower === 'cancelled' || $statusLower === 'canceled') {
        $class = 'status-badge status-canceled';
        $icon = 'bi-x-circle';
    } elseif ($statusKey !== '') {
        $class = 'status-badge status-' . Html::encode($statusKey);
    }

    return '<span class="' . Html::encode($class) . '"><i class="bi ' . Html::encode($icon) . '"></i>'
        . Html::encode($formattedStatus) . '</span>';
};

/*
 * IDENTITE MRO : la table prechargee evite une requete SQL par ligne. Le repli
 * conserve le comportement historique si ce fragment est appele isolément.
 */
$getMroUsername = static function ($mroId) use ($mroMap): string {
    if (!$mroId) {
        return 'MRO Deleted';
    }
    if (isset($mroMap[(int) $mroId])) {
        return (string) $mroMap[(int) $mroId];
    }
    $mro = MroProfile::findOne($mroId);
    return $mro ? (string) $mro->username : 'MRO Deleted';
};

/* FORMAT DES DATES : applique la meme representation au rendu initial et AJAX. */
$formatDate = static function ($date): string {
    if (empty($date)) {
        return '<span class="text-muted fw-bold">N/A</span>';
    }
    return '<span class="date-text"><i class="bi bi-calendar-event text-primary"></i>'
        . Html::encode(date('d M Y H:i', strtotime($date))) . '</span>';
};
?>

<!--
    LISTE AO : toutes les actions et leurs conditions restent identiques au code
    métier existant ; seul ce conteneur visuel sera remplacé après un événement.
-->
<div class="content-card">
    <div class="table-responsive-custom">
        <table class="table requests-table">
            <thead>
                <tr>
                    <th>Request ID</th><th>Request Details</th><th>MRO</th><th>Date</th>
                    <th>Status</th><th>Reschedule Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                    $status = strtolower(trim((string) $appointment->status));
                    $mroUsername = $getMroUsername($appointment->mro_id);
                    $hasAction = false;
                    $requestModel = $requestMap[(int) $appointment->request_id] ?? null;
                    $aircraft = $requestModel ? $requestModel->aircraft : null;
                    ?>
                    <tr>
                        <td data-label="Request ID"><span class="request-link">#<?= Html::encode($appointment->request_id) ?></span></td>
                        <td data-label="Request Details" class="request-details-cell">
                            <?= $this->render('../shared/_request-summary', ['requestModel' => $requestModel, 'aircraft' => $aircraft]) ?>
                        </td>
                        <td data-label="MRO">
                            <?php if ($mroUsername !== 'MRO Deleted'): ?>
                                <span class="mro-link"><?= Html::encode($mroUsername) ?></span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">MRO Deleted</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Date"><?= $formatDate($appointment->appointment_date) ?></td>
                        <td data-label="Status"><?= $statusBadge($appointment->status) ?></td>
                        <td data-label="Reschedule Date"><?= $formatDate($appointment->reschedule_appointment_date ?? null) ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <?php if ($status !== 'confirmed'): ?>
                                    <?php $hasAction = true; ?>
                                    <?= Html::a('<i class="bi bi-x-circle"></i>', ['cancel', 'id' => UrlIdHelper::encode($appointment->id)], [
                                        'class' => 'action-btn btn-delete', 'title' => 'Cancel Appointment',
                                        'aria-label' => 'Cancel Appointment', 'data-bs-toggle' => 'tooltip',
                                        'data' => ['confirm' => 'Request #' . $appointment->request_id . ' / Appointment #' . $appointment->id . ' will be cancelled.', 'method' => 'post'],
                                    ]) ?>
                                    <?= Html::a('<i class="bi bi-check-circle"></i>', ['confirm', 'id' => UrlIdHelper::encode($appointment->id)], [
                                        'class' => 'action-btn btn-view', 'title' => 'Confirm Appointment',
                                        'aria-label' => 'Confirm Appointment', 'data-bs-toggle' => 'tooltip',
                                        'data' => ['confirm' => 'Request #' . $appointment->request_id . ' / Appointment #' . $appointment->id . ' will be confirmed.', 'method' => 'post'],
                                    ]) ?>
                                <?php endif; ?>
                                <?php if ($status === 'waiting response' || $status === 'waiting_response'): ?>
                                    <?php $hasAction = true; ?>
                                    <?= Html::a('<i class="bi bi-arrow-repeat"></i>', ['reschedule', 'id' => UrlIdHelper::encode($appointment->id)], [
                                        'class' => 'action-btn btn-update', 'title' => 'Reschedule Appointment',
                                        'aria-label' => 'Reschedule Appointment', 'data-bs-toggle' => 'tooltip',
                                    ]) ?>
                                <?php endif; ?>
                                <?php if (!$hasAction): ?>
                                    <span class="no-actions"><i class="bi bi-lock-fill"></i> No actions</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="7"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 35px;"></i><div>No appointments found.</div></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pagination)): ?>
        <div class="pagination-container">
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination justify-content-center'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'page-link'],
            ]) ?>
        </div>
    <?php endif; ?>
</div>
