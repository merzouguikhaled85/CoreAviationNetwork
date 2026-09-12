<?php

use app\components\UrlIdHelper;
use app\models\Aircrafts;
use app\models\AoProfile;
use app\models\Requests;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/*
 * FRAGMENT DES DEMANDES DISPONIBLES MRO : le tableau, ses actions et sa
 * pagination sont rendus par ce même fichier au chargement initial et pendant
 * les mises à jour AJAX. Les fonctions de présentation reçues de la vue parente
 * ne modifient aucune règle d'éligibilité calculée dans le contrôleur.
 */

/*
 * CONTEXTE DE RECHERCHE : le rendu complet transmet searchQuery tandis que le
 * contrôleur AJAX transmet search. Cette normalisation accepte les deux sources
 * et garantit le même état vide filtré dans les deux modes de rendu.
 */
$searchQuery = trim((string) ($searchQuery ?? $search ?? ''));

/*
 * PRÉSENTATION DU STATUT : le texte et la classe CSS sont dérivés de la valeur
 * existante sans modifier celle-ci. Une valeur absente reçoit le libellé neutre
 * N/A afin que le fragment ne produise jamais de balise incomplète.
 */
$statusBadge = function ($status) {
    $rawStatus = trim((string) $status);
    $statusKey = strtolower(str_replace(' ', '_', $rawStatus));
    $formattedStatus = $rawStatus !== '' ? ucwords(str_replace('_', ' ', $rawStatus)) : 'N/A';
    $class = $statusKey !== '' ? 'status-badge status-' . $statusKey : 'status-badge status-default';

    return '<span class="' . Html::encode($class) . '"><i class="bi bi-info-circle"></i>' .
        Html::encode($formattedStatus) .
        '</span>';
};

/*
 * PRÉSENTATION DES DATES : l'icône ETA ou ETD est ajoutée uniquement à
 * l'affichage. La valeur métier reste inchangée et les dates absentes sont
 * représentées clairement par N/A.
 */
$formatDate = function ($date, $iconClass = 'bi-calendar-event', $textClass = 'text-primary') {
    if (empty($date)) {
        return '<span class="text-muted fw-bold">N/A</span>';
    }

    return '<span class="date-text"><i class="bi ' . Html::encode($iconClass) . ' ' . Html::encode($textClass) . '"></i>' .
        Html::encode(date('d M Y H:i', strtotime($date))) .
        '</span>';
};

/*
 * LIBELLÉS LIÉS : ces fonctions reproduisent les lectures déjà utilisées par la
 * page historique. Elles protègent le rendu lorsqu'un profil AO, un aéronef ou un
 * aéroport lié a été supprimé, sans assouplir les critères d'accès à la demande.
 */
$getAoUsername = function ($aoId) {
    if (!$aoId) {
        return 'N/A';
    }

    $aoProfile = AoProfile::findOne($aoId);

    return $aoProfile ? $aoProfile->username : 'N/A';
};

$getAircraftModel = function ($aircraftId) {
    if (!$aircraftId) {
        return 'N/A';
    }

    $aircraft = Aircrafts::findOne($aircraftId);

    return $aircraft ? $aircraft->model : 'N/A';
};

$getLocationName = function ($request) {
    if (isset($request->destinationAirport) && $request->destinationAirport) {
        return $request->destinationAirport->airport_name ?: 'N/A';
    }

    if (!empty($request->destination)) {
        return (string) $request->destination;
    }

    return 'N/A';
};
?>

