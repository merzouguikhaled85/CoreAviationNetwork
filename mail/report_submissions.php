<?php

use app\components\RequestMail;
use app\models\Requests;
use yii\helpers\Html;

/**
 * E-MAIL AO : « New report submitted »
 * Le MRO a soumis un rapport de travaux sur la demande de l'AO.
 *
 * @var \yii\web\View $this
 * @var \app\models\RepairReport $RepairReport
 */

$quote = $RepairReport->getMroRequestApply()->one();
$request = $quote ? Requests::findOne($quote->request_id) : null;
$mro = $quote ? $quote->getMro() : null;
$mroName = RequestMail::displayName($mro, 'The MRO');
$ao = $request ? $request->getAO()->one() : null;

$submittedAt = strtotime((string) $RepairReport->created_at);

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Report submitted',
    'tone' => 'success',
    'rows' => [
        ['label' => 'MRO', 'value' => $mroName],
        ['label' => 'Submitted on', 'value' => $submittedAt ? date('d M Y H:i', $submittedAt) : date('d M Y H:i')],
        ['label' => 'Report', 'value' => RequestMail::excerpt($RepairReport->report, 800), 'multiline' => true, 'strong' => false],
        ['label' => 'Additional quote', 'value' => RequestMail::excerpt($RepairReport->mro_quote, 300), 'strong' => false],
        ['label' => 'Attachment', 'value' => !empty($RepairReport->mro_attachment) ? 'Provided — available on the platform' : ''],
    ],
]);
if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);
}

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Maintenance report',
    'eyebrow' => 'NEW REPORT',
    'priority' => $request ? $request->operational_priority : null,
    'title' => 'A new report has been submitted',
    'recipientName' => RequestMail::displayName($ao),
    'introHtml' => '<strong>' . Html::encode($mroName) . '</strong> has submitted a report'
        . ($request ? ' for Request <strong>#' . Html::encode($request->request_id) . '</strong>' : '')
        . '. Please review it on the platform.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => $request ? RequestMail::requestUrl($request) : '',
    'ctaLabel' => 'Review the report',
    'noteHtml' => 'Reviewing the report promptly helps the MRO complete the work and issue the CRS without delay.',
    'preheader' => $mroName . ' submitted a report' . ($request ? ' for Request #' . $request->request_id : '') . '.',
]);
