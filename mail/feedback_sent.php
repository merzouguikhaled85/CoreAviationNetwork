<?php
use app\components\UrlIdHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $feedback app\models\Feedback */
/* @var $mro app\models\MroProfile */

$appName = Yii::$app->name ?: 'Core Aviation Network';

$mroName = $mro->username ?? 'MRO';

$requestId = $feedback->request_id ?? 'N/A';
$feedbackId = $feedback->feedback_id ?? 'N/A';

$feedbackDate = !empty($feedback->timestamp)
    ? Yii::$app->formatter->asDate($feedback->timestamp)
    : 'N/A';

$feedbackText = $feedback->feedback_text ?? 'N/A';
$rating = $feedback->rating ?? 'N/A';
$scheduleRating = $feedback->kept_to_agreed_schedule_rating ?? 'N/A';
$costRating = $feedback->kept_to_agreed_cost_rating ?? 'N/A';
$communicationRating = $feedback->overall_communication_rating ?? 'N/A';

/*
 * LIEN PUBLIC CANONIQUE : la page de consultation existante travaille avec
 * l'identifiant de la demande, et non avec l'identifiant interne du feedback.
 * UrlIdHelper signe cet identifiant afin qu'aucune valeur numérique ne soit
 * exposée dans les nouveaux e-mails envoyés au MRO.
 */
$feedbackLink = is_numeric($requestId) && (int) $requestId > 0
    ? Yii::$app->urlManager->createAbsoluteUrl([
        '/mro-applications/view-feedback',
        'id' => UrlIdHelper::encode((int) $requestId),
    ])
    : null;
?>

<div style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb; padding:30px 0;">
        <tr>
            <td align="center">

                <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,0.08);">

                    <tr>
                        <td style="background:#0f172a; padding:28px 35px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">
                                <?= Html::encode($appName) ?>
                            </h1>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:14px;">
                                New Feedback Received
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 38px;">

                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px;">
                                Feedback has been provided
                            </h2>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Dear <?= Html::encode($mroName) ?>,
                            </p>

                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#374151;">
                                We are pleased to inform you that feedback has been provided for the maintenance operation associated with the following details.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; margin:24px 0;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#1e40af;">
                                            You can review this feedback in your dashboard and use it to maintain high service standards.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin:24px 0;">
                                <tr>
                                    <td style="background:#f8fafc; padding:14px 18px; font-size:14px; font-weight:700; color:#111827;">
                                        Feedback Details
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Request ID
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    #<?= Html::encode($requestId) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Feedback ID
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    #<?= Html::encode($feedbackId) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Feedback Date
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($feedbackDate) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Feedback Summary
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($feedbackText) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Rating
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($rating) ?> / 5
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Schedule Rating
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($scheduleRating) ?> / 5
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Cost Rating
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($costRating) ?> / 5
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:42%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Communication Rating
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($communicationRating) ?> / 5
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <?php if ($feedbackLink !== null): ?>
                                <!--
                                    Le bouton n'est rendu que lorsqu'une demande valide est
                                    disponible. Cela évite de produire un lien incomplet ou un
                                    appel à UrlIdHelper avec une valeur non numérique.
                                -->
                                <div style="text-align:center; margin:30px 0;">
                                    <a href="<?= Html::encode($feedbackLink) ?>"
                                       style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:10px; font-size:15px; font-weight:700;">
                                        View Feedback
                                    </a>
                                </div>
                            <?php endif; ?>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Thank you for your continued cooperation and dedication to maintaining high standards in our operations.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#374151;">
                                Best regards,<br>
                                <strong><?= Html::encode($appName) ?> Team</strong>
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc; padding:20px 35px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                This is an automatic message from <?= Html::encode($appName) ?>. Please do not reply.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</div>