<!-- Main content card: same request table/card responsive structure -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table align-middle">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Aircraft Operator / CAMO</th>
                            <th>Aircraft Model</th>
                            <th>Registration</th>
                            <th>Serial No.</th>
                            <th>ETA</th>
                            <th>ETD</th>
                            <th>Maintenance Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th style="min-width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                    // Business data is read safely; original routes and fields are preserved.
                                    $aoUsername = $getAoUsername($request->ao_id ?? null);
                                    $aircraftModel = $getAircraftModel($request->aircraft_id ?? null);
                                    $locationName = $getLocationName($request);

                                    /*
                                     * PRIORITÉ DANS LA LISTE MRO : l'AOG devient visible
                                     * avant l'ouverture de la demande. L'échéance affichée
                                     * reste en UTC pour éviter toute ambiguïté entre pays.
                                     */
                                    $priority = $request->operational_priority ?: Requests::PRIORITY_ROUTINE;
                                    $priorityIcons = [
                                        Requests::PRIORITY_AOG => 'bi-exclamation-octagon',
                                        Requests::PRIORITY_URGENT => 'bi-lightning-charge',
                                        Requests::PRIORITY_ROUTINE => 'bi-calendar-check',
                                    ];
                                    $priorityDue = !empty($request->response_due_at_utc)
                                        ? strtotime($request->response_due_at_utc . ' UTC')
                                        : false;
                                    $priorityIsOverdue = $priorityDue !== false && $priorityDue < time();

                                    /*
                                     * DEMANDES TERMINÉES : le MRO conserve la lecture de la priorité historique,
                                     * mais le compteur de réponse disparaît dès que le dossier est fermé ou annulé.
                                     * Cette règle est uniquement visuelle et ne modifie ni l'échéance enregistrée,
                                     * ni l'historique de priorité, ni le statut de la demande.
                                     */
                                    $priorityDeadlineVisible = $priorityDue !== false
                                        && !in_array((string) $request->status, [
                                            Requests::STATUS_CLOSED,
                                            Requests::STATUS_CANCELLED,
                                            'canceled',
                                        ], true);
                                ?>

                                <tr>
                                    <td data-label="Request ID">

 <?php
                                        $encodedId = UrlIdHelper::encode($request->request_id);
                                        ?>

                                        <?= Html::a(
                                            Html::encode($request->request_id),
                                            ['requests/view', 'id' => $encodedId],
                                            ['class' => 'request-link']
                                        ) ?>
                                    </td>

                                    <td data-label="Aircraft Operator / CAMO">
                                        <span class="mro-link"><?= Html::encode($aoUsername) ?></span>
                                    </td>

                                    <td data-label="Aircraft Model">
                                        <?= Html::encode($aircraftModel) ?>
                                    </td>

                                    <td data-label="Registration">
                                        <?= Html::encode($request->aircraft_registration ?: 'N/A') ?>
                                    </td>

                                    <td data-label="Serial No.">
                                        <?= Html::encode($request->serial_number ?: 'N/A') ?>
                                    </td>

                                    <td data-label="ETA">
                                        <?= $formatDate($request->eta ?? null, 'bi-calendar-event', 'text-primary') ?>
                                    </td>

                                    <td data-label="ETD">
                                        <?= $formatDate($request->etd ?? null, 'bi-calendar-check', 'text-success') ?>
                                    </td>

                                    <td data-label="Maintenance Location" class="location-cell">
                                        <i class="bi bi-geo-alt text-danger"></i>
                                        <span class="location-text" title="<?= Html::encode($locationName) ?>">
                                            <?= Html::encode($locationName) ?>
                                        </span>
                                    </td>

                                    <td data-label="Priority">
                                        <div class="operational-priority-display">
                                            <span class="operational-priority-badge priority-<?= Html::encode($priority) ?>">
                                                <i class="bi <?= Html::encode($priorityIcons[$priority] ?? 'bi-calendar-check') ?>"></i>
                                                <?= Html::encode($request->getOperationalPriorityLabel()) ?>
                                            </span>
                                            <?php if ($priorityDeadlineVisible): ?>
                                                <small
                                                    class="operational-priority-deadline<?= $priorityIsOverdue ? ' is-overdue' : '' ?>"
                                                    data-response-deadline-utc="<?= Html::encode(gmdate('c', $priorityDue)) ?>"
                                                    title="Response due <?= Html::encode(gmdate('d M Y H:i', $priorityDue)) ?> UTC"
                                                >
                                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                                    <span data-countdown-label>
                                                        <?= $priorityIsOverdue ? 'Response overdue' : 'Calculating…' ?>
                                                    </span>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td data-label="Status">
                                        <?= $statusBadge($request->status ?? null) ?>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <?= Html::a(
                                                '<i class="bi bi-check2-circle"></i>',
                                                ['apply', 'id' =>  $encodedId],
                                                [
                                                    'class' => 'action-btn btn-apply',
                                                    'title' => 'Apply',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'aria-label' => 'Apply',
                                                ]
                                            ) ?>

                                            <!-- Recommend action preserved and kept disabled as in the original file.
                                            <?= Html::a(
                                                '<i class="bi bi-person-plus-fill"></i>',
                                                ['recommend-mro', 'id' => $request->request_id],
                                                [
                                                    'class' => 'action-btn btn-recommend',
                                                    'title' => 'Recommend Other MRO',
                                                    'data-bs-toggle' => 'tooltip',
                                                    'aria-label' => 'Recommend Other MRO',
                                                ]
                                            ) ?>
                                            -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes"></i>
                                        <div class="empty-state-title">No New Requests</div>
                                        <div class="empty-state-text">
                                            <?php if ($searchQuery !== ''): ?>
                                                No requests found for your current search.
                                            <?php else: ?>
                                                You currently have no new AOG or maintenance requests.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination: displayed only when the controller provides a pagination object -->
            <?php if (isset($pagination) && !empty($pagination)): ?>
                <div class="pagination-container">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
