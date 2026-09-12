<?php

use app\components\UrlIdHelper;
use app\models\SupportTicket;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/*
 * TABLEAU DE PILOTAGE SUPPORT : la page assemble les indicateurs, les filtres et la
 * file de traitement sans modifier les donnees. Les mutations restent reservees a
 * la fiche detaillee afin d'eviter un changement accidentel depuis une liste dense.
 */
$this->title = 'Support Tickets';

$adminOptions = ArrayHelper::map($admins, 'admin_id', static function ($admin) {
    $name = trim((string) $admin->first_name . ' ' . (string) $admin->last_name);
    return $name !== '' ? $name : $admin->username;
});

$statusClass = static function ($status) {
    return [
        'new' => 'new', 'in_progress' => 'progress', 'waiting_user' => 'waiting',
        'resolved' => 'resolved', 'closed' => 'closed',
    ][$status] ?? 'closed';
};

/*
 * BORNES D'AFFICHAGE : elles sont derivees de l'offset backend et restent exactes
 * apres une recherche, un filtre ou un changement de taille de page.
 */
$totalTickets = (int) $pagination->totalCount;
$firstTicket = $totalTickets > 0 ? (int) $pagination->offset + 1 : 0;
$lastTicket = min((int) $pagination->offset + count($tickets), $totalTickets);
?>

