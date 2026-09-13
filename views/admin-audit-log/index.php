<?php

use app\components\UrlIdHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var app\models\AuditLogSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $summary */

$this->title = 'Audit Trail';
$actionOptions = [
    '' => 'All actions',
    'CREATE' => 'CREATE',
    'UPDATE' => 'UPDATE',
    'DELETE' => 'DELETE',
    'LOGIN' => 'LOGIN',
    'LOGIN_FAILED' => 'LOGIN_FAILED',
    'LOGOUT' => 'LOGOUT',
    'BLOCK_USER' => 'BLOCK_USER',
    'UNBLOCK_USER' => 'UNBLOCK_USER',
];
$roleOptions = ['' => 'All roles', 'admin' => 'Admin', 'ao' => 'AO', 'mro' => 'MRO'];
$badgeClass = static function ($action) {
    return [
        'CREATE' => 'is-create', 'UPDATE' => 'is-update', 'DELETE' => 'is-delete',
        'LOGIN' => 'is-login', 'LOGIN_FAILED' => 'is-failed', 'LOGOUT' => 'is-logout',
        'BLOCK_USER' => 'is-delete', 'UNBLOCK_USER' => 'is-create',
    ][$action] ?? 'is-default';
};
$resourceName = static function ($class) {
    $position = strrpos((string) $class, '\\');
    return $position === false ? (string) $class : substr((string) $class, $position + 1);
};
?>

