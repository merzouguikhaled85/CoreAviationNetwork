<?php

use app\components\UrlIdHelper;
use app\models\SupportTicket;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/*
 * FICHE DE TRAITEMENT ADMIN : les donnees envoyees par l'utilisateur sont separees
 * des commandes internes. Cette separation permet de verifier le contexte complet
 * avant de changer le statut ou le responsable du ticket.
 */
$this->title = $ticket->publicReference;

$adminOptions = ArrayHelper::map($admins, 'admin_id', static function ($admin) {
    $name = trim((string) $admin->first_name . ' ' . (string) $admin->last_name);
    return $name !== '' ? $name : $admin->username;
});
$adminName = static function ($admin) {
    if (!$admin) {
        return 'Administrator account removed';
    }
    $name = trim((string) $admin->first_name . ' ' . (string) $admin->last_name);
    return $name !== '' ? $name : $admin->username;
};
$context = array_filter([
    'Request reference' => $ticket->request_reference,
    'User type' => $ticket->user_type,
    'User ID' => $ticket->user_id,
    'Visitor fingerprint' => $ticket->ip_hash ? substr($ticket->ip_hash, 0, 12) . '…' : null,
    'Browser' => $ticket->user_agent,
], static function ($value) { return $value !== null && $value !== ''; });

/*
 * NOM DE PIECE JOINTE : une seule valeur normalisee alimente le texte et l'infobulle.
 * Le nom complet reste ainsi disponible au survol même lorsque l'affichage est tronque.
 */
$attachmentDisplayName = $ticket->attachment_path
    ? (string) ($ticket->attachment_original_name ?: basename($ticket->attachment_path))
    : '';
?>

