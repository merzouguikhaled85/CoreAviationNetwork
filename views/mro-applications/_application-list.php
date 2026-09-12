<?php

use app\components\UrlIdHelper;
use app\models\AoRequestsApplications;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/*
 * FRAGMENT DES APPLICATIONS MRO : le tableau conserve toutes les décisions
 * conditionnelles déjà en place (PO, rendez-vous, contact, rapport et feedback).
 * Le contrôleur fournit uniquement les enregistrements filtrés et paginés ; ce
 * fichier sert au rendu initial comme au remplacement AJAX du seul conteneur.
 */
?>

<!-- Requests table card -->
        <div class="content-card">
            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>AO</th>
                            <th>Request Details</th>
                            <th>Maintenance Location</th>
                            <th>ETA · Arrival</th>
                            <th>ETD · Departure</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($appliedRequests)): ?>
                            <?php foreach ($appliedRequests as $mroRequestApplication): ?>
                                <?php
                                    // The controller sends MroRequestApply records in `$appliedRequests`.
                                    // Do not loop on `$requests` here, otherwise the table stays empty.
                                    $request = $mroRequestApplication->getRequest()->one();

                                    if ($request === null) {
                                        continue;
                                    }

                                    // Prepare aircraft data safely.
                                    $aircraft = $request->getAircraft()->one();

                                    // Prepare AO data safely.
                                    $ao = $request->aO ?? null;

                                    // Latest AO application / PO linked to this MRO application.
                                    $aoRequestApplication = AoRequestsApplications::find()
                                        ->where(['application_id' => $mroRequestApplication->id])
                                        ->orderBy(['id' => SORT_DESC])
                                        ->one();

                                    $hasPo = $aoRequestApplication && !empty($aoRequestApplication->po);

                                    // Check report for this exact MRO application.
                                    $report = \app\models\RepairReport::find()
                                        ->where(['mro_request_apply_id' => $mroRequestApplication->id])
                                        ->orderBy(['repair_report_id' => SORT_DESC])
                                        ->one();

                                    // Prepare status display after checking PO and report.
                                    // This keeps the old workflow while preserving the new design.
                                    $status = (string) $request->status;

                                    // Old workflow: after AO uploads the PO, the MRO must see PO Loaded
                                    // and the Accept PO action, even if the request status is still answered.
                                    if ($status === 'answered' && $hasPo) {
                                        $status = 'po_loaded';
                                    }

                                    // Old workflow: if a repair report exists, show Report Submitted
                                    // unless the request is already closed or canceled.
                                    if ($report && !in_array($status, ['closed', 'canceled'], true)) {
                                        $status = 'report_submitted';
                                    }

                                    $formattedStatus = ucwords(str_replace('_', ' ', $status));
                                    $statusClass = 'status-' . Html::encode($status);

                                    // Request ID utilise dans la colonne Status et la colonne Actions.
                                    // Important: on le declare ici avant son premier usage.
                                    $reqId = $request->request_id ?? null;
                                    
                                        $encodedId = UrlIdHelper::encode($request->request_id);
                                        // MRO ACTION IDS 2026: keep request and application tokens distinct.
                                        $encodedRequestId = $encodedId;
                                        $encodedApplicationId = UrlIdHelper::encode($mroRequestApplication->id);
                                        

                                    // Check feedback for closed request.
                                    $existingfeedback = \app\models\Feedback::find()
                                        ->where([
                                            'request_id' => $request->request_id,
                                            'mro_id' => $mroRequestApplication->mro_id,
                                        ])
                                        ->exists();
                                ?>

                                <tr>
                                    <td data-label="Request ID">
                                        <?php $encodedId = UrlIdHelper::encode($request->request_id); ?>
                                        <?= Html::a(
                                            '#' . Html::encode($request->request_id),
                                            ['requests/view', 'id' => $encodedId],
                                            [
                                                'class' => 'request-link',
                                                'data' => [
                                                    'confirm' => 'Open request #' . $request->request_id . ' details?',
                                                    'swal-title' => 'Open request details?',
                                                    'swal-text' => 'You will be redirected to the selected request application details.',
                                                    'swal-icon' => 'info',
                                                    'swal-confirm-text' => '<i class="bi bi-eye"></i> Open',
                                                    'swal-confirm-class' => 'swal-action-confirm swal-action-info',
                                                ],
                                            ]
                                        ) ?>
                                    </td>

                                    <td data-label="AO">
                                        <?php if ($ao): ?>
                                            <?= Html::encode($ao->username ?? $ao->email ?? ('AO #' . $ao->ao_id)) ?>
                                        <?php else: ?>
                                            <span class="text-danger">AO Deleted</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Request Details">
                                        <!-- SHARED REQUEST DETAILS: compact aircraft row; complete data opens in the modal. -->
                                        <?= $this->render('../shared/_request-summary', [
                                            'requestModel' => $request,
                                            'aircraft' => $aircraft,
                                        ]) ?>
                                    </td>
                                    <!-- SHARED OPERATIONAL COLUMNS: operational data remains in dedicated columns. -->
                                    <td data-label="Maintenance Location">
                                        <span class="can-location-column" title="<?= Html::encode($request->location ?: 'N/A') ?>">
                                            <i class="bi bi-geo-alt"></i><?= Html::encode($request->location ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    <td data-label="ETA · Arrival">
                                        <span class="can-date-column is-eta">
                                            <i class="bi bi-box-arrow-in-down-right"></i><?= Html::encode($request->eta ? date('d M Y H:i', strtotime($request->eta)) : 'N/A') ?>
                                        </span>
                                    </td>
                                    <td data-label="ETD · Departure">
                                        <span class="can-date-column is-etd">
                                            <i class="bi bi-box-arrow-up-right"></i><?= Html::encode($request->etd ? date('d M Y H:i', strtotime($request->etd)) : 'N/A') ?>
                                        </span>
                                    </td>
<td data-label="Status">
    <?php if ($status === 'answered'): ?>

        <?= Html::a(
            '<i class="bi bi-reply-fill"></i> Answered',
            ['view-answer', 'id' => UrlIdHelper::encode($mroRequestApplication->id)],
            [
                'class' => 'status-badge status-answered',
            ]
        ) ?>

    <?php elseif ($status === 'po_loaded'): ?>

        <?= Html::a(
            '<i class="bi bi-file-earmark-check"></i> PO Loaded',
            ['view-po', 'id' => $encodedApplicationId],
            [
                'class' => 'status-badge status-po_loaded',
                'data' => [
                    'confirm' => 'View PO for request #' . $request->request_id . '?',
                    'swal-title' => 'Open PO?',
                    'swal-text' => 'You will be redirected to the PO page for this request.',
                    'swal-icon' => 'info',
                    'swal-confirm-text' => '<i class="bi bi-file-earmark-check"></i> Open PO',
                    'swal-confirm-class' => 'swal-action-confirm swal-action-info',
                ],
            ]
        ) ?>

    <?php elseif ($status === 'report_submitted'): ?>
  <?php $encodedId = UrlIdHelper::encode($mroRequestApplication->id); ?>
        <?= Html::a(
            '<i class="bi bi-file-text"></i> View CRSs',
            ['view-reports', 'id' => $encodedId],
            [
                'class' => 'status-badge status-report_submitted',
                'data' => [
                    'confirm' => 'View submitted report for request #' . $request->request_id . '?',
                    'swal-title' => 'Open report?',
                    'swal-text' => 'You will be redirected to the submitted report page.',
                    'swal-icon' => 'info',
                    'swal-confirm-text' => '<i class="bi bi-file-text"></i> Open report',
                    'swal-confirm-class' => 'swal-action-confirm swal-action-info',
                ],
            ]
        ) ?>

    <?php elseif ($status === 'update_request'): ?>

        <?php if ($reqId !== null): ?>
            <?= Html::a(
                '<i class="bi bi-pencil-square"></i> View Updated Request',
                ['requests/view', 'id' => $encodedId],
                [
                    'class' => 'status-badge status-update_request',
                ]
            ) ?>
        <?php else: ?>
            <span class="status-badge status-update_request">
                <i class="bi bi-pencil-square"></i> Update Request
            </span>
        <?php endif; ?>

    <?php elseif ($status === 'update_request_accepted'): ?>
 <?php
                                        $encodedId = UrlIdHelper::encode($mroRequestApplication->id);
                                        ?>
        <?= Html::a(
            '<i class="bi bi-pencil-square"></i> Update Quotation',
            ['mro-requests/applyupdate', 'id' => $encodedId],
            [
                'class' => 'status-badge status-update_request_accepted',
                'data' => [
                    'confirm' => 'Update quotation for request #' . $request->request_id . '?',
                    'swal-title' => 'Update quotation?',
                    'swal-text' => 'You will be redirected to the quotation update page.',
                    'swal-icon' => 'info',
                    'swal-confirm-text' => '<i class="bi bi-pencil-square"></i> Update',
                    'swal-confirm-class' => 'swal-action-confirm swal-action-info',
                ],
            ]
        ) ?>

    <?php elseif ($status === 'work_accepted'): ?>

        <span class="status-badge status-work_accepted">
            <i class="bi bi-check2-circle"></i> Work Accepted
        </span>

    <?php elseif ($status === 'work_started'): ?>

        <span class="status-badge status-work_started">
            <i class="bi bi-tools"></i> Work Started
        </span>

    <?php elseif ($status === 'closed'): ?>

        <span class="status-badge status-closed">
            <i class="bi bi-check-circle"></i> Closed
        </span>

    <?php else: ?>

        <span class="status-badge <?= $statusClass ?: 'status-default' ?>">
            <i class="bi bi-info-circle"></i>
            <?= Html::encode($formattedStatus ?: 'N/A') ?>
        </span>

    <?php endif; ?>
</td>


                                    
<td data-label="Actions">
    <div class="action-buttons">

        <?php
        // $reqId est deja prepare plus haut dans la meme ligne du tableau.

        $existingReport = \app\models\RepairReport::find()
            ->where(['mro_request_apply_id' => $mroRequestApplication->id])
            ->exists();

        $lastReport = \app\models\RepairReport::find()
            ->where(['mro_request_apply_id' => $mroRequestApplication->id])
            ->orderBy(['updated_at' => SORT_DESC])
            ->one();

        $existingfeedback = \app\models\Feedback::find()
            ->where(['request_id' => $reqId])
            ->exists();
        ?>

        <!-- View answer / application details -->
        <?php
        $encodedId = \app\components\UrlIdHelper::encode($mroRequestApplication->id);
        ?>
        <?= Html::a(
            '<i class="bi bi-eye"></i>',
            ['view-answer', 'id' => $encodedId],
            [
                'class' => 'action-btn btn-view',
                'title' => 'View answer',
                'data-bs-toggle' => 'tooltip',
            ]
        ) ?>

        <!-- Accept PO -->
        <?php if ($status === 'po_loaded' && $hasPo): ?>
            <?= Html::a(
                '<i class="bi bi-check-circle"></i>',
                ['accept-po', 'id' => $encodedApplicationId],
                [
                    'class' => 'action-btn btn-view',
                    'title' => 'Accept PO',
                    'data-bs-toggle' => 'tooltip',
                    'data' => [
                        'confirm' => 'Are you sure you want to accept this PO?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>

        <!-- Start Work -->
        <?php if ($status === 'work_accepted' && $reqId !== null): ?>
            <?= Html::a(
                '<i class="bi bi-play-circle"></i>',
                ['start-work', 'id' => $encodedRequestId],
                [
                    'class' => 'action-btn btn-update-request',
                    'title' => 'Start Working',
                    'data-bs-toggle' => 'tooltip',
                    'data' => [
                        'confirm' => 'Are you sure you want to start work?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>

        <!-- Accept / Deny updated request -->
        <?php if ($status === \app\models\Requests::STATUS_UPDATE_REQUEST): ?>
            <?= Html::a(
                '<i class="bi bi-check-square"></i>',
                ['accept-update', 'id' => UrlIdHelper::encode($reqId)],
                [
                    'class' => 'action-btn btn-view',
                    'title' => 'Accept Updated Request',
                    'data-bs-toggle' => 'tooltip',
                    'data' => [
                        'confirm' => 'Are you sure?',
                        'method' => 'post',
                    ],
                ]
            ) ?>

            <?= Html::a(
                '<i class="bi bi-trash"></i>',
                ['deny-request', 'id' => UrlIdHelper::encode($reqId)],
                [
                    'class' => 'action-btn btn-delete',
                    'title' => 'Delete Request',
                    'data-bs-toggle' => 'tooltip',
                    'data' => [
                        'confirm' => 'Are you sure you want to deny this request?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>

        <!-- Submit / Upload CRS -->
        <?php if (
            in_array($status, ['work_started', 'report_submitted'], true)
            && (!$existingReport || ($lastReport && (int)$lastReport->quote_approved === 0))
        ): ?>

         <?php
        $encodedId = \app\components\UrlIdHelper::encode($mroRequestApplication->id);
        ?>
            <?= Html::a(
                '<i class="bi bi-upload"></i>',
                ['submit-report', 'id' => $encodedId],
                [
                    'class' => 'action-btn btn-update-request',
                    'title' => 'Upload CRS',
                    'data-bs-toggle' => 'tooltip',
                ]
            ) ?>
        <?php endif; ?>

        <!-- View reports -->
        <?php if ($existingReport): ?>
             <?php
        $encodedId = \app\components\UrlIdHelper::encode($mroRequestApplication->id);
        ?>
            <?= Html::a(
                '<i class="bi bi-file-text"></i>',
                ['view-reports', 'id' => $encodedId],
                [
                    'class' => 'action-btn btn-reports',
                    'title' => 'View CRSs',
                    'data-bs-toggle' => 'tooltip',
                ]
            ) ?>
        <?php endif; ?>

        <!-- View feedback -->
        <?php if ($status === 'closed' && $existingfeedback && $reqId !== null): ?>
             <?php
        $encodedReqId = \app\components\UrlIdHelper::encode($reqId);
        ?>
            <?= Html::a(
                '<i class="bi bi-chat-square-text"></i>',
                ['view-feedback', 'id' => $encodedReqId],
                [
                    'class' => 'action-btn btn-feedback',
                    'title' => 'View Feedback',
                    'data-bs-toggle' => 'tooltip',
                ]
            ) ?>
        <?php endif; ?>

        <!-- Contact + Appointment -->
        <?php if ($status !== 'closed'): ?>

            
            <?= Html::a(
                '<i class="bi bi-envelope"></i>',
                ['contact', 'id' => $encodedId],
                [
                    'class' => 'action-btn btn-contact',
                    'title' => 'Contact',
                    'data-bs-toggle' => 'tooltip',
                ]
            ) ?>

            <?= Html::a(
                '<i class="bi bi-calendar-event"></i>',
                ['set-appointment', 'app_request_id' => $encodedId],
                [
                    'class' => 'action-btn btn-contact',
                    'title' => 'Set Appointment',
                    'data-bs-toggle' => 'tooltip',
                ]
            ) ?>
        <?php endif; ?>

        <!-- Cancel application -->
        <?php if (in_array($status, ['answered', 'created', 'po_loaded'], true)): ?>
            <?= Html::a(
                '<i class="bi bi-x-circle"></i>',
                ['cancel-application', 'id' => $encodedApplicationId],
                [
                    'class' => 'action-btn btn-delete',
                    'title' => 'Cancel',
                    'data-bs-toggle' => 'tooltip',
                    'data' => [
                        'confirm' => 'Are you sure you want to cancel this application?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>

    </div>
</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="8">
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

            <!-- Pagination: displayed only when the controller provides a pagination object -->
            <?php if (isset($pagination) && !empty($pagination)): ?>
                <div class="pagination-container">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?= $this->render('../shared/_request-details-modal') ?>

