<?php

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Notification Preferences';
$this->params['breadcrumbs'][] = ['label' => 'Account', 'url' => ['/ao/profile/update']];
$this->params['breadcrumbs'][] = $this->title;

/* Icones Remix */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['position' => $this::POS_HEAD]
);

/* ===========================
   CSS – Aviation Premium Light + Switches
   Same design as MRO
   =========================== */
$this->registerCss(<<<CSS
:root {
  --deep-blue: #0D3261;
  --sky: #00B2FF;
  --gold: #EED811;
  --soft-border: #D8E4EE;
  --soft-bg: #F4F7FC;
  --text-main: #0F172A;
  --text-muted: #6B7280;
}

/* wrapper qui respecte le thème global, mais très clair */
.notification-pref-wrapper {
  min-height: calc(100vh - 70px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 40px 20px;
  background:
    radial-gradient(circle at 20% 50%, rgba(13, 50, 97, 0.06) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(0, 178, 255, 0.06) 0%, transparent 50%),
    linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 45%, #F8FBFF 100%);
}

/* Card principale */
.notification-pref-card {
  width: 100%;
  max-width: 780px;
  background: #FFFFFF;
  border-radius: 22px;
  border: 1px solid var(--soft-border);
  box-shadow:
      0 20px 60px rgba(15, 23, 42, 0.18),
      0 0 0 1px rgba(255,255,255,0.9);
  overflow: hidden;
  position: relative;
}

/* top border animée */
.notification-pref-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 5px;
  background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
  background-size: 220% 220%;
  animation: notifGradient 8s ease infinite;
}

@keyframes notifGradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Header */
.notification-header {
  padding: 26px 28px 6px;
  margin-bottom: 18px;
  background: linear-gradient(135deg, #FFFFFF, #F4F8FF);
}

.notification-title-icon {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E7F4FF;
  color: var(--deep-blue);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.28);
  position: relative;
  overflow: hidden;
}

.notification-title-icon::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.32) 50%, transparent 70%);
  animation: notifShine 3s infinite linear;
}

@keyframes notifShine {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.notification-title-icon i {
  font-size: 26px;
  line-height: 1;
}

.notification-title {
  font-size: 24px;
  font-weight: 700;
  margin: 10px 0 4px;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.notification-title span.accent {
  color: var(--sky);
}

.notification-subtitle {
  font-size: 14px;
  color: var(--text-muted);
  max-width: 520px;
}

/* flash */
.notification-flash {
  padding: 0 28px;
  margin-bottom: 10px;
}
.notification-flash .alert {
  border-radius: 10px;
  border: none;
  padding: 10px 14px;
  font-size: 14px;
}

/* contenu */
.notification-body {
  padding: 8px 28px 26px;
}

/* groupe switches */
.notification-group-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 4px;
}

.notification-group-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

/* switch row */
.notification-switch {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid var(--soft-border);
  background: #F9FBFF;
  margin-bottom: 10px;
  transition: all .22s ease;
}

.notification-switch:hover {
  border-color: var(--sky);
  box-shadow: 0 10px 26px rgba(15,23,42,0.08);
  transform: translateY(-1px);
}

.notification-switch-main {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.notification-switch-main i {
  font-size: 20px;
  margin-top: 2px;
  background: linear-gradient(135deg, var(--deep-blue), var(--sky));
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.notification-switch-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-main);
}
.notification-switch-desc {
  font-size: 12px;
  color: var(--text-muted);
}

/* switch (checkbox) */
.notification-switch .form-check {
  margin-bottom: 0;
}

.notification-switch .form-check-input {
  width: 2.6em;
  height: 1.4em;
  cursor: pointer;
  box-shadow: none;
  border-radius: 999px;
  border-color: #CBD5E1;
  background-color: #E2E8F0;
}

.notification-switch .form-check-input:checked {
  background-color: var(--sky);
  border-color: var(--sky);
}

/* bouton principal */
.btn-notification-save {
  padding: 14px 28px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 15px;
  width: 100%;
  height: 50px;
  background: linear-gradient(135deg, #1D4ED8, #0B1B36);
  border: none;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.45);
  transition: all .25s ease;
}

.btn-notification-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.65);
}

.btn-notification-save i {
  font-size: 18px;
}

