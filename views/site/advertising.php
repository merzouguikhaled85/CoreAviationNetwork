<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

$this->title = 'Advertising Solutions | Core Aviation Network';
$this->params['meta_description'] = 'Contextual advertising for MROs, OEMs and aviation suppliers across real aircraft maintenance workflows on Core Aviation Network.';
$this->params['bodyClass'] = 'page-advertising';

$this->registerCssFile('@web/css/advertising-media-kit.css');
$this->registerJsFile('@web/js/advertising-media-kit.js', [
    'depends' => [YiiAsset::class],
    'position' => yii\web\View::POS_END,
]);

// APPLICATION CONTEXT: these placements mirror the advertising zones currently used by the platform.
$placements = [
    [
        'id' => 'homepage',
        'number' => '01',
        'zone' => 'Public platform',
        'name' => 'Aviation Network Showcase',
        'format' => 'Wide creative · 1600 × 600 px',
        'context' => 'Brand and capability discovery',
        'price' => 390,
        'class' => 'ad-placement-home',
        'description' => 'Build awareness on the public entry point where aircraft operators, MROs and aviation partners discover the network.',
    ],
    [
        'id' => 'quotation',
        'number' => '02',
        'zone' => 'MRO quotation',
        'name' => 'Quote Workspace Spotlight',
        'format' => 'Landscape creative · 1200 × 675 px',
        'context' => 'During quotation preparation',
        'price' => 490,
        'class' => 'ad-placement-market',
        'description' => 'Present parts, tooling, engineering or specialist services while an MRO prepares a technical and commercial quotation.',
    ],
    [
        'id' => 'application-review',
        'number' => '03',
        'zone' => 'Application review',
        'name' => 'Decision Side Panel',
        'format' => 'Landscape creative · 1200 × 675 px',
        'context' => 'During quote and answer review',
        'price' => 590,
        'class' => 'ad-placement-rail',
        'description' => 'Reach operators and MRO teams while they verify request data, compare a response and evaluate maintenance options.',
    ],
    [
        'id' => 'purchase-order',
        'number' => '04',
        'zone' => 'Purchase Orders',
        'name' => 'PO Workflow Spotlight',
        'format' => 'Landscape creative · 1200 × 675 px',
        'context' => 'During operational follow-up',
        'price' => 690,
        'class' => 'ad-placement-brief',
        'description' => 'Maintain visibility when maintenance partners consult accepted work, linked purchase orders and request information.',
    ],
];

// APPLICATION CONTEXT: packages describe coverage across real AO/MRO workflow screens.
$plans = [
    [
        'id' => 'essential',
        'number' => '01',
        'name' => 'Essential',
        'price' => 490,
        'description' => 'Test one strategic placement.',
        'features' => ['1 workflow placement', '1 active creative', 'AO or MRO audience targeting', 'Monthly delivery report'],
    ],
    [
        'id' => 'performance',
        'number' => '02',
        'name' => 'Performance',
        'price' => 890,
        'description' => 'Build a consistent market presence.',
        'featured' => true,
        'features' => ['2 coordinated workflow placements', 'Up to 3 creatives', 'Region + aviation activity targeting', 'Twice-monthly reporting'],
    ],
    [
        'id' => 'dominance',
        'number' => '03',
        'name' => 'Dominance',
        'price' => 1490,
        'description' => 'Own multiple audience touchpoints.',
        'features' => ['All 4 application placements', 'Unlimited creative rotations', 'A/B testing included', 'Priority campaign support'],
    ],
];

$periods = [
    ['months' => 1, 'label' => '1 month', 'discount' => 0],
    ['months' => 3, 'label' => '3 months', 'discount' => 5],
    ['months' => 6, 'label' => '6 months', 'discount' => 12],
    ['months' => 12, 'label' => '12 months', 'discount' => 20],
];

$faqs = [
    ['Who sees the advertising campaigns?', 'Campaigns reach MRO facilities, aircraft operators, OEMs, suppliers, technical buyers and airworthiness professionals using Core Aviation Network.'],
    ['Which creative formats are accepted?', 'The current platform accepts JPG, PNG, MP4 and AVI files up to 20 MB. Landscape creatives are recommended for workflow panels, while the public showcase supports a wider format.'],
    ['Can creatives be changed during a campaign?', 'Yes. The number of simultaneous creatives depends on the selected package. Planned replacements are included after editorial validation.'],
    ['How is campaign performance measured?', 'Reports cover impressions, clicks, engagement rate, placement and delivery period. Performance and Dominance include more frequent reporting.'],
    ['How quickly can a campaign go live?', 'Allow two business days after receiving compliant assets. Every creative receives a technical and editorial check before publication.'],
];

$heroImage = Url::to('@web/img/advertising/hero-mro-hangar.png');
$contactUrl = Url::to(['/site/contact']);
?>

