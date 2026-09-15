<?php
/** @var yii\web\View $this */
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Advert;

$this->title = 'Core Aviation Network';

/*
 * ANCIENS MEDIAS DU HERO - CONSERVES COMME REFERENCE :
 * ces quatre ressources alimentaient le precedent carrousel. Elles restent
 * volontairement commentees pour permettre un retour visuel rapide, mais elles
 * ne sont plus telechargees par le navigateur et n'alourdissent plus la page.
 *
 * $heroFirstImageUrl = Url::to('@web/img/pngtree-jet-engine-at-night-image_2622340.jpg');
 * $heroSecondImageUrl = Url::to('@web/img/Falcon10X_GAL_203_202302.jpg');
 * $heroThirdImageUrl = Url::to('@web/img/hero-hangar-heavy.webp');
 * $heroVideoUrl = Url::to('@web/videos/Concept%20Of%20Visualization%20Of%20Futuristic%20Airplane%20Engine%20Maintenance%20Conducted%20By%20Engineer%20Holding%20Tablet%20Computer%20Animation%20Of%20Digitalization%20Of%20Analytics%20Checking%20Optimal%20Functioning%20Of%20The%20Plane%204K%20Stock%20Video.mp4');
 */

/*
 * NOUVELLE NARRATION VISUELLE DU HERO :
 * les cinq images presentent successivement les acteurs et les etapes reelles
 * du parcours maintenance. Parts, ingenieurs et techniciens sont uniquement
 * des contextes operationnels ; cette presentation ne cree aucun nouveau role
 * applicatif et ne modifie aucune regle metier.
 */
$heroDroneVideoUrl = Url::to('@web/img/can-drone.mp4');
$heroSlides = [
    [
        'image' => Url::to('@web/img/home-carousel/ao-camo.jpg'),
        'video' => $heroDroneVideoUrl,
        'motion' => 'motion-slow-zoom',
        'kicker' => 'AO & CAMO',
        'title' => 'One request.',
        'highlight' => 'Structured from the start.',
        'description' => 'Aircraft, location, priority, ETA and ETD stay together in one controlled request.',
        'label' => 'AO and CAMO professionals preparing an aircraft maintenance request',
    ],
    [
        'image' => Url::to('@web/img/home-carousel/mro-team.jpg'),
        'motion' => 'motion-pan-left',
        'kicker' => 'Qualified MRO',
        'title' => 'The right capability.',
        'highlight' => 'At the right location.',
        'description' => 'Qualified maintenance teams review the scope and respond through a traceable workflow.',
        'label' => 'Qualified MRO team inspecting a business aircraft',
    ],
    [
        'image' => Url::to('@web/img/home-carousel/parts-traceability.jpg'),
        'motion' => 'motion-slow-zoom',
        'kicker' => 'Parts traceability',
        'title' => 'Approved parts.',
        'highlight' => 'Traceable decisions.',
        'description' => 'Serialized components and supporting documents remain connected to the maintenance process.',
        'label' => 'Aircraft parts specialist controlling component traceability',
    ],
    [
        'image' => Url::to('@web/img/home-carousel/engineers-technicians.jpg'),
        'motion' => 'motion-pan-right',
        'kicker' => 'Engineering expertise',
        'title' => 'Qualified people.',
        'highlight' => 'Controlled execution.',
        'description' => 'Engineers and technicians carry out the work with clear operational information.',
        'label' => 'Engineers and technicians performing aircraft line maintenance',
    ],
    [
        'image' => Url::to('@web/img/home-carousel/global-network.jpg'),
        'motion' => 'motion-slow-zoom',
        'network' => true,
        'kicker' => 'Global aviation network',
        'title' => 'One workflow.',
        'highlight' => 'Full traceability.',
        'description' => 'Operators and MRO partners stay connected from the first request to maintenance release.',
        'label' => 'Business aircraft connected to an international MRO network',
    ],
];
$heroFirstImageUrl = $heroSlides[0]['image'];

$this->registerLinkTag([
    'rel' => 'preload',
    'as' => 'image',
    'href' => $heroFirstImageUrl,
    'fetchpriority' => 'high',
]);

/*
 * HOME HERO: adapt the primary actions to the connected audience without
 * changing authentication or request business rules.
 */
$isGuest = Yii::$app->user->isGuest;
$homeUserType = (string) Yii::$app->session->get('user_type');

