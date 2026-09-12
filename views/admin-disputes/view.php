<?php

/** @var yii\web\View $this */
/** @var app\models\Dispute $dispute */

use yii\helpers\Html;
use app\components\UrlIdHelper;

// SIGNED ACTION IDS: keep all identifiers protected when leaving this detail page.
$encodedDisputeId = UrlIdHelper::encode($dispute->dispute_id);
$encodedAoId = !empty($dispute->ao_id) ? UrlIdHelper::encode($dispute->ao_id) : null;
$encodedMroId = !empty($dispute->mro_id) ? UrlIdHelper::encode($dispute->mro_id) : null;

$headerActions = [];

if ($dispute->status !== 'resolved') {
    $headerActions[] = Html::a(
        '<i class="bi bi-reply-fill"></i> Reply',
        ['reply', 'id' => $encodedDisputeId],
        ['class' => 'detail-action-button detail-action-reply']
    );
}

$headerActions[] = Html::a(
    '<i class="bi bi-trash"></i> Delete',
    ['delete', 'id' => $encodedDisputeId],
    [
        'class' => 'detail-action-button detail-action-delete',
        'data' => [
            'confirm' => 'Dispute #' . $dispute->dispute_id . ' will be permanently deleted.',
            'method' => 'post',
        ],
    ]
);

if ($dispute->ao !== null) {
    $aoIsBanned = $dispute->ao->status === 'banned';
    $headerActions[] = Html::a(
        $aoIsBanned
            ? '<i class="bi bi-person-check"></i> Unban AO'
            : '<i class="bi bi-person-slash"></i> Ban AO',
        [$aoIsBanned ? 'unban-ao' : 'ban-ao', 'id' => $encodedAoId],
        [
            'class' => 'detail-action-button ' . ($aoIsBanned ? 'detail-action-unban' : 'detail-action-ban'),
            'data' => [
                'confirm' => 'Are you sure you want to ' . ($aoIsBanned ? 'unban' : 'ban') . ' this AO?',
                'method' => 'post',
            ],
        ]
    );
}

if ($dispute->mro !== null) {
    $mroIsBanned = $dispute->mro->status === 'banned';
    $headerActions[] = Html::a(
        $mroIsBanned
            ? '<i class="bi bi-tools"></i> Unban MRO'
            : '<i class="bi bi-wrench-adjustable-circle"></i> Ban MRO',
        [$mroIsBanned ? 'unban-mro' : 'ban-mro', 'id' => $encodedMroId],
        [
            'class' => 'detail-action-button ' . ($mroIsBanned ? 'detail-action-unban' : 'detail-action-ban'),
            'data' => [
                'confirm' => 'Are you sure you want to ' . ($mroIsBanned ? 'unban' : 'ban') . ' this MRO?',
                'method' => 'post',
            ],
        ]
    );
}

// Use the same responsive dispute presentation for the admin area.
echo $this->render('//ao-mro-dispute/view', [
    'dispute' => $dispute,
    'headerActions' => implode('', $headerActions),
    'showRequestDetails' => true,
]);
