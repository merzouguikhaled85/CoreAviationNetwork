<?php

use app\models\PrelaunchSubscriber;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var PrelaunchSubscriber $subscriber */

$this->title = 'Early Access Registration #' . (int) $subscriber->id;
$this->registerCssFile(
    '@web/css/admin-prelaunch.css?v=20260926-1',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);
$emailStatus = PrelaunchSubscriber::confirmationEmailStatusOptions()[$subscriber->confirmation_email_status]
    ?? $subscriber->confirmation_email_status;
?>

<div class="prelaunch-admin-page">
    <section class="prelaunch-admin-header">
        <div class="prelaunch-admin-title">
            <span class="prelaunch-admin-title-icon"><i class="fas fa-address-card"></i></span>
            <div>
                <p>Early access registration #<?= (int) $subscriber->id ?></p>
                <h1><?= Html::encode(trim($subscriber->first_name . ' ' . $subscriber->last_name)) ?></h1>
                <span><?= Html::encode($subscriber->company_name) ?></span>
            </div>
        </div>
        <?= Html::a(
            '<i class="fas fa-arrow-left"></i> Back to subscribers',
            ['/admin/prelaunch-subscribers/index'],
            ['class' => 'prelaunch-export-button secondary']
        ) ?>
    </section>

    <div class="prelaunch-detail-grid">
        <section class="prelaunch-detail-card">
            <header><i class="fas fa-building"></i><h2>Registration</h2></header>
            <dl>
                <div><dt>Name</dt><dd><?= Html::encode(trim($subscriber->first_name . ' ' . $subscriber->last_name)) ?></dd></div>
                <div><dt>Business email</dt><dd><?= Html::mailto($subscriber->business_email, $subscriber->business_email) ?></dd></div>
                <div><dt>Company</dt><dd><?= Html::encode($subscriber->company_name) ?></dd></div>
                <div><dt>Company type</dt><dd><?= Html::encode(PrelaunchSubscriber::companyTypeOptions()[$subscriber->company_type] ?? $subscriber->company_type) ?></dd></div>
                <div><dt>Website</dt><dd><?= $subscriber->company_website ? Html::a(Html::encode($subscriber->company_website), $subscriber->company_website, ['target' => '_blank', 'rel' => 'noopener noreferrer']) : '—' ?></dd></div>
                <div><dt>Registered</dt><dd><?= Yii::$app->formatter->asDatetime($subscriber->created_at, 'php:d M Y H:i:s') ?></dd></div>
                <div><dt>Source</dt><dd><?= Html::encode($subscriber->source ?: '—') ?></dd></div>
            </dl>
        </section>

        <section class="prelaunch-detail-card">
            <header><i class="fas fa-shield-alt"></i><h2>Consent & confirmation</h2></header>
            <dl>
                <div><dt>Subscription status</dt><dd><?= Html::encode(PrelaunchSubscriber::subscriptionStatusOptions()[$subscriber->subscription_status] ?? $subscriber->subscription_status) ?></dd></div>
                <div><dt>Consent recorded</dt><dd><?= $subscriber->consent ? 'Yes' : 'No' ?></dd></div>
                <div><dt>Consent date</dt><dd><?= Html::encode($subscriber->consent_at ?: '—') ?></dd></div>
                <div><dt>Consent version</dt><dd><?= Html::encode($subscriber->consent_text_version ?: '—') ?></dd></div>
                <div><dt>Confirmed</dt><dd><?= Html::encode($subscriber->confirmed_at ?: '—') ?></dd></div>
                <div><dt>Unsubscribed</dt><dd><?= Html::encode($subscriber->unsubscribed_at ?: '—') ?></dd></div>
            </dl>
        </section>

        <section class="prelaunch-detail-card prelaunch-delivery-card">
            <header><i class="fas fa-envelope"></i><h2>Confirmation email delivery</h2></header>
            <dl>
                <div><dt>Status</dt><dd><?= Html::encode($emailStatus) ?></dd></div>
                <div><dt>Attempts</dt><dd><?= (int) $subscriber->confirmation_attempt_count ?></dd></div>
                <div><dt>Last attempt</dt><dd><?= Html::encode($subscriber->confirmation_attempted_at ?: '—') ?></dd></div>
                <div><dt>Sent</dt><dd><?= Html::encode($subscriber->confirmation_sent_at ?: '—') ?></dd></div>
            </dl>
            <?php if ($subscriber->confirmation_last_error): ?>
                <div class="prelaunch-error-panel">
                    <strong><i class="fas fa-exclamation-triangle"></i> Last delivery error</strong>
                    <p><?= Html::encode($subscriber->confirmation_last_error) ?></p>
                </div>
            <?php else: ?>
                <div class="prelaunch-no-error"><i class="fas fa-check-circle"></i> No delivery error recorded.</div>
            <?php endif; ?>
            <p class="prelaunch-private-note"><i class="fas fa-lock"></i> Delivery diagnostics are restricted to authenticated administrators.</p>
        </section>
    </div>
</div>