<div class="ad-media-page">
    <section class="ad-hero" id="advertising-top">
        <div class="ad-grid-overlay" aria-hidden="true"></div>
        <div class="ad-shell ad-hero-grid">
            <div class="ad-hero-copy">
                <!-- APPLICATION CONTEXT: value proposition tied to aircraft maintenance workflows. -->
                <p class="ad-eyebrow"><span></span>Core Aviation Network · Advertising 2026</p>
                <h1>Place your brand inside real maintenance decisions.</h1>
                <p class="ad-hero-lead">Contextual campaigns for OEMs, parts suppliers, tooling providers and aviation specialists—visible while AO and MRO professionals manage requests, quotations and purchase orders.</p>
                <div class="ad-actions">
                    <a class="ad-button ad-button-primary" href="#campaign-planner">Reserve a placement <span>→</span></a>
                    <a class="ad-button ad-button-outline" href="#advertising-plans">View packages <span>→</span></a>
                </div>
            </div>
            <div class="ad-hero-visual" style="--ad-hero-image: url('<?= Html::encode($heroImage) ?>')" role="img" aria-label="Commercial aircraft undergoing maintenance in a modern MRO hangar">
                <div class="ad-audience-badge">
                    <span class="ad-badge-symbol" aria-hidden="true">◎</span>
                    <div><strong>Qualified aviation audience</strong><small>AO · MRO · OEM · Suppliers</small></div>
                </div>
            </div>
        </div>
        <div class="ad-shell ad-proof-strip">
            <!-- APPLICATION CONTEXT: factual platform coverage rather than generic marketing metrics. -->
            <div><strong>4</strong><span>application placements</span></div>
            <div><strong>AO + MRO</strong><span>qualified audiences</span></div>
            <div><strong>Photo + Video</strong><span>supported campaigns</span></div>
        </div>
    </section>

    <section class="ad-section ad-placements" id="advertising-placements">
        <div class="ad-shell">
            <header class="ad-section-heading ad-heading-split">
                <div><p class="ad-eyebrow ad-orange"><span></span>Application placements</p><h2>Visible at meaningful workflow moments.</h2></div>
                <p>Select the operational context that matches your objective, from platform discovery to quotation and purchase-order follow-up.</p>
            </header>
            <div class="ad-placement-grid">
                <?php foreach ($placements as $placement): ?>
                    <article class="ad-placement-card">
                        <div class="ad-placement-preview <?= Html::encode($placement['class']) ?>">
                            <div class="ad-mock-nav"><strong>CAN</strong><i></i><i></i><i></i></div>
                            <div class="ad-mock-slot"><b>Your brand</b><span>Contextual aviation campaign</span></div>
                            <small><?= Html::encode($placement['format']) ?></small>
                        </div>
                        <div class="ad-placement-content">
                            <p class="ad-card-kicker"><span><?= $placement['number'] ?></span><?= Html::encode($placement['zone']) ?></p>
                            <h3><?= Html::encode($placement['name']) ?></h3>
                            <p><?= Html::encode($placement['description']) ?></p>
                            <div class="ad-card-meta"><span><?= Html::encode($placement['context']) ?></span><strong>From €<?= number_format($placement['price'], 0, '.', ',') ?></strong></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ad-section ad-plans" id="advertising-plans">
        <div class="ad-shell">
            <header class="ad-section-heading ad-plans-heading">
                <div><p class="ad-eyebrow"><span></span>Flexible packages</p><h2>A presence shaped around your ambition.</h2></div>
                <div class="ad-period-switch" aria-label="Campaign period">
                    <?php foreach ($periods as $period): ?>
                        <button type="button" data-months="<?= $period['months'] ?>" data-discount="<?= $period['discount'] ?>" class="<?= $period['months'] === 3 ? 'is-active' : '' ?>" aria-pressed="<?= $period['months'] === 3 ? 'true' : 'false' ?>">
                            <?= Html::encode($period['label']) ?><?php if ($period['discount']): ?><small>−<?= $period['discount'] ?>%</small><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </header>
            <div class="ad-plan-grid">
                <?php foreach ($plans as $plan): ?>
                    <?php $monthly = $plan['price'] * .95; ?>
                    <article class="ad-plan-card <?= !empty($plan['featured']) ? 'is-featured' : '' ?>" data-plan-card data-base-price="<?= $plan['price'] ?>">
                        <?php if (!empty($plan['featured'])): ?><span class="ad-popular">Most selected</span><?php endif; ?>
                        <p class="ad-plan-number"><?= $plan['number'] ?></p>
                        <h3><?= Html::encode($plan['name']) ?></h3>
                        <p class="ad-plan-description"><?= Html::encode($plan['description']) ?></p>
                        <div class="ad-plan-price"><strong data-monthly-price>€<?= number_format($monthly, 0, '.', ',') ?></strong><span>/ month excl. tax</span></div>
                        <p class="ad-plan-total" data-total-price>€<?= number_format($monthly * 3, 0, '.', ',') ?> excl. tax over 3 months</p>
                        <ul><?php foreach ($plan['features'] as $feature): ?><li><span>✓</span><?= Html::encode($feature) ?></li><?php endforeach; ?></ul>
                        <button class="ad-button ad-plan-button" type="button" data-choose-plan="<?= Html::encode($plan['id']) ?>">Choose <?= Html::encode($plan['name']) ?><span>→</span></button>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="ad-price-note">Indicative prices excluding taxes. Final availability and targeting are confirmed before campaign activation.</p>
        </div>
    </section>

    <section class="ad-section ad-delivery" id="advertising-delivery">
        <div class="ad-shell ad-delivery-grid">
            <div class="ad-delivery-copy">
                <p class="ad-eyebrow ad-orange"><span></span>Controlled delivery</p>
                <h2>From creative brief to reporting, with full visibility.</h2>
                <p>A scheduled and measurable campaign connected to relevant AO/MRO screens. You stay in control of delivery periods, creatives and audience priorities.</p>
                <a class="ad-text-link" href="#campaign-planner">Plan my campaign <span>→</span></a>
            </div>
            <ol class="ad-timeline">
                <li><span>01</span><div><h3>Brief</h3><p>Placement, audience, region and objective.</p></div><small>D−5</small></li>
                <li><span>02</span><div><h3>Validation</h3><p>Creative format and readability control.</p></div><small>D−2</small></li>
                <li><span>03</span><div><h3>Delivery</h3><p>Publication throughout the booked period.</p></div><small>Day 1</small></li>
                <li><span>04</span><div><h3>Reporting</h3><p>Impressions, clicks and campaign insights.</p></div><small>Included</small></li>
            </ol>
        </div>
    </section>

    <section class="ad-section ad-planner-section" id="campaign-planner">
        <div class="ad-shell ad-planner-grid">
            <div class="ad-planner-intro">
                <p class="ad-eyebrow"><span></span>Campaign planner</p>
                <h2>Build your aviation campaign brief.</h2>
                <p>Select an application placement, coverage package and period to receive an immediate budget estimate. No information leaves your browser.</p>
                <div class="ad-local-note"><span>✓</span>Runs entirely in your browser</div>
            </div>
            <form class="ad-planner" id="ad-campaign-form">
                <label class="ad-field ad-field-wide" for="ad-placement">Placement
                    <select id="ad-placement"><?php foreach ($placements as $placement): ?><option value="<?= Html::encode($placement['id']) ?>"><?= Html::encode($placement['name'] . ' — ' . $placement['zone']) ?></option><?php endforeach; ?></select>
                </label>
                <label class="ad-field" for="ad-plan">Package
                    <select id="ad-plan"><?php foreach ($plans as $plan): ?><option value="<?= Html::encode($plan['id']) ?>" data-price="<?= $plan['price'] ?>" <?= $plan['id'] === 'performance' ? 'selected' : '' ?>><?= Html::encode($plan['name']) ?></option><?php endforeach; ?></select>
                </label>
                <label class="ad-field" for="ad-period">Period
                    <select id="ad-period"><?php foreach ($periods as $period): ?><option value="<?= $period['months'] ?>" data-discount="<?= $period['discount'] ?>" <?= $period['months'] === 3 ? 'selected' : '' ?>><?= Html::encode($period['label']) ?><?= $period['discount'] ? ' — save ' . $period['discount'] . '%' : '' ?></option><?php endforeach; ?></select>
                </label>
                <div class="ad-estimate"><span>Estimated campaign budget</span><strong id="ad-estimate-total">€2,537 <small>excl. tax</small></strong><p id="ad-estimate-monthly">€846 per month for 3 months</p></div>
                <button class="ad-button ad-button-orange" type="submit">Copy campaign brief <span>→</span></button>
                <a class="ad-contact-link" href="<?= Html::encode($contactUrl) ?>">Contact the Core Aviation Network team</a>
                <p class="ad-copy-status" role="status" aria-live="polite"></p>
            </form>
        </div>
    </section>

    <section class="ad-section ad-faq" id="advertising-faq">
        <div class="ad-shell ad-faq-grid">
            <div class="ad-faq-intro"><p class="ad-eyebrow ad-orange"><span></span>Frequently asked questions</p><h2>Everything to know before booking.</h2><p>Campaigns can be adapted to a precise aircraft segment, region or commercial objective.</p></div>
            <div class="ad-faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <article class="ad-faq-item <?= $index === 0 ? 'is-open' : '' ?>">
                        <h3><button type="button" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><b><?= Html::encode($faq[0]) ?></b><i>+</i></button></h3>
                        <div class="ad-faq-answer"><p><?= Html::encode($faq[1]) ?></p></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="ad-final-cta">
        <div class="ad-shell">
            <div><p>Contextual visibility across the platform</p><h2>Be present when aviation professionals act.</h2></div>
            <a class="ad-button ad-button-light" href="#campaign-planner">Build my campaign <span>→</span></a>
        </div>
    </section>
</div>
