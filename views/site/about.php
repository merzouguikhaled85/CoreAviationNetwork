<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About';
// $this->params['breadcrumbs'][] = $this->title;
?>
<section class="about section-padding justify-content-center align-items-center" aria-labelledby="about-title" id="about">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-7 text-center text-lg-start">
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
        <div class="col-lg-5 text-center">
          <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network (CAN) brand"
               class="about-illustration img-fluid" loading="lazy" />
        </div>
      </div>
    </div>
  </section>

   