<div class="admin-ticket-view">
    <!-- EN-TETE : la reference publique est lisible, mais l'URL conserve l'ID signe. -->
    <section class="ticket-view-header">
        <div class="ticket-title"><span><i class="fas fa-ticket-alt"></i></span><div><small>Support ticket</small><h1><?= Html::encode($ticket->publicReference) ?></h1><p><?= Html::encode($ticket->subject) ?></p></div></div>
        <a href="<?= Url::to(['admin-support-tickets/index']) ?>"><i class="fas fa-arrow-left"></i> Back to tickets</a>
    </section>

    <!-- RESUME : maintient les quatre informations de priorisation au-dessus du dossier. -->
    <section class="ticket-summary">
        <article><span>Status</span><strong><?= Html::encode(SupportTicket::statusOptions()[$ticket->status] ?? $ticket->status) ?></strong></article>
        <article><span>Priority</span><strong class="<?= $ticket->priority === 'urgent' ? 'urgent-text' : '' ?>"><?= Html::encode(SupportTicket::priorityOptions()[$ticket->priority] ?? $ticket->priority) ?></strong></article>
        <article><span>Category</span><strong><?= Html::encode(SupportTicket::categoryOptions()[$ticket->category] ?? $ticket->category) ?></strong></article>
        <article><span>Submitted</span><strong><?= Yii::$app->formatter->asDatetime($ticket->created_at, 'php:d M Y H:i') ?></strong></article>
    </section>

    <div class="ticket-layout">
        <main class="ticket-main">
            <!-- MESSAGE : le contenu est echappe avant affichage pour neutraliser tout HTML. -->
            <section class="ticket-panel">
                <header><i class="fas fa-comment-alt"></i><h2>Request details</h2></header>
                <div class="ticket-contact"><div><span>Name</span><strong><?= Html::encode($ticket->name) ?></strong></div><div><span>Email</span><a href="mailto:<?= Html::encode($ticket->email) ?>"><?= Html::encode($ticket->email) ?></a></div></div>
                <div class="ticket-message"><?= nl2br(Html::encode($ticket->message)) ?></div>
                <?php if ($ticket->attachment_path): ?>
                    <!-- PIECE JOINTE : le fichier reste prive et passe par le controle admin. -->
                    <div class="ticket-attachment">
                        <i class="fas fa-paperclip"></i>
                        <div>
                            <span>Attachment</span>
                            <strong title="<?= Html::encode($attachmentDisplayName) ?>"><?= Html::encode($attachmentDisplayName) ?></strong>
                            <small><?= $ticket->attachment_size ? Yii::$app->formatter->asShortSize($ticket->attachment_size) : '' ?></small>
                        </div>
                        <a href="<?= Url::to(['admin-support-tickets/download', 'id' => UrlIdHelper::encode((int) $ticket->id)]) ?>"><i class="fas fa-download"></i> Download</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CONTEXTE : informations techniques utiles au diagnostic, sans exposer l'IP brute. -->
            <section class="ticket-panel">
                <header><i class="fas fa-info-circle"></i><h2>Submission context</h2></header>
                <?php if (!$context): ?><p class="ticket-muted">No additional context was recorded.</p><?php endif; ?>
                <dl class="ticket-context"><?php foreach ($context as $label => $value): ?><div><dt><?= Html::encode($label) ?></dt><dd><?= Html::encode((string) $value) ?></dd></div><?php endforeach; ?></dl>
            </section>

            <!--
                CONVERSATION SORTANTE : chaque reponse reste consultable avec son etat
                d'envoi. Une panne SMTP ne supprime donc jamais le contenu deja redige.
            -->
            <section class="ticket-panel">
                <header><i class="fas fa-comments"></i><h2>Support replies</h2></header>
                <?php if (empty($replies)): ?><p class="ticket-muted">No reply has been sent yet.</p><?php endif; ?>
                <div class="support-replies">
                    <?php foreach ($replies as $reply): ?>
                        <article class="support-reply">
                            <div class="support-reply-head">
                                <strong><?= Html::encode($adminName($reply->admin)) ?></strong>
                                <span class="reply-delivery <?= Html::encode($reply->email_status) ?>"><i class="fas <?= $reply->email_status === 'sent' ? 'fa-check-circle' : ($reply->email_status === 'failed' ? 'fa-exclamation-circle' : 'fa-clock') ?>"></i><?= Html::encode(ucfirst($reply->email_status)) ?></span>
                            </div>
                            <div class="support-reply-message"><?= nl2br(Html::encode($reply->message)) ?></div>
                            <time><?= Yii::$app->formatter->asDatetime($reply->sent_at ?: $reply->created_at, 'php:d M Y H:i') ?></time>
                            <?php if ($reply->email_status === 'failed'): ?>
                                <!-- RELANCE MANUELLE : reutilise cette reponse sauvegardee sans la dupliquer. -->
                                <button type="button" class="reply-retry-button" data-url="<?= Url::to(['admin-support-tickets/retry-reply', 'id' => UrlIdHelper::encode((int) $reply->id)]) ?>"><i class="fas fa-redo"></i> Retry delivery</button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!--
                HISTORIQUE : les evenements sont immuables. Le premier element materialise
                la creation, puis chaque modification indique son auteur et les transitions.
            -->
            <section class="ticket-panel">
                <header><i class="fas fa-history"></i><h2>Activity history</h2></header>
                <div class="ticket-timeline">
                    <article><span class="timeline-dot"><i class="fas fa-plus"></i></span><div><strong>Ticket created</strong><p>Submitted by <?= Html::encode($ticket->name) ?>.</p><time><?= Yii::$app->formatter->asDatetime($ticket->created_at, 'php:d M Y H:i') ?></time></div></article>
                    <?php foreach ($history as $event): ?>
                        <article><span class="timeline-dot"><i class="fas fa-pen"></i></span><div>
                            <strong><?= Html::encode($adminName($event->admin)) ?></strong>
                            <?php if ($event->old_status !== $event->new_status): ?><p>Status: <?= Html::encode(SupportTicket::statusOptions()[$event->old_status] ?? $event->old_status) ?> &rarr; <?= Html::encode(SupportTicket::statusOptions()[$event->new_status] ?? $event->new_status) ?></p><?php endif; ?>
                            <?php if ((string) $event->old_assigned_admin_id !== (string) $event->new_assigned_admin_id): ?><p>Assignment updated.</p><?php endif; ?>
                            <?php if ($event->comment): ?><blockquote><?= nl2br(Html::encode($event->comment)) ?></blockquote><?php endif; ?>
                            <time><?= Yii::$app->formatter->asDatetime($event->created_at, 'php:d M Y H:i') ?></time>
                        </div></article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <aside class="ticket-side">
            <!--
                REPONSE AU DEMANDEUR : l'administrateur choisit le statut qui sera applique
                uniquement si l'e-mail est confirme comme envoye. Waiting for user reste
                le choix naturel pour une question ou une demande d'information.
            -->
            <section class="ticket-panel reply-panel">
                <header><i class="fas fa-paper-plane"></i><h2>Reply to requester</h2></header>
                <?= Html::beginForm(['admin-support-tickets/reply', 'id' => UrlIdHelper::encode((int) $ticket->id)], 'post', ['id' => 'support-reply-form', 'class' => 'ticket-form']) ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <div class="reply-recipient"><i class="fas fa-at"></i><span>To</span><strong><?= Html::encode($ticket->email) ?></strong></div>
                <label>Message<?= Html::textarea('message', '', ['rows' => 7, 'minlength' => 2, 'maxlength' => 5000, 'required' => true, 'placeholder' => 'Write a clear response to the requester...']) ?></label>
                <label>Status after successful delivery<?= Html::dropDownList('status_after_send', 'waiting_user', SupportTicket::statusOptions(), ['required' => true]) ?></label>
                <button type="submit" id="support-reply-submit"><i class="fas fa-paper-plane"></i> Send reply</button>
                <?= Html::endForm() ?>
            </section>

            <!--
                GESTION : le formulaire POST utilise le jeton CSRF Yii et l'identifiant
                signe. La note facultative documente la decision dans la piste d'audit.
            -->
            <section class="ticket-panel">
                <header><i class="fas fa-user-cog"></i><h2>Manage ticket</h2></header>
                <?= Html::beginForm(['admin-support-tickets/update', 'id' => UrlIdHelper::encode((int) $ticket->id)], 'post', ['class' => 'ticket-form']) ?>
                <!--
                    PROTECTION CSRF EXPLICITE : l'application historique desactive cette
                    verification globalement. Le jeton est donc ajoute manuellement pour
                    cette mutation sensible et sera controle par le controleur Support.
                -->
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                <label>Status<?= Html::dropDownList('status', $ticket->status, SupportTicket::statusOptions(), ['required' => true]) ?></label>
                <label>Assigned administrator<?= Html::dropDownList('assigned_admin_id', $ticket->assigned_admin_id, ['' => 'Unassigned'] + $adminOptions) ?></label>
                <label>Internal activity note<?= Html::textarea('comment', '', ['rows' => 4, 'maxlength' => 500, 'placeholder' => 'Optional note recorded in history...']) ?></label>
                <button type="submit"><i class="fas fa-save"></i> Save changes</button>
                <?= Html::endForm() ?>
            </section>

            <!-- LIVRAISON E-MAIL : differencie l'accuse visiteur de l'alerte equipe Support. -->
            <section class="ticket-panel">
                <header><i class="fas fa-envelope"></i><h2>Email delivery</h2></header>
                <div class="email-state <?= $ticket->acknowledgement_status === 'sent' ? 'ok' : 'error' ?>"><i class="fas <?= $ticket->acknowledgement_status === 'sent' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i><div><strong>Requester acknowledgement</strong><span><?= Html::encode(ucfirst($ticket->acknowledgement_status)) ?></span></div></div>
                <div class="email-state <?= $ticket->email_status === 'sent' ? 'ok' : 'error' ?>"><i class="fas <?= $ticket->email_status === 'sent' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i><div><strong>Support notification</strong><span><?= Html::encode(ucfirst($ticket->email_status)) ?></span></div></div>
            </section>
        </aside>
    </div>