/* bouton retour */
.btn-notification-back {
  height: 46px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* responsive */
@media (max-width: 768px) {
  .notification-pref-wrapper { padding: 24px 14px; }
  .notification-header { padding: 22px 20px 4px; }
  .notification-body { padding: 8px 20px 22px; }
  .notification-title { font-size: 22px; }
  .notification-switch {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
CSS);
?>

<div class="notification-pref-wrapper can-form-page">
    <div class="notification-pref-card" role="region" aria-label="Notification Preferences">

        <!-- Header -->
        <div class="notification-header">
            <div class="d-flex align-items-center gap-4">
                <div class="notification-title-icon">
                    <i class="ri-notification-3-fill"></i>
                </div>
                <div>
                    <h1 class="notification-title mb-0">
                        <span>Notification</span> <span class="accent">Preferences</span>
                    </h1>
                    <p class="notification-subtitle mb-0">
                        Choose how you want to be notified about important updates, appointments and feedback.
                    </p>
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        <div class="notification-flash">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <div class="alert alert-<?= Html::encode($type) ?> alert-dismissible fade show" role="alert">
                    <i class="ri-information-line me-2"></i>
                    <?= Html::encode($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="notification-body">
            <?php $form = ActiveForm::begin([
                'id' => 'notification-pref-form',
                'options' => ['class' => 'notification-form'],
            ]); ?>

            <div class="mb-3">
                <div class="notification-group-title">Delivery channels</div>
                <div class="notification-group-subtitle">
                    Select which channels we can use to contact you.
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-mail-send-line"></i>
                        <div>
                            <div class="notification-switch-label">Email notifications</div>
                            <div class="notification-switch-desc">
                                Receive important updates and reminders directly in your inbox.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_by_email', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-layout-grid-line"></i>
                        <div>
                            <div class="notification-switch-label">In-platform notifications</div>
                            <div class="notification-switch-desc">
                                See alerts directly inside your CAN dashboard.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_by_platform', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="notification-group-title">What you want to be notified about</div>
                <div class="notification-group-subtitle">
                    Fine-tune which events will trigger a notification.
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-chat-3-line"></i>
                        <div>
                            <div class="notification-switch-label">MRO replies</div>
                            <div class="notification-switch-desc">
                                Get an alert when an MRO replies to your request or conversation.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_mro_replies', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-calendar-check-line"></i>
                        <div>
                            <div class="notification-switch-label">Appointment requests</div>
                            <div class="notification-switch-desc">
                                Be notified when an appointment request is created or updated.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_appointment_requests', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-file-check-line"></i>
                        <div>
                            <div class="notification-switch-label">PO acceptance</div>
                            <div class="notification-switch-desc">
                                Receive notifications when a purchase order is accepted.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_po_acceptance', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-tools-line"></i>
                        <div>
                            <div class="notification-switch-label">Maintenance proposals</div>
                            <div class="notification-switch-desc">
                                Get notified when maintenance proposals are available.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_maintenance_proposals', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-play-circle-line"></i>
                        <div>
                            <div class="notification-switch-label">Work start</div>
                            <div class="notification-switch-desc">
                                Be notified when maintenance work starts.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_work_start', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>

                <div class="notification-switch">
                    <div class="notification-switch-main">
                        <i class="ri-file-list-3-line"></i>
                        <div>
                            <div class="notification-switch-label">Report submissions</div>
                            <div class="notification-switch-desc">
                                Receive notifications when reports are submitted.
                            </div>
                        </div>
                    </div>
                    <div>
                        <?= $form->field($model, 'notify_report_submissions', [
                            'options' => ['class' => 'form-check form-switch mb-0'],
                            'template' => '{input}{error}',
                        ])->checkbox(['class' => 'form-check-input', 'label' => false]) ?>
                    </div>
                </div>
            </div>

            <!-- ao_id caché -->
            <?= $form->field($model, 'ao_id')
                ->hiddenInput(['value' => Yii::$app->user->identity->ao_id])
                ->label(false) ?>

            <!-- Actions -->
            <div class="d-grid gap-2 mt-4">
                <?= Html::submitButton(
                    '<i class="ri-save-3-line"></i> Save Notification Preferences',
                    ['class' => ' btn btn-primary btn-lg']
                ) ?>

                <!-- <?= Html::a(
                    '<i class="ri-arrow-left-line"></i> Back to Profile',
                    ['/ao/profile/update'],
                    ['class' => 'btn btn-outline-secondary btn-notification-back']
                ) ?> -->
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