if ($isGuest) {
    $heroPrimaryUrl = ['/site/become-ao'];
    $heroPrimaryLabel = 'Join as AO';
    $heroSecondaryUrl = ['/site/become-mro'];
    $heroSecondaryLabel = 'Join as MRO';
} elseif ($homeUserType === 'ao') {
    $heroPrimaryUrl = ['/requests/create'];
    $heroPrimaryLabel = 'New Request';
    $heroSecondaryUrl = ['/dashboard/home'];
    $heroSecondaryLabel = 'Dashboard';
} else {
    $heroPrimaryUrl = ['/mro-requests/index'];
    $heroPrimaryLabel = 'View Requests';
    $heroSecondaryUrl = ['/dashboard/home'];
    $heroSecondaryLabel = 'Dashboard';
}


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

  /*
   * SYNCHRONISATION DU MESSAGE :
   * chaque image porte son propre texte dans des attributs data. Le contenu
   * visible est mis a jour au changement de scene, sans requete reseau et sans
   * toucher aux liens d'inscription ou aux droits de l'utilisateur.
   */
  const messagePanel = root.querySelector('.home-hero-panel');
  const kickerEl = root.querySelector('[data-hero-kicker]');
  const titleEl = root.querySelector('[data-hero-title]');
  const highlightEl = root.querySelector('[data-hero-highlight]');
  const descriptionEl = root.querySelector('[data-hero-description]');

  let idx = Math.max(0, slides.findIndex(s => s.classList.contains('active')));
  if (idx < 0) idx = 0;

  /*
   * RYTHME DU CARROUSEL :
   * sept secondes laissent le temps de lire le titre et la description sans
   * donner une impression de lenteur. Le fondu visuel reste gere en CSS sur
   * une seconde, independamment de ce temps d'exposition.
   */
  const INTERVAL = 7000;
  let timer = null;
  let videoFallbackTimer = null;

  /*
   * POLITIQUE MEDIA HYBRIDE :
   * la video est reservee aux ecrans suffisamment larges et aux visiteurs qui
   * acceptent les animations. Sur mobile ou en mode mouvement reduit, l'image
   * de secours prend immediatement sa place et la video n'est pas telechargee.
   */
  const videoMediaQuery = window.matchMedia('(min-width:768px) and (prefers-reduced-motion:no-preference)');
  const canPlayHeroVideo = () => videoMediaQuery.matches;

  /* Chargement progressif de l'image ou de la video de la scene active/suivante. */
  function hydrateSlide(i){
    const slide = slides[i];
    if(!slide || slide.dataset.mediaReady === 'true') return;

    if(slide.dataset.bgImage){
      slide.style.backgroundImage = 'url("' + slide.dataset.bgImage + '")';
    }

    const image = slide.querySelector('img[data-src]');
    if(image){
      image.src = image.dataset.src;
      image.removeAttribute('data-src');
    }

    const video = slide.querySelector('video');
    const source = video?.querySelector('source[data-src]');
    if(source && canPlayHeroVideo()){
      source.src = source.dataset.src;
      source.removeAttribute('data-src');
      video.load();
    }

    slide.dataset.mediaReady = 'true';
  }

  function isVideoSlide(i){
    return canPlayHeroVideo() && !!slides[i]?.querySelector('video');
  }
  function stopVideo(i){
    const v = slides[i]?.querySelector('video'); if(!v) return;
    try { v.pause(); v.currentTime = 0; } catch(e){}
    v.removeEventListener('ended', onVideoEnded);
    if(videoFallbackTimer){
      clearTimeout(videoFallbackTimer);
      videoFallbackTimer = null;
    }
  }
  function playVideo(i){
    hydrateSlide(i);
    const v = slides[i]?.querySelector('video'); if(!v) return;
    try {
      v.currentTime = 0;
      const playPromise = v.play();
      if(playPromise && typeof playPromise.catch === 'function') playPromise.catch(() => {});
    } catch(e){}
    v.addEventListener('ended', onVideoEnded, { once: true });

    /*
     * SECURITE DE ROTATION :
     * une scene video ne peut pas bloquer le carrousel si la lecture automatique
     * est refusee ou si le fichier ne declenche pas l'evenement ended.
     */
    /*
     * La video passe a la scene suivante apres huit secondes au maximum. Si son
     * evenement ended arrive avant, on avance immediatement et ce timer est annule.
     */
    videoFallbackTimer = setTimeout(() => next(true), 8000);
  }
  function onVideoEnded(){
    if(videoFallbackTimer){
      clearTimeout(videoFallbackTimer);
      videoFallbackTimer = null;
    }
    next(true);
  }

  /*
   * Le texte est injecte avec textContent : meme si les valeurs deviennent un
   * jour dynamiques, aucun HTML arbitraire ne pourra etre interprete ici.
   */
  function updateHeroMessage(slide){
    if(!slide) return;
    if(kickerEl) kickerEl.textContent = slide.dataset.kicker || '';
    if(titleEl) titleEl.textContent = slide.dataset.title || '';
    if(highlightEl) highlightEl.textContent = slide.dataset.highlight || '';
    if(descriptionEl) descriptionEl.textContent = slide.dataset.description || '';

    if(messagePanel){
      messagePanel.classList.remove('is-copy-refreshing');
      void messagePanel.offsetWidth;
      messagePanel.classList.add('is-copy-refreshing');
    }
  }

  function render(){
    hydrateSlide(idx);
    hydrateSlide((idx + 1) % slides.length);
    slides.forEach((s, i) => s.classList.toggle('active', i === idx));
    /* La proposition de valeur reste stable pendant la rotation des visuels. */
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
    var carouselInViewport = !('IntersectionObserver' in window);

    /* PHASE 9: hydrate advertising media only when its carousel is visible. */
    function hydrateSlide(index) {
        var slide = slides[index];

        if (!slide || slide.getAttribute('data-media-ready') === 'true') {
            return;
        }

        var backdrop = slide.querySelector('.about-ad-backdrop[data-bg-image]');
        var video = slide.querySelector('video');
        var source = video ? video.querySelector('source[data-src]') : null;

        if (backdrop) {
            backdrop.style.backgroundImage =
                'url("' + backdrop.getAttribute('data-bg-image') + '")';
        }

        if (source) {
            source.src = source.getAttribute('data-src');
            source.removeAttribute('data-src');
            video.load();
        }

        slide.setAttribute('data-media-ready', 'true');
    }

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

            if (index === currentIndex && carouselInViewport) {
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

        if (carouselInViewport) {
            hydrateSlide(currentIndex);
            hydrateSlide((currentIndex + 1) % slides.length);
        }

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

        if (carouselInViewport) {
            restartProgress();
        }
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

        if (slides.length <= 1 || document.hidden || !carouselInViewport) {
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

    /* PHASE 9: do not animate or download advertising video below the fold. */
    if ('IntersectionObserver' in window) {
        var visibilityObserver = new IntersectionObserver(function (entries) {
            carouselInViewport = entries[0].isIntersecting;

            if (carouselInViewport) {
                showSlide(currentIndex);
                startAutoplay();
            } else {
                stopAutoplay();
                updateVideos();
            }
        }, { threshold: 0.2 });

        visibilityObserver.observe(carousel);
    } else {
        showSlide(0);
        startAutoplay();
    }
})();
JS);

