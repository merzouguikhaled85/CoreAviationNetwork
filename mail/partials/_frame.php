<?php

use yii\helpers\Html;

/**
 * GABARIT COMMUN DES E-MAILS LIÉS À UNE DEMANDE
 * ---------------------------------------------
 * Même présentation que le mail de réinitialisation du mot de passe
 * (password-reset-html.php) : bandeau bleu, carte blanche, bouton d'action,
 * lien de secours et pied de page. Chaque e-mail ne fournit que son contenu.
 *
 * Tous les styles sont « inline » pour rester lisibles dans Gmail / Outlook.
 * Les blocs $introHtml, $bodyHtml et $noteHtml sont du HTML DÉJÀ ÉCHAPPÉ par
 * l'appelant (partiels _request-card.php et _details.php) ; les autres
 * paramètres sont du texte brut, échappé ici.
 *
 * @var \yii\web\View $this
 * @var string $headerSubtitle Ligne sous le nom de l'application (texte)
 * @var string $eyebrow        Badge au-dessus du titre, ex. « NEW QUOTE » (texte)
 * @var string $title          Titre principal (texte)
 * @var string|null $recipientName Nom du destinataire (texte)
 * @var string $introHtml      Paragraphe d'introduction (HTML échappé)
 * @var string $bodyHtml       Cartes de détails (HTML échappé)
 * @var string|null $priority  aog / urgent / routine : badge à côté du badge principal
 * @var string|null $ctaUrl    Lien du bouton (texte)
 * @var string|null $ctaLabel  Libellé du bouton (texte)
 * @var string|null $noteHtml  Encadré d'information final (HTML échappé)
 * @var string|null $preheader Aperçu affiché par la boîte mail à côté du sujet (texte)
 */

$appName = Html::encode(Yii::$app->name);
$recipientName = isset($recipientName) ? trim((string) $recipientName) : '';
$priority = isset($priority) ? strtolower((string) $priority) : '';
$ctaUrl = isset($ctaUrl) ? (string) $ctaUrl : '';
$ctaLabel = isset($ctaLabel) && $ctaLabel !== '' ? (string) $ctaLabel : 'View request';
$noteHtml = $noteHtml ?? '';
$preheader = $preheader ?? '';

$priorityBadges = [
    'aog' => ['AOG', 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;'],
    'urgent' => ['URGENT', 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;'],
    'routine' => ['ROUTINE', 'background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff;'],
];
$safeCtaUrl = Html::encode($ctaUrl);
?>
    <div style='margin:0; padding:0; background:#f4f6f8;'>
      <?php if ($preheader !== ''): ?>
      <!-- Aperçu (preheader) : visible dans la liste des mails, masqué dans le corps. -->
      <div style='display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;'><?= Html::encode($preheader) ?></div>
      <?php endif; ?>
      <div style='max-width:640px; margin:0 auto; padding:24px; font-family:Arial, Helvetica, sans-serif; color:#1f2937;'>
        <div style='background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;'>

          <!-- En-tête -->
          <div style='padding:18px 22px; background:#0b5ed7; color:#ffffff;'>
            <div style='font-size:16px; font-weight:700; letter-spacing:.2px;'>
              <?= $appName ?>
            </div>
            <div style='font-size:13px; opacity:.9; margin-top:4px;'>
              <?= Html::encode($headerSubtitle) ?>
            </div>
          </div>

          <!-- Corps du mail -->
          <div style='padding:22px;'>
            <div style='margin:0 0 12px;'>
              <span style='display:inline-block; padding:4px 10px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; letter-spacing:.4px;'><?= Html::encode($eyebrow) ?></span>
              <?php if (isset($priorityBadges[$priority])): ?>
              <span style='display:inline-block; margin-left:6px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.4px; <?= $priorityBadges[$priority][1] ?>'><?= $priorityBadges[$priority][0] ?></span>
              <?php endif; ?>
            </div>

            <h2 style='margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;'>
              <?= Html::encode($title) ?>
            </h2>

            <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
              Hello<?= $recipientName !== '' ? ' <strong>' . Html::encode($recipientName) . '</strong>' : '' ?>,
            </p>

            <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
              <?= $introHtml ?>
            </p>

            <?= $bodyHtml ?>

            <?php if ($ctaUrl !== ''): ?>
            <!-- Bouton d'action -->
            <div style='margin:20px 0 18px;'>
              <a href='<?= $safeCtaUrl ?>'
                 style='display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;'>
                <?= Html::encode($ctaLabel) ?>
              </a>
            </div>

            <p style='margin:0 0 10px; font-size:13px; line-height:1.7; color:#374151;'>
              If the button doesn't work, copy and paste this link into your browser:
            </p>

            <p style='margin:0 0 16px; font-size:13px; line-height:1.7; word-break:break-all;'>
              <a href='<?= $safeCtaUrl ?>' style='color:#0b5ed7; text-decoration:underline;'>
                <?= $safeCtaUrl ?>
              </a>
            </p>
            <?php endif; ?>

            <?php if ($noteHtml !== ''): ?>
            <div style='padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;'>
              <p style='margin:0; font-size:13px; line-height:1.7; color:#374151;'>
                <?= $noteHtml ?>
              </p>
            </div>
            <?php endif; ?>

            <p style='margin:18px 0 0; font-size:14px; line-height:1.7;'>
              Best regards,<br>
              <strong><?= $appName ?> Team</strong>
            </p>
          </div>

          <!-- Pied de page -->
          <div style='padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;'>
            <p style='margin:0; font-size:12px; line-height:1.6; color:#6b7280;'>
              This is an automated message. Please do not reply.
            </p>
          </div>

        </div>
      </div>
    </div>
