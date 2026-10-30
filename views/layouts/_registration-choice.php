<?php
/** @var yii\web\View $this */
use app\components\PrelaunchMode;
use yii\helpers\Url;

if (!Yii::$app->user->isGuest) {
    return;
}
$prelaunchMode = PrelaunchMode::isEnabled();
$this->registerCss(<<<'CSS'
.nav-aviation .btn-register-now{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:44px;
    padding:10px 22px;
    border:1px solid rgba(125,211,252,.45);
    border-radius:9px;
    background:linear-gradient(135deg, #0ea5e9, #0284c7);
    box-shadow:0 6px 20px rgba(14,165,233,.22);
    color:#fff;
    font-family:inherit;
    font-size:14px;
    font-weight:700;
    line-height:1.4;
    white-space:nowrap;
    cursor:pointer;
    transition:transform .2s ease, box-shadow .2s ease;
}
.nav-aviation .btn-register-now:hover{transform:translateY(-2px); box-shadow:0 10px 26px rgba(14,165,233,.35);}
.nav-aviation .btn-register-now:focus-visible{outline:3px solid #7dd3fc; outline-offset:4px;}
.nav-aviation .mobile-register-now{display:none;}
body.can-registration-open{overflow:hidden;}
.can-registration-dialog{
    box-sizing:border-box;
    width:min(920px, calc(100% - 32px));
    max-height:calc(100dvh - 32px);
    margin:auto;
    padding:0;
    border:1px solid #d7e3ef;
    border-radius:16px;
    background:#f4f8fc;
    color:#10243d;
    font-family:inherit;
    box-shadow:0 32px 100px rgba(0,10,24,.4);
    overflow:auto;
}
.can-registration-dialog::backdrop{background:rgba(2,12,27,.76); backdrop-filter:blur(6px);}
.can-registration-dialog[open]{animation:canRegistrationIn .22s ease both;}
.can-registration-heading{
    position:relative;
    padding:28px 36px;
    border-bottom:1px solid #163d5c;
    background:radial-gradient(ellipse at 100% 0, rgba(14,165,233,.2), transparent 65%), #071b30;
    text-align:left;
}
.can-registration-close{
    position:absolute; top:18px; right:18px;
    display:grid; place-items:center;
    width:36px; height:36px; padding:0;
    border:1px solid rgba(255,255,255,.2); border-radius:8px;
    background:rgba(255,255,255,.06); color:#e2edf8; font-size:23px; cursor:pointer;
    transition:background .2s ease;
}
.can-registration-close:hover{background:rgba(255,255,255,.16);}
.can-registration-eyebrow{
    display:flex; align-items:center; gap:9px;
    margin:0 44px 14px 0;
    color:#7dd3fc; font-size:10px; font-weight:700; letter-spacing:.16em; text-transform:uppercase;
}
.can-registration-eyebrow i{font-size:19px;}
.can-registration-heading h2{
    margin:0 24px 0 0; color:#fff; font-family:inherit;
    font-size:clamp(23px, 2.6vw, 30px); font-weight:700; line-height:1.25; letter-spacing:-.025em;
}
.can-registration-heading > p:last-child{margin:10px 0 0; color:#b4c9dd; font-size:14px; line-height:1.6;}
.can-registration-options{display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:20px; padding:24px;}
.can-registration-card{
    --profile-accent:#0275b5;
    --profile-soft:#e7f4fd;
    display:flex; flex-direction:column; align-items:stretch;
    gap:14px; min-width:0; padding:24px;
    border:1px solid #d5e2ee; border-top:3px solid var(--profile-accent); border-radius:10px;
    background:#fff; color:#10243d; text-decoration:none;
    box-shadow:0 3px 12px rgba(10,40,70,.035);
    transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}
.can-registration-card--ao{--profile-accent:#0f766e; --profile-soft:#e7f5f2;}
.can-registration-card:hover{
    transform:translateY(-3px); border-color:var(--profile-accent);
    box-shadow:0 12px 28px rgba(13,50,97,.11); color:#10243d; text-decoration:none;
}
.can-registration-card-top{display:flex; align-items:center; gap:12px;}
.can-registration-card-icon{
    display:grid; place-items:center; flex:0 0 48px; width:48px; height:48px;
    border:1px solid rgba(15,70,110,.06); border-radius:10px;
    background:var(--profile-soft); color:var(--profile-accent); font-size:26px;
}
.can-registration-profile-label{color:var(--profile-accent); font-size:10px; font-weight:700; line-height:1.5; letter-spacing:.09em; text-transform:uppercase;}
.can-registration-card h3{
    display:flex; align-items:center; min-height:54px; margin:0;
    font-family:inherit; font-size:22px; font-weight:700; color:#10243d; line-height:1.25; letter-spacing:-.025em;
}
.can-registration-card p{min-height:46px; margin:0; color:#52677d; font-size:14px; line-height:1.6;}
.can-registration-benefits{display:grid; gap:9px; margin:0 0 6px; padding:0; list-style:none;}
.can-registration-benefits li{display:flex; align-items:flex-start; gap:8px; color:#344b63; font-size:13px; line-height:1.5;}
.can-registration-benefits i{flex-shrink:0; color:var(--profile-accent); font-size:16px;}
.can-registration-card-action{
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    min-height:46px; margin-top:auto; padding:11px 14px;
    border:1px solid var(--profile-accent); border-radius:6px;
    background:var(--profile-accent); color:#fff; font-size:13px; font-weight:700; line-height:1.5;
}
.can-registration-card-action i{font-size:18px; transition:transform .2s ease;}
.can-registration-card:hover .can-registration-card-action i{transform:translateX(3px);}
.can-registration-footer{
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    padding:17px 28px; border-top:1px solid #dfe8f1; background:#fff;
    color:#64748b; font-size:12px; line-height:1.6;
}
.can-registration-footer p{margin:0;}
.can-registration-footer a{display:inline-flex; align-items:center; gap:5px; color:#075985; font-weight:700; text-decoration:none;}
.can-registration-footer a:hover{text-decoration:underline;}
.can-registration-footer-brand{display:inline-flex; align-items:center; gap:7px; color:#52677d;}
.can-registration-footer-brand i{color:#0284c7; font-size:17px;}
.can-registration-note{margin:0; padding:0 28px 22px; color:#64748b; font-size:12px; line-height:1.6; text-align:center;}
.can-registration-dialog :focus-visible{outline:3px solid #38bdf8; outline-offset:4px;}
@keyframes canRegistrationIn{from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:translateY(0);}}
@media (max-width:575.98px){
    .nav-aviation .header-actions > .btn-register-now{display:none;}
    .nav-aviation .mobile-register-now{display:inline-flex; width:100%; margin:12px 0;}
    .can-registration-heading{padding:24px;}
    .can-registration-heading h2{margin-right:20px;}
    .can-registration-close{top:12px; right:12px; width:32px; height:32px;}
    .can-registration-options{grid-template-columns:1fr; padding:18px; gap:16px;}
    .can-registration-card{padding:20px; gap:12px;}
    .can-registration-card h3,.can-registration-card p{min-height:0;}
    .can-registration-footer{padding:16px 20px;}
}
@media (prefers-reduced-motion:reduce){
    .can-registration-dialog[open]{animation:none;}
    .nav-aviation .btn-register-now,.can-registration-card,.can-registration-card-action i{transition:none;}
    .nav-aviation .btn-register-now:hover,.can-registration-card:hover,.can-registration-card:hover .can-registration-card-action i{transform:none;}
}

CSS, [], 'can-registration-choice');
$this->registerJs(<<<'JS'
(() => {
    const dialog = document.getElementById('canRegistrationDialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    let trigger = null;
    document.querySelectorAll('[data-registration-open]').forEach(button => {
        button.addEventListener('click', () => {
            if (dialog.open) return;
            trigger = button;
            const mobileNav = document.getElementById('mobileNav');
            const mobileButton = document.getElementById('mobileMenuBtn');
            mobileNav?.classList.remove('open');
            mobileButton?.classList.remove('open');
            mobileButton?.setAttribute('aria-expanded', 'false');
            dialog.showModal();
            document.body.classList.add('can-registration-open');
        });
    });
    dialog.querySelector('[data-registration-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => {
        document.body.classList.remove('can-registration-open');
        // Mobile triggers live in a menu that is closed when the dialog opens.
        const returnTarget = trigger?.closest('#mobileNav') ? document.getElementById('mobileMenuBtn') : trigger;
        returnTarget?.focus();
    });
})();
JS, \yii\web\View::POS_END, 'can-registration-choice');
?>
<dialog class="can-registration-dialog" id="canRegistrationDialog" aria-labelledby="can-registration-title" aria-describedby="can-registration-description">
  <header class="can-registration-heading">
    <button class="can-registration-close" type="button" data-registration-close aria-label="Close registration choices" autofocus><i class="ri-close-line" aria-hidden="true"></i></button>
    <p class="can-registration-eyebrow"><i class="ri-flight-takeoff-line" aria-hidden="true"></i> Core Aviation Network</p>
    <h2 id="can-registration-title">Choose your organisation profile</h2>
    <p id="can-registration-description">Select the profile that matches your role in aviation maintenance.</p>
  </header>
  <div class="can-registration-options">
    <a class="can-registration-card" href="<?= Url::to(['/site/become-mro']) ?>">
      <div class="can-registration-card-top">
        <span class="can-registration-card-icon" aria-hidden="true"><i class="ri-building-2-line"></i></span>
        <span class="can-registration-profile-label">Maintenance provider</span>
      </div>
      <h3>MRO Organisation</h3>
      <p>Present your expertise and connect with operators seeking maintenance support.</p>
      <ul class="can-registration-benefits">
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Showcase capabilities and coverage</span></li>
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Explore relevant maintenance requests</span></li>
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Submit and manage quotations</span></li>
      </ul>
      <span class="can-registration-card-action">Continue as MRO <i class="ri-arrow-right-line" aria-hidden="true"></i></span>
    </a>
    <a class="can-registration-card can-registration-card--ao" href="<?= Url::to(['/site/become-ao']) ?>">
      <div class="can-registration-card-top">
        <span class="can-registration-card-icon" aria-hidden="true"><i class="ri-plane-line"></i></span>
        <span class="can-registration-profile-label">Aircraft operations</span>
      </div>
      <h3>Aircraft Operator / CAMO</h3>
      <p>Source maintenance expertise and coordinate your aircraft maintenance needs.</p>
      <ul class="can-registration-benefits">
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Submit structured maintenance requests</span></li>
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Compare MRO responses and quotations</span></li>
        <li><i class="ri-check-line" aria-hidden="true"></i><span>Track work and maintenance records</span></li>
      </ul>
      <span class="can-registration-card-action">Continue as Operator / CAMO <i class="ri-arrow-right-line" aria-hidden="true"></i></span>
    </a>
  </div>
  <?php if ($prelaunchMode): ?>
    <p class="can-registration-note">Registration is currently in early access. Both options lead to the launch registration form.</p>
  <?php endif; ?>
  <footer class="can-registration-footer">
    <span class="can-registration-footer-brand"><i class="ri-global-line" aria-hidden="true"></i> One world. One network.</span>
    <p>Already have an account? <a href="<?= Url::to(['/site/login']) ?>">Sign in <i class="ri-arrow-right-line" aria-hidden="true"></i></a></p>
  </footer>
</dialog>
