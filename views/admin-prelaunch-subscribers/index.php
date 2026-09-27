<?php

use app\components\UrlIdHelper;
use app\models\PrelaunchSubscriber;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var app\models\PrelaunchSubscriberSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */

$this->title = 'Early Access Subscribers';
$this->registerCssFile(
    '@web/css/admin-prelaunch.css?v=20260926-1',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);

$subscriptionClass = static function ($status) {
    return [
        PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION => 'pending',
        PrelaunchSubscriber::STATUS_CONFIRMED => 'confirmed',
        PrelaunchSubscriber::STATUS_UNSUBSCRIBED => 'unsubscribed',
    ][$status] ?? 'neutral';
};
$deliveryClass = static function ($status) {
    return [
        PrelaunchSubscriber::EMAIL_PENDING => 'pending',
        PrelaunchSubscriber::EMAIL_SENT => 'sent',
        PrelaunchSubscriber::EMAIL_FAILED => 'failed',
    ][$status] ?? 'neutral';
};
$models = $dataProvider->getModels();
$pagination = $dataProvider->pagination;
$total = (int) $dataProvider->getTotalCount();
$first = $total > 0 ? (int) $pagination->offset + 1 : 0;
$last = min((int) $pagination->offset + count($models), $total);
$exportUrl = Url::to(array_merge(
    ['/admin-prelaunch-subscribers/export'],
    Yii::$app->request->queryParams
));
?>

