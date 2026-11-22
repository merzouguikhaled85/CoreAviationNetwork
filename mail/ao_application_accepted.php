<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL AO : « PO Accepted »
 * Le MRO a accepté le bon de commande (PO) émis par l'AO.
 *
 * @var \yii\web\View $this
 * @var \app\models\AoRequestsApplications $application  Lien PO ↔ devis retenu
 */

$quote = $application->getApplication()->one();
$request = $application->getRequest()->one();
$mro = $quote ? $quote->getMro() : null;
$mroName = RequestMail::displayName($mro, 'The MRO');
$ao = $request ? $request->getAO()->one() : null;

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Purchase order accepted',
    'tone' => 'success',
    'rows' => [
        ['label' => 'MRO', 'value' => $mroName],
        ['label' => 'Agreed amount', 'value' => $quote ? RequestMail::money($quote->price, $quote->currency) : ''],
        ['label' => 'Purchase order', 'value' => !empty($application->po) ? 'Document on file' : ''],
        ['label' => 'Accepted on', 'value' => date('d M Y H:i')],
        ['label' => 'Next step', 'value' => 'The MRO will start the work and keep the request status up to date.', 'strong' => false],
    ],
]);
if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);
}

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Purchase order',
    'eyebrow' => 'PO ACCEPTED',
    'priority' => $request ? $request->operational_priority : null,
    'title' => 'Your purchase order has been accepted',
    'recipientName' => RequestMail::displayName($ao),
    'introHtml' => '<strong>' . Html::encode($mroName) . '</strong> has accepted the purchase order'
        . ($request ? ' for Request <strong>#' . Html::encode($request->request_id) . '</strong>' : '')
        . '. The maintenance can now be scheduled.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => $request ? RequestMail::requestUrl($request) : '',
    'ctaLabel' => 'View the request',
    'noteHtml' => 'You can follow the progress, exchange messages with the MRO and receive the reports from the request page.',
    'preheader' => $mroName . ' accepted your purchase order' . ($request ? ' for Request #' . $request->request_id : '') . '.',
]);
