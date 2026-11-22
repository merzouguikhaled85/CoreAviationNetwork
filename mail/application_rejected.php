<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL MRO : « Application Not Accepted »
 * L'AO a émis le PO pour un autre MRO : les autres devis ne sont pas retenus.
 * Confidentialité : le MRO retenu et son prix ne sont jamais indiqués (D-013).
 *
 * @var \yii\web\View $this
 * @var \app\models\MroRequestApply $application  Devis non retenu
 * @var \app\models\Requests $request
 */

$request = $request ?? $application->getRequest()->one();
$mro = $application->getMro();

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Your quotation',
    'tone' => 'warning',
    'rows' => [
        ['label' => 'Status', 'value' => 'Not selected — another provider was chosen'],
        ['label' => 'Quoted price', 'value' => RequestMail::money($application->price, $application->currency)],
        ['label' => 'Your proposal', 'value' => RequestMail::excerpt($application->Description, 600), 'multiline' => true, 'strong' => false],
    ],
]);
if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);
}

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Quotation',
    'eyebrow' => 'QUOTE NOT SELECTED',
    'priority' => $request ? $request->operational_priority : null,
    'title' => 'Another provider was selected',
    'recipientName' => RequestMail::displayName($mro),
    'introHtml' => 'Thank you for your quotation'
        . ($request ? ' for Request <strong>#' . Html::encode($request->request_id) . '</strong>' : '')
        . '. The operator has issued the purchase order to another provider, so your offer was not selected this time.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/mro-requests/index']),
    'ctaLabel' => 'See new requests',
    'noteHtml' => 'New requests matching your scope of work are announced by e-mail and in your notifications.',
    'preheader' => 'Your quotation' . ($request ? ' for Request #' . $request->request_id : '') . ' was not selected.',
]);
