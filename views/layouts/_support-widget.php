<?php

use app\models\SupportTicket;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/*
 * PREFILL UTILISATEUR :
 * les proprietes sont lues uniquement lorsque l'identite existe. Le visiteur
 * public conserve des champs vides, tandis qu'un AO/MRO/admin gagne du temps
 * sans que le formulaire ne modifie les donnees de son profil.
 */
$supportIdentity = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
$supportNameParts = $supportIdentity
    ? array_filter([
        trim((string) ($supportIdentity->first_name ?? '')),
        trim((string) ($supportIdentity->last_name ?? '')),
    ])
    : [];
$supportDefaultName = trim(implode(' ', $supportNameParts));

if ($supportDefaultName === '' && $supportIdentity) {
    $supportDefaultName = trim((string) (
        $supportIdentity->company_name
        ?? $supportIdentity->username
        ?? ''
    ));
}

$supportDefaultEmail = $supportIdentity
    ? trim((string) ($supportIdentity->email ?? ''))
    : '';
$supportCategories = SupportTicket::categoryOptions();
$supportPriorities = SupportTicket::priorityOptions();
$supportCreateUrl = Url::to(['/support/create']);
$supportCsrfParam = Yii::$app->request->csrfParam;
$supportCsrfToken = Yii::$app->request->csrfToken;
$supportIsGuest = Yii::$app->user->isGuest;
$supportTurnstileSiteKey = trim((string) (Yii::$app->params['turnstileSiteKey'] ?? ''));
$supportTurnstileSecretConfigured = trim((string) (Yii::$app->params['turnstileSecretKey'] ?? '')) !== '';
$supportTurnstileEnabled = $supportIsGuest
    && $supportTurnstileSiteKey !== ''
    && $supportTurnstileSecretConfigured;

/*
 * ICONES AUTONOMES :
 * certains layouts publics ne chargent pas Remix Icon. Cet enregistrement garantit
 * donc les memes pictogrammes sur le portail, la connexion et les pages publiques.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css',
    ['position' => View::POS_HEAD],
    'can-remixicon'
);

/*
 * SCRIPT TURNSTILE CONDITIONNEL :
 * la ressource Cloudflare n'est chargee que pour un visiteur devant relever le
 * challenge. Les utilisateurs authentifies ne paient donc aucun cout reseau inutile.
 */
if ($supportTurnstileEnabled) {
    $this->registerJsFile(
        'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
        ['position' => View::POS_HEAD, 'defer' => true],
        'can-turnstile-api'
    );
}

/*
 * DESIGN ISOLE :
 * tous les selecteurs commencent par can-support afin de ne pas modifier les
 * formulaires, boutons, modales ou SweetAlert deja presents dans l'application.
 */
$this->registerCss(<<<'CSS'
.can-support-widget{
    --support-navy:#08182d;
    --support-blue:#1677ff;
    --support-cyan:#16b9e8;
    --support-border:#d8e4f1;
    --support-muted:#64748b;
    position:relative;
    z-index:2147482000;
    font-family:"Nunito","Open Sans",Arial,sans-serif;
    transition:opacity .18s ease, visibility .18s ease;
}

/*
 * DIMENSIONS ISOLEES : le widget ne depend plus du box-sizing fourni par Bootstrap
 * ou par la page qui l'heberge. Les paddings et bordures restent ainsi inclus dans
 * les largeurs annoncees et ne peuvent plus creer de defilement horizontal.
 */
.can-support-widget,
.can-support-widget *{
    box-sizing:border-box;
}

/*
 * PRIORITE AU SPINNER :
 * le widget entier devient invisible et non cliquable pendant un chargement global.
 * Il reprend sa place automatiquement lorsque le spinner annonce sa fin.
 */
.can-support-widget.is-spinner-active{
    opacity:0;
    visibility:hidden;
    pointer-events:none;
}

/*
 * REPLI SANS JAVASCRIPT :
 * dans les layouts standards, le loader precede directement le widget. Ce selecteur
 * masque donc deja la bulle pendant les toutes premieres millisecondes du chargement.
 */
#av-global-loader:not(.hidden) ~ .can-support-widget,
#can-global-spinner.is-active ~ .can-support-widget{
    opacity:0;
    visibility:hidden;
    pointer-events:none;
}

/*
 * ETAT FERME GARANTI :
 * certaines regles de composant utilisent display:flex et peuvent prendre le
 * dessus sur le style natif de hidden. Cette regle maintient le panneau et sa
 * confirmation invisibles jusqu'a leur activation explicite par JavaScript.
 */
.can-support-widget [hidden]{
    display:none !important;
}

.can-support-launcher{
    position:fixed;
    right:24px;
    bottom:88px;
    z-index:2147482001;
    width:54px;
    height:54px;
    display:grid;
    place-items:center;
    align-items:center;
    border:1px solid rgba(255,255,255,.3);
    border-radius:50%;
    padding:0;
    background:linear-gradient(135deg, var(--support-blue), var(--support-cyan));
    color:#fff;
    box-shadow:0 14px 34px rgba(15,76,151,.32);
    font-size:14px;
    font-weight:700;
    line-height:1;
    cursor:pointer;
    transition:transform .2s ease, box-shadow .2s ease;
    animation:canSupportIconBlink 2.2s ease-in-out infinite;
}

