<?php

use app\components\UrlIdHelper;
use app\models\AoRequestsApplications;
use app\models\Requests;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/*
 * FRAGMENT DE LA LISTE AO : ce fichier contient exclusivement le tableau et sa
 * pagination. Il est utilisé au premier rendu puis lors des actualisations AJAX,
 * ce qui garantit que les actions, statuts et règles d'affichage restent identiques.
 */
?>

<!-- Requests table card -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>

                            <?php if ($title !== "New requests"): ?>
                                <th>MRO</th>
                            <?php endif; ?>

                            <th>Aircraft Model</th>
                            <th>Aircraft Registration</th>
                            <th>Aircraft Serial Number</th>
                            <th>ETA</th>
                            <th>ETD</th>
                            <th>Maintenance Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                    // Prepare aircraft data
                                    $aircraft = $request->getAircraft()->one();

                                    // Prepare status display
                                    $status = $request->status;
                                    $formattedStatus = ucwords(str_replace('_', ' ', $status));
                                    $statusClass = 'status-' . Html::encode($status);

                                    /*
                                     * PRIORITÉ DANS LA LISTE AO : la couleur et l'icône
                                     * représentent uniquement l'urgence déclarée. Le délai
                                     * UTC est informatif et ne remplace jamais le statut.
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
                                     * FIN DU COMPTE À REBOURS : une demande fermée ou annulée ne peut plus
                                     * recevoir de réponse MRO. Le badge AOG/Urgent reste affiché pour conserver
                                     * le contexte opérationnel, mais « remaining/overdue » est masqué car il
                                     * n'a plus de valeur d'action et pourrait induire l'utilisateur en erreur.
                                     */
                                    $priorityDeadlineVisible = $priorityDue !== false
                                        && !in_array($status, [
                                            Requests::STATUS_CLOSED,
                                            Requests::STATUS_CANCELLED,
                                            'canceled',
                                        ], true);

                                    // Check if an application exists
                                    $existingApplication = AoRequestsApplications::find()
                                        ->where(['request_id' => $request->request_id])
                                        ->exists();

                                    $application = AoRequestsApplications::find()
                                        ->where(['request_id' => $request->request_id])
                                        ->orderBy(['id' => SORT_DESC])
                                        ->one();

                                    // Check report
                                    $report = \app\models\RepairReport::find()
                                        ->joinWith('mroRequestApply')
                                        ->where([
                                            'mro_request_apply.request_id' => $request->request_id
                                        ])
                                        ->one();

                                    $mroId = $report->mroRequestApply->mro_id ?? null;

                                    // Check feedback
                                    $existingfeedback = false;

                                    if ($mroId) {
                                        $existingfeedback = \app\models\Feedback::find()
                                            ->where([
                                                'request_id' => $request->request_id,
                                                'mro_id' => $mroId
                                            ])
                                            ->exists();
                                    }
                                ?>

                                <tr>
                                    <td data-label="Request ID">
                                       <?php
                                        $encodedId = UrlIdHelper::encode($request->request_id);
                                        ?>

                                        <?= Html::a(
                                            '#' . Html::encode($request->request_id),
                                            ['view', 'id' => $encodedId],
                                            ['class' => 'request-link']
                                        ) ?>
                                    </td>

                                    <?php if ($title !== "New requests"): ?>
                                        <td data-label="MRO">
                                            <?php
                                                $mro = $request->mroApplication->mro ?? null;

                                                echo $mro
                                                    ? Html::a(
                                                        Html::encode($mro->username),
                                                        ['mro-profile/view', 'id' => UrlIdHelper::encode($mro->mro_id)],
                                                        ['class' => 'mro-link']
                                                    )
                                                    : '<span class="text-danger">MRO Deleted</span>';
                                            ?>
                                        </td>
                                    <?php endif; ?>

                                    <td data-label="Aircraft Model">
                                        <?= $aircraft
                                            ? Html::encode($aircraft->model)
                                            : '<span class="text-danger">Aircraft deleted</span>'
                                        ?>
                                    </td>

                                    <td data-label="Aircraft Registration"><?= Html::encode($request->aircraft_registration) ?></td>
                                    <td data-label="Aircraft Serial Number"><?= Html::encode($request->serial_number) ?></td>

                                    <td data-label="ETA">
                                        <i class="bi bi-calendar-event text-primary"></i>
                                        <?= Html::encode($request->eta ? date('d M Y H:i', strtotime($request->eta)) : '-') ?>
                                    </td>

                                    <td data-label="ETD">
                                        <i class="bi bi-calendar-check text-success"></i>
                                        <?= Html::encode($request->etd ? date('d M Y H:i', strtotime($request->etd)) : '-') ?>
                                    </td>

                                    <td data-label="Maintenance Location" class="location-cell">
                                        <i class="bi bi-geo-alt text-danger"></i>
                                        <?= Html::encode($request->location) ?>
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
                                        <?php if ($request->status === 'answered'): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-reply-all"></i> Answered',
                                                ['check-applications', 'id' =>  $encodedId],
                                                ['class' => 'status-badge status-answered']
                                            ) ?>

                                        <?php elseif ($request->status === 'po_loaded'): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-file-earmark-check"></i> PO Loaded',
                                                ['view-po', 'id' =>  $encodedId],
                                                ['class' => 'status-badge status-po_loaded']
                                            ) ?>

                                        <?php elseif (in_array($request->status, ['report_submitted', 'work_started', 'closed', 'canceled'])): ?>
                                            <?php
                                                $hasReport = \app\models\RepairReport::find()
                                                    ->joinWith('mroRequestApply')
                                                    ->where([
                                                        'mro_request_apply.request_id' => $request->request_id
                                                    ])
                                                    ->exists();
                                            ?>

                                            <?= Html::a(
                                                $hasReport
                                                    ? '<i class="bi bi-file-text"></i> View CRS'
                                                    : '<i class="bi bi-hourglass-split"></i> Waiting for CRS',
                                                ['view-reports', 'id' => $encodedId],
                                                ['class' => 'status-badge ' . $statusClass]
                                            ) ?>

                                        <?php elseif ($request->status === 'update_request'): ?>
                                            <?= Html::a(
                                                '<i class="bi bi-pencil-square"></i> Update Request',
                                                [
                                                    'update',
                                                    'id' => $encodedId,
                                                    'status' => $request->status
                                                ],
                                                ['class' => 'status-badge status-update_request']
                                            ) ?>

                                        <?php else: ?>
                                            <span class="status-badge <?= $statusClass ?: 'status-default' ?>">
                                                <i class="bi bi-info-circle"></i>
                                                <?= Html::encode($formattedStatus) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="action-buttons">

                                            <!-- View button -->
                                            <?= Html::a(
                                                '<i class="bi bi-eye"></i>',
                                                ['view', 'id' => $encodedId],
                                                [
                                                    'class' => 'action-btn btn-view',
                                                    'title' => 'View',
                                                    'data-bs-toggle' => 'tooltip'
                                                ]
                                            ) ?>

                                            <!-- Update request button -->
                                            <?php if (in_array($request->status, ['work_accepted', 'work_started'])): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-pencil-square"></i>',
                                                    ['update-request', 'id' => $encodedId],
                                                    [
                                                        'class' => 'action-btn btn-update-request',
                                                        'title' => 'Update Request',
                                                        'data-bs-toggle' => 'tooltip'
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                            <!-- Contact button -->
                                            <?php if ($existingApplication && !in_array($request->status, ['answered', 'created', 'po_loaded'])): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-chat-dots"></i>',
                                                    ['contact', 'id' => UrlIdHelper::encode($application->application_id)],
                                                    [
                                                        'class' => 'action-btn btn-contact',
                                                        'title' => 'Contact',
                                                        'data-bs-toggle' => 'tooltip'
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                            <!-- Edit and delete buttons -->
                                            <?php if (in_array($request->status, ['answered', 'created', 'po_loaded'])): ?>
                                                <?= Html::a(
                                                    '<i class="bi bi-pencil"></i>',
                                                    ['update', 'id' => $encodedId],
                                                    [
                                                        'class' => 'action-btn btn-update',
                                                        'title' => 'Update',
                                                        'data-bs-toggle' => 'tooltip'
                                                    ]
                                                ) ?>

                                                <?= Html::a(
                                                    '<i class="bi bi-trash"></i>',
                                                    ['delete', 'id' => $encodedId],
                                                    [
                                                        'class' => 'action-btn btn-delete',
                                                        'title' => 'Delete',
                                                        'data-bs-toggle' => 'tooltip',
                                                        'data' => [
                                                            'confirm' => 'Request #' . $request->request_id . ' will be permanently deleted.',
                                                            'method' => 'post',
                                                        ],
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                            <!-- Feedback button -->
                                            <?php if (
                                                $title === 'Closed requests' &&
                                                $report &&
                                                $request->status === 'closed' &&
                                                !$existingfeedback &&
                                                $report->quote_approved == 1
                                            ): ?>
                                                <!-- FEEDBACK ENCODED ID 2026: never expose the numeric CRS report ID. -->
                                                <?= Html::a(
                                                    '<i class="bi bi-star-half"></i>',
                                                    ['provide-feedback', 'id' => UrlIdHelper::encode($report->repair_report_id)],
                                                    [
                                                        'class' => 'action-btn btn-feedback',
                                                        'title' => 'Provide Feedback',
                                                        'data-bs-toggle' => 'tooltip'
                                                    ]
                                                ) ?>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="<?= $title !== 'New requests' ? '11' : '10' ?>">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox" style="font-size: 35px;"></i>
                                        <div>No requests found.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                ]) ?>
            </div>
        </div>
