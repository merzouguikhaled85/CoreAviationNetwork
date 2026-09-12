<?php

/** @var yii\web\View $this */
/** @var \app\models\Appointment[] $appointments */
/** @var \yii\data\Pagination|null $pagination */
/** @var array<int,string> $aoMap */
/** @var array<int,\app\models\Requests> $requestMap */

use app\components\UrlIdHelper;
use app\models\AoProfile;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/*
 * RAFRAICHISSEMENT CIBLE DES RENDEZ-VOUS MRO : le fragment est autonome afin
 * qu'une réponse AJAX et le chargement complet partagent strictement le même HTML.
 */
$appointments = $appointments ?? [];
$pagination = $pagination ?? null;
$aoMap = $aoMap ?? [];
$requestMap = $requestMap ?? [];

/* PRESENTATION DU STATUT : mêmes couleurs et icônes après chaque remplacement AJAX. */
$statusBadge = static function ($status): string {
    $status = (string) $status;
    $statusLower = strtolower(trim($status));
    $statusKey = str_replace(' ', '_', $statusLower);
    $formattedStatus = ucwords(str_replace('_', ' ', $status));
    $class = 'status-badge status-default';
    $icon = 'bi-info-circle';
    if ($statusLower === 'confirmed') {
        $class = 'status-badge status-confirmed'; $icon = 'bi-check-circle';
    } elseif ($statusLower === 'waiting response' || $statusLower === 'waiting_response') {
        $class = 'status-badge status-waiting_response'; $icon = 'bi-hourglass-split';
    } elseif ($statusLower === 'reschedule' || $statusLower === 'rescheduled') {
        $class = 'status-badge status-reschedule'; $icon = 'bi-arrow-repeat';
    } elseif ($statusLower === 'cancelled' || $statusLower === 'canceled') {
        $class = 'status-badge status-canceled'; $icon = 'bi-x-circle';
    } elseif ($statusKey !== '') {
        $class = 'status-badge status-' . Html::encode($statusKey);
    }
    return '<span class="' . Html::encode($class) . '"><i class="bi ' . Html::encode($icon) . '"></i>' . Html::encode($formattedStatus) . '</span>';
};

/* IDENTITE AO : utilise le préchargement et conserve un repli sûr pour le rendu isolé. */
$getAoUsername = static function ($aoId) use ($aoMap): string {
    if (!$aoId) {
        return 'N/A';
    }
    if (isset($aoMap[(int) $aoId])) {
        return (string) $aoMap[(int) $aoId];
    }
    $ao = AoProfile::findOne($aoId);
    return $ao ? (string) $ao->username : 'N/A';
};

/* FORMAT DES DATES : conserve la présentation actuelle sans toucher aux fuseaux métier. */
$formatDate = static function ($date): string {
    if (empty($date)) {
        return '<span class="text-muted fw-bold">N/A</span>';
    }
    return '<span class="date-text"><i class="bi bi-calendar-event text-primary"></i>' . Html::encode(date('d M Y H:i', strtotime($date))) . '</span>';
};
?>

<!-- LISTE MRO : les conditions des boutons restent strictement inchangées. -->
<div class="content-card">
    <div class="table-responsive-custom">
        <table class="table requests-table">
            <thead><tr><th>Request ID</th><th>Request Details</th><th>AO</th><th>Date</th><th>Status</th><th>Reschedule Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                    $status = strtolower(trim((string) $appointment->status));
                    $hasAction = false;
                    $requestModel = $requestMap[(int) $appointment->request_id] ?? null;
                    $aircraft = $requestModel ? $requestModel->aircraft : null;
                    ?>
                    <tr>
                        <td data-label="Request ID"><span class="request-link">#<?= Html::encode($appointment->request_id) ?></span></td>
                        <td data-label="Request Details" class="request-details-cell"><?= $this->render('../shared/_request-summary', ['requestModel' => $requestModel, 'aircraft' => $aircraft]) ?></td>
                        <td data-label="AO"><span class="mro-link"><?= Html::encode($getAoUsername($appointment->ao_id)) ?></span></td>
                        <td data-label="Date"><?= $formatDate($appointment->appointment_date) ?></td>
                        <td data-label="Status"><?= $statusBadge($appointment->status) ?></td>
                        <td data-label="Reschedule Date"><?= $formatDate($appointment->reschedule_appointment_date) ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <?php if ($status === 'reschedule' || $status === 'rescheduled'): ?>
                                    <?php $hasAction = true; ?>
                                    <?= Html::a('<i class="bi bi-check-circle"></i>', ['accept-reschedule', 'id' => UrlIdHelper::encode($appointment->id)], [
                                        'class' => 'action-btn btn-view', 'title' => 'Accept Reschedule',
                                        'aria-label' => 'Accept Reschedule', 'data-bs-toggle' => 'tooltip',
                                        'data' => ['confirm' => 'Are you sure you want to accept this rescheduled appointment date?', 'method' => 'post'],
                                    ]) ?>
                                <?php endif; ?>
                                <?php if ($status !== 'confirmed'): ?>
                                    <?php $hasAction = true; ?>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['remove-appointment', 'id' => UrlIdHelper::encode($appointment->id)], [
                                        'class' => 'action-btn btn-delete', 'title' => 'Remove Appointment',
                                        'aria-label' => 'Remove Appointment', 'data-bs-toggle' => 'tooltip',
                                        'data' => ['confirm' => 'Are you sure you want to remove this appointment?', 'method' => 'post'],
                                    ]) ?>
                                <?php endif; ?>
                                <?php if (!$hasAction): ?><span class="no-actions"><i class="bi bi-lock-fill"></i> No actions</span><?php endif; ?>
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
        <div class="pagination-container"><?= LinkPager::widget(['pagination' => $pagination]) ?></div>
    <?php endif; ?>
</div>