<div class="prelaunch-admin-page">
    <section class="prelaunch-admin-header">
        <div class="prelaunch-admin-title">
            <span class="prelaunch-admin-title-icon"><i class="fas fa-user-clock"></i></span>
            <div>
                <p>Launch operations</p>
                <h1>Early Access Subscribers</h1>
                <span>Private administration of confirmed and pending launch registrations.</span>
            </div>
        </div>
        <div class="prelaunch-admin-actions">
            <span class="prelaunch-private-badge"><i class="fas fa-lock"></i> Admin only</span>
            <a href="<?= Html::encode($exportUrl) ?>" class="prelaunch-export-button">
                <i class="fas fa-file-csv"></i> Export filtered CSV
            </a>
        </div>
    </section>

    <section class="prelaunch-stat-grid" aria-label="Early access summary">
        <?php foreach ([
            ['Total registrations', $stats['total'], 'fa-users', 'blue'],
            ['MRO', $stats['mro'], 'fa-wrench', 'cyan'],
            ['Aircraft Operators', $stats['aircraft_operator'], 'fa-plane', 'violet'],
            ['Confirmed', $stats['confirmed'], 'fa-user-check', 'green'],
            ['Pending', $stats['pending'], 'fa-hourglass-half', 'amber'],
            ['Delivery failed', $stats['delivery_failed'], 'fa-exclamation-triangle', 'red'],
        ] as $card): ?>
            <article class="prelaunch-stat-card">
                <span class="prelaunch-stat-icon <?= Html::encode($card[3]) ?>"><i class="fas <?= Html::encode($card[2]) ?>"></i></span>
                <span><?= Html::encode($card[0]) ?><strong><?= number_format((int) $card[1]) ?></strong></span>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="prelaunch-filter-card">
        <?= Html::beginForm(['/admin-prelaunch-subscribers/index'], 'get', ['class' => 'prelaunch-filter-form']) ?>
            <label class="prelaunch-search">
                <i class="fas fa-search"></i>
                <?= Html::textInput('search', $searchModel->search, [
                    'placeholder' => 'Name, company, email, website or ID...',
                    'aria-label' => 'Search registrations',
                ]) ?>
            </label>
            <?= Html::dropDownList(
                'company_type',
                $searchModel->company_type,
                ['' => 'All company types'] + PrelaunchSubscriber::companyTypeOptions(),
                ['aria-label' => 'Company type']
            ) ?>
            <?= Html::dropDownList(
                'subscription_status',
                $searchModel->subscription_status,
                ['' => 'All subscription statuses'] + PrelaunchSubscriber::subscriptionStatusOptions(),
                ['aria-label' => 'Subscription status']
            ) ?>
            <?= Html::dropDownList(
                'confirmation_email_status',
                $searchModel->confirmation_email_status,
                ['' => 'All email statuses'] + PrelaunchSubscriber::confirmationEmailStatusOptions(),
                ['aria-label' => 'Confirmation email status']
            ) ?>
            <?= Html::input('date', 'date_from', $searchModel->date_from, ['aria-label' => 'Registration date from']) ?>
            <?= Html::input('date', 'date_to', $searchModel->date_to, ['aria-label' => 'Registration date to']) ?>
            <?= Html::dropDownList(
                'page_size',
                $searchModel->page_size,
                [10 => '10 rows', 20 => '20 rows', 50 => '50 rows', 100 => '100 rows'],
                ['aria-label' => 'Rows per page']
            ) ?>
            <?= Html::submitButton('<i class="fas fa-filter"></i> Apply', ['class' => 'prelaunch-filter-button primary']) ?>
            <?= Html::a(
                '<i class="fas fa-undo"></i> Reset',
                ['/admin-prelaunch-subscribers/index'],
                ['class' => 'prelaunch-filter-button reset']
            ) ?>
        <?= Html::endForm() ?>
    </section>

    <section class="prelaunch-table-card">
        <div class="prelaunch-table-scroll">
            <table class="prelaunch-admin-table">
                <thead>
                    <tr>
                        <th><?= $dataProvider->sort->link('created_at', ['label' => 'Registered']) ?></th>
                        <th>Contact</th>
                        <th><?= $dataProvider->sort->link('company_name', ['label' => 'Company']) ?></th>
                        <th><?= $dataProvider->sort->link('company_type', ['label' => 'Type']) ?></th>
                        <th><?= $dataProvider->sort->link('subscription_status', ['label' => 'Subscription']) ?></th>
                        <th><?= $dataProvider->sort->link('confirmation_email_status', ['label' => 'Email delivery']) ?></th>
                        <th><?= $dataProvider->sort->link('confirmed_at', ['label' => 'Confirmed']) ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($models)): ?>
                    <tr><td colspan="8" class="prelaunch-empty"><i class="fas fa-users-slash"></i><span>No registrations match these filters.</span></td></tr>
                <?php endif; ?>
                <?php foreach ($models as $subscriber): ?>
                    <?php $encodedId = UrlIdHelper::encode((int) $subscriber->id); ?>
                    <tr class="<?= $subscriber->confirmation_email_status === PrelaunchSubscriber::EMAIL_FAILED ? 'has-delivery-error' : '' ?>">
                        <td class="nowrap">
                            <strong><?= Yii::$app->formatter->asDatetime($subscriber->created_at, 'php:d M Y') ?></strong>
                            <small><?= Yii::$app->formatter->asTime($subscriber->created_at, 'php:H:i') ?></small>
                        </td>
                        <td>
                            <strong><?= Html::encode(trim($subscriber->first_name . ' ' . $subscriber->last_name)) ?></strong>
                            <small><?= Html::encode($subscriber->business_email) ?></small>
                        </td>
                        <td>
                            <strong><?= Html::encode($subscriber->company_name) ?></strong>
                            <?php if ($subscriber->company_website): ?>
                                <small><?= Html::a(
                                    Html::encode(parse_url($subscriber->company_website, PHP_URL_HOST) ?: $subscriber->company_website),
                                    $subscriber->company_website,
                                    ['target' => '_blank', 'rel' => 'noopener noreferrer']
                                ) ?></small>
                            <?php else: ?><small>No website</small><?php endif; ?>
                        </td>
                        <td><span class="prelaunch-type-badge"><?= Html::encode(PrelaunchSubscriber::companyTypeOptions()[$subscriber->company_type] ?? $subscriber->company_type) ?></span></td>
                        <td><span class="prelaunch-status <?= $subscriptionClass($subscriber->subscription_status) ?>"><?= Html::encode(PrelaunchSubscriber::subscriptionStatusOptions()[$subscriber->subscription_status] ?? $subscriber->subscription_status) ?></span></td>
                        <td>
                            <span class="prelaunch-delivery <?= $deliveryClass($subscriber->confirmation_email_status) ?>">
                                <i class="fas <?= $subscriber->confirmation_email_status === PrelaunchSubscriber::EMAIL_SENT ? 'fa-check-circle' : ($subscriber->confirmation_email_status === PrelaunchSubscriber::EMAIL_FAILED ? 'fa-exclamation-circle' : 'fa-clock') ?>"></i>
                                <?= Html::encode(PrelaunchSubscriber::confirmationEmailStatusOptions()[$subscriber->confirmation_email_status] ?? $subscriber->confirmation_email_status) ?>
                            </span>
                            <small><?= (int) $subscriber->confirmation_attempt_count ?> attempt(s)</small>
                        </td>
                        <td class="nowrap"><?= $subscriber->confirmed_at ? Yii::$app->formatter->asDatetime($subscriber->confirmed_at, 'php:d M Y H:i') : '—' ?></td>
                        <td class="prelaunch-action-cell">
                            <?= Html::a(
                                '<i class="fas fa-eye"></i>',
                                ['/admin-prelaunch-subscribers/view', 'id' => $encodedId],
                                ['title' => 'View registration and delivery details', 'aria-label' => 'View registration']
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="prelaunch-list-footer">
            <span>Showing <strong><?= $first ?>–<?= $last ?></strong> of <strong><?= $total ?></strong> registrations</span>
            <nav aria-label="Subscriber pages">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'prevPageLabel' => '<i class="fas fa-chevron-left"></i>',
                    'nextPageLabel' => '<i class="fas fa-chevron-right"></i>',
                    'maxButtonCount' => 7,
                ]) ?>
            </nav>
        </footer>
    </section>
</div>
