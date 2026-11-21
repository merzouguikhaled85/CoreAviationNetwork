<?php
/**
 * ONGLET « MRO QUOTES » DE LA PAGE requests/view (2026-11)
 *
 * Affiche, pour l'AO propriétaire, les réponses (devis) des MRO à sa demande,
 * avec les mêmes actions que la page requests/check-applications :
 * voir le détail, accepter (charger un PO), contacter, refuser, profil MRO.
 *
 * Paramètres :
 * @var \yii\web\View $this
 * @var \app\models\Requests $requestModel        demande déjà contrôlée (AO propriétaire)
 * @var \app\models\MroRequestApply[] $mroQuotes   réponses MRO de cette demande
 *
 * SÉCURITÉ : ce fragment n'est rendu que si le contrôleur a fourni $mroQuotes,
 * c'est-à-dire uniquement pour l'AO propriétaire (RequestsController::actionView).
 * Les actions ciblées vérifient elles-mêmes la propriété (findOwnedApplication).
 *
 * STYLE : toutes les règles CSS sont préfixées par .mro-quotes-panel, car la page
 * view possède déjà des classes génériques (.action-btn, .empty-state…).
 */

use app\components\UrlIdHelper;
use app\models\Currency;
use yii\helpers\Html;

$mroQuotes = $mroQuotes ?? [];

/*
 * Accepter ou refuser un devis n'a de sens que tant qu'aucun PO n'a été émis
 * (mêmes statuts que la liste AO « New requests » qui mène à check-applications).
 */
$canDecide = in_array((string) $requestModel->status, ['created', 'answered'], true);

// Devis retenu : celui du dernier PO émis (même règle que Requests::getSelectedMroApplication).
$selectedApplication = $requestModel->selectedMroApplication ?? null;
$selectedApplicationId = $selectedApplication ? (int) $selectedApplication->id : null;

// Noms des devises chargés en une seule requête (au lieu d'une requête par ligne).
$currencyCodes = array_values(array_unique(array_filter(array_map(static function ($quote) {
    return (string) $quote->currency;
}, $mroQuotes))));
$currencies = $currencyCodes
    ? Currency::find()->where(['code' => $currencyCodes])->indexBy('code')->all()
    : [];

