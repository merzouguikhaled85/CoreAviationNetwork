<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\helpers\Json;
use yii\web\View;

BootstrapAsset::register($this);
BootstrapPluginAsset::register($this);

AppAsset::register($this);

/**
 * Toastr CSS and JS files
 * Toastr is a JavaScript notification library.
 */
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css');

$this->registerJsFile(
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

/**
 * Global CSS
 */
$this->registerCss("
    #main {
        padding: 25px;
    }

    /* Toastr custom aviation/light style */
    #toast-container {
        z-index: 2147483647 !important;
    }

    #toast-container > .toast {
        border-radius: 14px !important;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.18) !important;
        opacity: 1 !important;
        padding: 16px 18px 16px 54px !important;
        font-family: 'Nunito', 'Open Sans', Arial, sans-serif !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        background-position: 18px center !important;
    }

    #toast-container > .toast-success {
        background-color: #10b981 !important;
    }

    #toast-container > .toast-error {
        background-color: #ef4444 !important;
    }

    #toast-container > .toast-warning {
        background-color: #f59e0b !important;
    }

    #toast-container > .toast-info {
        background-color: #3b82f6 !important;
    }

    #toast-container .toast-close-button {
        font-weight: 700 !important;
        opacity: 0.85 !important;
    }

    #toast-container .toast-close-button:hover {
        opacity: 1 !important;
    }

    #toast-container .toast-progress {
        height: 4px !important;
        opacity: 0.55 !important;
        background-color: #ffffff !important;
    }

    @media (max-width: 576px) {
        #main {
            padding: 15px;
        }

        #toast-container.toast-top-right {
            top: 16px !important;
            right: 16px !important;
            left: 16px !important;
            width: auto !important;
        }

        #toast-container > .toast {
            width: 100% !important;
        }
    }
");

/*
 * PREPARATION DU SUPPORT GLOBAL :
 * le rendu anticipe du partiel enregistre son CSS avant l'appel a head(). Le HTML
 * produit est conserve puis affiche en bas de page pour ne pas perturber le layout.
 */
$supportWidget = $this->render('_support-widget');
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600|Open+Sans:400,600,700" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js"></script>

    <title><?= Html::encode($this->title) ?></title>

    <?php $this->head() ?>
</head>

<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    /*
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
    ]);

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav'],
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index']],
            ['label' => 'About', 'url' => ['/site/about']],
            ['label' => 'Contact', 'url' => ['/site/contact']],
            Yii::$app->user->isGuest
                ? ['label' => 'Login', 'url' => ['/site/login']]
                : '<li class="nav-item">'
                    . Html::beginForm(['/site/logout'])
                    . Html::submitButton(
                        'Logout (' . Yii::$app->user->identity->username . ')',
                        ['class' => 'nav-link btn btn-link logout']
                    )
                    . Html::endForm()
                    . '</li>'
        ]
    ]);

    NavBar::end();
    */
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="">

        <!-- Breadcrumbs are currently disabled -->
        <!--
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        -->

        <?= $this->render('header') ?>

        <?= $content ?>

        <?= $this->render('footer') ?>

    </div>
</main>

<?php
/*
$user_type = Yii::$app->session->get('user_type');
$isLoggedin = Yii::$app->session->get('isLoggedIn');

$this->registerJs("
    const user_type = " . json_encode($user_type) . ";
    const isLoggedin = " . json_encode($isLoggedin) . ";

    if (!user_type && user_type) {
        fetch('" . \yii\helpers\Url::to(['site/logout']) . "', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': '" . Yii::$app->request->csrfToken . "'
            }
        }).then(response => {
            if (response.ok) {
                window.location.href = '" . \yii\helpers\Url::to(['site/landing']) . "';
            }
        });
    } else {
        let idleTime = 0;
        const idleInterval = setInterval(timerIncrement, 60000);

        function timerIncrement() {
            idleTime++;
            if (idleTime >= 20) {
                fetch('" . \yii\helpers\Url::to(['site/logout']) . "', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '" . Yii::$app->request->csrfToken . "'
                    }
                }).then(response => {
                    if (response.ok) {
                        window.location.href = '" . \yii\helpers\Url::to(['site/landing']) . "';
                    }
                });
            }
        }

        document.onmousemove = document.onkeypress = function() {
            idleTime = 0;
        };
    }
");
*/
?>

<!-- CAN Global Spinner -->
<?= $this->render('can-spinner') ?>

<!-- Bulle Support globale, independante du contenu metier de la page. -->
<?= $supportWidget ?>

<?php
/**
 * Display Yii flash messages using Toastr.
 */
$flashes = Yii::$app->session->getAllFlashes();

$toastrJs = <<<JS
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: "7000",
    extendedTimeOut: "1000",
    preventDuplicates: true,
    newestOnTop: true,
    showDuration: "300",
    hideDuration: "500",
    showMethod: "fadeIn",
    hideMethod: "fadeOut"
};
JS;

foreach ($flashes as $type => $messages) {
    // Convert Yii/Bootstrap flash type to Toastr type
   $toastrTypeMap = [
    'success'       => 'success',
    'message'       => 'success',
    'error'         => 'error',
    'danger'        => 'error',
    'usernameError' => 'error',
    'passwordError' => 'error',
    'warning'       => 'warning',
    'info'          => 'info',
];

$type = $toastrTypeMap[$type] ?? 'info';

    foreach ((array) $messages as $message) {
        $encodedMessage = Json::htmlEncode($message);
        $toastrJs .= "toastr.{$type}({$encodedMessage});";
    }
}

$this->registerJs($toastrJs, View::POS_READY);
?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
