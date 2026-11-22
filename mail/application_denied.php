<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL MRO : « Request Application Denied »
 * L'AO a refusé le devis du MRO (RequestsController::actionDeny).
 * Confidentialité : aucune information sur les autres devis ou MRO (D-013).
 *
 * @var \yii\web\View $this
 * @var \app\models\MroRequestApply $application  Devis refusé (rendu avant sa suppression)
 */

$request = $application->getRequest()->one();
$mro = $application->getMro();

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Your quotation',
    'tone' => 'warning',
    'rows' => [
        ['label' => 'Status', 'value' => 'Declined by the operator'],
        ['label' => 'Quoted price', 'value' => RequestMail::money($application->price, $application->currency)],
        ['label' => 'Your proposal', 'value' => RequestMail::excerpt($application->Description, 600), 'multiline' => true, 'strong' => false],
    ],
]);
if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'showDetails' => false]);
}

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Quotation',
    'eyebrow' => 'QUOTE DECLINED',
    'priority' => $request ? $request->operational_priority : null,
    'title' => 'Your quotation was not selected',
    'recipientName' => RequestMail::displayName($mro),
    'introHtml' => 'Thank you for your prompt response. The operator has declined your quotation'
        . ($request ? ' for Request <strong>#' . Html::encode($request->request_id) . '</strong>' : '')
        . '. You were not selected to carry out this work.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/mro-requests/index']),
    'ctaLabel' => 'See new requests',
    'noteHtml' => 'The operator may still contact you about this request — keep an eye on your notifications.',
    'preheader' => 'Your quotation' . ($request ? ' for Request #' . $request->request_id : '') . ' was not selected.',
]);
