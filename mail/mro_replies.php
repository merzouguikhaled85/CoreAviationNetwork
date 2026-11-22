<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL AO : « New Request Application » / « Updated Request Application »
 * Un MRO a envoyé (ou mis à jour) un devis sur une demande de l'AO.
 *
 * @var \yii\web\View $this
 * @var \app\models\MroRequestApply $MroRequestApply
 * @var bool|null $isNew  true = nouveau devis, false = devis mis à jour (défaut : true)
 */

$isNew = $isNew ?? true;
$request = $MroRequestApply->getRequest()->one();
$mro = $MroRequestApply->getMro();
$mroName = RequestMail::displayName($mro, 'The MRO');
$ao = $request ? $request->getAO()->one() : null;

$quoteRows = [
    ['label' => 'MRO', 'value' => $mroName],
    ['label' => 'Quoted price', 'value' => RequestMail::money($MroRequestApply->price, $MroRequestApply->currency)],
    ['label' => 'Technical proposal', 'value' => RequestMail::excerpt($MroRequestApply->Description), 'multiline' => true, 'strong' => false],
    ['label' => 'Attachment', 'value' => !empty($MroRequestApply->attachment) ? 'Provided — available on the platform' : ''],
];

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => $isNew ? 'Quotation received' : 'Updated quotation',
    'rows' => $quoteRows,
    'tone' => 'info',
]);
if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);
}

$requestRef = $request ? ' for Request <strong>#' . Html::encode($request->request_id) . '</strong>' : '';

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Quotation',
    'eyebrow' => $isNew ? 'NEW QUOTE' : 'QUOTE UPDATED',
    'priority' => $request ? $request->operational_priority : null,
    'title' => $isNew ? 'You received a new quotation' : 'A quotation has been updated',
    'recipientName' => RequestMail::displayName($ao),
    'introHtml' => '<strong>' . Html::encode($mroName) . '</strong> has '
        . ($isNew ? 'submitted a quotation' : 'updated its quotation') . $requestRef
        . '. Review the offer below and compare it with the other quotes on the platform.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => $request ? RequestMail::requestUrl($request, 'quotes') : '',
    'ctaLabel' => 'Review the quotation',
    'noteHtml' => 'To accept this offer, open the request, go to the <strong>MRO Quotes</strong> tab and issue the purchase order.',
    'preheader' => $mroName . ($isNew ? ' sent a quotation' : ' updated its quotation')
        . ($request ? ' for Request #' . $request->request_id : '') . '.',
]);
