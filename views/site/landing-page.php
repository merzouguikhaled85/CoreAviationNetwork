<?php
/** @var yii\web\View $this */
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Advert;

$this->title = 'Core Aviation Network';


$now = date('Y-m-d H:i:s');

$aboutAdverts = Advert::find()
    ->where(['status' => 'active'])
    ->andWhere([
        'or',
        ['start_date' => null],
        ['<=', 'start_date', $now],
    ])
    ->andWhere([
        'or',
        ['end_date' => null],
        ['>=', 'end_date', $now],
    ])
    ->orderBy(['advert_id' => SORT_DESC])
    ->all();


/* ===== JS du slider (auto, dots, prev/next, pause-hover, clavier, swipe, vidéo) ===== */
$this->registerJs(<<<JS
(function(){
  const root = document.querySelector('.hero-slider');
  if(!root) return;

  const slides   = Array.from(root.querySelectorAll('.slide'));
  const prevBtn  = root.querySelector('#prevBtn');
  const nextBtn  = root.querySelector('#nextBtn');
  const dotsWrap = root.querySelector('#sliderDots');
  const dots     = dotsWrap ? Array.from(dotsWrap.querySelectorAll('.dot')) : [];

  let idx = Math.max(0, slides.findIndex(s => s.classList.contains('active')));
  if (idx < 0) idx = 0;

  const INTERVAL = 6000;
  let timer = null;

  function isVideoSlide(i){ return !!slides[i]?.querySelector('video'); }
  function stopVideo(i){
    const v = slides[i]?.querySelector('video'); if(!v) return;
    try { v.pause(); v.currentTime = 0; } catch(e){}
    v.removeEventListener('ended', onVideoEnded);
  }
  function playVideo(i){
    const v = slides[i]?.querySelector('video'); if(!v) return;
    try { v.currentTime = 0; v.play(); } catch(e){}
    v.addEventListener('ended', onVideoEnded, { once: true });
  }
  function onVideoEnded(){ next(true); }

  function render(){
    slides.forEach((s, i) => s.classList.toggle('active', i === idx));
    dots.forEach((d, i) => {
      d.classList.toggle('active', i === idx);
      d.setAttribute('aria-selected', i === idx ? 'true' : 'false');
    });
  }
  function goTo(i, user=false){
    const prev = idx;
    idx = (i + slides.length) % slides.length;
    stopVideo(prev);
    render();
    if (isVideoSlide(idx)) { stop(); playVideo(idx); }
    else if (user) { restart(); }
  }
  function next(user=false){ goTo(idx+1, user); }
  function prev(user=false){ goTo(idx-1, user); }
  function start(){
    if (timer || isVideoSlide(idx)) return;
    timer = setInterval(() => { if (!isVideoSlide(idx)) next(false); }, INTERVAL);
  }
  function stop(){ if (timer){ clearInterval(timer); timer=null; } }
  function restart(){ stop(); start(); }

  prevBtn?.addEventListener('click', () => prev(true));
  nextBtn?.addEventListener('click', () => next(true));
  dots.forEach(d => {
    d.setAttribute('role','tab');
    d.addEventListener('click', () => goTo(parseInt(d.dataset.slide||'0',10), true));
  });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);

  root.setAttribute('tabindex','0');
  root.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') next(true);
    if (e.key === 'ArrowLeft')  prev(true);
  });

  let sx=0, sy=0;
  root.addEventListener('touchstart', (e) => { const t=e.touches[0]; sx=t.clientX; sy=t.clientY; }, {passive:true});
  root.addEventListener('touchend', (e) => {
    const t=e.changedTouches[0]; const dx=t.clientX-sx; const dy=t.clientY-sy;
    if (Math.abs(dx) > 40 && Math.abs(dy) < 60) { dx < 0 ? next(true) : prev(true); }
  }, {passive:true});

  render();
  if (isVideoSlide(idx)) { playVideo(idx); } else { start(); }
})();
JS);


