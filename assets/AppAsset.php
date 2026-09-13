<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
         'css/can.css',
        'css/site.css',
        // SHARED LIST SYSTEM: common visual contract for operational list pages.
        'css/list-system.css',
        // SHARED DETAIL SYSTEM: common visual contract for read-only detail pages.
        'css/detail-system.css',
        // SHARED FORM SYSTEM: common presentation for operational forms.
        'css/form-system.css',
        // 'css/demo.css',
        // 'css/demo.min.css',
        'css/spur.css',
        // 'css/spur.min.css',
    ];
    public $js = [
        'js/can.js',
        'js/spur.js',
        'js/chart-js-config.js',

        // ALERTES HARMONISEES : SweetAlert est chargé avant le module de synchronisation
        // afin que toutes les listes, y compris les demandes disponibles MRO, puissent
        // informer l'utilisateur sans dépendre d'un chargement propre à chaque vue.
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',

        // SYNCHRONISATION DES DEMANDES : ce module global reste inactif par defaut
        // et demarre uniquement lorsqu'une vue declare data-request-sync-context.
        'js/request-sync.js',

        // PRIORITÉ OPÉRATIONNELLE : ce module pilote uniquement l'affichage des
        // cartes AOG/Urgent/Routine et du délai associé. Le calcul définitif de
        // l'échéance demeure côté serveur dans le modèle Requests.
        'js/operational-priority.js',

    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset', // 🔥 indispensable pour alert close

    ];
}
