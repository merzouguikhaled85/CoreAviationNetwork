<?php

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->title = 'About | Core Aviation Network';
$this->params['meta_description'] = 'Learn how Core Aviation Network connects Aircraft Operators, CAMOs and MRO providers through one controlled maintenance workflow.';
$this->registerCssFile(
    '@web/css/about-page.css?v=20261026-1',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);
?>

<div class="can-about-page">
  <section class="can-about-hero" aria-labelledby="about-title">
    <div class="can-about-container">
      <div class="can-about-hero-copy">
        <p class="can-about-eyebrow">About Core Aviation Network</p>
        <h1 id="about-title">Connecting the future of aircraft maintenance.</h1>
        <p class="can-about-hero-summary">
          Core Aviation Network brings Aircraft Operators, CAMOs and MRO providers together in a dedicated platform built specifically for aviation maintenance.
        </p>
        <p class="can-about-hero-summary">
          Whether you're seeking maintenance support or looking to secure new business opportunities, CAN helps you connect with the right organisations, compare options with confidence and manage every opportunity from enquiry through to aircraft release.
        </p>
        <div class="can-about-actions">
          <a class="can-about-action is-primary" href="#how-we-connect">
            <span>Explore how CAN works</span>
            <i class="ri-arrow-down-line" aria-hidden="true"></i>
          </a>
          <a class="can-about-action" href="<?= Url::to(['/site/index', '#' => 'early-access']) ?>">
            <span style="text-transform: none;">Join the network</span>
            <i class="ri-arrow-right-line" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="can-about-section" aria-labelledby="about-mission-title">
    <div class="can-about-container can-about-intro">
      <div>
        <p class="can-about-kicker">Our mission</p>
        <h2 id="about-mission-title">Creating a more connected maintenance ecosystem.</h2>
      </div>
      <div class="can-about-copy">
        <p>
          Aircraft maintenance depends on collaboration, yet many organisations still rely on fragmented processes, disconnected communication and manual workflows to identify opportunities and manage projects.
        </p>
        <p>
          Core Aviation Network was created to bring greater visibility, efficiency and connectivity to the maintenance sourcing process.
        </p>
        <p>
          Our vision is to make it easier for Aircraft Operators, CAMOs and MRO providers to discover opportunities, build valuable business relationships and manage maintenance activity through a platform designed specifically for the aviation industry.
        </p>
        <p>
          As the network grows, every new participant strengthens its value, creating greater visibility, more opportunities and stronger connections across the aviation maintenance community.
        </p>
      </div>
    </div>
  </section>

  <section class="can-about-section is-tinted" id="how-we-connect" aria-labelledby="about-network-title">
    <div class="can-about-container">
      <header class="can-about-heading">
        <p class="can-about-kicker">ONE NETWORK. SHARED OPPORTUNITIES</p>
        <h2 id="about-network-title">Where maintenance demand meets proven capability.</h2>
        <p>Core Aviation Network connects organisations seeking maintenance support with providers capable of delivering it.</p>
        <p>By bringing opportunities, quotations, availability, documentation and project updates into one environment, CAN helps participants work more efficiently and make <span class="keep-together">better-informed</span> decisions.</p>
      </header>

      <div class="can-about-audiences">
        <article class="can-about-audience">
          <div class="can-about-audience-label">Operations / Continuing Airworthiness</div>
          <span class="can-about-audience-icon" aria-hidden="true"><i class="ri-plane-line"></i></span>
          <h3>Aircraft Operators &amp; CAMOs</h3>
          <p>Create detailed maintenance requests, compare capability, availability and commercial proposals, and maintain visibility from initial enquiry through to aircraft release.</p>
        </article>

        <article class="can-about-audience is-mro">
          <div class="can-about-audience-label">Maintenance / Repair / Overhaul</div>
          <span class="can-about-audience-icon" aria-hidden="true"><i class="ri-building-2-line"></i></span>
          <h3>MRO Providers</h3>
          <p>Receive relevant maintenance opportunities, showcase your capabilities, respond with pricing and availability, and manage supporting documentation throughout the project lifecycle.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="can-about-section" aria-labelledby="about-principles-title">
    <div class="can-about-container">
      <header class="can-about-heading">
        <p class="can-about-kicker">Our operating principles</p>
        <h2 id="about-principles-title">Built around the realities of aircraft maintenance.</h2>
      </header>

      <div class="can-about-principles">
        <article class="can-about-principle">
          <i class="ri-focus-3-line" aria-hidden="true"></i>
          <h3>Relevant Opportunities</h3>
          <p>Operators connect with maintenance providers capable of supporting their requirements, while MROs receive opportunities aligned with their expertise and approvals.</p>
        </article>
        <article class="can-about-principle">
          <i class="ri-git-merge-line" aria-hidden="true"></i>
          <h3>Better Decisions</h3>
          <p>Compare capability, availability, turnaround times and commercial proposals in one place, helping organisations make faster and more informed decisions.</p>
        </article>
        <article class="can-about-principle">
          <i class="ri-file-shield-2-line" aria-hidden="true"></i>
          <h3>Complete Visibility</h3>
          <p>Keep communications, documentation and project activity organised from enquiry through to return-to-service.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="can-about-section is-tinted" aria-labelledby="about-control-title">
    <div class="can-about-container">
      <div class="can-about-control">
        <div class="can-about-control-copy">
          <p class="can-about-kicker">Why CAN</p>
          <h2 id="about-control-title">Less fragmentation. More connections.<br>More opportunities.</h2>
          <p>Core Aviation Network helps aviation organisations connect, collaborate and make better maintenance decisions through a shared industry platform built specifically for maintenance sourcing and project visibility.</p>
        </div>
        <ul class="can-about-control-list">
          <li><i class="ri-check-line" aria-hidden="true"></i> Connect with relevant industry partners</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Compare capability, availability and commercial options</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Manage opportunities from enquiry to aircraft release</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Build valuable long-term business relationships</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="can-about-cta" aria-labelledby="about-cta-title">
    <div class="can-about-container can-about-cta-inner">
      <div>
        <h2 id="about-cta-title">Ready to be part of the network?</h2>
        <p>Join Aircraft Operators, CAMOs and MRO providers building a more connected future for aviation maintenance.</p>
      </div>
      <a class="can-about-action" href="<?= Url::to(['/site/index', '#' => 'early-access']) ?>">
        <span>Register for go live</span>
        <i class="ri-arrow-right-line" aria-hidden="true"></i>
      </a>
    </div>
  </section>
</div>
