<?php

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var app\models\AuditLog $model */
/** @var yii\data\ActiveDataProvider $relatedProvider */

$this->title = 'Audit Event #' . $model->id;
$oldValues = $model->oldValuesArray;
$newValues = $model->newValuesArray;
$fields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
$resourceName = static function ($class) {
    $position = strrpos((string) $class, '\\');
    return $position === false ? (string) $class : substr((string) $class, $position + 1);
};
$displayValue = static function ($value) {
    if ($value === null) return '<span class="text-muted">NULL</span>';
    if (is_bool($value)) return $value ? 'true' : 'false';
    if (is_array($value)) $value = Json::encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $encoded = Html::encode((string) $value);
    return mb_strlen(strip_tags((string) $value)) > 240
        ? '<details><summary>Show value</summary><pre>' . $encoded . '</pre></details>'
        : nl2br($encoded);
};
?>

<style>
.audit-view{padding:24px}.audit-view-panel{background:#fff;border:1px solid #d8e3f1;border-radius:18px;box-shadow:0 12px 35px rgba(21,52,90,.07);padding:22px;margin-bottom:18px}.audit-view-head{display:flex;justify-content:space-between;gap:18px;align-items:center}.audit-view-head h1{margin:0;color:#082b52}.audit-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:20px}.audit-meta div{background:#f5f9fd;border:1px solid #dce7f2;border-radius:12px;padding:13px}.audit-meta small{display:block;color:#70859d;text-transform:uppercase;font-size:10px;letter-spacing:.06em}.audit-meta strong{display:block;margin-top:4px;color:#173a61;overflow-wrap:anywhere}.audit-change-table th{background:#edf4fa;color:#173a61}.audit-change-table td{vertical-align:top;max-width:430px;overflow-wrap:anywhere}.audit-change-table pre{white-space:pre-wrap;margin-top:8px}.audit-related{width:100%}.audit-related td{padding:10px;border-top:1px solid #e3ebf4}@media(max-width:900px){.audit-meta{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.audit-view{padding:12px}.audit-meta{grid-template-columns:1fr}.audit-view-head{align-items:flex-start;flex-direction:column}}
</style>

<div class="audit-view">
    <section class="audit-view-panel audit-view-head">
        <div><small>Read-only security record</small><h1><i class="fas fa-shield-alt"></i> Audit Event #<?= Html::encode($model->id) ?></h1></div>
        <?= Html::a('<i class="fas fa-arrow-left"></i> Back to Audit Trail', ['admin-audit-log/index'], ['class' => 'btn btn-outline-primary']) ?>
    </section>

    <section class="audit-view-panel">
        <div class="audit-meta">
            <div><small>Action</small><strong><?= Html::encode($model->action) ?></strong></div>
            <div><small>Resource</small><strong><?= Html::encode($resourceName($model->model) ?: 'Authentication') ?></strong></div>
            <div><small>Record</small><strong><?= Html::encode($model->record_id ?: '—') ?></strong></div>
            <div><small>UTC date/time</small><strong><?= Html::encode($model->created_at) ?></strong></div>
            <div><small>User</small><strong><?= Html::encode($model->username ?: 'Guest/System') ?><?= $model->user_id ? ' #' . Html::encode($model->user_id) : '' ?></strong></div>
            <div><small>Company</small><strong><?= Html::encode($model->company_name ?: '—') ?></strong></div>
            <div><small>Role</small><strong><?= Html::encode(strtoupper((string) ($model->user_role ?: '—'))) ?></strong></div>
            <div><small>IP address</small><strong><?= Html::encode($model->ip_address ?: '—') ?></strong></div>
            <div><small>Location</small><strong><?= Html::encode(trim(($model->city ? $model->city . ', ' : '') . ($model->country_name ?: $model->country_code ?: '—'))) ?></strong></div>
            <div><small>HTTP request</small><strong><?= Html::encode(trim(($model->request_method ?: '') . ' ' . ($model->request_url ?: '—'))) ?></strong></div>
            <div><small>Request ID</small><strong><?= Html::encode($model->request_id ?: '—') ?></strong></div>
            <div><small>User-Agent</small><strong><?= Html::encode($model->user_agent ?: '—') ?></strong></div>
        </div>
    </section>

    <section class="audit-view-panel">
        <h3>Changes</h3>
        <div class="table-responsive">
            <table class="table audit-change-table"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>
            <?php foreach ($fields as $field): ?>
                <tr><th><?= Html::encode($field) ?></th><td><?= $displayValue($oldValues[$field] ?? null) ?></td><td><?= $displayValue($newValues[$field] ?? null) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$fields): ?><tr><td colspan="3" class="text-muted text-center">This event has no attribute comparison.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </section>

    <section class="audit-view-panel">
        <h3>Related events from the same request</h3>
        <table class="audit-related"><tbody>
        <?php foreach ($relatedProvider->getModels() as $related): ?>
            <tr><td><?= Html::encode($related->created_at) ?></td><td><strong><?= Html::encode($related->action) ?></strong></td><td><?= Html::encode($resourceName($related->model) ?: 'Authentication') ?> <?= $related->record_id ? '#' . Html::encode($related->record_id) : '' ?></td><td><?= Html::a('View', ['admin-audit-log/view', 'id' => UrlIdHelper::encode($related->id)]) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($relatedProvider->getCount() === 0): ?><tr><td class="text-muted">No related event.</td></tr><?php endif; ?>
        </tbody></table>
        <?= LinkPager::widget(['pagination' => $relatedProvider->pagination]) ?>
    </section>
</div>
