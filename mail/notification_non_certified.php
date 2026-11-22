<?php

use app\components\RequestMail;
use yii\helpers\Html;

/**
 * E-MAIL MRO : « New Request Notification » / « Request Updated » (MRO NON couvert)
 * La demande concerne un aéroport desservi par le MRO, mais son profil ne couvre
 * pas tout (type d'avion, certificats ou assurance). Mêmes détails que le mail
 * « couvert » (décision produit du 2026-11-22), plus la raison de non-couverture.
 *
 * @var \yii\web\View $this
 * @var string|null $url                    Lien vers la liste des demandes MRO
 * @var \app\models\Requests|null $request
 * @var \app\models\Aircrafts|null $aircraft
 * @var \app\models\MroProfile|null $mro    Destinataire (facultatif)
 * @var string|null $reasonText             Raisons séparées par « | »
 * @var bool|null $isUpdate
 * @var string|null $manufacturer           Ancien appel (sans demande) : constructeur
 * @var string|null $model                  Ancien appel (sans demande) : modèle
 */

$request = $request ?? null;
$isUpdate = $isUpdate ?? false;

$reasons = array_values(array_filter(array_map('trim', explode('|', (string) ($reasonText ?? '')))));
if ($reasons === []) {
    $reasons = ['Your profile does not fully cover this aircraft, its certificates or your insurance.'];
}
$coverageRows = [];
foreach ($reasons as $index => $reason) {
    $coverageRows[] = ['label' => count($reasons) > 1 ? 'Reason ' . ($index + 1) : 'Reason', 'value' => $reason, 'strong' => false];
}

$bodyHtml = $this->render('@app/mail/partials/_details.php', [
    'heading' => 'Coverage gap',
    'tone' => 'warning',
    'rows' => $coverageRows,
]);

if ($request) {
    $bodyHtml .= $this->render('@app/mail/partials/_request-card.php', ['request' => $request, 'aircraft' => $aircraft ?? null]);
    $requestRef = 'Request <strong>#' . Html::encode($request->request_id) . '</strong>';
} else {
    $legacyAircraft = trim((string) ($manufacturer ?? '') . ' ' . (string) ($model ?? ''));
    $requestRef = 'A request' . ($legacyAircraft !== '' ? ' for a <strong>' . Html::encode($legacyAircraft) . '</strong>' : '');
}

echo $this->render('@app/mail/partials/_frame.php', [
    'headerSubtitle' => 'Maintenance request notification',
    'eyebrow' => $isUpdate ? 'REQUEST UPDATED · OUTSIDE YOUR COVERAGE' : 'NEW REQUEST · OUTSIDE YOUR COVERAGE',
    'priority' => $request ? $request->operational_priority : null,
    'title' => $isUpdate ? 'An updated request is not fully covered by your profile' : 'A new request is not fully covered by your profile',
    'recipientName' => RequestMail::displayName($mro ?? null),
    'introHtml' => $requestRef . ($isUpdate ? ' has been updated' : ' has been submitted')
        . ' at an airport you serve, but your profile does not fully cover it. See the reason below.',
    'bodyHtml' => $bodyHtml,
    'ctaUrl' => (string) ($url ?? ''),
    'ctaLabel' => 'Open the platform',
    'noteHtml' => 'To receive and quote similar requests in the future, add the missing aircraft type, certificates or insurance documents to your profile.',
    'preheader' => ($request ? 'Request #' . $request->request_id : 'A request') . ' is outside your current coverage: ' . $reasons[0],
]);
