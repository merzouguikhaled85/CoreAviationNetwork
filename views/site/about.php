<?php

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->title = 'About | Core Aviation Network';
$this->params['meta_description'] = 'Learn how Core Aviation Network connects Aircraft Operators, CAMOs and MRO providers through one controlled maintenance workflow.';
$this->registerCssFile(
    '@web/css/about-page.css?v=20260927-1',
    ['depends' => [\yii\bootstrap5\BootstrapAsset::class]]
);
?>

<div class="can-about-page">
  <section class="can-about-hero" aria-labelledby="about-title">
    <div class="can-about-container">
      <div class="can-about-hero-copy">
        <p class="can-about-eyebrow">About Core Aviation Network</p>
        <h1 id="about-title">Aircraft maintenance, connected with control.</h1>
        <p class="can-about-hero-summary">
          Core Aviation Network brings Aircraft Operators, CAMO teams and MRO providers into one structured environment where maintenance needs, responses, decisions and records remain connected.
        </p>
        <div class="can-about-actions">
          <a class="can-about-action is-primary" href="#how-we-connect">
            <span>How we connect the network</span>
            <i class="ri-arrow-down-line" aria-hidden="true"></i>
          </a>
          <a class="can-about-action" href="<?= Url::to(['/site/index', '#' => 'early-access']) ?>">
            <span>Register for go live</span>
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
        <h2 id="about-mission-title">Built around the maintenance request.</h2>
      </div>
      <div class="can-about-copy">
        <p>
          Aircraft maintenance depends on precise information, relevant capability and timely coordination. Core Aviation Network is designed to keep those elements attached to the same operational request from the first requirement through completion.
        </p>
        <p>
          Instead of separating conversations, quotations and technical records across disconnected channels, CAN provides a common workflow where each participant can work with clearer context and greater continuity.
        </p>
      </div>
    </div>
  </section>

  <section class="can-about-section is-tinted" id="how-we-connect" aria-labelledby="about-network-title">
    <div class="can-about-container">
      <header class="can-about-heading">
        <p class="can-about-kicker">Two sides of one workflow</p>
        <h2 id="about-network-title">Connecting operational demand with maintenance capability.</h2>
        <p>CAN gives each side the tools and context required to move a maintenance request forward without losing the decisions and documents that support it.</p>
      </header>

      <div class="can-about-audiences">
        <article class="can-about-audience">
          <div class="can-about-audience-label">Operations / Continuing Airworthiness</div>
          <span class="can-about-audience-icon" aria-hidden="true"><i class="ri-plane-line"></i></span>
          <h3>Aircraft Operators &amp; CAMOs</h3>
          <p>Define the aircraft, location, priority, schedule and scope; share controlled attachments; review relevant responses; and retain visibility over the request through completion.</p>
        </article>

        <article class="can-about-audience is-mro">
          <div class="can-about-audience-label">Maintenance / Repair / Overhaul</div>
          <span class="can-about-audience-icon" aria-hidden="true"><i class="ri-building-2-line"></i></span>
          <h3>MRO Providers</h3>
          <p>Receive opportunities aligned with available capabilities, prepare structured quotations, coordinate the work and return the reports and release records connected to the original request.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="can-about-section" aria-labelledby="about-principles-title">
    <div class="can-about-container">
      <header class="can-about-heading">
        <p class="can-about-kicker">Our operating principles</p>
        <h2 id="about-principles-title">A network designed for real maintenance work.</h2>
      </header>

      <div class="can-about-principles">
        <article class="can-about-principle">
          <i class="ri-focus-3-line" aria-hidden="true"></i>
          <h3>Relevant connections</h3>
          <p>Maintenance requirements are presented with the operational context needed to identify appropriate capabilities and prepare a meaningful response.</p>
        </article>
        <article class="can-about-principle">
          <i class="ri-git-merge-line" aria-hidden="true"></i>
          <h3>Controlled workflow</h3>
          <p>Requests, quotations, purchase decisions, messages and updates remain organised around the same maintenance journey.</p>
        </article>
        <article class="can-about-principle">
          <i class="ri-file-shield-2-line" aria-hidden="true"></i>
          <h3>Traceable records</h3>
          <p>Supporting documents, reports, CRS records and feedback stay connected so the history remains easier to review and understand.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="can-about-section is-tinted" aria-labelledby="about-control-title">
    <div class="can-about-container">
      <div class="can-about-control">
        <div class="can-about-control-copy">
          <p class="can-about-kicker">Why CAN</p>
          <h2 id="about-control-title">Less fragmentation. More operational continuity.</h2>
          <p>Core Aviation Network is not intended to replace the expertise of operators, airworthiness teams or maintenance organisations. It gives that expertise a clearer shared environment in which to coordinate.</p>
        </div>
        <ul class="can-about-control-list">
          <li><i class="ri-check-line" aria-hidden="true"></i> One request as the operational reference</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Clearer commercial and technical context</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Documents retained with their decisions</li>
          <li><i class="ri-check-line" aria-hidden="true"></i> Progress visible to the relevant participants</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="can-about-cta" aria-labelledby="about-cta-title">
    <div class="can-about-container can-about-cta-inner">
      <div>
        <h2 id="about-cta-title">Join the maintenance network.</h2>
        <p>Be among the aviation professionals preparing to use Core Aviation Network.</p>
      </div>
      <a class="can-about-action" href="<?= Url::to(['/site/index', '#' => 'early-access']) ?>">
        <span>Register for go live</span>
        <i class="ri-arrow-right-line" aria-hidden="true"></i>
      </a>
    </div>
  </section>
</div>