<div class="admin-support-page">
    <!-- EN-TETE : identifie clairement la file Support, separee du metier maintenance. -->
    <section class="support-page-header">
        <span class="support-page-icon"><i class="fas fa-headset"></i></span>
        <div><h1>Support Tickets</h1><p>Review, assign and track platform support requests.</p></div>
    </section>

    <!-- INDICATEURS : lecture immediate de la charge globale, independante des filtres. -->
    <section class="support-stat-grid" aria-label="Support ticket summary">
        <article><i class="fas fa-inbox blue"></i><span>New<strong><?= (int) $stats['new'] ?></strong></span></article>
        <article><i class="fas fa-layer-group green"></i><span>Open workload<strong><?= (int) $stats['open'] ?></strong></span></article>
        <article><i class="fas fa-bolt red"></i><span>Urgent<strong><?= (int) $stats['urgent'] ?></strong></span></article>
        <article><i class="fas fa-user-clock amber"></i><span>Waiting for user<strong><?= (int) $stats['waiting'] ?></strong></span></article>
    </section>

    <!--
        FILTRES CUMULABLES : les valeurs restent dans l'URL pour conserver le contexte
        lors d'un retour depuis une fiche. Le filtre d'affectation accepte aussi les
        tickets sans responsable.
    -->
    <section class="support-filter-card">
        <?= Html::beginForm(['admin-support-tickets/index'], 'get', ['class' => 'support-filter-form']) ?>
        <?= Html::hiddenInput('page_size', $filters['pageSize']) ?>
        <label class="support-search"><i class="fas fa-search"></i><?= Html::textInput('search', $filters['search'], ['placeholder' => 'Reference, name, email, subject or request ID...', 'aria-label' => 'Search tickets']) ?></label>
        <?= Html::dropDownList('status', $filters['status'], ['' => 'All statuses'] + SupportTicket::statusOptions(), ['aria-label' => 'Status']) ?>
        <?= Html::dropDownList('priority', $filters['priority'], ['' => 'All priorities'] + SupportTicket::priorityOptions(), ['aria-label' => 'Priority']) ?>
        <?= Html::dropDownList('category', $filters['category'], ['' => 'All categories'] + SupportTicket::categoryOptions(), ['aria-label' => 'Category']) ?>
        <?= Html::dropDownList('assigned_admin_id', $filters['assignedAdminId'], ['' => 'All assignments', 'unassigned' => 'Unassigned'] + $adminOptions, ['aria-label' => 'Assignment']) ?>
        <?= Html::submitButton('<i class="fas fa-search"></i> Search', ['class' => 'support-button primary']) ?>
        <a class="support-button reset" href="<?= Url::to(['admin-support-tickets/index']) ?>"><i class="fas fa-redo-alt"></i> Reset</a>
        <?= Html::endForm() ?>
    </section>

    <!--
        FILE DE TRAITEMENT : une bordure rouge signale l'urgence sans surcharger la ligne.
        Les liens utilisent exclusivement l'identifiant signe produit par UrlIdHelper.
    -->
    <section class="support-table-card">
        <div class="support-table-scroll">
            <table class="support-ticket-table">
                <thead><tr><th>Ticket</th><th>Submitted</th><th>Sender</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned to</th><th>Email delivery</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td class="support-empty" colspan="9"><i class="fas fa-inbox"></i><span>No support tickets found.</span></td></tr>
                <?php endif; ?>
                <?php foreach ($tickets as $ticket): ?>
                    <?php
                    $encodedId = UrlIdHelper::encode((int) $ticket->id);
                    $assignee = 'Unassigned';
                    if ($ticket->assignedAdmin) {
                        $assignee = trim((string) $ticket->assignedAdmin->first_name . ' ' . (string) $ticket->assignedAdmin->last_name);
                        $assignee = $assignee !== '' ? $assignee : $ticket->assignedAdmin->username;
                    }
                    ?>
                    <tr class="<?= $ticket->priority === 'urgent' ? 'urgent-row' : '' ?>">
                        <td><a class="ticket-reference" href="<?= Url::to(['admin-support-tickets/view', 'id' => $encodedId]) ?>"><?= Html::encode($ticket->publicReference) ?></a><small title="<?= Html::encode($ticket->subject) ?>"><?= Html::encode($ticket->subject) ?></small></td>
                        <td class="nowrap"><?= Yii::$app->formatter->asDatetime($ticket->created_at, 'php:d M Y H:i') ?></td>
                        <td><span><?= Html::encode($ticket->name) ?></span><small><?= Html::encode($ticket->email) ?></small></td>
                        <td><span class="category"><i class="fas fa-tag"></i><?= Html::encode(SupportTicket::categoryOptions()[$ticket->category] ?? $ticket->category) ?></span></td>
                        <td><span class="priority <?= $ticket->priority === 'urgent' ? 'urgent' : '' ?>"><i class="fas <?= $ticket->priority === 'urgent' ? 'fa-bolt' : 'fa-minus' ?>"></i><?= Html::encode(SupportTicket::priorityOptions()[$ticket->priority] ?? $ticket->priority) ?></span></td>
                        <td><span class="status <?= $statusClass($ticket->status) ?>"><?= Html::encode(SupportTicket::statusOptions()[$ticket->status] ?? $ticket->status) ?></span></td>
                        <td><span class="assignee <?= $ticket->assignedAdmin ? '' : 'unassigned' ?>"><i class="fas fa-user-shield"></i><?= Html::encode($assignee) ?></span></td>
                        <td>
                            <span class="delivery <?= $ticket->acknowledgement_status === 'sent' ? 'sent' : 'failed' ?>"><i class="fas <?= $ticket->acknowledgement_status === 'sent' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>Requester</span>
                            <span class="delivery <?= $ticket->email_status === 'sent' ? 'sent' : 'failed' ?>"><i class="fas <?= $ticket->email_status === 'sent' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>Support</span>
                        </td>
                        <td class="action-cell"><a href="<?= Url::to(['admin-support-tickets/view', 'id' => $encodedId]) ?>" title="View and manage ticket" aria-label="View ticket"><i class="fas fa-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!--
            PAGINATION BACKEND : le resume indique les lignes reellement retournees par
            LIMIT/OFFSET. Le changement 10/20/50 relance une requete GET en conservant
            tous les filtres, sans charger ni decouper la liste dans JavaScript.
        -->
        <footer class="support-list-footer">
            <div class="support-result-count">Showing <strong><?= $firstTicket ?>–<?= $lastTicket ?></strong> of <strong><?= $totalTickets ?></strong> tickets</div>
            <?= Html::beginForm(['admin-support-tickets/index'], 'get', ['class' => 'support-page-size-form']) ?>
                <?= Html::hiddenInput('search', $filters['search']) ?>
                <?= Html::hiddenInput('status', $filters['status']) ?>
                <?= Html::hiddenInput('priority', $filters['priority']) ?>
                <?= Html::hiddenInput('category', $filters['category']) ?>
                <?= Html::hiddenInput('assigned_admin_id', $filters['assignedAdminId']) ?>
                <label>Rows per page <?= Html::dropDownList('page_size', $filters['pageSize'], array_combine($allowedPageSizes, $allowedPageSizes), ['onchange' => 'this.form.submit()', 'aria-label' => 'Rows per page']) ?></label>
            <?= Html::endForm() ?>
            <nav class="support-pagination" aria-label="Ticket pages">
                <?php if ($pagination->pageCount > 1): ?>
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'prevPageLabel' => '<i class="fas fa-chevron-left"></i>',
                        'nextPageLabel' => '<i class="fas fa-chevron-right"></i>',
                        'maxButtonCount' => 7,
                        'disableCurrentPageButton' => true,
                    ]) ?>
                <?php else: ?>
                    <span class="support-single-page" aria-current="page">1</span>
                <?php endif; ?>
            </nav>
        </footer>
    </section>
</div>

<?php
/*
 * STYLE ISOLE DU MODULE : toutes les regles commencent par une classe Support pour
 * proteger les listes AO/MRO et les autres ecrans administratifs deja harmonises.
 */
