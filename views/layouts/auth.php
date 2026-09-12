<?php
/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

AppAsset::register($this);

/**
 * Get flash messages before rendering page content.
 * This prevents views from consuming them before Toastr.
 */
$flashes = Yii::$app->session->getAllFlashes();

$this->registerCssFile('@web/css/can.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class]
]);

$this->registerJsFile('@web/js/can.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => View::POS_END
]);

/**
 * Toastr CSS and JS files.
 */
$this->registerCssFile(
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    [
        'depends' => [\yii\web\JqueryAsset::class],
        'position' => View::POS_END
    ]
);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag([
    'name'    => 'viewport',
    'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no'
]);

/**
 * Custom Toastr style for auth layout.
 */
$this->registerCss(<<<CSS
#toast-container {
    z-index: 2147483647 !important;
}

#toast-container > .toast {
    border-radius: 14px !important;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.22) !important;
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
    opacity: 0.6 !important;
    background-color: #ffffff !important;
}

@media (max-width: 576px) {
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
CSS);

/*
 * PREPARATION DU SUPPORT GLOBAL :
 * cette execution avant head() permet au partiel d'enregistrer ses styles. Le
 * contenu HTML reste memorise jusqu'a son insertion a la fin de la page d'auth.
 */
$supportWidget = $this->render('_support-widget');
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title ?: Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>

<body class="auth-body login-layout" data-theme="dark">
<?php $this->beginBody() ?>

    <!-- Auth layout: no navbar, no footer -->
    <?= $content ?>

    <?= $this->render('can-spinner') ?>

    <!-- Bulle Support pour les parcours d'inscription et de verification. -->
    <?= $supportWidget ?>

<?php
/**
 * Display Yii flash messages using Toastr.
 */
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

$toastrJs = <<<JS
(function(){
    function showToastrMessages() {
        if (typeof toastr === 'undefined') {
            console.error('Toastr is not loaded. Check CDN or internet connection.');
            return;
        }

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
            hideMethod: "fadeOut",
            escapeHtml: false
        };
JS;

foreach ($flashes as $type => $messages) {
    $toastrType = $toastrTypeMap[$type] ?? 'info';

    foreach ((array) $messages as $message) {
        if (is_array($message)) {
            $message = implode('<br>', $message);
        }

        $encodedMessage = Json::htmlEncode((string) $message);
        $toastrJs .= "\n        toastr.{$toastrType}({$encodedMessage});";
    }
}

$toastrJs .= <<<JS

    }

    window.addEventListener('load', function() {
        setTimeout(showToastrMessages, 250);
    });
})();
JS;

$this->registerJs($toastrJs, View::POS_END);
?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