.can-support-launcher::before,
.can-support-launcher::after{
    content:"";
    position:absolute;
    inset:-5px;
    z-index:-1;
    border:2px solid rgba(22,185,232,.48);
    border-radius:50%;
    animation:canSupportRing 2.2s ease-out infinite;
}

.can-support-launcher::after{animation-delay:1.1s;}

.can-support-launcher:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 38px rgba(15,76,151,.4);
}

.can-support-launcher:focus-visible,
.can-support-close:focus-visible,
.can-support-submit:focus-visible{
    outline:3px solid rgba(56,189,248,.42);
    outline-offset:3px;
}

.can-support-launcher-icon{
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    border-radius:50%;
    background:rgba(255,255,255,.16);
    font-size:21px;
}

@keyframes canSupportIconBlink{
    0%,72%,100%{transform:scale(1);filter:brightness(1);}
    78%{transform:scale(1.08) rotate(-5deg);filter:brightness(1.18);}
    84%{transform:scale(1.02) rotate(5deg);}
    90%{transform:scale(1.08) rotate(-3deg);}
}

@keyframes canSupportRing{
    0%{transform:scale(.82);opacity:.72;}
    70%,100%{transform:scale(1.28);opacity:0;}
}

.can-support-overlay{
    position:fixed;
    inset:0;
    z-index:2147482002;
    background:rgba(2,8,23,.48);
    backdrop-filter:blur(3px);
    -webkit-backdrop-filter:blur(3px);
}

.can-support-panel{
    position:fixed;
    top:16px;
    right:16px;
    bottom:auto;
    z-index:2147482003;
    width:min(440px, calc(100vw - 32px));
    height:min(780px, calc(100vh - 32px));
    display:flex;
    flex-direction:column;
    overflow:hidden;
    border:1px solid rgba(255,255,255,.42);
    border-radius:22px;
    background:#fff;
    box-shadow:0 28px 80px rgba(2,8,23,.32);
    animation:canSupportPanelIn .28s ease both;
}

.can-support-panel.is-dragging{
    animation:none;
    user-select:none;
}

.can-support-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    padding:22px 22px 18px;
    background:linear-gradient(145deg, #071426, #0c2e55);
    color:#fff;
    cursor:grab;
    touch-action:none;
}

.can-support-panel.is-dragging .can-support-header{cursor:grabbing;}
.can-support-header button{cursor:pointer;}

.can-support-eyebrow{
    display:flex;
    align-items:center;
    gap:7px;
    margin:0 0 6px;
    color:#7dd3fc;
    font-size:10px;
    font-weight:800;
    letter-spacing:.12em;
    text-transform:uppercase;
}

.can-support-title{
    margin:0;
    color:#fff;
    font-size:22px;
    font-weight:800;
    line-height:1.2;
}

.can-support-subtitle{
    margin:7px 0 0;
    color:#cbdaf0;
    font-size:12.5px;
    line-height:1.5;
}

.can-support-close{
    width:36px;
    height:36px;
    flex:0 0 36px;
    display:grid;
    place-items:center;
    border:1px solid rgba(255,255,255,.2);
    border-radius:10px;
    background:rgba(255,255,255,.08);
    color:#fff;
    font-size:20px;
    cursor:pointer;
}

.can-support-body{
    flex:1;
    overflow-y:auto;
    overflow-x:hidden;
    padding:20px 22px 22px;
    scrollbar-width:thin;
    scrollbar-color:#b9cade transparent;
}

.can-support-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.can-support-field{min-width:0;}
.can-support-field.is-full{grid-column:1 / -1;}

.can-support-label{
    display:flex;
    align-items:center;
    gap:6px;
    margin:0 0 6px;
    color:#203754;
    font-size:12px;
    font-weight:700;
}

