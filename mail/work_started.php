<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL AO : « Work Started »
 * Le MRO retenu (PO accepté) a démarré les travaux sur la demande de l'AO.
 *
 * @var \yii\web\View $this
 * @var \app\models\Requests $request
 */

$quote = $request->getSelectedMroApplication()->one();
$mro = $quote ? $quote->getMro() : null;
$mroName = RequestMail::displayName($mro, 'The MRO');
$ao = $request->getAO()->one();

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Work in progress',
    'tone' => 'info',
    'rows' => [
        ['label' => 'Status', 'value' => 'Work Started'],
        ['label' => 'MRO', 'value' => $mroName],
        ['label' => 'Started on', 'value' => date('d M Y H:i')],
        ['label' => 'Agreed amount', 'value' => $quote ? RequestMail::money($quote->price, $quote->currency) : ''],
        ['label' => 'Next step', 'value' => 'The MRO will submit the work report and the CRS (Certificate of Release to Service).', 'strong' => false],
    ],
]);
$bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Maintenance progress',
    'eyebrow' => 'WORK STARTED',
    'priority' => $request->operational_priority,
    'title' => 'Work has started on your aircraft',
    'recipientName' => RequestMail::displayName($ao),
    'introHtml' => '<strong>' . Html::encode($mroName) . '</strong> has started the maintenance work for Request <strong>#'
        . Html::encode($request->request_id) . '</strong>.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => RequestMail::requestUrl($request),
    'ctaLabel' => 'Follow the request',
    'noteHtml' => 'You will be notified as soon as the report and the CRS are available. You can message the MRO from the request page at any time.',
    'preheader' => $mroName . ' started the work for Request #' . $request->request_id . '.',
]);