/* ===== HOME EXPERIENCE: visible value proposition and AO/MRO workflow ===== */
$this->registerCss(<<<CSS
/*
 * HERO FULL VIEWPORT 2026:
 * the home header is fixed and overlays the media, so its height must not be
 * subtracted; the carousel covers the full visible viewport without a white band.
 */
.hero-slider{
    height:100vh;
    height:100svh;
    min-height:620px;
}

.hero-slider .slide::before{
    background:linear-gradient(90deg, rgba(2,8,23,.30) 0%, rgba(2,8,23,.13) 42%, rgba(2,8,23,.02) 75%, transparent 100%);
    z-index:3;
}

/*
 * COUCHE MEDIA HYBRIDE :
 * les images et la video occupent la meme surface et utilisent object-fit pour
 * eviter toute deformation. Le poster de la video assure un premier affichage
 * immediat pendant que le MP4 est charge de facon differee.
 */
.hero-scene-slide{background:#020817;}
.hero-scene-media{
    position:absolute;
    inset:0;
    z-index:0;
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
}
.hero-scene-video{display:block;}
.hero-video-fallback{display:none;}

/*
 * MOUVEMENTS CINEMATIQUES :
 * les transformations s'appliquent uniquement a la scene active. Une legere
 * mise a l'echelle garde les bords couverts pendant les panoramiques et evite
 * ainsi toute bande vide autour de l'image.
 */
.slide.active .hero-scene-image.motion-slow-zoom{
    animation:heroSlowZoom 7s ease-out both;
}
.slide.active .hero-scene-image.motion-pan-left{
    animation:heroPanLeft 7s ease-out both;
}
.slide.active .hero-scene-image.motion-pan-right{
    animation:heroPanRight 7s ease-out both;
}

@keyframes heroSlowZoom{
    from{transform:scale(1.01);}
    to{transform:scale(1.09);}
}
@keyframes heroPanLeft{
    from{transform:scale(1.09) translateX(1.6%);}
    to{transform:scale(1.09) translateX(-1.6%);}
}
@keyframes heroPanRight{
    from{transform:scale(1.09) translateX(-1.6%);}
    to{transform:scale(1.09) translateX(1.6%);}
}

/*
 * RESEAU MONDIAL :
 * les points pulsent au-dessus des connexions deja presentes dans le visuel.
 * Cette couche CSS est decorative et reste ignoree par les lecteurs d'ecran.
 */
.hero-network-pulses{
    position:absolute;
    inset:0;
    z-index:2;
    pointer-events:none;
}
.hero-network-pulse{
    position:absolute;
    top:var(--pulse-y);
    left:var(--pulse-x);
    width:8px;
    height:8px;
    border-radius:50%;
    background:#7dd3fc;
    box-shadow:0 0 16px rgba(56,189,248,.95);
    animation:heroNetworkPulse 2.4s ease-out infinite;
    animation-delay:var(--pulse-delay);
}
.hero-network-pulse::after{
    position:absolute;
    inset:-1px;
    border:1px solid rgba(125,211,252,.85);
    border-radius:inherit;
    content:"";
    animation:heroNetworkRing 2.4s ease-out infinite;
    animation-delay:var(--pulse-delay);
}

@keyframes heroNetworkPulse{
    0%,100%{opacity:.55; transform:scale(.8);}
    45%{opacity:1; transform:scale(1.25);}
}
@keyframes heroNetworkRing{
    from{opacity:.9; transform:scale(.5);}
    to{opacity:0; transform:scale(4.2);}
}

.home-hero-message{
    position:absolute;
    z-index:15;
    top:50%;
    left:clamp(34px, 5vw, 88px);
    width:min(560px, calc(100% - 120px));
    transform:translateY(-50%);
    color:#fff;
}

/* HERO GLASS 2026: smaller translucent panel so the aircraft remains visible. */
.home-hero-panel{
    padding:clamp(20px, 2.3vw, 29px);
    border:1px solid rgba(255,255,255,.30);
    border-radius:20px;
    background:linear-gradient(135deg, rgba(5,20,39,.62), rgba(13,50,97,.38));
    box-shadow:0 20px 48px rgba(2,8,23,.28);
    backdrop-filter:blur(9px) saturate(118%);
    -webkit-backdrop-filter:blur(9px) saturate(118%);
}

/*
 * TRANSITION DU MESSAGE :
 * ce mouvement tres court accompagne le fondu de l'image sans masquer les
 * commandes. Il est neutralise plus bas pour les visiteurs sensibles au mouvement.
 */
.home-hero-panel.is-copy-refreshing{
    animation:homeHeroCopyIn .42s ease both;
}

@keyframes homeHeroCopyIn{
    from{opacity:.55; transform:translateY(8px);}
    to{opacity:1; transform:translateY(0);}
}

.home-hero-kicker{
    display:flex;
    align-items:center;
    gap:10px;
    margin:0 0 10px;
    color:#7dd3fc;
    font-size:10.5px;
    font-weight:800;
    letter-spacing:.12em;
    text-transform:uppercase;
}

.home-hero-kicker::before{
    width:34px;
    height:2px;
    background:#0ea5e9;
    content:"";
}

.home-hero-title{
    margin:0;
    max-width:480px;
    font-size:clamp(34px, 3.1vw, 46px);
    font-weight:850;
    letter-spacing:-.04em;
    line-height:1.02;
}

.home-hero-title > span:first-child{
    display:block;
    white-space:nowrap;
    font-size:clamp(30px, 2.8vw, 43px);
}

.home-hero-title [data-hero-highlight]{
    display:block;
    color:#38bdf8;
}

.home-hero-text{
    max-width:450px;
    margin:12px 0 0;
    color:#dbeafe;
    font-size:clamp(13px, 1.1vw, 15px);
    font-weight:600;
    line-height:1.5;
}


/* PREUVE DU HERO : resume la promesse de tracabilite sans suivre le carrousel. */
.home-hero-proof{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin:13px 0 0;
    color:#e0f2fe;
    font-size:11px;
    font-weight:800;
    letter-spacing:.04em;
}
.home-hero-proof::before{
    width:7px;
    height:7px;
    border-radius:50%;
    background:#22c55e;
    box-shadow:0 0 0 5px rgba(34,197,94,.14);
    content:"";
}
.home-hero-actions{
    display:flex;
    flex-wrap:wrap;
    gap:9px;
    margin-top:17px;
}

.home-hero-action{
    min-height:44px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    border:1px solid rgba(255,255,255,.34);
    border-radius:10px;
    padding:10px 16px;
    color:#fff !important;
    font-size:12.5px;
    font-weight:800;
    text-decoration:none !important;
    transition:transform .2s ease, background .2s ease, box-shadow .2s ease;
}

.home-hero-action:hover{transform:translateY(-2px);}
.home-hero-action.is-primary{
    border-color:transparent;
    background:linear-gradient(135deg, #2563eb, #0ea5e9);
    box-shadow:0 12px 30px rgba(14,165,233,.3);
}
.home-hero-action.is-secondary{background:rgba(255,255,255,.08);}
.home-hero-action.is-secondary:hover{background:rgba(255,255,255,.16);}

/*
 * INDICATEURS ACCESSIBLES :
 * les anciens spans deviennent de vrais boutons clavier. Cette remise a zero
 * retire les styles natifs du navigateur sans modifier leur aspect circulaire.
 */
.slider-dots .dot{
    display:block;
    border:0;
    padding:0;
    appearance:none;
    -webkit-appearance:none;
}

/*
 * ACCESSIBILITE DU MOUVEMENT :
 * l'utilisateur qui demande moins d'animations conserve le changement de scene
 * et de texte, mais sans translation du panneau.
 */
@media (prefers-reduced-motion:reduce){
    .home-hero-panel.is-copy-refreshing{animation:none;}
    .hero-scene-video{display:none;}
    .hero-video-fallback{display:block;}
    .slide.active .hero-scene-image{animation:none !important; transform:none !important;}
    .hero-network-pulse,
    .hero-network-pulse::after{animation:none;}
}

/* HERO MEDIA 2026: use normal cover sizing to avoid excessive zoom or cropping. */
@media (min-width:992px){
    .hero-engine-slide{
        background-size:cover;
        background-position:center;
    }
}

/* HOME NAVIGATION: slightly reinforce legibility against the full-screen media. */
@media (min-width:961px){
    .nav-aviation .header-inner{padding:14px 24px;}
    .nav-aviation .logo-text-main{font-size:19px; font-weight:700;}
    .nav-aviation .logo-text-sub{font-size:11.5px;}
    .nav-aviation .nav{gap:23px; font-size:14.5px;}
    .nav-aviation .btn-login{padding:10px 20px; font-size:14px; font-weight:700;}
}

/* ===== PUBLICS DU RESEAU : operateurs/CAMO, CAN et MRO ===== */
.network-audience-section{
    padding:clamp(72px, 8vw, 112px) 0;
    background:
        radial-gradient(circle at 50% 45%, rgba(14,165,233,.12), transparent 24%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}
.home-section-heading{
    max-width:760px;
    margin:0 auto 44px;
    text-align:center;
}
.home-section-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin:0 0 12px;
    color:#0369a1;
    font-size:11px;
    font-weight:900;
    letter-spacing:.14em;
    text-transform:uppercase;
}
.home-section-eyebrow::before,
.home-section-eyebrow::after{
    width:24px;
    height:1px;
    background:#38bdf8;
    content:"";
}
.home-section-heading h2{
    margin:0;
    color:#0f172a;
    font-size:clamp(30px, 4vw, 48px);
    font-weight:850;
    letter-spacing:-.04em;
    line-height:1.05;
}
.home-section-heading p{
    max-width:660px;
    margin:18px auto 0;
    color:#64748b;
    font-size:16px;
    line-height:1.7;
}
.audience-network-grid{
    display:grid;
    max-width:1110px;
    margin:0 auto;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    align-items:stretch;
    gap:24px;
}
.audience-card{
    position:relative;
    overflow:hidden;
    border:1px solid #dbe7f3;
    border-radius:24px;
    padding:32px;
    background:#fff;
    box-shadow:0 18px 48px rgba(15,23,42,.08);
}
.audience-card::after{
    position:absolute;
    right:-55px;
    bottom:-65px;
    width:170px;
    height:170px;
    border-radius:50%;
    background:rgba(14,165,233,.07);
    content:"";
}
.audience-card-icon{
    width:56px;
    height:56px;
    display:grid;
    place-items:center;
    border-radius:17px;
    background:#e0f2fe;
    color:#0369a1;
    font-size:27px;
}
.audience-card h3{
    margin:23px 0 10px;
    color:#0f172a;
    font-size:22px;
    font-weight:850;
}
.audience-card > p{
    margin:0;
    color:#64748b;
    line-height:1.65;
}
.audience-benefits{
    position:relative;
    z-index:1;
    display:grid;
    gap:11px;
    margin:24px 0 0;
    padding:0;
    list-style:none;
}
.audience-benefits li{
    display:flex;
    align-items:center;
    gap:10px;
    color:#334155;
    font-size:13px;
    font-weight:750;
}
.audience-benefits i{color:#0284c7; font-size:18px;}
@media (max-width:991.98px){
    .audience-network-grid{grid-template-columns:1fr;}
}

@media (max-width:575.98px){
    .network-audience-section{padding:58px 0;}
    .home-section-heading{margin-bottom:30px; text-align:left;}
    .home-section-eyebrow::before,
    .home-section-eyebrow::after{display:none;}
    .audience-card{padding:25px 22px;}
}

/* PARCOURS PRINCIPAL : meme hierarchie centree que la section des publics. */
.workflow-heading.home-section-heading{
    display:block;
    max-width:760px;
    margin:0 auto 34px;
    text-align:center;
}
.workflow-heading.home-section-heading p:last-child{
    max-width:660px;
    margin:18px auto 0;
}

.workflow-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:18px;
}

.workflow-card{
    position:relative;
    min-height:245px;
    overflow:hidden;
    border:1px solid var(--border-subtle, #dbe5f1);
    border-radius:20px;
    padding:25px;
    background:linear-gradient(145deg, #fff, #f5f9ff);
    box-shadow:0 14px 34px rgba(15,23,42,.08);
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.workflow-card:hover{
    transform:translateY(-5px);
    border-color:#7dd3fc;
    box-shadow:0 20px 44px rgba(15,23,42,.13);
}

.workflow-number{
    position:absolute;
    top:18px;
    right:20px;
    color:#bfdbfe;
    font-size:34px;
    font-weight:900;
}

.workflow-icon{
    width:48px;
    height:48px;
    display:grid;
    place-items:center;
    border-radius:14px;
    background:#e0f2fe;
    color:#0369a1;
    font-size:23px;
}

.workflow-card h3{
    margin:24px 0 10px;
    color:#0f172a;
    font-size:18px;
    font-weight:800;
}

.workflow-card p{
    margin:0;
    color:#64748b;
    font-size:13px;
    line-height:1.65;
}

@media (max-width:1100px){
    .workflow-grid{grid-template-columns:repeat(2, minmax(0, 1fr));}
}

@media (max-width:767.98px){
    /*
     * HERO MOBILE :
     * les limites gauche et droite sont ancrees au viewport afin qu'une largeur
     * minimale provenant de la navigation ne puisse plus pousser la carte hors
     * de l'ecran. Le contenu reste lisible sans defilement horizontal.
     */
    .hero-slider{height:100vh; height:100svh; min-height:540px;}
    .hero-scene-video{display:none;}
    .hero-video-fallback{display:block;}
    .slide.active .hero-scene-image{animation:none !important; transform:none !important;}
    .home-hero-message{
        left:50%;
        right:auto;
        width:min(420px, calc(100vw - 32px));
        max-width:none;
        transform:translate(-50%, -50%);
    }
    .home-hero-panel{
        width:100%;
        box-sizing:border-box;
        padding:18px 17px;
        overflow:hidden;
        background:linear-gradient(135deg, rgba(5,20,39,.68), rgba(13,50,97,.44));
    }
    .home-hero-title{font-size:clamp(30px, 9vw, 39px);}
    .home-hero-actions{gap:8px;}
    .home-hero-action{flex:1 1 145px;}
    .slider-nav{display:none;}
    .workflow-heading{align-items:flex-start; flex-direction:column;}
    .hero-engine-slide{background-size:cover; background-position:center;}
}

@media (max-width:575.98px){
    /*
     * ACTIONS SUR PETIT ECRAN :
     * une seule colonne garantit que les deux libelles et leurs icones restent
     * visibles, y compris avec une langue plus longue ou un zoom navigateur.
     */
    .home-hero-title{font-size:clamp(28px, 8.5vw, 35px); overflow-wrap:anywhere;}
    .home-hero-title > span:first-child{font-size:clamp(23px, 7.4vw, 32px);}
    .home-hero-text{overflow-wrap:anywhere;}
    .home-hero-actions{display:grid; grid-template-columns:1fr;}
    .home-hero-action{width:100%; box-sizing:border-box;}
    .workflow-grid{grid-template-columns:1fr;}
    .workflow-card{min-height:0;}
}

/* ===== PRIORITES OPERATIONNELLES : AOG, urgent et planifie ===== */
.maintenance-priority-section{
    padding:clamp(72px, 8vw, 108px) 0;
    border-top:1px solid #e5edf6;
    background:#f7f9fc;
}

.maintenance-priority-grid{
    display:grid;
    max-width:1160px;
    margin:0 auto;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:20px;
}

.maintenance-priority-card{
    --priority-color:#0284c7;
    --priority-soft:#eff8ff;
    position:relative;
    overflow:hidden;
    border:1px solid #dbe5ef;
    border-radius:18px;
    padding:28px 28px 24px 32px;
    background:#fff;
    box-shadow:0 12px 28px rgba(15,23,42,.06);
}

.maintenance-priority-card::before{
    position:absolute;
    top:0;
    bottom:0;
    left:0;
    width:5px;
    background:var(--priority-color);
    content:"";
}

.maintenance-priority-card--aog{
    --priority-color:#dc3f4f;
    --priority-soft:#fff1f2;
}

.maintenance-priority-card--urgent{
    --priority-color:#d97706;
    --priority-soft:#fff7ed;
}

.maintenance-priority-card--planned{
    --priority-color:#087fb8;
    --priority-soft:#eff8ff;
}

.maintenance-priority-card__header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
}

.maintenance-priority-card__icon{
    width:48px;
    height:48px;
    display:grid;
    flex:0 0 48px;
    place-items:center;
    border-radius:12px;
    background:var(--priority-soft);
    color:var(--priority-color);
    font-size:23px;
}

.maintenance-priority-card__level{
    margin:4px 0 0;
    color:var(--priority-color);
    font-size:10px;
    font-weight:900;
    letter-spacing:.13em;
    text-transform:uppercase;
}

.maintenance-priority-card h3{
    margin:24px 0 6px;
    color:#0f172a;
    font-size:24px;
    font-weight:850;
    letter-spacing:-.025em;
}

.maintenance-priority-card__context{
    min-height:52px;
    margin:0;
    color:#64748b;
    font-size:14px;
    line-height:1.65;
}

.maintenance-priority-card__details{
    display:grid;
    gap:0;
    margin:24px 0 0;
    padding:0;
    border-top:1px solid #e8eef5;
    list-style:none;
}

.maintenance-priority-card__details li{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:13px 0;
    border-bottom:1px solid #eef3f8;
    color:#64748b;
    font-size:12px;
}

.maintenance-priority-card__details strong{
    color:#1e293b;
    font-size:12px;
    font-weight:800;
    text-align:right;
}

.maintenance-priority-example{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-top:18px;
    border-radius:12px;
    padding:13px 15px;
    background:#172033;
    color:#fff;
}

.maintenance-priority-example span{
    color:#a9b7ca;
    font-size:10px;
    font-weight:750;
    letter-spacing:.06em;
    text-transform:uppercase;
}

.maintenance-priority-example strong{
    color:#fff;
    font-size:13px;
    white-space:nowrap;
}

.maintenance-priority-note{
    display:flex;
    max-width:1160px;
    margin:24px auto 0;
    align-items:flex-start;
    justify-content:center;
    gap:9px;
    color:#64748b;
    font-size:12px;
    line-height:1.6;
    text-align:center;
}

.maintenance-priority-note i{
    margin-top:2px;
    color:#0284c7;
    font-size:15px;
}

@media (max-width:991.98px){
    .maintenance-priority-grid{
        max-width:680px;
        grid-template-columns:1fr;
    }
    .maintenance-priority-card__context{min-height:0;}
}

@media (max-width:575.98px){
    .maintenance-priority-section{padding:58px 0;}
    .maintenance-priority-card{padding:24px 22px 21px 27px;}
    .maintenance-priority-note{text-align:left;}
}

/* ==========================================================
   ABOUT US — PRODUCT INFORMATION FIRST / ADVERTISING SECOND
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
    /* PHASE 9: wider sponsored media while keeping About Us equally readable. */
    grid-template-columns:minmax(0, 52%) minmax(360px, 48%);
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
    /* PHASE 9: explicit width allows the small source logo to scale up visibly. */
    width:clamp(170px, 13vw, 220px);
    max-width:72%;
    max-height:92px;
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
        grid-template-columns:minmax(0, 52%) minmax(300px, 48%);
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

/* PHASE 9: sponsored content is intentionally excluded from printed pages. */
@media print{
    .about .about-ad-column{
        display:none !important;
    }

    .about .about-ad-layout{
        display:block !important;
        height:auto !important;
    }

    .about.section-padding{
        min-height:0 !important;
        overflow:visible !important;
    }
}

CSS
);
?>

<div class="site-index" style="margin:0;padding:0;">

  <!-- ===== FLASH MESSAGES ===== -->
  <?php if (Yii::$app->session->hasFlash('message')): ?>
    <div class="alert alert-success"><?= Yii::$app->session->getFlash('message') ?></div>
  <?php endif; ?>
  <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger"><?= Yii::$app->session->getFlash('error') ?></div>
  <?php endif; ?>
  <?php if (Yii::$app->session->hasFlash('usernameError')): ?>
    <div class="alert alert-danger"><?= Yii::$app->session->getFlash('usernameError') ?></div>
  <?php endif; ?>
  <?php if (Yii::$app->session->hasFlash('passwordError')): ?>
    <div class="alert alert-danger"><?= Yii::$app->session->getFlash('passwordError') ?></div>
  <?php endif; ?>

  <!--
    NOUVEAU HERO : ce carrousel raconte le parcours maintenance en cinq scenes.
    Le texte et les images changent ensemble, tandis que les deux actions restent
    celles deja autorisees pour le profil connecte.
  -->
  <section class="hero-slider" id="heroSlider" aria-labelledby="home-hero-title">
    <!--
      MESSAGE CENTRAL : les valeurs initiales correspondent a la premiere scene.
      Le JavaScript les actualise ensuite depuis les attributs data des slides.
    -->
    <div class="home-hero-message">
      <div class="home-hero-panel" aria-live="polite">
        <p class="home-hero-kicker">Aircraft maintenance coordination</p>
        <h1 class="home-hero-title" id="home-hero-title">
          <span>Aircraft Maintenance.</span>
          <span data-hero-highlight>One Network. One Workflow.</span>
        </h1>
        <p class="home-hero-text">Connect aircraft operators and CAMOs with relevant MRO capabilities—from request and quotation to work completion.</p>
        <p class="home-hero-proof">One request. One history. Full traceability.</p>
        <div class="home-hero-actions">
          <?= Html::a(
              '<i class="ri-send-plane-line"></i><span>' . Html::encode($heroPrimaryLabel) . '</span>',
              $heroPrimaryUrl,
              ['class' => 'home-hero-action is-primary']
          ) ?>
          <?= Html::a(
              '<i class="ri-building-2-line"></i><span>' . Html::encode($heroSecondaryLabel) . '</span>',
              $heroSecondaryUrl,
              ['class' => 'home-hero-action is-secondary']
          ) ?>
        </div>
      </div>
    </div>

    <?php
    /*
     * ANCIEN CARROUSEL - CONSERVE MAIS DESACTIVE :
     * le bloc ci-dessous reste dans le fichier pour comparaison et retour arriere.
     * La condition false empeche son rendu et evite tout chargement de ses medias.
     */
    if (false):
    ?>
    <!-- Slide 1 -->
    <div class="slide hero-engine-slide active" style="background-image:url('<?= Html::encode($heroFirstImageUrl) ?>');" data-media-ready="true">
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
    <!-- PERFORMANCE: optimized existing WebP replaces the 7.68 MiB Falcon image. -->
    <div class="slide" data-bg-image="<?= Html::encode($heroSecondImageUrl) ?>">
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
    <div class="slide" data-bg-image="<?= Html::encode($heroThirdImageUrl) ?>">
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
      <!-- PHASE 9: the video source is attached only when its slide approaches. -->
      <video class="hero-video" preload="none" muted playsinline>
        <source data-src="<?= Html::encode($heroVideoUrl) ?>" type="video/mp4" />
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
    <?php endif; ?>

    <!--
      NOUVELLES SCENES HYBRIDES :
      la premiere scene utilise la video drone avec une image de secours. Les
      quatre autres images sont chargees progressivement puis animees en CSS.
    -->
    <?php foreach ($heroSlides as $index => $heroSlide): ?>
      <?php
      /*
       * PREPARATION DE LA SCENE :
       * ces valeurs concernent uniquement la presentation du media. Elles ne
       * changent ni le contenu d'une request ni les profils AO/MRO disponibles.
       */
      $hasHeroVideo = !empty($heroSlide['video']);
      $heroMotionClass = (string) ($heroSlide['motion'] ?? 'motion-slow-zoom');
      ?>
      <div
        class="slide hero-scene-slide<?= $index === 0 ? ' active' : '' ?>"
        data-kicker="<?= Html::encode($heroSlide['kicker']) ?>"
        data-title="<?= Html::encode($heroSlide['title']) ?>"
        data-highlight="<?= Html::encode($heroSlide['highlight']) ?>"
        data-description="<?= Html::encode($heroSlide['description']) ?>"
        role="img"
        aria-label="<?= Html::encode($heroSlide['label']) ?>"
      >
        <?php if ($hasHeroVideo): ?>
          <!--
            VIDEO DRONE : la source reste dans data-src jusqu'a l'activation du
            slide. muted et playsinline autorisent la lecture fluide sans son.
          -->
          <video
            class="hero-scene-media hero-scene-video"
            poster="<?= Html::encode($heroSlide['image']) ?>"
            preload="none"
            muted
            playsinline
            aria-hidden="true"
          >
            <source data-src="<?= Html::encode($heroSlide['video']) ?>" type="video/mp4">
          </video>

          <!--
            IMAGE DE SECOURS : elle remplace la video sur mobile, avec mouvement
            reduit ou en cas de delai de chargement du media anime.
          -->
          <img
            class="hero-scene-media hero-scene-image hero-video-fallback <?= Html::encode($heroMotionClass) ?>"
            src="<?= Html::encode($heroSlide['image']) ?>"
            alt=""
            decoding="async"
            fetchpriority="high"
          >
        <?php else: ?>
          <!--
            IMAGE ANIMEE : data-src permet au JavaScript de ne charger que la
            scene active et la suivante, ce qui protege le temps d'affichage.
          -->
          <img
            class="hero-scene-media hero-scene-image <?= Html::encode($heroMotionClass) ?>"
            data-src="<?= Html::encode($heroSlide['image']) ?>"
            alt=""
            decoding="async"
          >
        <?php endif; ?>

        <?php if (!empty($heroSlide['network'])): ?>
          <!--
            POINTS RESEAU : positions decoratives alignees sur la carte du visuel.
            aria-hidden garantit qu'elles n'ajoutent aucun bruit pour l'accessibilite.
          -->
          <span class="hero-network-pulses" aria-hidden="true">
            <span class="hero-network-pulse" style="--pulse-x:43%;--pulse-y:12%;--pulse-delay:0s"></span>
            <span class="hero-network-pulse" style="--pulse-x:58%;--pulse-y:11%;--pulse-delay:.45s"></span>
            <span class="hero-network-pulse" style="--pulse-x:71%;--pulse-y:13%;--pulse-delay:.9s"></span>
            <span class="hero-network-pulse" style="--pulse-x:50%;--pulse-y:25%;--pulse-delay:1.35s"></span>
            <span class="hero-network-pulse" style="--pulse-x:76%;--pulse-y:25%;--pulse-delay:1.8s"></span>
          </span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <!-- Flèches -->
    <button class="slider-nav slider-prev" id="prevBtn" aria-label="Previous slide" type="button">❮</button>
    <button class="slider-nav slider-next" id="nextBtn" aria-label="Next slide" type="button">❯</button>

    <!--
      INDICATEURS : leur nombre est genere depuis la meme source que les slides,
      ce qui evite un decalage si une scene est ajoutee ou retiree plus tard.
    -->
    <div class="slider-dots" id="sliderDots" role="tablist" aria-label="Slides">
      <?php foreach ($heroSlides as $index => $heroSlide): ?>
        <button
          class="dot<?= $index === 0 ? ' active' : '' ?>"
          data-slide="<?= $index ?>"
          type="button"
          aria-label="Show scene <?= $index + 1 ?>: <?= Html::encode($heroSlide['kicker']) ?>"
        ></button>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ================== PUBLICS DU RESEAU ================== -->
  <section class="network-audience-section" id="network" aria-labelledby="network-audience-title">
    <div class="container">
      <header class="home-section-heading">
        <p class="home-section-eyebrow">Who CAN is for</p>
        <h2 id="network-audience-title">Two sides. One controlled maintenance network.</h2>
        <p>Core Aviation Network connects operational demand with relevant maintenance capabilities while keeping every exchange attached to the same request.</p>
      </header>

      <div class="audience-network-grid">
        <article class="audience-card">
          <span class="audience-card-icon" aria-hidden="true"><i class="ri-plane-line"></i></span>
          <h3>For Aircraft Operators &amp; CAMOs</h3>
          <p>Structure maintenance needs, compare responses and retain operational visibility from the first request to completion.</p>
          <ul class="audience-benefits">
            <li><i class="ri-check-line" aria-hidden="true"></i> Submit structured maintenance requests</li>
            <li><i class="ri-check-line" aria-hidden="true"></i> Compare relevant MRO quotations</li>
            <li><i class="ri-check-line" aria-hidden="true"></i> Track documents, decisions and progress</li>
          </ul>
        </article>


        <article class="audience-card">
          <span class="audience-card-icon" aria-hidden="true"><i class="ri-building-2-line"></i></span>
          <h3>For MROs</h3>
          <p>Receive requests aligned with your maintenance capabilities and manage commercial and operational responses in one place.</p>
          <ul class="audience-benefits">
            <li><i class="ri-check-line" aria-hidden="true"></i> Review relevant maintenance opportunities</li>
            <li><i class="ri-check-line" aria-hidden="true"></i> Prepare controlled quotations</li>
            <li><i class="ri-check-line" aria-hidden="true"></i> Coordinate work, reports and CRS records</li>
          </ul>
        </article>
      </div>
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
            <p class="lead">
              <strong>Core Aviation Network</strong> connects aircraft operators and CAMO teams
              with MRO facilities through one controlled maintenance workflow.
            </p>
            <p class="lead">
              Operators can submit an operational request with aircraft data, ETA, ETD,
              maintenance location and controlled attachments, then compare qualified responses.
            </p>
            <p class="lead mb-0">
              MRO partners can prepare quotations, receive purchase orders, follow the work
              and exchange reports, CRS documents and feedback in a traceable environment.
            </p>
          </div>
        </div>

        <!-- PHASE 9: responsive sponsored zone, secondary to the product message. -->
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
                            data-bg-image="<?= Html::encode($mediaUrl) ?>"
                            aria-hidden="true"
                          ></div>

                          <?= Html::img($mediaUrl, [
                              'class' => 'about-ad-media',
                              'alt' => 'Sponsored aviation advertisement',
                              /* PHASE 9: advertising is below the hero and never blocks it. */
                              'loading' => 'lazy',
                              'decoding' => 'async',
                              'onerror' =>
                                  "this.closest('.about-ad-slide').classList.add('has-media-error');",
                          ]) ?>

                        <?php elseif ($advertType === 'video'): ?>

                          <video
                            class="about-ad-media about-ad-video"
                            muted
                            loop
                            playsinline
                            preload="none"
                            onerror="
                              this.closest('.about-ad-slide')
                                .classList.add('has-media-error');
                            "
                          >
                            <source
                              data-src="<?= Html::encode($mediaUrl) ?>"
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

  <!-- ================== AO / MRO WORKFLOW ================== -->
  <section class="services workflow-section" aria-labelledby="services-title">
    <div class="container">
      <header class="home-section-heading workflow-heading">
        <p class="home-section-eyebrow">How CAN works</p>
        <h2 id="services-title">From request to completion</h2>
        <p>One structured workflow connects operational demand, relevant maintenance capabilities, commercial decisions and final records.</p>
      </header>

      <div class="workflow-grid">
        <article class="workflow-card">
          <span class="workflow-number">01</span>
          <span class="workflow-icon"><i class="ri-file-add-line"></i></span>
          <h3>Request</h3>
          <p>Capture aircraft, location, priority, schedule, maintenance scope and controlled attachments.</p>
        </article>

        <article class="workflow-card">
          <span class="workflow-number">02</span>
          <span class="workflow-icon"><i class="ri-route-line"></i></span>
          <h3>Match</h3>
          <p>Connect the requirement with MRO partners according to the operational information available in CAN.</p>
        </article>

        <article class="workflow-card">
          <span class="workflow-number">03</span>
          <span class="workflow-icon"><i class="ri-file-list-3-line"></i></span>
          <h3>Quote</h3>
          <p>Compare technical scope, price, currency, lead time and quotation documents in one place.</p>
        </article>

        <article class="workflow-card">
          <span class="workflow-number">04</span>
          <span class="workflow-icon"><i class="ri-shopping-bag-3-line"></i></span>
          <h3>Purchase Order</h3>
          <p>Confirm the selected response and retain the purchase order with its maintenance request.</p>
        </article>

        <article class="workflow-card">
          <span class="workflow-number">05</span>
          <span class="workflow-icon"><i class="ri-tools-line"></i></span>
          <h3>Work</h3>
          <p>Coordinate appointments, messages, operational updates and supporting maintenance records.</p>
        </article>

        <article class="workflow-card">
          <span class="workflow-number">06</span>
          <span class="workflow-icon"><i class="ri-shield-check-line"></i></span>
          <h3>Complete</h3>
          <p>Keep reports, CRS documents, feedback and the final history connected and traceable.</p>
        </article>
      </div>
    </div>
  </section>
  <!-- ========== PRIORITES DE MAINTENANCE ========== -->
  <section class="maintenance-priority-section" id="maintenance-priorities" aria-labelledby="maintenance-priority-title">
    <div class="container">
      <header class="home-section-heading">
        <p class="home-section-eyebrow">AOG and Planned Maintenance</p>
        <h2 id="maintenance-priority-title">One workflow, adapted to operational urgency.</h2>
        <p>From an aircraft grounded event to scheduled maintenance, CAN keeps the priority, requested response timing and operational context visible.</p>
      </header>

      <div class="maintenance-priority-grid">
        <article class="maintenance-priority-card maintenance-priority-card--aog">
          <div class="maintenance-priority-card__header">
            <span class="maintenance-priority-card__icon" aria-hidden="true">
              <i class="ri-error-warning-line"></i>
            </span>
            <span class="maintenance-priority-card__level">Immediate attention</span>
          </div>
          <h3>AOG</h3>
          <p class="maintenance-priority-card__context">Aircraft grounded and a response time is required for the maintenance request.</p>
          <ul class="maintenance-priority-card__details">
            <li><span>Response time</span><strong>Required</strong></li>
            <li><span>Accepted range</span><strong>15 min – 24 h</strong></li>
          </ul>
          <div class="maintenance-priority-example" aria-label="Illustrative AOG response target">
            <span>Example · response target</span>
            <strong>18 min remaining</strong>
          </div>
        </article>

        <article class="maintenance-priority-card maintenance-priority-card--urgent">
          <div class="maintenance-priority-card__header">
            <span class="maintenance-priority-card__icon" aria-hidden="true">
              <i class="ri-flashlight-line"></i>
            </span>
            <span class="maintenance-priority-card__level">Time-sensitive</span>
          </div>
          <h3>Urgent</h3>
          <p class="maintenance-priority-card__context">A maintenance need requiring a fast review without declaring the aircraft grounded.</p>
          <ul class="maintenance-priority-card__details">
            <li><span>Response time</span><strong>Optional</strong></li>
            <li><span>Operational focus</span><strong>Fast review</strong></li>
          </ul>
        </article>

        <article class="maintenance-priority-card maintenance-priority-card--planned">
          <div class="maintenance-priority-card__header">
            <span class="maintenance-priority-card__icon" aria-hidden="true">
              <i class="ri-calendar-check-line"></i>
            </span>
            <span class="maintenance-priority-card__level">Scheduled work</span>
          </div>
          <h3>Routine / Planned</h3>
          <p class="maintenance-priority-card__context">Scheduled maintenance coordinated through the same structured and traceable request workflow.</p>
          <ul class="maintenance-priority-card__details">
            <li><span>Response deadline</span><strong>None required</strong></li>
            <li><span>Operational focus</span><strong>Standard planning</strong></li>
          </ul>
        </article>
      </div>

      <p class="maintenance-priority-note">
        <i class="ri-information-line" aria-hidden="true"></i>
        <span>Response timing is defined within each request and does not represent a guaranteed MRO response SLA.</span>
      </p>
    </div>
  </section>

  

</div>
