<?php

use yii\helpers\Html;

/**
 * BLOC DE DÉTAILS PROPRE À CHAQUE E-MAIL (devis, PO, rapport, couverture…)
 * -------------------------------------------------------------------------
 * Liste de lignes « libellé / valeur ». Les valeurs sont du texte brut,
 * échappé ici ; 'multiline' => true conserve les retours à la ligne.
 *
 * @var string $heading  Titre du bloc (texte)
 * @var array  $rows     [['label' => string, 'value' => string, 'multiline' => bool, 'strong' => bool], ...]
 * @var string|null $tone  info (bleu, défaut) / success (vert) / warning (orange)
 */

$tones = [
    'info' => ['#eff6ff', '#bfdbfe', '#1d4ed8'],
    'success' => ['#ecfdf5', '#a7f3d0', '#047857'],
    'warning' => ['#fffbeb', '#fde68a', '#b45309'],
];
[$background, $border, $accent] = $tones[$tone ?? 'info'] ?? $tones['info'];
?>
            <!-- Bloc de détails -->
            <div style='margin:0 0 16px; padding:14px 16px; background:<?= $background ?>; border:1px solid <?= $border ?>; border-radius:10px;'>
              <div style='margin:0 0 8px; font-size:12px; font-weight:700; color:<?= $accent ?>; text-transform:uppercase; letter-spacing:.4px;'>
                <?= Html::encode($heading) ?>
              </div>
              <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='font-size:13px; line-height:1.6; color:#1f2937;'>
                <?php foreach ($rows as $row): ?>
                  <?php
                  $value = trim((string) ($row['value'] ?? ''));
                  if ($value === '') {
                      continue;
                  }
                  $safeValue = !empty($row['multiline']) ? nl2br(Html::encode($value)) : Html::encode($value);
                  ?>
                <tr>
                  <td style='width:38%; padding:5px 10px 5px 0; color:#64748b; vertical-align:top;'><?= Html::encode($row['label']) ?></td>
                  <td style='padding:5px 0; vertical-align:top;'><?= !array_key_exists('strong', $row) || $row['strong'] ? '<strong>' . $safeValue . '</strong>' : $safeValue ?></td>
                </tr>
                <?php endforeach; ?>
              </table>
            </div>