<style>
.audit-page{padding:24px}.audit-panel{background:#fff;border:1px solid #d8e3f1;border-radius:18px;box-shadow:0 12px 35px rgba(21,52,90,.07);padding:22px;margin-bottom:18px}.audit-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.audit-heading h1{margin:0;color:#082b52;font-size:30px}.audit-heading p{margin:5px 0 0;color:#68819d}.audit-cards{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:14px;margin:18px 0}.audit-card{background:#fff;border:1px solid #d8e3f1;border-radius:14px;padding:16px}.audit-card span{display:block;color:#70859d;font-size:12px;text-transform:uppercase;letter-spacing:.06em}.audit-card strong{display:block;color:#082b52;font-size:25px;margin-top:4px}.audit-filters{display:grid;grid-template-columns:repeat(6,minmax(140px,1fr));gap:10px}.audit-filters .form-control,.audit-filters .form-select{min-height:42px;border-color:#cbd9e9}.audit-filter-actions{display:flex;gap:8px;align-items:end}.audit-filter-actions .btn{min-height:42px}.audit-table-wrap{overflow:auto}.audit-table{min-width:1180px;margin:0}.audit-table th{background:#edf4fa;color:#173a61;font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.audit-table td{vertical-align:middle;color:#18334f;font-size:13px}.audit-action{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:700}.audit-action.is-create,.audit-action.is-login{background:#e5f8ee;color:#087641}.audit-action.is-update{background:#e7f2ff;color:#0869be}.audit-action.is-delete,.audit-action.is-failed{background:#ffeaed;color:#bd2437}.audit-action.is-logout{background:#f1edff;color:#6542bc}.audit-action.is-default{background:#edf1f5;color:#526578}.audit-request{font-family:monospace;font-size:11px}.audit-empty{text-align:center;padding:48px!important;color:#70859d!important}.audit-pagination{display:flex;justify-content:space-between;align-items:center;margin-top:16px;color:#70859d}.audit-pagination .pagination{margin:0}@media(max-width:1200px){.audit-filters{grid-template-columns:repeat(3,1fr)}.audit-cards{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.audit-page{padding:12px}.audit-filters,.audit-cards{grid-template-columns:1fr}.audit-heading{align-items:flex-start;flex-direction:column}}
</style>

<div class="audit-page">
    <section class="audit-panel audit-heading">
        <div>
            <h1><i class="fas fa-shield-alt"></i> Audit Trail</h1>
            <p>Review security, authentication and business-data events. All dates are UTC.</p>
        </div>
        <span class="badge bg-primary">Read only</span>
    </section>

    <section class="audit-cards" aria-label="Audit summary">
        <?php foreach ([
            ['Total Events', $summary['total']], ['Logins', $summary['login']],
            ['Failed Logins', $summary['login_failed']], ['Updates', $summary['update']],
            ['Deletes', $summary['delete']],
        ] as $card): ?>
            <div class="audit-card"><span><?= Html::encode($card[0]) ?></span><strong><?= number_format($card[1]) ?></strong></div>
        <?php endforeach; ?>
    </section>

    <section class="audit-panel">
        <?= Html::beginForm(['admin-audit-log/index'], 'get', ['class' => 'audit-filters']) ?>
            <?= Html::input('date', 'date_from', $searchModel->date_from, ['class' => 'form-control', 'aria-label' => 'Date from']) ?>
            <?= Html::input('date', 'date_to', $searchModel->date_to, ['class' => 'form-control', 'aria-label' => 'Date to']) ?>
            <?= Html::input('text', 'username', $searchModel->username, ['class' => 'form-control', 'placeholder' => 'Username']) ?>
            <?= Html::input('number', 'user_id', $searchModel->user_id, ['class' => 'form-control', 'placeholder' => 'User ID']) ?>
            <?= Html::input('text', 'company', $searchModel->company, ['class' => 'form-control', 'placeholder' => 'Company']) ?>
            <?= Html::dropDownList('role', $searchModel->role, $roleOptions, ['class' => 'form-select']) ?>
            <?= Html::dropDownList('action', $searchModel->action, $actionOptions, ['class' => 'form-select']) ?>
            <?= Html::input('text', 'model', $searchModel->model, ['class' => 'form-control', 'placeholder' => 'Resource/model']) ?>
            <?= Html::input('text', 'record_id', $searchModel->record_id, ['class' => 'form-control', 'placeholder' => 'Record ID']) ?>
            <?= Html::input('text', 'ip_address', $searchModel->ip_address, ['class' => 'form-control', 'placeholder' => 'IP address']) ?>
            <?= Html::input('text', 'country', $searchModel->country, ['class' => 'form-control', 'placeholder' => 'Country']) ?>
            <?= Html::input('text', 'request_id', $searchModel->request_id, ['class' => 'form-control', 'placeholder' => 'Request ID']) ?>
            <?= Html::dropDownList('page_size', $searchModel->page_size, [25 => '25 / page', 50 => '50 / page', 100 => '100 / page'], ['class' => 'form-select']) ?>
            <div class="audit-filter-actions">
                <?= Html::submitButton('<i class="fas fa-search"></i> Search', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fas fa-undo"></i> Reset', ['admin-audit-log/index'], ['class' => 'btn btn-dark']) ?>
            </div>
        <?= Html::endForm() ?>
    </section>

    <section class="audit-panel">
        <div class="audit-table-wrap">
            <table class="table audit-table">
                <thead><tr><th>UTC Date/Time</th><th>User</th><th>Company</th><th>Role</th><th>Action</th><th>Resource</th><th>Record</th><th>IP</th><th>Location</th><th>Request ID</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($dataProvider->getModels() as $event): ?>
                    <tr>
                        <td><?= Html::encode($event->created_at) ?></td>
                        <td><strong><?= Html::encode($event->username ?: 'Guest/System') ?></strong><?php if ($event->user_id): ?><br><small>#<?= Html::encode($event->user_id) ?></small><?php endif; ?></td>
                        <td><?= Html::encode($event->company_name ?: '—') ?></td>
                        <td><?= Html::encode(strtoupper((string) ($event->user_role ?: '—'))) ?></td>
                        <td><span class="audit-action <?= $badgeClass($event->action) ?>"><?= Html::encode($event->action) ?></span></td>
                        <td><?= Html::encode($resourceName($event->model) ?: 'Authentication') ?></td>
                        <td><?= Html::encode($event->record_id ?: '—') ?></td>
                        <td><?= Html::encode($event->ip_address ?: '—') ?></td>
                        <td><?= Html::encode(trim(($event->city ? $event->city . ', ' : '') . ($event->country_name ?: $event->country_code ?: '—'))) ?></td>
                        <td><?php if ($event->request_id): ?><?= Html::a(Html::encode(substr($event->request_id, 0, 10)) . '…', ['admin-audit-log/request', 'requestId' => $event->request_id], ['class' => 'audit-request', 'title' => $event->request_id]) ?><?php else: ?>—<?php endif; ?></td>
                        <td><?= Html::a('<i class="fas fa-eye"></i>', ['admin-audit-log/view', 'id' => UrlIdHelper::encode($event->id)], ['class' => 'btn btn-sm btn-info text-white', 'aria-label' => 'View audit event']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($dataProvider->getCount() === 0): ?><tr><td colspan="11" class="audit-empty"><i class="fas fa-inbox fa-2x"></i><br>No audit events found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="audit-pagination">
            <span><?= number_format($dataProvider->getTotalCount()) ?> event(s)</span>
            <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
        </div>
    </section>
</div>