$this->registerCss(<<<'CSS'
.admin-support-page{box-sizing:border-box;width:100%;max-width:none;margin:0;padding:18px 22px 26px;color:#10233f}.support-page-header,.support-filter-card,.support-table-card{background:#fff;border:1px solid #d7e3f2;border-radius:18px;box-shadow:0 12px 30px rgba(32,60,96,.06)}.support-page-header{padding:20px 24px;background:linear-gradient(110deg,#fbfdff,#eef6ff);display:flex;align-items:center;gap:14px}.support-page-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#e4f5ff;color:#0797cf;font-size:20px}.support-page-header h1{font-size:27px;font-weight:500;margin:0 0 4px}.support-page-header p{margin:0;color:#6a7f9e}.support-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:18px 0}.support-stat-grid article{display:flex;align-items:center;gap:13px;background:#fff;border:1px solid #dce6f1;border-radius:15px;padding:14px 16px}.support-stat-grid article>i{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#edf6ff}.support-stat-grid .green{color:#12915a;background:#eafaf1}.support-stat-grid .red{color:#e53935;background:#fff0f0}.support-stat-grid .amber{color:#c78300;background:#fff7df}.support-stat-grid .blue{color:#1677d2}.support-stat-grid span{font-size:12px;color:#6e7f97;display:flex;flex-direction:column}.support-stat-grid strong{font-size:22px;color:#10233f;font-weight:650}.support-filter-card{padding:14px;margin-bottom:18px}.support-filter-form{display:grid;grid-template-columns:minmax(310px,1fr) repeat(4,minmax(130px,190px)) auto auto;gap:9px}.support-filter-form select,.support-search{height:44px;border:1px solid #cbdcf0;border-radius:11px;background:#fff}.support-filter-form select{padding:0 10px;color:#354965}.support-search{display:flex;align-items:center;padding:0 13px;gap:9px}.support-search i{color:#6984a6}.support-search input{border:0;outline:0;width:100%;height:40px}.support-button{height:44px;border:0;border-radius:11px;padding:0 15px;display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;font-weight:600}.support-button.primary{background:#2368e8;color:#fff}.support-button.reset{background:#111;color:#fff}.support-table-card{padding:16px}.support-table-scroll{overflow-x:auto;border:1px solid #d7e3ef;border-radius:14px}.support-ticket-table{width:100%;min-width:1320px;border-collapse:collapse}.support-ticket-table th{padding:14px 12px;background:#edf4fa;color:#183454;text-transform:uppercase;letter-spacing:.04em;font-size:11px;text-align:left;white-space:nowrap}.support-ticket-table td{padding:13px 12px;border-top:1px solid #dbe5ef;font-size:13px;vertical-align:middle}.support-ticket-table tbody tr:hover{background:#f8fbff}.support-ticket-table .urgent-row{box-shadow:inset 4px 0 #ef4444;background:#fffafa}.support-ticket-table td>small{display:block;color:#71839d;font-size:11px;max-width:195px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:3px}.ticket-reference{color:#1764dd;font-weight:650;text-decoration:none}.nowrap{white-space:nowrap}.category,.assignee,.delivery{display:flex;align-items:center;gap:6px}.category,.assignee{white-space:nowrap}.assignee.unassigned{color:#8a6b29}.priority,.status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:11px;white-space:nowrap}.priority{background:#edf6ff;color:#176baf;border:1px solid #c7e2f7}.priority.urgent{background:#fff0f0;color:#cf2727;border-color:#ffcaca}.status.new{background:#e7f4ff;color:#0870b3}.status.progress{background:#eaf8ef;color:#147747}.status.waiting{background:#fff6d9;color:#976100}.status.resolved{background:#e8faef;color:#087943}.status.closed{background:#eef1f5;color:#58677b}.delivery{font-size:10px;white-space:nowrap}.delivery.sent{color:#13804d}.delivery.failed{color:#ce3535}.action-cell{text-align:center}.action-cell a{width:38px;height:38px;border-radius:10px;background:#079bd3;color:#fff;display:inline-grid;place-items:center;box-shadow:0 6px 14px rgba(0,126,188,.2)}.support-empty{text-align:center!important;padding:70px!important;color:#7387a2}.support-empty i,.support-empty span{display:block}.support-empty i{font-size:28px;margin-bottom:9px}.support-list-footer{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;padding:16px 4px 2px;color:#62758d;font-size:12px}.support-result-count strong{color:#173b62}.support-page-size-form label{display:flex;align-items:center;gap:8px;margin:0;white-space:nowrap}.support-page-size-form select{height:36px;min-width:68px;border:1px solid #cad9e9;border-radius:9px;background:#fff;padding:0 9px;color:#29425f}.support-pagination{display:flex;justify-content:flex-end;margin:0}.support-pagination .pagination{margin:0;gap:5px}.support-pagination .page-link,.support-single-page{min-width:36px;height:36px;border:1px solid #cad9e9;border-radius:9px!important;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#2564cc}.support-pagination .active .page-link,.support-single-page{background:#2368e8;color:#fff;border-color:#2368e8}.support-pagination .disabled .page-link{color:#a4b1c0;background:#f5f7fa}@media(max-width:1200px){.support-filter-form{grid-template-columns:1fr 1fr 1fr}.support-search{grid-column:1/-1}.support-stat-grid{grid-template-columns:1fr 1fr}}@media(max-width:700px){.admin-support-page{padding:12px}.support-stat-grid,.support-filter-form{grid-template-columns:1fr}.support-search{grid-column:auto}.support-list-footer{grid-template-columns:1fr;justify-items:center}.support-result-count{order:2}.support-pagination{justify-content:center}}
CSS);
?>