/* ===== ABOUT US ADVERTISING CAROUSEL ===== */
$this->registerJs(<<<'JS'
(function () {
    'use strict';

    var carousel = document.getElementById('about-ad-carousel');
    var track = document.getElementById('about-ad-track');

    if (!carousel || !track) {
        return;
    }

    var slides = Array.prototype.slice.call(
        track.querySelectorAll('.about-ad-slide')
    );

    var dots = Array.prototype.slice.call(
        carousel.querySelectorAll('.about-ad-dot')
    );

    var previousButton = document.getElementById('about-ad-prev');
    var nextButton = document.getElementById('about-ad-next');
    var progressBar = document.getElementById('about-ad-progress-bar');

    var currentIndex = 0;
    var timer = null;
    var delay = 7000;
    var touchStartX = 0;

    function restartProgress() {
        if (!progressBar) {
            return;
        }

        progressBar.classList.remove('is-running');
        progressBar.style.width = '0';

        void progressBar.offsetWidth;

        progressBar.classList.add('is-running');
    }

    function updateVideos() {
        slides.forEach(function (slide, index) {
            var video = slide.querySelector('video');

            if (!video) {
                return;
            }

            if (index === currentIndex) {
                var playPromise = video.play();

                if (
                    playPromise &&
                    typeof playPromise.catch === 'function'
                ) {
                    playPromise.catch(function () {});
                }
            } else {
                video.pause();

                try {
                    video.currentTime = 0;
                } catch (error) {
                    // The browser may prevent seeking before metadata is ready.
                }
            }
        });
    }

    function showSlide(index) {
        if (!slides.length) {
            return;
        }

        if (index < 0) {
            index = slides.length - 1;
        }

        if (index >= slides.length) {
            index = 0;
        }

        currentIndex = index;

        track.style.transform =
            'translate3d(-' + (currentIndex * 100) + '%, 0, 0)';

        slides.forEach(function (slide, slideIndex) {
            slide.classList.toggle(
                'is-active',
                slideIndex === currentIndex
            );
        });

        dots.forEach(function (dot, dotIndex) {
            var active = dotIndex === currentIndex;

            dot.classList.toggle('is-active', active);
            dot.setAttribute(
                'aria-current',
                active ? 'true' : 'false'
            );
        });

        updateVideos();
        restartProgress();
    }

    function stopAutoplay() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }

        if (progressBar) {
            progressBar.classList.remove('is-running');
        }
    }

    function startAutoplay() {
        stopAutoplay();

        if (slides.length <= 1 || document.hidden) {
            return;
        }

        restartProgress();

        timer = window.setInterval(function () {
            showSlide(currentIndex + 1);
        }, delay);
    }

    function navigate(index) {
        showSlide(index);
        startAutoplay();
    }

    if (previousButton) {
        previousButton.addEventListener('click', function () {
            navigate(currentIndex - 1);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            navigate(currentIndex + 1);
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var target = parseInt(
                dot.getAttribute('data-about-ad-target'),
                10
            );

            navigate(isNaN(target) ? 0 : target);
        });
    });

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);
    carousel.addEventListener('focusin', stopAutoplay);
    carousel.addEventListener('focusout', startAutoplay);

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopAutoplay();
        } else {
            startAutoplay();
        }
    });

    carousel.addEventListener(
        'touchstart',
        function (event) {
            touchStartX = event.changedTouches[0].screenX;
        },
        { passive: true }
    );

    carousel.addEventListener(
        'touchend',
        function (event) {
            var touchEndX = event.changedTouches[0].screenX;
            var difference = touchEndX - touchStartX;

            if (Math.abs(difference) < 45) {
                return;
            }

            navigate(
                difference < 0
                    ? currentIndex + 1
                    : currentIndex - 1
            );
        },
        { passive: true }
    );

    showSlide(0);
    startAutoplay();
})();
JS);

