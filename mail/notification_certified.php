<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL MRO : « New Request Notification » / « Request Updated » (MRO couvert)
 * Une demande correspond au périmètre du MRO : aéroport, avion, certificats, assurance.
 *
 * @var \yii\web\View $this
 * @var string $url                         Lien vers la liste des demandes MRO
 * @var \app\models\Requests|null $request
 * @var \app\models\Aircrafts|null $aircraft
 * @var \app\models\MroProfile|null $mro    Destinataire (facultatif)
 * @var bool|null $isUpdate                 true = demande mise à jour
 */

$request = $request ?? null;
$isUpdate = $isUpdate ?? false;

$bodyHtml = $request
    ? $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'aircraft' => $aircraft ?? null])
    : '';

$requestRef = $request ? 'Request <strong>#' . Html::encode($request->request_id) . '</strong>' : 'A request';

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Maintenance request notification',
    'eyebrow' => $isUpdate ? 'REQUEST UPDATED' : 'NEW REQUEST',
    'priority' => $request ? $request->operational_priority : null,
    'title' => $isUpdate ? 'A request in your scope has been updated' : 'A request matches your scope of work',
    'recipientName' => RequestMail::displayName($mro ?? null),
    'introHtml' => $requestRef . ($isUpdate
        ? ' has been updated by the operator and still matches your scope of work. Check the changes before quoting.'
        : ' matches your airports, aircraft coverage and certificates. It is open for review and quotation.'),
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => (string) $url,
    'ctaLabel' => 'Review the request',
    'noteHtml' => 'Sign in to the platform before submitting a quotation or viewing controlled documents.',
    'preheader' => $request
        ? 'Request #' . $request->request_id . ($isUpdate ? ' was updated' : ' matches your scope of work') . '.'
        : 'A request matches your scope of work.',
]);