</div>

<?php
/* STYLE LOCAL : le prefixe ticket limite ces regles a la fiche Support. */
$this->registerCss(<<<'CSS'
.admin-ticket-view{padding:24px;max-width:1580px;margin:auto;color:#122641}.ticket-view-header,.ticket-panel,.ticket-summary article{background:#fff;border:1px solid #d8e4f1;border-radius:17px;box-shadow:0 12px 30px rgba(25,55,90,.06)}.ticket-view-header{padding:18px 22px;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(110deg,#fbfdff,#edf6ff)}.ticket-view-header>a{padding:10px 14px;border:1px solid #cad9e9;border-radius:10px;color:#244565;text-decoration:none;background:#fff}.ticket-title{display:flex;align-items:center;gap:14px}.ticket-title>span{width:47px;height:47px;border-radius:14px;display:grid;place-items:center;background:#e4f5ff;color:#078fc8}.ticket-title small{text-transform:uppercase;letter-spacing:.1em;color:#68809d}.ticket-title h1{font-size:25px;font-weight:600;margin:1px 0}.ticket-title p{margin:0;color:#607691}.ticket-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin:17px 0}.ticket-summary article{padding:14px 16px;display:flex;flex-direction:column;gap:4px}.ticket-summary span,.ticket-contact span,.ticket-attachment span{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#71839a}.ticket-summary strong{font-size:15px;font-weight:600}.urgent-text{color:#dc3030}.ticket-layout{display:grid;grid-template-columns:minmax(0,1fr) 355px;gap:17px;align-items:start}.ticket-main,.ticket-side{display:grid;gap:17px}.ticket-side{position:sticky;top:18px}.ticket-panel{padding:19px}.ticket-panel>header{display:flex;align-items:center;gap:9px;padding-bottom:13px;border-bottom:1px solid #e2eaf3;margin-bottom:15px}.ticket-panel>header i{color:#078fc8}.ticket-panel h2{font-size:17px;font-weight:600;margin:0}.ticket-contact{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ticket-contact>div{padding:12px;border:1px solid #dce6f0;border-radius:11px;background:#f8fbff;display:flex;flex-direction:column;gap:4px}.ticket-contact a,.ticket-contact strong{font-weight:500;color:#173c64}.ticket-message{margin-top:14px;padding:16px;border:1px dashed #c6d8eb;border-radius:12px;line-height:1.65;color:#354d69;background:#fcfdff;overflow-wrap:anywhere}.ticket-attachment{margin-top:14px;padding:12px;display:flex;align-items:center;gap:11px;border:1px solid #cfe0ee;border-radius:12px;background:#f5faff}.ticket-attachment>i{width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#e1f1ff;color:#1478bf}.ticket-attachment div{min-width:0;display:flex;flex:1;flex-direction:column}.ticket-attachment div strong{font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ticket-attachment small{color:#73849a}.ticket-attachment a{padding:9px 12px;border-radius:9px;background:#087e79;color:#fff;text-decoration:none;white-space:nowrap}.ticket-context{margin:0}.ticket-context>div{display:grid;grid-template-columns:160px 1fr;padding:10px 0;border-bottom:1px solid #edf1f5}.ticket-context>div:last-child{border:0}.ticket-context dt{color:#6d7e94;font-weight:500}.ticket-context dd{margin:0;overflow-wrap:anywhere}.ticket-muted{color:#77899f}.ticket-form{display:grid;gap:13px}.ticket-form label{display:grid;gap:6px;font-size:12px;color:#425a75}.ticket-form select,.ticket-form textarea{width:100%;border:1px solid #cad9e9;border-radius:10px;padding:10px;background:#fff;color:#203955}.ticket-form textarea{resize:vertical}.ticket-form button{border:0;border-radius:10px;background:#2368e8;color:#fff;padding:12px;font-weight:600}.email-state{display:flex;align-items:center;gap:10px;padding:11px;border-radius:11px;margin-top:9px}.email-state>i{font-size:18px}.email-state div{display:flex;flex-direction:column}.email-state strong{font-size:12px;font-weight:600}.email-state span{font-size:11px}.email-state.ok{background:#ebfaf1;color:#147747}.email-state.error{background:#fff0f0;color:#bf3030}.ticket-timeline>article{display:grid;grid-template-columns:36px 1fr;gap:10px;padding-bottom:18px;position:relative}.ticket-timeline>article:not(:last-child):before{content:"";position:absolute;left:17px;top:31px;bottom:0;border-left:1px solid #ccdbea}.timeline-dot{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#e7f4ff;color:#0879bb;z-index:1}.ticket-timeline p{margin:4px 0;color:#536b87}.ticket-timeline blockquote{margin:7px 0;padding:9px 11px;border-left:3px solid #7fbce6;background:#f4f9fd;color:#415a74}.ticket-timeline time{font-size:10px;color:#8190a3}@media(max-width:1050px){.ticket-layout{grid-template-columns:1fr}.ticket-side{position:static}.ticket-summary{grid-template-columns:1fr 1fr}}@media(max-width:650px){.admin-ticket-view{padding:13px}.ticket-view-header{align-items:flex-start;gap:14px;flex-direction:column}.ticket-summary,.ticket-contact{grid-template-columns:1fr}.ticket-context>div{grid-template-columns:1fr;gap:3px}.ticket-attachment{flex-wrap:wrap}}
CSS);

/*
 * PROTECTION CONTRE LES TEXTES LONGS : dans une grille CSS, un enfant conserve par
 * defaut une largeur minimale basee sur son contenu. Ces contraintes a zero autorisent
 * la colonne principale, le nom de fichier et les informations techniques a se reduire
 * avant d'appliquer l'ellipse. Le bouton Download reste donc toujours dans la carte et
 * aucune barre de defilement horizontale n'est ajoutee a la page.
 */
$this->registerCss(<<<'CSS'
.admin-ticket-view{
    width:100%;
    min-width:0;
    overflow-x:hidden;
    box-sizing:border-box;
}
.ticket-layout,
.ticket-main,
.ticket-side,
.ticket-panel,
.ticket-contact,
.ticket-contact>div,
.ticket-context>div,
.ticket-context dd{
    min-width:0;
    max-width:100%;
}
.ticket-attachment{
    width:100%;
    min-width:0;
    max-width:100%;
    box-sizing:border-box;
    overflow:hidden;
}
.ticket-attachment>div{
    width:0;
    min-width:0;
    flex:1 1 0%;
    overflow:hidden;
}
.ticket-attachment>div strong{
    display:block;
    width:100%;
    max-width:100%;
}
.ticket-attachment>a{
    flex:0 0 auto;
}
CSS);

/*
 * COMPLEMENTS VISUELS DE CONVERSATION : ils sont declares separement pour rendre
 * explicite l'ajout fonctionnel et garder le style historique de la fiche intact.
 */
$this->registerCss(<<<'CSS'
.reply-panel{border-top:3px solid #159ed2}.reply-recipient{display:grid;grid-template-columns:auto auto 1fr;align-items:center;gap:7px;padding:10px;border-radius:10px;background:#f2f8fd;color:#617791;font-size:11px}.reply-recipient strong{color:#173c64;overflow:hidden;text-overflow:ellipsis}.ticket-form button[disabled]{opacity:.7;cursor:wait}.support-replies{display:grid;gap:12px}.support-reply{padding:14px;border:1px solid #d8e5f1;border-radius:12px;background:#fbfdff}.support-reply-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.support-reply-head strong{font-size:13px}.reply-delivery{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:999px;font-size:10px;background:#eef2f6;color:#637389}.reply-delivery.sent{background:#e9f9ef;color:#147747}.reply-delivery.failed{background:#fff0f0;color:#c73333}.support-reply-message{margin:11px 0;line-height:1.6;color:#354d69;overflow-wrap:anywhere}.support-reply time{font-size:10px;color:#8190a3}.reply-retry-button{float:right;border:1px solid #f0b7b7;border-radius:8px;background:#fff4f4;color:#bd3030;padding:6px 9px;font-size:11px}.support-swal-popup{width:390px!important;border-radius:16px!important;padding:18px!important}.support-swal-title{font-size:19px!important;font-weight:700!important}.support-swal-confirm{border-radius:9px!important;padding:9px 15px!important;background:#2368e8!important}.support-swal-cancel{border-radius:9px!important;padding:9px 15px!important}
CSS);

/*
 * SWEETALERT ET ENVOI EN DEUX TEMPS : le premier appel sauvegarde la reponse, le
 * second contacte SMTP. Le bouton reste verrouille pendant les appels afin d'eviter
 * un double clic et l'administrateur peut retenter exactement le meme message.
 */
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [
    'depends' => [yii\web\JqueryAsset::class],
]);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();
$replyScript = <<<'JS'
(function () {
    const form = document.getElementById('support-reply-form');
    if (!form) return;
    const button = document.getElementById('support-reply-submit');

    const popupOptions = {
        customClass: {
            popup: 'support-swal-popup',
            title: 'support-swal-title',
            confirmButton: 'support-swal-confirm',
            cancelButton: 'support-swal-cancel'
        },
        buttonsStyling: false
    };

    async function readJson(response) {
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
            const error = new Error(payload.message || 'The support operation could not be completed.');
            error.payload = payload;
            throw error;
        }
        return payload;
    }

    async function dispatchSavedReply(dispatch) {
        const data = new FormData();
        data.append(window.supportReplyCsrfParam, window.supportReplyCsrfToken);
        data.append('reply_id', dispatch.replyId);
        data.append('token', dispatch.dispatchToken);
        const response = await fetch(dispatch.dispatchUrl, {method: 'POST', body: data, credentials: 'same-origin'});
        return readJson(response);
    }

    /*
     * RELANCE DEPUIS L'HISTORIQUE : un nouveau jeton court est demande au serveur,
     * puis la fonction d'envoi commune retransmet exactement la reponse sauvegardee.
     */
    document.querySelectorAll('.reply-retry-button').forEach(function (retryButton) {
        retryButton.addEventListener('click', async function () {
            retryButton.disabled = true;
            const data = new FormData();
            data.append(window.supportReplyCsrfParam, window.supportReplyCsrfToken);
            try {
                const dispatch = await readJson(await fetch(retryButton.dataset.url, {method: 'POST', body: data, credentials: 'same-origin'}));
                /* Une autre session peut avoir termine l'envoi entre l'affichage et le clic. */
                if (!dispatch.alreadySent) await dispatchSavedReply(dispatch);
                await Swal.fire({...popupOptions, icon: 'success', title: 'Reply sent', text: 'The saved response was delivered successfully.', confirmButtonText: '<i class="fas fa-check"></i> Done'});
                window.location.reload();
            } catch (error) {
                await Swal.fire({...popupOptions, icon: 'error', title: 'Retry failed', text: error.message, confirmButtonText: '<i class="fas fa-times"></i> Close'});
                retryButton.disabled = false;
            }
        });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Saving reply...';

        let dispatch = null;
        try {
            dispatch = await readJson(await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin'}));
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending email...';
            await dispatchSavedReply(dispatch);
            await Swal.fire({...popupOptions, icon: 'success', title: 'Reply sent', text: 'The requester received your response.', confirmButtonText: '<i class="fas fa-check"></i> Done'});
            window.location.reload();
        } catch (error) {
            const choice = await Swal.fire({...popupOptions, icon: 'error', title: 'Email not sent', text: error.message, showCancelButton: Boolean(dispatch), confirmButtonText: dispatch ? '<i class="fas fa-redo"></i> Retry' : '<i class="fas fa-times"></i> Close', cancelButtonText: '<i class="fas fa-arrow-left"></i> Later'});
            if (dispatch && choice.isConfirmed) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-paper-plane"></i> Send reply';
                try {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Retrying...';
                    await dispatchSavedReply(dispatch);
                    await Swal.fire({...popupOptions, icon: 'success', title: 'Reply sent', text: 'The requester received your response.', confirmButtonText: '<i class="fas fa-check"></i> Done'});
                    window.location.reload();
                    return;
                } catch (retryError) {
                    await Swal.fire({...popupOptions, icon: 'error', title: 'Retry failed', text: retryError.message, confirmButtonText: '<i class="fas fa-times"></i> Close'});
                }
            }
            if (dispatch) window.location.reload();
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-paper-plane"></i> Send reply';
        }
    });
})();
JS;
$replyScript = 'window.supportReplyCsrfParam=' . json_encode($csrfParam)
    . ';window.supportReplyCsrfToken=' . json_encode($csrfToken) . ';' . $replyScript;
$this->registerJs($replyScript);
?>