/* ===== CSS correctif pour forcer l'affichage sur la même ligne et bypasser le cache du navigateur ===== */
$this->registerCss(<<<CSS
@media (min-width: 768px) {
    .services .row {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 24px !important;
    }
    .services .col-md-4 {
        flex: 1 1 0px !important;
        width: auto !important;
        max-width: unset !important;
    }
}

/* ==========================================================
   ABOUT US — 30% TEXT / 70% ADVERTISING
   The whole section fits in the available viewport without
   an internal vertical scrollbar.
   ========================================================== */
.about.section-padding{
    min-height:calc(100svh - 106px);
    display:flex;
    align-items:center;
    padding:clamp(22px, 3.5vh, 42px) 0;
    overflow:hidden;
    background:
        radial-gradient(
            circle at 85% 10%,
            rgba(0,178,255,.08),
            transparent 34%
        ),
        linear-gradient(180deg, #F7FAFD 0%, #FFFFFF 100%);
}

.about .container{
    width:100%;
    max-width:1540px;
}

.about .about-ad-layout{
    display:grid;
    grid-template-columns:minmax(250px, 30%) minmax(0, 70%);
    gap:clamp(20px, 2.2vw, 34px);
    align-items:stretch;
    height:clamp(430px, calc(100svh - 170px), 640px);
    min-height:0;
}

.about .about-copy-column{
    min-width:0;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:clamp(12px, 1.7vw, 26px);
    border:1px solid rgba(211,226,242,.92);
    border-radius:22px;
    background:rgba(255,255,255,.90);
    box-shadow:0 18px 46px rgba(13,50,97,.07);
}

.about .about-copy-inner{
    width:100%;
    max-width:460px;
}

.about-brand-logo-wrap{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:74px;
    margin-bottom:clamp(12px, 1.8vh, 20px);
}

.about-brand-logo{
    display:block;
    width:auto;
    max-width:min(180px, 72%);
    max-height:76px;
    object-fit:contain;
    object-position:center;
    filter:drop-shadow(0 8px 18px rgba(13,50,97,.12));
}

.about .about-copy-column .section-title{
    margin-bottom:clamp(18px, 2.3vh, 28px);
}

.about .about-copy-column .lead{
    margin-bottom:clamp(12px, 1.8vh, 20px);
    color:#41566F;
    font-size:clamp(14px, 1.05vw, 17px);
    line-height:1.65;
}

.about .about-copy-column .lead:last-child{
    margin-bottom:0;
}

.about .about-ad-column{
    min-width:0;
    min-height:0;
}

.about-ad-zone{
    position:relative;
    width:100%;
    height:100%;
    min-height:0;
    overflow:hidden;
    border:1px solid #C9D9EB;
    border-radius:24px;
    background:#071A31;
    box-shadow:0 24px 60px rgba(13,50,97,.17);
    isolation:isolate;
}

.about-ad-carousel,
.about-ad-track,
.about-ad-slide,
.about-ad-media-wrap{
    width:100%;
    height:100%;
    min-height:0;
}

.about-ad-carousel{
    position:relative;
    overflow:hidden;
}

.about-ad-track{
    display:flex;
    will-change:transform;
    transition:transform .72s cubic-bezier(.22,.61,.36,1);
}

.about-ad-slide{
    position:relative;
    flex:0 0 100%;
    min-width:100%;
    overflow:hidden;
    background:#071A31;
}

.about-ad-media-wrap{
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    background:#071A31;
}

/*
 * A blurred copy fills the wide 70% space while the foreground
 * keeps the full advertisement visible with object-fit: contain.
 */
.about-ad-backdrop{
    position:absolute;
    inset:-24px;
    z-index:0;
    background-position:center;
    background-repeat:no-repeat;
    background-size:cover;
    filter:blur(22px) brightness(.48) saturate(1.15);
    transform:scale(1.08);
    opacity:.88;
}

.about-ad-media{
    position:relative;
    z-index:1;
    display:block;
    width:100%;
    height:100%;
    object-fit:contain;
    object-position:center;
    background:transparent;
}

.about-ad-slide.is-active .about-ad-media{
    animation:aboutAdSoftZoom 7s linear both;
}

@keyframes aboutAdSoftZoom{
    from{transform:scale(1)}
    to{transform:scale(1.018)}
}

.about-ad-video{
    background:#071A31;
}

.about-ad-shade{
    position:absolute;
    inset:0;
    z-index:2;
    pointer-events:none;
    background:
        linear-gradient(
            180deg,
            rgba(3,14,29,.30) 0%,
            rgba(3,14,29,0) 26%
        ),
        linear-gradient(
            0deg,
            rgba(3,14,29,.26) 0%,
            rgba(3,14,29,0) 24%
        );
}

.about-ad-sponsored,
.about-ad-counter{
    position:absolute;
    z-index:5;
    top:16px;
    min-height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0 12px;
    border:1px solid rgba(255,255,255,.40);
    border-radius:999px;
    color:#FFFFFF;
    background:rgba(7,26,49,.68);
    box-shadow:0 8px 22px rgba(0,0,0,.18);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    font-size:10px;
    font-weight:900;
    letter-spacing:.07em;
    text-transform:uppercase;
}

.about-ad-sponsored{
    left:16px;
}

.about-ad-counter{
    right:16px;
    min-width:54px;
}

.about-ad-nav{
    position:absolute;
    z-index:7;
    top:50%;
    width:44px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0;
    border:1px solid rgba(255,255,255,.72);
    border-radius:50%;
    color:#0D3261;
    background:rgba(255,255,255,.96);
    box-shadow:0 10px 28px rgba(0,0,0,.24);
    transform:translateY(-50%);
    transition:transform .2s ease, background .2s ease;
}

.about-ad-nav:hover{
    background:#FFFFFF;
    transform:translateY(-50%) scale(1.08);
}

.about-ad-prev{
    left:16px;
}

.about-ad-next{
    right:16px;
}

.about-ad-dots{
    position:absolute;
    z-index:7;
    left:50%;
    bottom:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    transform:translateX(-50%);
}

.about-ad-dot{
    width:8px;
    height:8px;
    padding:0;
    border:0;
    border-radius:999px;
    background:rgba(255,255,255,.50);
    box-shadow:0 2px 8px rgba(0,0,0,.18);
    transition:width .25s ease, background .25s ease;
}

.about-ad-dot.is-active{
    width:28px;
    background:#FFFFFF;
}

.about-ad-progress{
    position:absolute;
    z-index:8;
    left:0;
    right:0;
    bottom:0;
    height:4px;
    background:rgba(255,255,255,.18);
}

.about-ad-progress span{
    display:block;
    width:0;
    height:100%;
    background:linear-gradient(90deg, #00B2FF, #FFFFFF);
}

.about-ad-progress span.is-running{
    animation:aboutAdProgress 7s linear forwards;
}

@keyframes aboutAdProgress{
    from{width:0}
    to{width:100%}
}

.about-ad-error,
.about-ad-unsupported{
    position:absolute;
    inset:0;
    z-index:10;
    display:none;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:28px;
    color:#FFFFFF;
    text-align:center;
    background:linear-gradient(145deg, #0D3261, #071A31);
}

.about-ad-unsupported{
    display:flex;
}

.about-ad-slide.has-media-error .about-ad-error{
    display:flex;
}

.about-ad-error strong,
.about-ad-unsupported strong{
    font-size:16px;
    font-weight:900;
}

.about-ad-error small,
.about-ad-unsupported small{
    max-width:320px;
    color:rgba(255,255,255,.74);
    font-size:12px;
    line-height:1.5;
}

.about-ad-empty{
    width:100%;
    height:100%;
    min-height:0;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:14px;
    padding:32px;
    color:#FFFFFF;
    text-align:center;
    background:
        radial-gradient(
            circle at top,
            rgba(0,178,255,.16),
            transparent 42%
        ),
        linear-gradient(150deg, #081F3D, #0D3261);
}

.about-ad-empty img{
    width:min(240px, 72%);
    height:auto;
    object-fit:contain;
    filter:drop-shadow(0 12px 26px rgba(0,0,0,.25));
}

.about-ad-empty strong{
    font-size:18px;
    font-weight:900;
}

.about-ad-empty small{
    max-width:360px;
    color:rgba(255,255,255,.76);
    font-size:13px;
    line-height:1.55;
}

@media (max-height:760px) and (min-width:992px){
    .about-brand-logo-wrap{
        min-height:58px;
        margin-bottom:10px;
    }

    .about-brand-logo{
        max-height:58px;
        max-width:150px;
    }

    .about .about-copy-column .section-title{
        margin-bottom:14px;
    }

    .about .about-copy-column .lead{
        margin-bottom:10px;
        font-size:14px;
        line-height:1.5;
    }
}

@media (max-width:1199.98px){
    .about .about-ad-layout{
        grid-template-columns:minmax(240px, 34%) minmax(0, 66%);
        gap:22px;
    }
}

@media (max-width:991.98px){
    .about.section-padding{
        min-height:auto;
        overflow:visible;
    }

    .about .about-ad-layout{
        grid-template-columns:1fr;
        height:auto;
    }

    .about .about-copy-column{
        padding:26px 22px;
    }

    .about-ad-zone{
        height:clamp(420px, 62svh, 560px);
    }
}

@media (max-width:575.98px){
    .about.section-padding{
        padding:24px 0;
    }

    .about .about-ad-layout{
        gap:20px;
    }

    .about .about-copy-column{
        padding:22px 18px;
        border-radius:18px;
    }

    .about-ad-zone{
        height:clamp(380px, 60svh, 500px);
        border-radius:18px;
    }

    .about-ad-nav{
        width:38px;
        height:38px;
    }

    .about-ad-prev{
        left:11px;
    }

    .about-ad-next{
        right:11px;
    }
}

CSS
);
?>

<div class="site-index" style="margin:0;padding:0;">

  <!--
      NOTIFICATIONS CENTRALISÉES : les messages flash sont affichés exclusivement
      par le layout public sous forme de cartes flottantes. Ne pas les rendre ici
      évite la bande colorée pleine largeur et le double affichage observé après
      l'inscription d'un opérateur ou d'un MRO.
  -->

  <!-- ================== HERO SLIDER FULLSCREEN (remplace ton Hero/Vision) ================== -->
  <section class="hero-slider" id="heroSlider">
    <!-- Slide 1 -->
    <div class="slide active" style="background-image:url('<?= Url::to('@web/img/pngtree-jet-engine-at-night-image_2622340.jpg') ?>');">
      <!-- <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-panel">
          <div class="hero-kicker">
            <span class="hero-kicker-dot"></span>
            <span>Aircraft Maintenance Platform</span>
          </div>
          <h1 class="hero-title">Maintenance <span>Aircraft Premium</span> for MRO &amp; CAMO.</h1>
          <p class="hero-subtitle">
            Core Aviation Network connect operators, CAMO and MRO stations around an intelligent maintenance engine.
          </p>
          <div class="hero-actions">
            <button class="btn-primary"><span class="icon">🚀</span><span>Start now – It’s free</span></button>
            <a href="#about" class="btn-secondary"><span>▶</span><span>Voir la démo</span></a>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Slide 2 -->
    <div class="slide" style="background-image:url('<?= Url::to('@web/img/Falcon10X_GAL_203_202302.jpg') ?>');">
      <!-- <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-panel">
          <div class="hero-kicker"><span class="hero-kicker-dot"></span><span>Global Network</span></div>
          <h1 class="hero-title">Connect to <span>+120 MRO Stations</span> certified.</h1>
          <p class="hero-subtitle">Find instantly availability for A-checks, C-checks and structural repairs worldwide.</p>
          <div class="hero-actions">
            <button class="btn-primary"><span class="icon">🌍</span><span>Explore Network</span></button>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Slide 3 -->
    <div class="slide" style="background-image:url('<?= Url::to('@web/img/hero-hangar-heavy.webp') ?>');">
      <!-- <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-panel">
          <div class="hero-kicker"><span class="hero-kicker-dot"></span><span>Digital Logbook</span></div>
          <h1 class="hero-title">Conformity <span>EASA / FAA</span> simplified.</h1>
          <p class="hero-subtitle">Generate your CRS and maintenance reports in one click. Audit-ready.</p>
          <div class="hero-actions">
            <button class="btn-primary"><span class="icon">📋</span><span>Voir les features</span></button>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Slide 4 (Vidéo) -->
    <div class="slide" id="videoSlide">
      <video class="hero-video" autoplay muted playsinline>
        <source src="<?= Url::to('@web/videos/Concept%20Of%20Visualization%20Of%20Futuristic%20Airplane%20Engine%20Maintenance%20Conducted%20By%20Engineer%20Holding%20Tablet%20Computer%20Animation%20Of%20Digitalization%20Of%20Analytics%20Checking%20Optimal%20Functioning%20Of%20The%20Plane%204K%20Stock%20Video.mp4') ?>" type="video/mp4" />
      </video>
      <!-- <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-panel">
          <div class="hero-kicker"><span class="hero-kicker-dot"></span><span>Smart AOG Engine</span></div>
          <h1 class="hero-title">Real-time <span>Engine Diagnostics</span>.</h1>
          <p class="hero-subtitle">Live insights, automatic fault detection & predictive health monitoring.</p>
          <div class="hero-actions">
            <button class="btn-primary"><span class="icon">⚙️</span><span>Discover Engine AI</span></button>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Flèches -->
    <button class="slider-nav slider-prev" id="prevBtn" aria-label="Previous slide" type="button">❮</button>
    <button class="slider-nav slider-next" id="nextBtn" aria-label="Next slide" type="button">❯</button>

    <!-- Dots -->
    <div class="slider-dots" id="sliderDots" role="tablist" aria-label="Slides">
      <span class="dot active" data-slide="0"></span>
      <span class="dot" data-slide="1"></span>
      <span class="dot" data-slide="2"></span>
      <span class="dot" data-slide="3"></span>
    </div>
  </section>

 
  <!-- ================== ABOUT / GLOBALMROs ================== -->
  <section class="about section-padding" aria-labelledby="about-title" id="about">
    <div class="container">
      <div class="about-ad-layout">

        <!-- About Us: titre, soulignement et contenu conservés -->
        <div class="about-copy-column text-center">
          <div class="about-copy-inner">
            <div class="about-brand-logo-wrap">
              <img
                src="<?= Url::to('@web/logo/can-logo-main.png') ?>"
                alt="Core Aviation Network"
                class="about-brand-logo"
                loading="lazy"
              >
            </div>

            <h2 id="about-title" class="section-title">
              About Us <span class="underline"></span>
            </h2>
            <p class="lead ">
              At <strong>Core Aviation Network</strong>, we provide a centralised platform
              offering valuable resources for aviation professionals seeking
              maintenance stations worldwide...
            </p>
            <p class="lead">
              The ability to find maintenance stations worldwide is crucial for
              airlines, MRO facilities, and aircraft owners.
            </p>
            <p class="lead mb-0">
              By considering aircraft type, service level, and certifications,
              <strong>Core Aviation Network</strong> matches users with the most suitable
              stations.
            </p>
          </div>
        </div>

        <!-- Advertising zone: 70% of the desktop width -->
        <div class="about-ad-column">
          <aside
            class="about-ad-zone"
            aria-label="Sponsored aviation content"
          >
            <?php if (!empty($aboutAdverts)): ?>

              <div
                class="about-ad-carousel"
                id="about-ad-carousel"
              >
                <div
                  class="about-ad-track"
                  id="about-ad-track"
                >
                  <?php foreach ($aboutAdverts as $index => $advert): ?>
                    <?php
                    $advertType = strtolower(
                        trim((string) $advert->advert_type)
                    );

                    /*
                     * chr(92) represents a backslash and avoids escaping
                     * errors in PHP strings on Windows file paths.
                     */
                    $storedContent = str_replace(
                        chr(92),
                        '/',
                        trim((string) $advert->content)
                    );

                    $storedContent = ltrim(
                        $storedContent,
                        '/'
                    );

                    if (
                        !empty($advert->use_url) &&
                        !empty($advert->url)
                    ) {
                        $mediaUrl = trim(
                            (string) $advert->url
                        );
                    } elseif (
                        strpos($storedContent, 'uploads/') === 0
                    ) {
                        $mediaUrl =
                            Yii::getAlias('@web/') .
                            $storedContent;
                    } else {
                        $mediaUrl =
                            Yii::getAlias('@web/uploads/') .
                            $storedContent;
                    }
                    ?>

                    <article
                      class="about-ad-slide <?= $index === 0 ? 'is-active' : '' ?>"
                      data-about-ad-index="<?= (int) $index ?>"
                    >
                      <div class="about-ad-media-wrap">

                        <?php if ($advertType === 'photo'): ?>

                          <div
                            class="about-ad-backdrop"
                            style="background-image:url('<?= Html::encode($mediaUrl) ?>');"
                            aria-hidden="true"
                          ></div>

                          <?= Html::img($mediaUrl, [
                              'class' => 'about-ad-media',
                              'alt' => 'Sponsored aviation advertisement',
                              'loading' => $index === 0
                                  ? 'eager'
                                  : 'lazy',
                              'onerror' =>
                                  "this.closest('.about-ad-slide').classList.add('has-media-error');",
                          ]) ?>

                        <?php elseif ($advertType === 'video'): ?>

                          <video
                            class="about-ad-media about-ad-video"
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            <?= $index === 0 ? 'autoplay' : '' ?>
                            onerror="
                              this.closest('.about-ad-slide')
                                .classList.add('has-media-error');
                            "
                          >
                            <source
                              src="<?= Html::encode($mediaUrl) ?>"
                              type="video/mp4"
                            >
                          </video>

                        <?php else: ?>

                          <div class="about-ad-unsupported">
                            <strong>Unsupported media</strong>
                            <small>
                              This advertising format is not supported.
                            </small>
                          </div>

                        <?php endif; ?>

                        <div
                          class="about-ad-shade"
                          aria-hidden="true"
                        ></div>

                        <span class="about-ad-sponsored">
                          Sponsored
                        </span>

                        <span class="about-ad-counter">
                          <?= (int) ($index + 1) ?>
                          /
                          <?= count($aboutAdverts) ?>
                        </span>

                        <div class="about-ad-error">
                          <strong>Advertisement unavailable</strong>
                          <small>
                            The advertising image or video could not be loaded.
                          </small>
                        </div>

                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>

                <?php if (count($aboutAdverts) > 1): ?>

                  <button
                    type="button"
                    class="about-ad-nav about-ad-prev"
                    id="about-ad-prev"
                    aria-label="Previous advertisement"
                  >
                    &#10094;
                  </button>

                  <button
                    type="button"
                    class="about-ad-nav about-ad-next"
                    id="about-ad-next"
                    aria-label="Next advertisement"
                  >
                    &#10095;
                  </button>

                  <div
                    class="about-ad-dots"
                    aria-label="Advertisement navigation"
                  >
                    <?php foreach ($aboutAdverts as $index => $advert): ?>
                      <button
                        type="button"
                        class="about-ad-dot <?= $index === 0 ? 'is-active' : '' ?>"
                        data-about-ad-target="<?= (int) $index ?>"
                        aria-label="Show advertisement <?= (int) ($index + 1) ?>"
                      ></button>
                    <?php endforeach; ?>
                  </div>

                  <div
                    class="about-ad-progress"
                    aria-hidden="true"
                  >
                    <span id="about-ad-progress-bar"></span>
                  </div>

                <?php endif; ?>
              </div>

            <?php else: ?>

              <div class="about-ad-empty">
                <img
                  src="<?= Url::to('@web/logo/can-logo-main.png') ?>"
                  alt="Core Aviation Network"
                  loading="lazy"
                >
                <strong>Advertising space available</strong>
                <small>
                  Promote your aviation services to aircraft operators
                  and MRO professionals.
                </small>
              </div>

            <?php endif; ?>
          </aside>
        </div>

      </div>
    </div>
  </section>

  <!-- ================== SERVICES ================== -->
  <section class="services" aria-labelledby="services-title">
    <div class="container">
      <h2 id="services-title" class="section-title text-center text-lg-start">
        Our Core Services
        <span class="underline"></span>
      </h2>
      <div class="row g-4 mt-4">
        <div class="col-12 col-md-4">
          <article class="service-card h-100">
            <img src="<?= Url::to('@web/img/home1.jpg') ?>" class="card-img-top" alt="Aircraft Operator"
                 loading="lazy" />
            <div class="card-body">
              <h3 class="card-title h5 mb-2">Aircraft Operator</h3>
              <p class="card-text">
                Connect with MROs at your location with one tap.
              </p>
            </div>
          </article>
        </div>
        <div class="col-12 col-md-4">
          <article class="service-card h-100">
            <img src="<?= Url::to('@web/img/home2.jpg') ?>" class="card-img-top" alt="MRO Centers" loading="lazy" />
            <div class="card-body">
              <h3 class="card-title h5 mb-2">MRO Centers</h3>
              <p class="card-text">
                Support operators to keep aircraft flying.
              </p>
            </div>
          </article>
        </div>
        <div class="col-12 col-md-4">
          <article class="service-card h-100">
            <img src="<?= Url::to('@web/img/home3.jpg') ?>" class="card-img-top" alt="Around The Globe"
                 loading="lazy" />
            <div class="card-body">
              <h3 class="card-title h5 mb-2">Around The Globe</h3>
              <p class="card-text">
                Wherever you are, support is near.
              </p>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  

</div>