$this->registerCss(<<<CSS
.mro-quotes-panel { margin-top: 4px; }
.mro-quotes-panel .mro-quotes-intro {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    flex-wrap: wrap; margin-bottom: 12px; color: #475569; font-size: 13px;
}
.mro-quotes-panel .mro-quotes-intro strong { color: #0f172a; }
.mro-quotes-panel .mro-quotes-scroll {
    width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff;
}
.mro-quotes-panel table { width: 100%; min-width: 860px; border-collapse: collapse; margin: 0; }
.mro-quotes-panel thead th {
    background: #f8fafc; color: #475569; font-size: 11px; font-weight: 800; letter-spacing: .06em;
    text-transform: uppercase; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.mro-quotes-panel tbody td { padding: 12px 14px; border-bottom: 1px solid #eef2f7; vertical-align: middle; font-size: 13px; }
.mro-quotes-panel tbody tr:last-child td { border-bottom: 0; }
.mro-quotes-panel tbody tr:hover td { background: #f8fbff; }
.mro-quotes-panel tbody tr.is-selected td { background: #f0fdf4; }
.mro-quotes-panel .quote-mro { display: inline-flex; align-items: center; gap: 6px; font-weight: 800; color: #0f172a; }
.mro-quotes-panel .quote-selected {
    display: inline-flex; align-items: center; gap: 4px; margin-left: 6px; padding: 2px 8px; border-radius: 999px;
    background: #dcfce7; color: #166534; font-size: 11px; font-weight: 800;
}
.mro-quotes-panel .quote-description {
    max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #334155;
}
.mro-quotes-panel .quote-pill {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px;
    font-size: 12px; font-weight: 800; white-space: nowrap;
}
.mro-quotes-panel .quote-price { background: #ecfdf5; color: #047857; }
.mro-quotes-panel .quote-currency { background: #fef3c7; color: #92400e; }
.mro-quotes-panel .quote-attachment {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px;
    border: 1px solid #c7d2fe; background: #eef2ff; color: #3730a3; font-weight: 800; font-size: 12px;
    text-decoration: none; white-space: nowrap;
}
.mro-quotes-panel .quote-attachment:hover { background: #e0e7ff; color: #312e81; }
.mro-quotes-panel .quote-no-attachment { color: #94a3b8; font-size: 12px; white-space: nowrap; }
.mro-quotes-panel .quote-actions { display: flex; gap: 6px; flex-wrap: nowrap; }
.mro-quotes-panel .quote-action {
    width: 32px; height: 32px; min-width: 32px; border-radius: 8px; display: inline-flex; align-items: center;
    justify-content: center; color: #fff !important; text-decoration: none; font-size: 14px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, .12); transition: transform .2s ease, box-shadow .2s ease;
}
.mro-quotes-panel .quote-action:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(15, 23, 42, .18); }
.mro-quotes-panel .quote-action.is-view { background: #2563eb; }
/* Action désactivée (ex. « voir » quand un devis est retenu) : grisée, sans effet au survol. */
.mro-quotes-panel .quote-action.is-disabled {
    background: #cbd5e1; cursor: not-allowed; box-shadow: none; pointer-events: auto;
}
.mro-quotes-panel .quote-action.is-disabled:hover { transform: none; box-shadow: none; }
.mro-quotes-panel .quote-action.is-accept { background: #22c55e; }
.mro-quotes-panel .quote-action.is-contact { background: #0ea5e9; }
.mro-quotes-panel .quote-action.is-deny { background: #ef4444; }
.mro-quotes-panel .quote-action.is-profile { background: #6366f1; }
.mro-quotes-panel .quote-empty {
    display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 36px 16px;
    color: #64748b; text-align: center;
}
.mro-quotes-panel .quote-empty i { font-size: 28px; color: #94a3b8; }
.mro-quotes-panel .quote-empty strong { color: #0f172a; }
CSS);
?>

<div class="mro-quotes-panel">
    <div class="mro-quotes-intro">
        <span>
            <strong><?= Html::encode((string) count($mroQuotes)) ?></strong>
            MRO <?= count($mroQuotes) === 1 ? 'quote' : 'quotes' ?> received for this request.
        </span>
        <?php if (!$canDecide && !empty($mroQuotes)): ?>
            <span><i class="bi bi-lock"></i> A purchase order has been issued: quotes are read-only.</span>
        <?php endif; ?>
    </div>

    <?php if (empty($mroQuotes)): ?>
        <div class="mro-quotes-scroll">
            <div class="quote-empty">
                <i class="bi bi-inbox"></i>
                <strong>No quotes yet</strong>
                <span>No MRO has replied to this request yet.</span>
            </div>
        </div>
    <?php else: ?>
        <div class="mro-quotes-scroll">
            <table>
                <thead>
                    <tr>
                        <th>MRO</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Currency</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mroQuotes as $quote): ?>
                    <?php
                    $encodedApplicationId = UrlIdHelper::encode($quote->id);
                    $mroProfile = $quote->mro; // MroRequestApply::getMro() retourne déjà le profil
                    $mroUsername = $mroProfile->username ?? 'MRO deleted';
                    $currencyCode = (string) $quote->currency;
                    $currencyName = isset($currencies[$currencyCode]) ? $currencies[$currencyCode]->name : ($currencyCode !== '' ? $currencyCode : 'N/A');
                    $description = trim((string) $quote->Description) !== '' ? (string) $quote->Description : 'No description provided.';
                    $isSelected = $selectedApplicationId !== null && (int) $quote->id === $selectedApplicationId;
                    ?>
                    <tr class="<?= $isSelected ? 'is-selected' : '' ?>">
                        <td>
                            <span class="quote-mro"><i class="bi bi-tools"></i><?= Html::encode($mroUsername) ?></span>
                            <?php if ($isSelected): ?>
                                <span class="quote-selected"><i class="bi bi-check2-circle"></i>Selected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="quote-description" title="<?= Html::encode($description) ?>">
                                <?= Html::encode($description) ?>
                            </div>
                        </td>
                        <td>
                            <span class="quote-pill quote-price"><i class="bi bi-cash-stack"></i><?= Html::encode($quote->price !== null && $quote->price !== '' ? (string) $quote->price : '0') ?></span>
                        </td>
                        <td>
                            <span class="quote-pill quote-currency"><i class="bi bi-currency-exchange"></i><?= Html::encode($currencyName) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($quote->attachment)): ?>
                                <?= Html::a(
                                    '<i class="bi bi-download"></i> Download',
                                    Yii::getAlias('@web/') . ltrim((string) $quote->attachment, '/'),
                                    ['class' => 'quote-attachment', 'target' => '_blank', 'rel' => 'noopener', 'data-pjax' => '0', 'data-no-loader' => '1']
                                ) ?>
                            <?php else: ?>
                                <span class="quote-no-attachment"><i class="bi bi-file-earmark-x"></i> No attachment</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="quote-actions">
                                <?php
                                /*
                                 * BOUTON « VOIR » (2026-11) : dès qu'un devis est retenu (PO émis),
                                 * le détail des devis n'est plus consultable depuis cet onglet, pour
                                 * aucune ligne. Le bouton reste visible mais grisé et non cliquable
                                 * (un <span>, pas un lien), afin de garder l'alignement des actions.
                                 */
                                ?>
                                <?php if ($selectedApplicationId !== null): ?>
                                    <span class="quote-action is-view is-disabled"
                                          role="button"
                                          aria-disabled="true"
                                          title="A quote has been selected: details are no longer available here"
                                          data-bs-toggle="tooltip">
                                        <i class="bi bi-eye-slash"></i>
                                    </span>
                                <?php else: ?>
                                    <?= Html::a('<i class="bi bi-eye"></i>', ['view-application', 'id' => $encodedApplicationId], [
                                        'class' => 'quote-action is-view',
                                        'title' => 'View quote details',
                                        'aria-label' => 'View quote details',
                                        'data-bs-toggle' => 'tooltip',
                                    ]) ?>
                                <?php endif; ?>

                                <?php if ($canDecide): ?>
                                    <?php /* Les attributs data-swal-* alimentent la boîte de confirmation commune de view.php. */ ?>
                                    <?= Html::a('<i class="bi bi-check-circle"></i>', ['load-po', 'id' => $encodedApplicationId], [
                                        'class' => 'quote-action is-accept',
                                        'title' => 'Accept quote',
                                        'aria-label' => 'Accept quote',
                                        'data-bs-toggle' => 'tooltip',
                                        'data' => [
                                            'confirm' => 'Are you sure you want to accept this MRO reply?',
                                            'swal-title' => 'Accept this quote?',
                                            'swal-text' => 'You will upload the purchase order for ' . $mroUsername . '.',
                                            'swal-icon' => 'question',
                                            'swal-confirm-text' => '<i class="bi bi-check-circle-fill"></i> Accept',
                                        ],
                                    ]) ?>
                                <?php endif; ?>

                                <?= Html::a('<i class="bi bi-chat-dots"></i>', ['contact', 'id' => $encodedApplicationId], [
                                    'class' => 'quote-action is-contact',
                                    'title' => 'Contact MRO',
                                    'aria-label' => 'Contact MRO',
                                    'data-bs-toggle' => 'tooltip',
                                ]) ?>

                                <?php if ($canDecide): ?>
                                    <?= Html::a('<i class="bi bi-x-circle"></i>', ['deny', 'id' => $encodedApplicationId], [
                                        'class' => 'quote-action is-deny',
                                        'title' => 'Deny quote',
                                        'aria-label' => 'Deny quote',
                                        'data-bs-toggle' => 'tooltip',
                                        'data' => [
                                            'confirm' => 'Are you sure you want to deny this application?',
                                            'method' => 'post', // requests/deny n'accepte que POST
                                            'swal-title' => 'Deny this quote?',
                                            'swal-text' => 'The quote from ' . $mroUsername . ' will be removed and the MRO notified.',
                                            'swal-icon' => 'warning',
                                            'swal-confirm-text' => '<i class="bi bi-x-circle-fill"></i> Deny',
                                        ],
                                    ]) ?>
                                <?php endif; ?>

                                <?php if ($mroProfile): ?>
                                    <?= Html::a('<i class="bi bi-person-badge"></i>', [
                                        'mro-profile/view',
                                        'id' => UrlIdHelper::encode($quote->mro_id),
                                        'fromApplication' => $quote->id,
                                    ], [
                                        'class' => 'quote-action is-profile',
                                        'title' => 'View MRO profile',
                                        'aria-label' => 'View MRO profile',
                                        'data-bs-toggle' => 'tooltip',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