.can-support-required{color:#ef4444;}

.can-support-input,
.can-support-select,
.can-support-textarea{
    width:100%;
    box-sizing:border-box;
    border:1px solid var(--support-border);
    border-radius:10px;
    padding:10px 12px;
    background:#fbfdff;
    color:#14243b;
    font:inherit;
    font-size:13px;
    outline:0;
    transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
}

.can-support-input,
.can-support-select{min-height:42px;}
.can-support-textarea{min-height:112px;resize:vertical;line-height:1.55;}

.can-support-input:focus,
.can-support-select:focus,
.can-support-textarea:focus{
    border-color:#38a7ed;
    background:#fff;
    box-shadow:0 0 0 3px rgba(56,167,237,.13);
}

.can-support-input[aria-invalid="true"],
.can-support-select[aria-invalid="true"],
.can-support-textarea[aria-invalid="true"]{
    border-color:#ef4444;
    box-shadow:0 0 0 3px rgba(239,68,68,.1);
}

.can-support-error{
    display:block;
    min-height:16px;
    margin-top:4px;
    color:#dc2626;
    font-size:11px;
    line-height:1.35;
}

.can-support-turnstile{
    min-height:65px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    border:1px solid var(--support-border);
    border-radius:12px;
    padding:8px;
    background:#f8fbff;
}

.can-support-help{
    margin:5px 0 0;
    color:var(--support-muted);
    font-size:10.5px;
    line-height:1.45;
}

/*
 * PIECE JOINTE PERSONNALISEE :
 * l'input natif reste accessible mais hors ecran. La zone visible conserve ainsi
 * un libelle anglais identique dans Chrome, Edge et Firefox, quelle que soit la langue.
 */
.can-support-file-input{
    position:absolute !important;
    width:1px !important;
    height:1px !important;
    overflow:hidden !important;
    clip:rect(0,0,0,0) !important;
    white-space:nowrap !important;
}

.can-support-dropzone{
    width:100%;
    max-width:100%;
    min-height:112px;
    display:grid;
    place-items:center;
    border:1.5px dashed #8fc5ed;
    border-radius:13px;
    padding:15px;
    background:linear-gradient(145deg,#f8fcff,#eef7ff);
    color:#244665;
    text-align:center;
    overflow:hidden;
    cursor:pointer;
    transition:border-color .2s ease, background .2s ease, box-shadow .2s ease;
}

.can-support-dropzone:hover,
.can-support-dropzone:focus-visible,
.can-support-dropzone.is-dragover{
    border-color:#168bd5;
    background:#eaf7ff;
    box-shadow:0 0 0 3px rgba(22,139,213,.12);
    outline:0;
}

.can-support-dropzone.is-invalid{
    border-color:#ef4444;
    background:#fff5f5;
}

.can-support-drop-icon{
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    margin:0 auto 7px;
    border-radius:11px;
    background:#dff2ff;
    color:#087bc0;
    font-size:21px;
}

.can-support-drop-title{display:block;font-size:12.5px;font-weight:800;}
.can-support-drop-subtitle{display:block;margin-top:3px;font-size:10.5px;color:#64748b;}

.can-support-file-preview{
    width:100%;
    min-width:0;
    max-width:100%;
    display:flex;
    align-items:center;
    gap:10px;
    text-align:left;
    overflow:hidden;
}

.can-support-file-meta{
    width:0;
    min-width:0;
    flex:1 1 0%;
    overflow:hidden;
}
.can-support-file-name{
    display:block;
    width:100%;
    max-width:100%;
    overflow:hidden;
    color:#173653;
    font-size:12px;
    font-weight:800;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.can-support-file-size{display:block;margin-top:2px;color:#64748b;font-size:10.5px;}

.can-support-file-remove{
    width:34px;
    height:34px;
    flex:0 0 34px;
    display:grid;
    place-items:center;
    border:1px solid #fecaca;
    border-radius:9px;
    background:#fff;
    color:#dc2626;
    font-size:17px;
    cursor:pointer;
}

.can-support-note{
    grid-column:1 / -1;
    display:flex;
    gap:9px;
    margin-top:2px;
    padding:11px 12px;
    border:1px solid #bfdbfe;
    border-radius:10px;
    background:#eff8ff;
    color:#28547e;
    font-size:11px;
    line-height:1.45;
}

.can-support-honeypot{
    position:absolute !important;
    width:1px !important;
    height:1px !important;
    overflow:hidden !important;
    clip:rect(0,0,0,0) !important;
    white-space:nowrap !important;
}

.can-support-status{
    display:none;
    margin-bottom:14px;
    padding:11px 12px;
    border-radius:10px;
    font-size:12px;
    line-height:1.45;
}

.can-support-status.is-error{
    display:block;
    border:1px solid #fecaca;
    background:#fef2f2;
    color:#b91c1c;
}

.can-support-actions{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:10px;
    margin-top:16px;
    padding-top:16px;
    border-top:1px solid #e5edf5;
}

.can-support-submit{
    min-height:43px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    border:0;
    border-radius:10px;
    padding:10px 16px;
    background:linear-gradient(135deg, var(--support-blue), var(--support-cyan));
    color:#fff;
    font-size:12.5px;
    font-weight:800;
    box-shadow:0 10px 24px rgba(22,119,255,.22);
    cursor:pointer;
}

.can-support-submit:disabled{opacity:.65;cursor:wait;}

.can-support-success{
    height:100%;
    display:grid;
    align-content:center;
    justify-items:center;
    padding:28px;
    text-align:center;
}

.can-support-success-icon{
    width:62px;
    height:62px;
    display:grid;
    place-items:center;
    border-radius:50%;
    background:#dcfce7;
    color:#16a34a;
    font-size:30px;
}

.can-support-success h3{
    margin:16px 0 8px;
    color:var(--support-navy);
    font-size:21px;
    font-weight:800;
}

.can-support-success p{
    margin:0;
    color:var(--support-muted);
    font-size:13px;
    line-height:1.55;
}

.can-support-success-actions{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:18px;
}

.can-support-exit{
    border-color:#cbd5e1;
    background:#eef2f7;
    color:#334155;
    box-shadow:none;
}

.can-support-exit:hover{
    background:#e2e8f0;
    color:#0f172a;
}

.can-support-reference{
    margin:15px 0;
    padding:9px 13px;
    border:1px solid #bae6fd;
    border-radius:999px;
    background:#f0f9ff;
    color:#075985;
    font-size:13px;
    font-weight:800;
    letter-spacing:.04em;
}

@keyframes canSupportPanelIn{
    from{opacity:0;transform:translateX(24px);}
    to{opacity:1;transform:translateX(0);}
}

@media (max-width:575.98px){
    .can-support-launcher{
        right:14px;
        bottom:76px;
        width:50px;
        height:50px;
        padding:0;
    }
    .can-support-panel{inset:8px;width:auto;height:calc(100vh - 16px);border-radius:18px;}
    .can-support-header{padding:18px 17px 15px;}
    .can-support-body{padding:16px 17px 18px;}
    .can-support-grid{grid-template-columns:1fr;gap:10px;}
    .can-support-field.is-full,
    .can-support-note{grid-column:1;}
}

@media (prefers-reduced-motion:reduce){
    .can-support-panel{animation:none;}
    .can-support-launcher{animation:none;transition:none;}
    .can-support-launcher::before,
    .can-support-launcher::after{animation:none;display:none;}
}
CSS);

/*
 * COMPORTEMENT DU PANNEAU :
 * ce script autonome gere l'accessibilite, les erreurs de validation, la creation
 * du ticket puis l'envoi mail separe. Il ne depend ni de Bootstrap ni de jQuery.
 */
$supportWidgetJs = <<<'JS'
(function(){
    const widget = document.getElementById('canSupportWidget');
    if(!widget || widget.dataset.ready === 'true') return;
    widget.dataset.ready = 'true';

    const launcher = widget.querySelector('[data-support-open]');
    const overlay = widget.querySelector('[data-support-overlay]');
    const panel = widget.querySelector('[data-support-panel]');
    const closeButtons = widget.querySelectorAll('[data-support-close]');
    const form = widget.querySelector('[data-support-form]');
    const formView = widget.querySelector('[data-support-form-view]');
    const successView = widget.querySelector('[data-support-success]');
    const statusBox = widget.querySelector('[data-support-status]');
    const submitButton = widget.querySelector('[data-support-submit]');
    const referenceOutput = widget.querySelector('[data-support-reference]');
    const doneButton = widget.querySelector('[data-support-done]');
    const exitButton = widget.querySelector('[data-support-exit]');
    const requestReference = form.querySelector('[name="SupportTicket[request_reference]"]');
    const turnstileContainer = widget.querySelector('[data-support-turnstile]');
    const dragHandle = widget.querySelector('[data-support-drag-handle]');
    const fileInput = widget.querySelector('[data-support-file-input]');
    const dropzone = widget.querySelector('[data-support-dropzone]');
    const fileEmpty = widget.querySelector('[data-support-file-empty]');
    const filePreview = widget.querySelector('[data-support-file-preview]');
    const fileName = widget.querySelector('[data-support-file-name]');
    const fileSize = widget.querySelector('[data-support-file-size]');
    const fileRemove = widget.querySelector('[data-support-file-remove]');
    const attachmentError = widget.querySelector('[data-error-for="attachment"]');
    let turnstileWidgetId = null;
    let attachmentIsValid = true;
    let previouslyFocused = null;

    /*
     * SYNCHRONISATION AVEC LES SPINNERS :
     * le projet contient le loader av-global-loader et un ancien spinner CAN.
     * Un observateur de classes masque le Support pendant leur etat actif sans
     * temporisation arbitraire et sans modifier leur fonctionnement historique.
     */
    function loaderIsActive(loader){
        if(!loader) return false;
        if(loader.id === 'av-global-loader'){
            return !loader.classList.contains('hidden') && loader.style.display !== 'none';
        }
        return loader.classList.contains('is-active')
            || loader.getAttribute('aria-hidden') === 'false';
    }

    function syncSupportWithLoaders(){
        const active = loaderIsActive(document.getElementById('av-global-loader'))
            || loaderIsActive(document.getElementById('can-global-spinner'));
        widget.classList.toggle('is-spinner-active', active);
    }

    ['av-global-loader', 'can-global-spinner'].forEach(function(id){
        const loader = document.getElementById(id);
        if(!loader) return;
        new MutationObserver(syncSupportWithLoaders).observe(loader, {
            attributes:true,
            attributeFilter:['class', 'style', 'aria-hidden']
        });
    });
    syncSupportWithLoaders();

    /*
     * CHARGEMENT A LA DEMANDE :
     * le challenge est rendu lorsque le visiteur ouvre le panneau. Ce choix evite
     * une iframe active en permanence sur les pages publiques et preserve le mobile.
     */
    function ensureTurnstile(attempt){
        if(!turnstileContainer || widget.dataset.turnstileEnabled !== 'true') return;
        if(window.turnstile && turnstileWidgetId === null){
            turnstileWidgetId = window.turnstile.render(turnstileContainer, {
                sitekey:widget.dataset.turnstileSitekey,
                action:'support_ticket',
                theme:'light',
                size:'flexible',
                language:'en'
            });
            return;
        }
        if(!window.turnstile && (attempt || 0) < 25){
            window.setTimeout(function(){ ensureTurnstile((attempt || 0) + 1); }, 200);
        }
    }

    function resetTurnstile(){
        if(window.turnstile && turnstileWidgetId !== null){
            window.turnstile.reset(turnstileWidgetId);
        }
    }

    /*
     * REFERENCE CONTEXTUELLE :
     * si la page contient un identifiant dans son URL, il est propose dans le
     * formulaire mais reste modifiable car il peut ne pas designer une request.
     */
    function applyContextReference(){
        try {
            const currentUrl = new URL(window.location.href);
            const contextId = currentUrl.searchParams.get('id');
            if(contextId && requestReference && !requestReference.value){
                requestReference.value = contextId;
            }
        } catch(e) {}
    }
    applyContextReference();

    /*
     * VALIDATION LOCALE DE LA PIECE JOINTE :
     * le serveur reste l'autorite finale, mais ce controle explique immediatement
     * un depassement de 5 Mo ou un format refuse avant tout transfert reseau.
     */
    const maxAttachmentSize = 5 * 1024 * 1024;
    const allowedAttachmentExtensions = ['pdf', 'png', 'jpg', 'jpeg'];

    function formatFileSize(bytes){
        if(bytes < 1024) return bytes + ' B';
        if(bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function clearAttachment(){
        fileInput.value = '';
        attachmentIsValid = true;
        fileEmpty.hidden = false;
        filePreview.hidden = true;
        fileName.textContent = '';
        fileName.removeAttribute('title');
        fileSize.textContent = '';
        dropzone.classList.remove('is-invalid', 'is-dragover');
        attachmentError.textContent = '';
        fileInput.removeAttribute('aria-invalid');
    }

    function displayAttachment(file){
        if(!file){
            clearAttachment();
            return false;
        }

        const extension = (file.name.split('.').pop() || '').toLowerCase();
        /* Le nom complet reste consultable au survol lorsque l'ellipse est affichee. */
        fileName.title = file.name;
        if(file.size > maxAttachmentSize){
            attachmentIsValid = false;
            dropzone.classList.add('is-invalid');
            attachmentError.textContent = 'File is too large. Maximum allowed size is 5 MB.';
            fileInput.setAttribute('aria-invalid', 'true');
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size) + ' — rejected';
            fileEmpty.hidden = true;
            filePreview.hidden = false;
            return false;
        }
        if(allowedAttachmentExtensions.indexOf(extension) === -1){
            attachmentIsValid = false;
            dropzone.classList.add('is-invalid');
            attachmentError.textContent = 'Unsupported file type. Please select a PDF, PNG or JPG file.';
            fileInput.setAttribute('aria-invalid', 'true');
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size) + ' — rejected';
            fileEmpty.hidden = true;
            filePreview.hidden = false;
            return false;
        }

        dropzone.classList.remove('is-invalid');
        attachmentIsValid = true;
        attachmentError.textContent = '';
        fileInput.removeAttribute('aria-invalid');
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileEmpty.hidden = true;
        filePreview.hidden = false;
        return true;
    }

    fileInput.addEventListener('change', function(){
        displayAttachment(fileInput.files[0] || null);
    });
    dropzone.addEventListener('click', function(event){
        if(event.target.closest('[data-support-file-remove]')) return;
        fileInput.click();
    });
    dropzone.addEventListener('keydown', function(event){
        if(event.key === 'Enter' || event.key === ' '){
            event.preventDefault();
            fileInput.click();
        }
    });
    ['dragenter', 'dragover'].forEach(function(type){
        dropzone.addEventListener(type, function(event){
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function(type){
        dropzone.addEventListener(type, function(event){
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
        });
    });
    dropzone.addEventListener('drop', function(event){
        const droppedFile = event.dataTransfer && event.dataTransfer.files
            ? event.dataTransfer.files[0]
            : null;
        if(!droppedFile || !displayAttachment(droppedFile)) return;
        const transfer = new DataTransfer();
        transfer.items.add(droppedFile);
        fileInput.files = transfer.files;
    });
    fileRemove.addEventListener('click', function(event){
        event.preventDefault();
        event.stopPropagation();
        clearAttachment();
        dropzone.focus();
    });

    /*
     * DEPLACEMENT DU PANNEAU SUR ORDINATEUR :
     * Pointer Events gere souris et stylet. Les coordonnees sont bornees pour que
     * l'en-tete et le bouton Close restent toujours accessibles dans la fenetre.
     */
    let dragState = null;
    dragHandle.addEventListener('pointerdown', function(event){
        if(window.innerWidth < 768 || event.target.closest('button')) return;
        const rect = panel.getBoundingClientRect();
        dragState = {
            pointerId:event.pointerId,
            offsetX:event.clientX - rect.left,
            offsetY:event.clientY - rect.top,
            width:rect.width,
            height:rect.height
        };
        panel.style.left = rect.left + 'px';
        panel.style.top = rect.top + 'px';
        panel.style.right = 'auto';
        panel.classList.add('is-dragging');
        dragHandle.setPointerCapture(event.pointerId);
        event.preventDefault();
    });
    dragHandle.addEventListener('pointermove', function(event){
        if(!dragState || dragState.pointerId !== event.pointerId) return;
        const maxLeft = Math.max(8, window.innerWidth - dragState.width - 8);
        const maxTop = Math.max(8, window.innerHeight - dragState.height - 8);
        const left = Math.min(maxLeft, Math.max(8, event.clientX - dragState.offsetX));
        const top = Math.min(maxTop, Math.max(8, event.clientY - dragState.offsetY));
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    });
    function stopPanelDrag(event){
        if(!dragState || (event && dragState.pointerId !== event.pointerId)) return;
        panel.classList.remove('is-dragging');
        dragState = null;
    }
    dragHandle.addEventListener('pointerup', stopPanelDrag);
    dragHandle.addEventListener('pointercancel', stopPanelDrag);
    window.addEventListener('resize', function(){
        if(window.innerWidth < 768){
            panel.style.left = '';
            panel.style.top = '';
            panel.style.right = '';
        }
    });

    function openPanel(){
        previouslyFocused = document.activeElement;
        overlay.hidden = false;
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        ensureTurnstile(0);
        window.setTimeout(function(){
            const firstField = panel.querySelector('input:not([type="hidden"]):not(.can-support-honeypot), select, textarea');
            if(firstField) firstField.focus();
        }, 30);
    }

    function closePanel(){
        overlay.hidden = true;
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        if(previouslyFocused && typeof previouslyFocused.focus === 'function'){
            previouslyFocused.focus();
        }
    }

    function clearErrors(){
        statusBox.className = 'can-support-status';
        statusBox.textContent = '';
        form.querySelectorAll('[aria-invalid="true"]').forEach(function(field){
            field.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('[data-error-for]').forEach(function(error){
            error.textContent = '';
        });
        dropzone.classList.remove('is-invalid');
    }

    /*
     * REINITIALISATION APRES SUCCES :
     * les valeurs saisies, l'ancienne reference et le jeton CAPTCHA a usage unique
     * sont effaces. Les informations pre-remplies du compte reviennent via reset().
     */
    function resetCompletedForm(){
        form.reset();
        clearAttachment();
        clearErrors();
        referenceOutput.textContent = '';
        successView.hidden = true;
        formView.hidden = false;
        applyContextReference();
        resetTurnstile();
    }

    function showErrors(payload){
        statusBox.className = 'can-support-status is-error';
        statusBox.textContent = payload.message || 'The support request could not be submitted.';

        const errors = payload.errors || {};
        Object.keys(errors).forEach(function(attribute){
            const field = form.querySelector('[name="SupportTicket[' + attribute + ']"]');
            const error = form.querySelector('[data-error-for="' + attribute + '"]');
            if(field) field.setAttribute('aria-invalid', 'true');
            if(error) error.textContent = Array.isArray(errors[attribute])
                ? errors[attribute][0]
                : errors[attribute];
            if(attribute === 'attachment') dropzone.classList.add('is-invalid');
            if(attribute === 'attachment') attachmentIsValid = false;
        });

        const invalidField = form.querySelector('[aria-invalid="true"]');
        if(invalidField) invalidField.focus();
        resetTurnstile();
    }

    function dispatchEmail(payload){
        if(!payload.id || !payload.dispatchUrl || !payload.dispatchToken) return;
        const dispatchData = new FormData();
        dispatchData.append(widget.dataset.csrfParam, widget.dataset.csrfToken);
        dispatchData.append('id', payload.id);
        dispatchData.append('token', payload.dispatchToken);

        /*
         * ENVOI NON BLOQUANT :
         * keepalive laisse la seconde requete se terminer meme si l'utilisateur
         * change rapidement de page. Son resultat ne remet jamais en cause le ticket.
         */
        fetch(payload.dispatchUrl, {
            method:'POST',
            body:dispatchData,
            credentials:'same-origin',
            keepalive:true,
            headers:{'X-Requested-With':'XMLHttpRequest'}
        }).catch(function(){});
    }

    launcher.addEventListener('click', openPanel);
    overlay.addEventListener('click', closePanel);
    closeButtons.forEach(function(button){
        button.addEventListener('click', closePanel);
    });
    doneButton.addEventListener('click', function(){
        resetCompletedForm();
        const firstField = form.querySelector('input:not([type="hidden"]):not(.can-support-honeypot), select, textarea');
        if(firstField) firstField.focus();
    });
    exitButton.addEventListener('click', function(){
        resetCompletedForm();
        closePanel();
    });

    document.addEventListener('keydown', function(event){
        if(event.key === 'Escape' && !panel.hidden) closePanel();
    });

    form.addEventListener('submit', function(event){
        event.preventDefault();

        /*
         * BLOCAGE D'UN FICHIER INVALIDE :
         * comme la piece jointe est facultative, vider silencieusement un fichier de
         * plus de 5 Mo pourrait envoyer le ticket sans lui. On oblige donc le visiteur
         * a supprimer le fichier refuse ou a en choisir un conforme avant l'envoi.
         */
        if(!attachmentIsValid){
            dropzone.focus();
            return;
        }
        clearErrors();
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="ri-loader-4-line" aria-hidden="true"></i><span>Sending...</span>';

        fetch(form.action, {
            method:'POST',
            body:new FormData(form),
            credentials:'same-origin',
            headers:{'X-Requested-With':'XMLHttpRequest'}
        })
        .then(function(response){
            return response.json().then(function(payload){
                return {ok:response.ok, payload:payload};
            });
        })
        .then(function(result){
            if(!result.ok || !result.payload.success){
                showErrors(result.payload);
                return;
            }

            referenceOutput.textContent = result.payload.reference || 'SUP-RECEIVED';
            formView.hidden = true;
            successView.hidden = false;
            successView.focus();
            dispatchEmail(result.payload);
        })
        .catch(function(){
            showErrors({
                message:'The support service could not be reached. Please check your connection and try again.'
            });
            resetTurnstile();
        })
        .finally(function(){
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="ri-send-plane-line" aria-hidden="true"></i><span>Send support request</span>';
        });
    });
})();
JS;

$this->registerJs($supportWidgetJs, View::POS_END);
?>

<!--
  BULLE SUPPORT GLOBALE :
  le lanceur reste au-dessus des widgets existants. Le panneau est rendu une
  seule fois par layout et masque son contenu tant que l'utilisateur ne l'ouvre pas.
-->
<aside
    class="can-support-widget"
    id="canSupportWidget"
    data-csrf-param="<?= Html::encode($supportCsrfParam) ?>"
    data-csrf-token="<?= Html::encode($supportCsrfToken) ?>"
    data-turnstile-enabled="<?= $supportTurnstileEnabled ? 'true' : 'false' ?>"
    data-turnstile-sitekey="<?= Html::encode($supportTurnstileSiteKey) ?>"
>
    <button
        class="can-support-launcher"
        type="button"
        data-support-open
        aria-controls="canSupportPanel"
        aria-expanded="false"
        aria-label="Contact Core Aviation Network support"
        title="Support"
    >
        <span class="can-support-launcher-icon" aria-hidden="true"><i class="ri-customer-service-2-line"></i></span>
    </button>

    <div class="can-support-overlay" data-support-overlay hidden></div>

    <section
        class="can-support-panel"
        id="canSupportPanel"
        data-support-panel
        role="dialog"
        aria-modal="true"
        aria-labelledby="canSupportTitle"
        hidden
    >
        <header class="can-support-header" data-support-drag-handle title="Drag to reposition this support window">
            <div>
                <p class="can-support-eyebrow"><i class="ri-draggable" aria-hidden="true"></i> Platform assistance</p>
                <h2 class="can-support-title" id="canSupportTitle">How can we help?</h2>
                <p class="can-support-subtitle">Your message is recorded before the notification e-mail is sent.</p>
            </div>
            <button class="can-support-close" type="button" data-support-close aria-label="Close support form">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </header>

        <div class="can-support-body">
            <div data-support-form-view>
                <div class="can-support-status" data-support-status role="alert"></div>

                <form
                    action="<?= Html::encode($supportCreateUrl) ?>"
                    method="post"
                    enctype="multipart/form-data"
                    data-support-form
                    novalidate
                >
                    <?= Html::hiddenInput($supportCsrfParam, $supportCsrfToken) ?>

                    <!-- Champ anti-robot hors ecran, absent du parcours clavier. -->
                    <input
                        class="can-support-honeypot"
                        type="text"
                        name="SupportTicket[website]"
                        value=""
                        tabindex="-1"
                        autocomplete="off"
                        aria-hidden="true"
                    >

                    <div class="can-support-grid">
                        <div class="can-support-field">
                            <label class="can-support-label" for="canSupportName">
                                <i class="ri-user-line" aria-hidden="true"></i> Name <span class="can-support-required">*</span>
                            </label>
                            <input class="can-support-input" id="canSupportName" name="SupportTicket[name]" type="text" maxlength="120" value="<?= Html::encode($supportDefaultName) ?>" autocomplete="name" required>
                            <span class="can-support-error" data-error-for="name"></span>
                        </div>

                        <div class="can-support-field">
                            <label class="can-support-label" for="canSupportEmail">
                                <i class="ri-mail-line" aria-hidden="true"></i> E-mail <span class="can-support-required">*</span>
                            </label>
                            <input class="can-support-input" id="canSupportEmail" name="SupportTicket[email]" type="email" maxlength="190" value="<?= Html::encode($supportDefaultEmail) ?>" autocomplete="email" required>
                            <span class="can-support-error" data-error-for="email"></span>
                        </div>

                        <div class="can-support-field">
                            <label class="can-support-label" for="canSupportCategory">
                                <i class="ri-folder-help-line" aria-hidden="true"></i> Category <span class="can-support-required">*</span>
                            </label>
                            <select class="can-support-select" id="canSupportCategory" name="SupportTicket[category]" required>
                                <?php foreach ($supportCategories as $value => $label): ?>
                                    <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="can-support-error" data-error-for="category"></span>
                        </div>

                        <div class="can-support-field">
                            <label class="can-support-label" for="canSupportPriority">
                                <i class="ri-alarm-warning-line" aria-hidden="true"></i> Support priority <span class="can-support-required">*</span>
                            </label>
                            <select class="can-support-select" id="canSupportPriority" name="SupportTicket[priority]" required>
                                <?php foreach ($supportPriorities as $value => $label): ?>
                                    <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="can-support-error" data-error-for="priority"></span>
                        </div>

                        <div class="can-support-field is-full">
                            <label class="can-support-label" for="canSupportSubject">
                                <i class="ri-text" aria-hidden="true"></i> Subject <span class="can-support-required">*</span>
                            </label>
                            <input class="can-support-input" id="canSupportSubject" name="SupportTicket[subject]" type="text" minlength="4" maxlength="190" required>
                            <span class="can-support-error" data-error-for="subject"></span>
                        </div>

                        <div class="can-support-field is-full">
                            <label class="can-support-label" for="canSupportRequest">
                                <i class="ri-hashtag" aria-hidden="true"></i> Request reference
                            </label>
                            <input class="can-support-input" id="canSupportRequest" name="SupportTicket[request_reference]" type="text" maxlength="100" placeholder="Optional: request ID or encoded reference">
                            <span class="can-support-error" data-error-for="request_reference"></span>
                        </div>

                        <div class="can-support-field is-full">
                            <label class="can-support-label" for="canSupportMessage">
                                <i class="ri-message-3-line" aria-hidden="true"></i> Message <span class="can-support-required">*</span>
                            </label>
                            <textarea class="can-support-textarea" id="canSupportMessage" name="SupportTicket[message]" minlength="20" maxlength="5000" placeholder="Describe what happened, the expected result and any useful steps." required></textarea>
                            <span class="can-support-error" data-error-for="message"></span>
                        </div>

                        <div class="can-support-field is-full">
                            <label class="can-support-label" for="canSupportAttachment">
                                <i class="ri-attachment-2" aria-hidden="true"></i> Attachment
                            </label>
                            <!--
                              DEPOT DE FICHIER :
                              l'input reel reste associe au label et au clavier. Le cadre
                              fournit le glisser-deposer, l'apercu et la suppression explicite.
                            -->
                            <input class="can-support-file-input" id="canSupportAttachment" name="SupportTicket[attachment]" type="file" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" data-support-file-input>
                            <div class="can-support-dropzone" data-support-dropzone role="button" tabindex="0" aria-controls="canSupportAttachment">
                                <div data-support-file-empty>
                                    <span class="can-support-drop-icon" aria-hidden="true"><i class="ri-upload-cloud-2-line"></i></span>
                                    <span class="can-support-drop-title">Drag and drop your file here</span>
                                    <span class="can-support-drop-subtitle">or click to browse your device</span>
                                </div>
                                <div class="can-support-file-preview" data-support-file-preview hidden>
                                    <span class="can-support-drop-icon" aria-hidden="true"><i class="ri-file-check-line"></i></span>
                                    <span class="can-support-file-meta">
                                        <span class="can-support-file-name" data-support-file-name></span>
                                        <span class="can-support-file-size" data-support-file-size></span>
                                    </span>
                                    <button class="can-support-file-remove" type="button" data-support-file-remove aria-label="Remove selected attachment" title="Remove attachment">
                                        <i class="ri-delete-bin-line" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="can-support-help">Optional PDF, PNG or JPG. Maximum file size: 5 MB.</p>
                            <span class="can-support-error" data-error-for="attachment"></span>
                        </div>

                        <?php if ($supportTurnstileEnabled): ?>
                            <!--
                              CAPTCHA VISITEUR :
                              Turnstile injecte cf-turnstile-response dans ce formulaire.
                              La valeur reste inutile sans sa validation serveur obligatoire.
                            -->
                            <div class="can-support-field is-full">
                                <div class="can-support-turnstile" data-support-turnstile></div>
                                <span class="can-support-error" data-error-for="turnstile"></span>
                            </div>
                        <?php endif; ?>

                        <div class="can-support-note">
                            <i class="ri-information-line" aria-hidden="true"></i>
                            <span>For immediate flight-safety or AOG operations, continue using your established operational emergency channel.</span>
                        </div>
                    </div>

                    <div class="can-support-actions">
                        <button class="can-support-submit" type="submit" data-support-submit>
                            <i class="ri-send-plane-line" aria-hidden="true"></i>
                            <span>Send support request</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Confirmation durable affichee des que la base retourne la reference. -->
            <div class="can-support-success" data-support-success tabindex="-1" hidden>
                <span class="can-support-success-icon" aria-hidden="true"><i class="ri-check-line"></i></span>
                <h3>Request recorded</h3>
                <p>A confirmation e-mail has been requested. Keep this reference if you need to follow up with the platform team.</p>
                <div class="can-support-reference" data-support-reference></div>
                <!--
                  ACTIONS DE FIN :
                  Done prepare un nouveau ticket dans le panneau ; Exit remet aussi
                  le formulaire a zero puis rend la page courante a l'utilisateur.
                -->
                <div class="can-support-success-actions">
                    <button class="can-support-submit" type="button" data-support-done>
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                        <span>Done</span>
                    </button>
                    <button class="can-support-submit can-support-exit" type="button" data-support-exit>
                        <i class="ri-logout-box-r-line" aria-hidden="true"></i>
                        <span>Exit</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</aside>
