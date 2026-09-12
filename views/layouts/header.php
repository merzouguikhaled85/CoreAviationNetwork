<?php

use app\models\User;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use app\components\UrlIdHelper;
use app\models\SupportTicket;


// --- Calcul des infos utilisateur connecté ---
$identity = Yii::$app->user->identity;

/*
 * SESSION ABSENTE OU EXPIRÉE : le layout principal peut aussi être rendu pour un
 * visiteur non authentifié ou juste après l'expiration de sa session. Dans ce cas,
 * Yii retourne une identité nulle et il ne faut jamais appeler getId() dessus.
 * L'identifiant signé reste volontairement nul ; tous les liens privés qui
 * l'utilisent sont déjà protégés plus bas par la condition !isGuest.
 */
$encodedId = $identity !== null
    ? UrlIdHelper::encode($identity->getId())
    : null;
$userType  = Yii::$app->session->get('user_type');
$fullName  = '';
$initials  = '?';
$roleLabel = $userType ?? '';

if ($identity) {
    if (!empty($identity->first_name) || !empty($identity->last_name)) {
        $fullName = trim(($identity->first_name ?? '') . ' ' . ($identity->last_name ?? ''));
    } elseif (!empty($identity->name)) {
        $fullName = $identity->name;
    } elseif (!empty($identity->username)) {
        $fullName = $identity->username;
    } elseif (!empty($identity->email)) {
        $fullName = $identity->email;
    } else {
        $fullName = 'Utilisateur';
    }

    $parts    = array_filter(explode(' ', trim($fullName)));
    $initials = mb_strtoupper(mb_substr($parts[0] ?? 'U', 0, 1));
    if (count($parts) >= 2) {
        $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
    }
}

// Pas de troncature — on affiche le nom complet, le tooltip devient inutile
$displayName = $fullName;

/*
 * CONTEXTE DE NAVIGATION ADMIN : la route courante pilote l'etat actif sans JavaScript.
 * Le compteur Support utilise une seule requete agregee et ne touche ni aux requests de
 * maintenance ni aux notifications AO/MRO.
 */
$currentRoute = Yii::$app->controller ? Yii::$app->controller->route : '';
$routeMatches = static function (array $prefixes) use ($currentRoute): bool {
    foreach ($prefixes as $prefix) {
        if ($currentRoute === $prefix || strpos($currentRoute, $prefix . '/') === 0) {
            return true;
        }
    }
    return false;
};
$supportAttentionCount = 0;
if (!Yii::$app->user->isGuest && $userType === 'admin') {
    $supportAttentionCount = (int) SupportTicket::find()
        ->where(['status' => 'new'])
        ->orWhere([
            'and',
            ['priority' => 'urgent'],
            ['not in', 'status', ['resolved', 'closed']],
        ])
        ->count();
}
?>
<style>
    .field-error .help-block { color: red !important; }
    .alert { position: relative; z-index: -1; }
    .background-form { position: relative; z-index: -1; }

    .progress-bar-container {
        z-index: -1;
        background-color: #c4cfe1;
        border-radius: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        overflow-x: auto;
        white-space: nowrap;
        position: relative;
        padding: 10px;
    }

    .progress-bar-step {
        flex: 1;
        text-align: center;
        position: relative;
        color: black;
        margin-left: 20px;
        padding: 0 10px;
        font-weight: 600;
        z-index: 1;
    }

    .progress-bar-step.step-active   { color: #007bff; }
    .progress-bar-step.step-realized { color: #28a745; }

    .progress-bar-step:before {
        content: '';
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: calc(100% + 10px);
        width: 0; height: 0;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        border-left: 10px solid black;
        z-index: -1;
    }

    .progress-bar-step:last-child:before          { display: none; }
    .progress-bar-step.step-active:before         { border-left-color: #007bff; }
    .progress-bar-step.step-realized:before       { border-left-color: #28a745; }

    /* =========================================
       DROPDOWN MENU (global)
       ========================================= */
    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 8px 30px rgba(0,0,0,0.14);
        padding: 0;
        overflow: hidden;
    }

    .dropdown-menu-right { min-width: 230px; }

    /* ---- Header profil dans le dropdown ---- */
    .dropdown-user-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px 12px;
        border-bottom: 1px solid #f1f1f1;
        background: #fafafa;
    }

    .dropdown-avatar-lg {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f8ef7, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dropdown-user-name {
        font-size: 14px;
        font-weight: 600;
        color: #1a1a2e;
        text-transform: uppercase;
        margin: 0 0 3px;
        line-height: 1.2;
    }

    .dropdown-user-role {
        font-size: 11px;
        color: #888;
        margin: 0;
        text-transform: capitalize;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .user-role-badge {
        display: inline-block;
        background: #e8f0fe;
        color: #1a73e8;
        font-size: 10px;
        border-radius: 4px;
        padding: 1px 6px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    /* ---- Actions dropdown ---- */
    .dropdown-menu .btn-link {
        width: 100%;
        text-align: left;
        padding: 11px 16px;
        font-weight: 500;
        font-size: 13px;
        color: #333 !important;
        text-decoration: none;
        border-radius: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background-color 0.15s ease;
    }

    .dropdown-menu .btn-link:hover { background-color: #f8f9fa; }

    .dropdown-menu .logout-button {
        color: #e63946 !important;
        border-top: 1px solid #f1f1f1;
    }

    .dropdown-menu .logout-button:hover { background-color: #fff0f1; }

    /* =========================================
       TOOLBAR — zone droite
       ========================================= */
    .toolbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }

    /* =========================================
       NOTIFICATION BELL + BADGE
       ========================================= */

    /* Keep the notification badge visible even when it goes outside the bell circle */
    .dash-toolbar,
    .toolbar-right {
        overflow: visible !important;
    }

    .toolbar-right {
        position: relative;
        z-index: 1050;
    }

    /* Notification button */
    #notification-link.notif-bell-wrap {
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;

        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;

        border-radius: 50% !important;
        background: #ffffff !important;
        border: 1px solid #edf0f5 !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.10) !important;

        cursor: pointer !important;
        text-decoration: none !important;
        flex-shrink: 0 !important;
        overflow: visible !important;
        z-index: 1051 !important;
    }

    #notification-link.notif-bell-wrap:hover {
        background: #ffffff !important;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.14) !important;
        text-decoration: none !important;
    }

    /* Bell icon */
    #notification-link.notif-bell-wrap .fa-bell {
        font-size: 16px !important;
        color: #3f430c !important;
        pointer-events: none !important;
    }

    /* Red notification badge with white outline */
    #notification-count.notif-badge {
        position: absolute !important;
        top: -8px !important;
        right: -8px !important;

        display: none;
        align-items: center !important;
        justify-content: center !important;

        min-width: 22px !important;
        height: 22px !important;
        padding: 0 6px !important;

        background: #ef4444 !important;
        color: #ffffff !important;

        border: 3px solid #ffffff !important;
        border-radius: 999px !important;

        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.22) !important;

        font-size: 10px !important;
        font-weight: 800 !important;
        font-style: normal !important;
        line-height: 16px !important;
        text-align: center !important;

        z-index: 99999 !important;
        pointer-events: none !important;
        box-sizing: border-box !important;
    }


    /* ---- User trigger ---- */
    .user-trigger {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.10);
        border-radius: 8px;
        padding: 5px 10px 5px 6px;
        cursor: pointer;
        border: 1px solid rgba(255,255,255,0.18);
        transition: background 0.2s;
        text-decoration: none !important;
        color: #fff !important;
        white-space: nowrap;
    }

    .user-trigger:hover { background: rgba(255,255,255,0.20); }

    /* Rotation chevron quand dropdown ouvert */
    .dropdown.open .user-chevron,
    .user-trigger[aria-expanded="true"] .user-chevron {
        transform: rotate(180deg);
    }

    .user-avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f8ef7, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Nom affiché — blanc, lisible */
    .user-display-name {
        font-size: 13px;
        font-weight: 800;
        color: #052138ff;
        white-space: nowrap;
        max-width: 160px;
        overflow: hidden;
        text-transform: uppercase;
        text-overflow: ellipsis;
    }

    .user-chevron {
        font-size: 12px;
        color: rgba(243, 27, 11, 0.65);
        margin-left: 2px;
        font-weight: 700;
        transition: transform 0.2s;
        flex-shrink: 0;
    }

    /* =========================================
       NOTIFICATIONS DROPDOWN
       ========================================= */
    #notification-dropdown {
        width: 400px;
        max-width: 90vw;
    }

    .notification-dropdown-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
        padding: 12px 15px;
    }

    .notification-dropdown-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        text-align: center;
    }

    #notification-list {
        max-height: 350px;
        overflow-y: auto;
        padding: 0;
    }

    .notification-item {
        padding: 15px;
        border-bottom: 1px solid #f1f1f1;
        transition: background-color 0.2s;
        white-space: normal;
        cursor: default;
    }

    .notification-item:last-child { border-bottom: none; }
    .notification-item:hover      { background-color: #fbfbfb; }

    .unread-notification {
        background-color: #ffffff;
        border-left: 4px solid #1a73e8;
    }

    .read-notification {
        background-color: #fcfcfc;
        border-left: 4px solid transparent;
        opacity: 0.8;
    }

    .notification-content {
        margin-bottom: 12px;
        font-size: 0.9rem;
        color: #495057;
        line-height: 1.4;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .notification-message-icon {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #e8f0fe;
        color: #1a73e8;
        font-size: 0.78rem;
        margin-top: 1px;
    }

    .read-notification .notification-message-icon {
        background-color: #f1f3f4;
        color: #6c757d;
    }

    .notification-text { flex: 1; min-width: 0; }

    /*
       HORODATAGE : la date de creation reste secondaire par rapport au message, tout
       en demeurant lisible pour retracer rapidement la chronologie des evenements.
    */
    .notification-created-at {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
        color: #7b8798;
        font-size: .7rem;
        line-height: 1.25;
    }

    .notification-created-at i {
        color: #5d8fc9;
        font-size: .66rem;
    }

    .btn-notif i,
    .notification-footer-actions button i,
    .notification-dropdown-header h3 i { margin-right: 6px; }

    .notification-actions { display: flex; gap: 8px; }

    .btn-notif {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease;
        display: inline-block;
        text-align: center;
    }

    .btn-notif-view   { background-color: #e6f4ea; color: #1e8e3e; }
    .btn-notif-view:hover { background-color: #ceead6; color: #1e8e3e; text-decoration: none; }
    .btn-notif-read   { background-color: #e8f0fe; color: #1a73e8; }
    .btn-notif-read:hover { background-color: #d2e3fc; }
    .btn-notif-delete { background-color: #fce8e6; color: #d93025; }
    .btn-notif-delete:hover { background-color: #fad2cf; }

    .notification-footer-actions {
        padding: 10px 15px;
        background-color: #fff;
        border-top: 1px solid #eaeaea;
        display: flex;
        justify-content: space-between;
    }

    /* =========================================
       SIDEBAR
       ========================================= */
    /*
       LARGEUR COMMUNE : le thème définissait uniquement min-width: 238px. Un libellé
       long, comme Notification Preferences, pouvait alors élargir la sidebar AO/MRO
       contrairement à celle de l'admin. Les trois rôles utilisent désormais une largeur
       fixe identique, cohérente avec le décalage de 238 px déjà appliqué au contenu.
    */
    .dash-nav {
        width: 238px;
        min-width: 238px;
        max-width: 238px;
    }

    /* Sur tablette/mobile, le panneau reprend toute la largeur prévue par le thème. */
    @media (max-width: 991.98px) {
        .dash-nav {
            width: 100%;
            min-width: 0;
            max-width: none;
        }
    }

    /*
       EN-TETE DE SIDEBAR : sa hauteur correspond exactement aux 84 px de la toolbar.
       Le fichier du logo utilise un canevas carre avec beaucoup d'espace vertical ;
       le conteneur masque uniquement cet espace inutile sans deformer le logo visible.
       La suppression de l'ancienne marge negative retire aussi la cassure verticale.
    */
    .dash-nav > header {
        position: relative;
        width: 100%;
        height: 84px;
        min-height: 84px;
        margin: 0;
        padding: 0;
        overflow: hidden;
        justify-content: center;
        border-bottom: 1px solid rgba(255, 255, 255, .045);
    }

    .dash-nav > header .spur-logo {
        width: 100%;
        height: 84px;
        margin: 0;
        padding: 0;
        justify-content: center;
    }

    .sidebar-logo-image {
        display: block;
        width: 130px;
        height: 130px;
        object-fit: contain;
        flex: 0 0 130px;
    }

    /* Sur mobile, le bouton reste accessible au-dessus du logo centre. */
    .dash-nav > header .menu-toggle {
        position: absolute;
        left: 16px;
        z-index: 2;
    }

    .dash-nav-list {
        display: flex;
        flex-direction: column;
        height: 85vh;
    }

    /*
       MENU HIERARCHISE PAR ROLE : Admin, AO/CAMO et MRO partagent la meme presentation,
       leur propre defilement et un bouton Logout maintenu en bas de la navigation.
    */
    .role-sidebar-menu {
        height: calc(100vh - 84px);
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 4px 8px 0;
        scrollbar-width: thin;
        scrollbar-color: rgba(83, 196, 239, .45) transparent;
    }

    .role-sidebar-menu::-webkit-scrollbar { width: 5px; }
    .role-sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(83, 196, 239, .45);
        border-radius: 999px;
    }

    .sidebar-section-label {
        padding: 16px 14px 7px;
        color: rgba(179, 198, 222, .58);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .role-sidebar-menu .dash-nav-item {
        min-height: 43px;
        margin: 2px 0;
        border-left: 3px solid transparent;
        border-radius: 9px;
        padding-left: 13px;
    }

    /*
       LIBELLES LONGS : le texte reste sur une ligne et ne pousse jamais la largeur du
       menu. Le title présent sur les liens importants conserve l'information complète.
    */
    .role-sidebar-menu .dash-nav-item > span {
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .role-sidebar-menu .dash-nav-item i {
        width: 21px;
        margin-left: 0;
        margin-right: 9px;
        text-align: center;
    }

    /*
       ONGLETS DE NOTIFICATIONS : les trois filtres partagent la largeur disponible et
       affichent un compteur compact. L'etat actif utilise le bleu fonctionnel du portail
       sans augmenter les dimensions du panneau.
    */
    .notification-filter-tabs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        padding: 9px 12px;
        background: #fff;
        border-bottom: 1px solid #e7edf5;
    }

    .notification-filter-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-height: 32px;
        padding: 5px 8px;
        border: 1px solid #d9e3f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #607089;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        transition: border-color .18s ease, background-color .18s ease, color .18s ease;
    }

    .notification-filter-tab:hover {
        border-color: #9fc4f7;
        color: #135fbc;
    }

    .notification-filter-tab.active {
        border-color: #2878e7;
        background: #eaf3ff;
        color: #125db9;
        box-shadow: inset 0 0 0 1px rgba(40, 120, 231, .08);
    }

    .notification-filter-count {
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e5ebf3;
        color: #41516a;
        font-size: .68rem;
        line-height: 1;
    }

    .notification-filter-tab.active .notification-filter-count {
        background: #2878e7;
        color: #fff;
    }

    .role-sidebar-menu .dash-nav-item.active,
    .role-sidebar-menu .dash-nav-dropdown.show > .dash-nav-item {
        color: #fff;
        border-left-color: #25bdf0;
        background: linear-gradient(90deg, rgba(23, 139, 207, .28), rgba(23, 139, 207, .08));
    }

    .role-sidebar-menu .dash-nav-dropdown-menu { display: none; }
    .role-sidebar-menu .dash-nav-dropdown.show > .dash-nav-dropdown-menu { display: block; }

    .role-sidebar-menu .dash-nav-dropdown-item {
        position: relative;
        margin: 1px 0 1px 31px;
        padding: 8px 12px 8px 18px;
        border-radius: 8px;
        color: rgba(227, 236, 247, .74);
        font-size: 13px;
    }

    .role-sidebar-menu .dash-nav-dropdown-item::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 50%;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: rgba(124, 185, 220, .65);
        transform: translateY(-50%);
    }

    .role-sidebar-menu .dash-nav-dropdown-item.active {
        color: #fff;
        background: rgba(25, 149, 211, .19);
    }

    .role-sidebar-menu .dash-nav-dropdown-item.active::before { background: #2bc4f3; }

    .admin-support-badge {
        min-width: 21px;
        height: 21px;
        margin-left: auto;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .14);
    }

    .role-sidebar-menu .logout-container {
        position: sticky;
        bottom: 0;
        z-index: 3;
        margin-top: auto;
        padding: 12px 2px 8px;
        background: linear-gradient(180deg, rgba(24,31,44,0), #181f2c 24%);
    }

    /* Logout sidebar */
    .logout-container {
        margin-top: auto;
        padding: 12px 10px;
        /* border: 1px solid rgba(255,255,255,0.10); */
        /* background: #181f2c; */
    }

    .logout-container .logout-button {
        display: flex !important;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        color: rgba(245, 241, 241, 0.75) !important;
        background: #181f2c;
        border: 1px solid rgba(255,255,255,0.10) ;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
        text-align: center !important;
    }

    .logout-container .logout-button:hover {
        background: rgba(247, 8, 28, 1) !important;
        color: #f3eeeeff !important;
        border-color: rgba(8, 8, 8, 0.35) !important;
    }

    .dash-nav-dropdown-menu { display: block; }




    
</style>

<div class="dash">
    <div class="dash-nav dash-nav-dark">
        <header>
            <a href="#!" class="menu-toggle">
                <i class="fas fa-bars"></i>
            </a>
            <a href="<?= Url::to(['dashboard/home']) ?>" class="spur-logo">
                <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network" class="sidebar-logo-image">
            </a>
        </header>

        <nav class="dash-nav-list <?= in_array($userType, ['admin', 'ao', 'mro'], true) ? 'role-sidebar-menu' : '' ?>">

            <?php if (!Yii::$app->user->isGuest && $userType === 'admin'): ?>

                <!-- ACCES PRINCIPAL : Dashboard reste disponible sans ouvrir un groupe. -->
                <a href="<?= Url::to(['dashboard/home']) ?>" class="dash-nav-item <?= $routeMatches(['dashboard']) ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>

                <!-- OPERATIONS : regroupe les flux a traiter quotidiennement par l'admin. -->
                <div class="sidebar-section-label">Operations</div>
                <a href="<?= Url::to(['awaiting-request-response/index']) ?>" class="dash-nav-item <?= $routeMatches(['awaiting-request-response']) ? 'active' : '' ?>" title="Requests">
                    <i class="fas fa-list-alt"></i><span>Requests</span>
                </a>
                <a href="<?= Url::to(['admin-disputes/index']) ?>" class="dash-nav-item <?= $routeMatches(['admin-disputes']) ? 'active' : '' ?>" title="Disputes">
                    <i class="fas fa-balance-scale"></i><span>Disputes</span>
                </a>
                <a href="<?= Url::to(['admin-support-tickets/index']) ?>" class="dash-nav-item <?= $routeMatches(['admin-support-tickets']) ? 'active' : '' ?>" title="Support Tickets">
                    <i class="fas fa-headset"></i><span>Support Tickets</span>
                    <?php if ($supportAttentionCount > 0): ?><span class="admin-support-badge" title="<?= $supportAttentionCount ?> ticket(s) requiring attention"><?= $supportAttentionCount > 99 ? '99+' : $supportAttentionCount ?></span><?php endif; ?>
                </a>

                <!--
                    DONNEES AVIATION : un seul groupe remplace les anciens doublons
                    Aircraft/Certificates et conserve exactement les routes existantes.
                -->
                <?php $aviationOpen = $routeMatches(['airports', 'aircrafts', 'aircraft-model', 'certificates', 'certificate-types']); ?>
                <div class="sidebar-section-label">Reference data</div>
                <div class="dash-nav-dropdown <?= $aviationOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $aviationOpen ? 'active' : '' ?>" aria-expanded="<?= $aviationOpen ? 'true' : 'false' ?>" title="Aviation Data">
                        <i class="fas fa-plane"></i><span>Aviation Data</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['airports/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['airports']) ? 'active' : '' ?>">Airports</a>
                        <a href="<?= Url::to(['aircrafts/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['aircrafts']) ? 'active' : '' ?>">Aircraft</a>
                        <a href="<?= Url::to(['aircraft-model/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['aircraft-model']) ? 'active' : '' ?>">Aircraft Models</a>
                        <a href="<?= Url::to(['certificates/models-without-certificates']) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'certificates/models-without-certificates' ? 'active' : '' ?>">Certification Gaps</a>
                        <a href="<?= Url::to(['certificates/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['certificates']) && $currentRoute !== 'certificates/models-without-certificates' ? 'active' : '' ?>">Certificates</a>
                        <a href="<?= Url::to(['certificate-types/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['certificate-types']) ? 'active' : '' ?>">Certificate Types</a>
                    </div>
                </div>

                <!-- ORGANISATIONS : clarifie AO en Aircraft Operators sans changer la route. -->
                <?php $organizationsOpen = $routeMatches(['mro-profile', 'ao-profile']); ?>
                <div class="sidebar-section-label">Organizations</div>
                <div class="dash-nav-dropdown <?= $organizationsOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $organizationsOpen ? 'active' : '' ?>" aria-expanded="<?= $organizationsOpen ? 'true' : 'false' ?>" title="Organizations">
                        <i class="fas fa-building"></i><span>Organizations</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['mro-profile/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-profile']) ? 'active' : '' ?>">MROs</a>
                        <a href="<?= Url::to(['ao-profile/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['ao-profile']) ? 'active' : '' ?>">Aircraft Operators</a>
                    </div>
                </div>

                <!-- PLATFORME : rassemble les referentiels generaux et la publicite. -->
                <?php $platformOpen = $routeMatches(['country', 'city', 'currency', 'advert']); ?>
                <div class="sidebar-section-label">Platform</div>
                <div class="dash-nav-dropdown <?= $platformOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $platformOpen ? 'active' : '' ?>" aria-expanded="<?= $platformOpen ? 'true' : 'false' ?>" title="Platform Settings">
                        <i class="fas fa-cog"></i><span>Platform Settings</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['country/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['country']) ? 'active' : '' ?>">Countries</a>
                        <a href="<?= Url::to(['city/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['city']) ? 'active' : '' ?>">Cities</a>
                        <a href="<?= Url::to(['currency/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['currency']) ? 'active' : '' ?>">Currencies</a>
                        <a href="<?= Url::to(['advert/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['advert']) ? 'active' : '' ?>">Advertising</a>
                    </div>
                </div>

            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest && $userType === 'ao'): ?>

                <!-- ACCES PRINCIPAL AO/CAMO : lien direct et etat actif calcule par Yii. -->
                <a href="<?= Url::to(['dashboard/home']) ?>" class="dash-nav-item <?= $routeMatches(['dashboard']) ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>

                <!--
                    OPERATIONS AO/CAMO : les trois listes de requests restent dans un
                    accordéon, tandis que les rendez-vous et litiges restent accessibles
                    en un clic. Les routes existantes sont strictement conservees.
                -->
                <?php $aoRequestsOpen = $routeMatches(['requests']); ?>
                <div class="sidebar-section-label">Operations</div>
                <div class="dash-nav-dropdown <?= $aoRequestsOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $aoRequestsOpen ? 'active' : '' ?>" aria-expanded="<?= $aoRequestsOpen ? 'true' : 'false' ?>" title="Requests">
                        <i class="fas fa-clipboard-list"></i><span>Requests</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['requests/new-requests']) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'requests/new-requests' ? 'active' : '' ?>">New Requests</a>
                        <a href="<?= Url::to(['requests/open-requests']) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'requests/open-requests' ? 'active' : '' ?>">Open Requests</a>
                        <a href="<?= Url::to(['requests/closed-requests']) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'requests/closed-requests' ? 'active' : '' ?>">Closed Requests</a>
                    </div>
                </div>
                <a href="<?= Url::to(['ao-appointments/index']) ?>" class="dash-nav-item <?= $routeMatches(['ao-appointments']) ? 'active' : '' ?>" title="My Appointments">
                    <i class="fas fa-calendar-check"></i><span>My Appointments</span>
                </a>
                <a href="<?= Url::to(['ao-mro-dispute/index']) ?>" class="dash-nav-item <?= $routeMatches(['ao-mro-dispute']) ? 'active' : '' ?>" title="Disputes">
                    <i class="fas fa-balance-scale"></i><span>Disputes</span>
                </a>

                <!-- PARC AO/CAMO : l'avion est une donnee metier frequente et merite un acces direct. -->
                <div class="sidebar-section-label">Fleet</div>
                <a href="<?= Url::to(['ao-aircrafts/index']) ?>" class="dash-nav-item <?= $routeMatches(['ao-aircrafts']) ? 'active' : '' ?>" title="Aircraft">
                    <i class="fas fa-plane"></i><span>Aircraft</span>
                </a>

                <!-- COMMUNICATION : messages et preferences de notification sont separes du profil. -->
                <div class="sidebar-section-label">Communication</div>
                <a href="<?= Url::to(['conversations/index']) ?>" class="dash-nav-item <?= $routeMatches(['conversations']) ? 'active' : '' ?>" title="Messages">
                    <i class="fas fa-comments"></i><span>Messages</span>
                </a>
                <a href="<?= Url::to(['ao-notifications-preferences/index']) ?>" class="dash-nav-item <?= $routeMatches(['ao-notifications-preferences']) ? 'active' : '' ?>" title="Notification Preferences">
                    <i class="fas fa-bell"></i><span>Notification Preferences</span>
                </a>

                <!-- COMPTE AO/CAMO : seules les operations personnelles sont repliees ensemble. -->
                <?php $aoAccountOpen = $routeMatches(['ao-profile']); ?>
                <div class="sidebar-section-label">Account</div>
                <div class="dash-nav-dropdown <?= $aoAccountOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $aoAccountOpen ? 'active' : '' ?>" aria-expanded="<?= $aoAccountOpen ? 'true' : 'false' ?>" title="My Account">
                        <i class="fas fa-user-circle"></i><span>My Account</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['ao-profile/update', 'id' => $encodedId]) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'ao-profile/update' ? 'active' : '' ?>">Update Profile</a>
                        <a href="<?= Url::to(['ao-profile/reset-password', 'id' => $encodedId]) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'ao-profile/reset-password' ? 'active' : '' ?>">Update Password</a>
                    </div>
                </div>

            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest && $userType === 'mro'): ?>

                <!-- ACCES PRINCIPAL MRO : Dashboard conserve son acces direct. -->
                <a href="<?= Url::to(['dashboard/home']) ?>" class="dash-nav-item <?= $routeMatches(['dashboard']) ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>

                <!--
                    OPERATIONS MRO : les listes de demandes et candidatures restent
                    regroupees sans modifier leur signification ni leurs routes métier.
                -->
                <?php $mroRequestsOpen = $routeMatches(['mro-requests', 'mro-applications']); ?>
                <div class="sidebar-section-label">Operations</div>
                <div class="dash-nav-dropdown <?= $mroRequestsOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $mroRequestsOpen ? 'active' : '' ?>" aria-expanded="<?= $mroRequestsOpen ? 'true' : 'false' ?>" title="Requests">
                        <!--
                            ICÔNE REQUESTS MRO : clipboard-list appartient au même jeu
                            Font Awesome déjà chargé pour le menu AO. Elle remplace
                            fa-tools, absent de la version actuelle et donc invisible.
                        -->
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i><span>Requests</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['mro-requests/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-requests']) ? 'active' : '' ?>">New Requests</a>
                        <a href="<?= Url::to(['mro-applications/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-applications']) && $currentRoute !== 'mro-applications/closed-requests' ? 'active' : '' ?>">Open Requests</a>
                        <a href="<?= Url::to(['mro-applications/closed-requests']) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'mro-applications/closed-requests' ? 'active' : '' ?>">Closed Requests</a>
                    </div>
                </div>
                <a href="<?= Url::to(['mro-appointments/index']) ?>" class="dash-nav-item <?= $routeMatches(['mro-appointments']) ? 'active' : '' ?>" title="My Appointments">
                    <i class="fas fa-calendar-check"></i><span>My Appointments</span>
                </a>
                <a href="<?= Url::to(['ao-mro-dispute/index']) ?>" class="dash-nav-item <?= $routeMatches(['ao-mro-dispute']) ? 'active' : '' ?>" title="Disputes">
                    <i class="fas fa-balance-scale"></i><span>Disputes</span>
                </a>

                <!-- CONFORMITE MRO : tous les documents et perimetres d'agrement sont reunis. -->
                <?php $mroComplianceOpen = $routeMatches(['mro-airports', 'mro-certificates', 'mro-aircraft-certificates', 'mro-insurance-documents']); ?>
                <div class="sidebar-section-label">Compliance</div>
                <div class="dash-nav-dropdown <?= $mroComplianceOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $mroComplianceOpen ? 'active' : '' ?>" aria-expanded="<?= $mroComplianceOpen ? 'true' : 'false' ?>" title="Compliance">
                        <i class="fas fa-shield-alt"></i><span>Compliance</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['mro-airports/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-airports']) ? 'active' : '' ?>">Airports</a>
                        <a href="<?= Url::to(['mro-certificates/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-certificates']) ? 'active' : '' ?>">NAA Approvals</a>
                        <a href="<?= Url::to(['mro-aircraft-certificates/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-aircraft-certificates']) ? 'active' : '' ?>">Aircraft Certificates</a>
                        <a href="<?= Url::to(['mro-insurance-documents/index']) ?>" class="dash-nav-dropdown-item <?= $routeMatches(['mro-insurance-documents']) ? 'active' : '' ?>">Insurance</a>
                    </div>
                </div>

                <!-- COMMUNICATION MRO : acces directs pour limiter le nombre de clics. -->
                <div class="sidebar-section-label">Communication</div>
                <a href="<?= Url::to(['conversations/index']) ?>" class="dash-nav-item <?= $routeMatches(['conversations']) ? 'active' : '' ?>" title="Messages">
                    <i class="fas fa-comments"></i><span>Messages</span>
                </a>
                <a href="<?= Url::to(['mro-notifications-preferences/index']) ?>" class="dash-nav-item <?= $routeMatches(['mro-notifications-preferences']) ? 'active' : '' ?>" title="Notification Preferences">
                    <i class="fas fa-bell"></i><span>Notification Preferences</span>
                </a>

                <!-- COMPTE MRO : edition du profil et mot de passe dans un groupe dedie. -->
                <?php $mroAccountOpen = $routeMatches(['mro-profile']); ?>
                <div class="sidebar-section-label">Account</div>
                <div class="dash-nav-dropdown <?= $mroAccountOpen ? 'show' : '' ?>">
                    <a href="#!" class="dash-nav-item dash-nav-dropdown-toggle <?= $mroAccountOpen ? 'active' : '' ?>" aria-expanded="<?= $mroAccountOpen ? 'true' : 'false' ?>" title="My Account">
                        <i class="fas fa-user-circle"></i><span>My Account</span>
                    </a>
                    <div class="dash-nav-dropdown-menu">
                        <a href="<?= Url::to(['mro-profile/update', 'id' => $encodedId]) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'mro-profile/update' ? 'active' : '' ?>">Update Profile</a>
                        <a href="<?= Url::to(['mro-profile/reset-password', 'id' => $encodedId]) ?>" class="dash-nav-dropdown-item <?= $currentRoute === 'mro-profile/reset-password' ? 'active' : '' ?>">Update Password</a>
                    </div>
                </div>

            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest): ?>
                <div class="logout-container">
                    <?= Html::beginForm(['site/logout'], 'post', ['class' => 'logout-form']) ?>
                    <?= Html::submitButton('<i class="fas fa-sign-out-alt"></i> Logout', ['class' => 'btn btn-link logout-button text-white']) ?>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>

        </nav>
    </div>

    <div class="dash-app">
        <header class="dash-toolbar">
            <a href="#!" class="menu-toggle">
                <i class="fas fa-bars"></i>
            </a>

            <!-- Zone droite de la toolbar : cloche + profil, bien séparés -->
            <div class="toolbar-right">

                <!-- 1. Cloche notifications — élément indépendant -->
                <a href="#!" class="notif-bell-wrap" id="notification-link" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notification-count">0</span>
                </a>

                <!-- 2. Dropdown profil — élément indépendant -->
                <div class="dropdown">
                    <a href="#"
                       id="dropdownMenu1"
                       class="user-trigger"
                       data-toggle="dropdown"
                       aria-haspopup="true"
                       aria-expanded="false">
                        <div class="user-avatar-sm"><?= Html::encode($initials) ?></div>
                        <span class="user-display-name"><?= Html::encode($displayName) ?></span>
                        <i class="fas fa-chevron-down user-chevron"></i>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">

                        <!-- En-tête : avatar + nom complet + rôle -->
                        <div class="dropdown-user-header">
                            <div class="dropdown-avatar-lg"><?= Html::encode($initials) ?></div>
                            <div>
                                <p class="dropdown-user-name"><?= Html::encode($fullName) ?></p>
                                <p class="dropdown-user-role">
                                    <?= Html::encode($roleLabel) ?>
                                    <span class="user-role-badge"><?= Html::encode(strtoupper($roleLabel)) ?></span>
                                </p>
                            </div>
                        </div>

                        <?php if (!Yii::$app->user->isGuest && $userType === 'ao'): ?>
                            <a href="<?= Url::to(['ao-profile/view', 'id' => $encodedId]) ?>" class="btn btn-link">
                                <i class="fas fa-id-card"></i> Profile
                            </a>

                            <a href="<?= Url::to(['ao-profile/reset-password', 'id' => $encodedId]) ?>" class="btn btn-link">
                                <i class="fas fa-key"></i> Reset Password
                            </a>
                            

                        <?php endif; ?>

                        <?php if (!Yii::$app->user->isGuest && $userType === 'mro'): ?>
                            <a href="<?= Url::to(['mro-profile/view', 'id' => $encodedId, 'fromMro' => 'truse']) ?>" class="btn btn-link">
                                <i class="fas fa-id-card"></i> Profile
                            </a>

                            <a href="<?= Url::to(['mro-profile/reset-password', 'id' => $encodedId]) ?>" class="btn btn-link">
                                <i class="fas fa-key"></i> Reset Password
                            </a>
                        <?php endif; ?>

                        <?= Html::beginForm(['site/logout'], 'post', ['class' => 'logout-form m-0']) ?>
                        <?= Html::submitButton('<i class="fas fa-sign-out-alt"></i> Logout', ['class' => 'btn btn-link logout-button']) ?>
                        <?= Html::endForm() ?>

                    </div>
                </div>

            </div><!-- /.toolbar-right -->

            <!-- Dropdown notifications (positionné par JS) -->
            <div id="notification-dropdown" class="dropdown-menu dropdown-menu-right" aria-labelledby="notification-link">
                <div class="notification-dropdown-header text-center">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                </div>

                <!--
                    FILTRES : Unread est actif au premier affichage pour mettre en avant
                    les evenements qui demandent une attention, sans les marquer comme lus.
                -->
                <div class="notification-filter-tabs" role="tablist" aria-label="Notification filters">
                    <button type="button" class="notification-filter-tab" data-filter="all" role="tab" aria-selected="false">
                        All <span class="notification-filter-count" id="notification-tab-all-count">0</span>
                    </button>
                    <button type="button" class="notification-filter-tab active" data-filter="unread" role="tab" aria-selected="true">
                        Unread <span class="notification-filter-count" id="notification-tab-unread-count">0</span>
                    </button>
                    <button type="button" class="notification-filter-tab" data-filter="read" role="tab" aria-selected="false">
                        Read <span class="notification-filter-count" id="notification-tab-read-count">0</span>
                    </button>
                </div>

                <div id="notification-list"></div>

                <div class="notification-footer-actions">
                    <button id="mark-all-read-btn" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                    <button id="delete-all-btn" class="btn btn-sm btn-outline-danger delete-all">
                        <i class="fas fa-trash-alt"></i> Delete All
                    </button>
                </div>
            </div>
        </header>

<?php
$notificationCountUrl        = Url::to(['notification/notification-count']);
$notificationListUrl         = Url::to(['notification/notification-list']);
$notificationUpdateUrl       = Url::to(['notification/update-notification']);
$notificationDeleteUrl       = Url::to(['notification/delete-notification']);
$deleteAllNotificationsUrl   = Url::to(['notification/delete-all']);
$markllNotificationAsReadURL = Url::to(['notification/mark-all-as-read']);

$js = <<<JS
let notificationDropdownOpen = false;
let activeNotificationFilter = 'unread';
let notificationFilterInitialized = false;
let notificationRequestSequence = 0;

function updateNotificationCount() {
    fetch('$notificationCountUrl')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let badge = document.getElementById('notification-count');
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline-flex' : 'none';

                /* Le compteur Unread de l'onglet suit toujours le badge de la cloche. */
                const unreadTabCount = document.getElementById('notification-tab-unread-count');
                if (unreadTabCount) unreadTabCount.textContent = data.count;
            }
        })
        .catch(error => console.error('Error fetching count:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    updateNotificationCount();
    setInterval(updateNotificationCount, 5000);
});

/*
 * ETAT VISUEL DES ONGLETS : un seul filtre est actif et aria-selected reste
 * synchronise pour les utilisateurs de clavier ou de lecteur d'ecran.
 */
function selectNotificationFilter(filter) {
    activeNotificationFilter = filter;
    document.querySelectorAll('.notification-filter-tab').forEach(tab => {
        const isActive = tab.getAttribute('data-filter') === filter;
        tab.classList.toggle('active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
}

/* Met a jour les trois compteurs et masque l'action globale inutile dans Read. */
function updateNotificationTabCounts(counts) {
    const safeCounts = counts || {all: 0, unread: 0, read: 0};
    const allCount = document.getElementById('notification-tab-all-count');
    const unreadCount = document.getElementById('notification-tab-unread-count');
    const readCount = document.getElementById('notification-tab-read-count');
    if (allCount) allCount.textContent = safeCounts.all || 0;
    if (unreadCount) unreadCount.textContent = safeCounts.unread || 0;
    if (readCount) readCount.textContent = safeCounts.read || 0;

    const markAllButton = document.getElementById('mark-all-read-btn');
    if (markAllButton) {
        markAllButton.hidden = activeNotificationFilter === 'read' || Number(safeCounts.unread || 0) === 0;
    }
}

/* Chaque filtre dispose d'un message vide explicite pour eviter toute ambiguite. */
function renderEmptyNotificationState(filter) {
    const labels = {
        all: 'No notifications',
        unread: 'No unread notifications',
        read: 'No read notifications'
    };
    return '<div class="p-4 text-center text-muted" style="font-size:0.9rem;"><i class="fas fa-bell-slash mr-2"></i>' + labels[filter] + '</div>';
}

function fetchNotifications(filter) {
    const requestedFilter = filter || activeNotificationFilter;
    const requestNumber = ++notificationRequestSequence;
    const separator = '$notificationListUrl'.indexOf('?') === -1 ? '?' : '&';

    fetch('$notificationListUrl' + separator + 'filter=' + encodeURIComponent(requestedFilter))
        .then(response => response.json())
        .then(data => {
            /* Une reponse ancienne ne doit pas remplacer le dernier onglet selectionne. */
            if (requestNumber !== notificationRequestSequence) return;

            if (data.success) {
                updateNotificationTabCounts(data.counts);

                /*
                 * A la premiere ouverture, Unread est privilegie lorsqu'il contient des
                 * elements. Sinon All devient actif pour ne pas presenter un panneau vide.
                 */
                if (!notificationFilterInitialized) {
                    notificationFilterInitialized = true;
                    const preferredFilter = Number(data.counts && data.counts.unread) > 0 ? 'unread' : 'all';
                    if (preferredFilter !== requestedFilter) {
                        selectNotificationFilter(preferredFilter);
                        fetchNotifications(preferredFilter);
                        return;
                    }
                }

                selectNotificationFilter(requestedFilter);
                const notificationList = document.getElementById('notification-list');
                notificationList.innerHTML = '';

                if (data.notifications.length === 0) {
                    notificationList.innerHTML = renderEmptyNotificationState(requestedFilter);
                    return;
                }

                data.notifications.forEach(notification => {
                    let notificationItem = document.createElement('div');
                    notificationItem.classList.add('notification-item');

                    if (notification.read === "read") {
                        notificationItem.classList.add('read-notification');
                    } else {
                        notificationItem.classList.add('unread-notification');
                    }

                    let notificationIcon = notification.read === "read" ? 'fa-envelope-open' : 'fa-bell';
                    let content = '<div class="notification-content">';
                    content += '<i class="fas ' + notificationIcon + ' notification-message-icon"></i>';
                    content += '<div class="notification-text">';
                    content += '<div>' + notification.message + '</div>';
                    if (notification.created_at) {
                        content += '<div class="notification-created-at"><i class="far fa-clock"></i><span>' + notification.created_at + '</span></div>';
                    }
                    content += '</div>';
                    content += '</div>';
                    content += '<div class="notification-actions">';

                    if (notification.actions) {
                        content += '<a href="' + notification.actions + '" class="btn-notif btn-notif-view"><i class="fas fa-eye"></i> View</a>';
                    }
                    if (notification.read !== "read") {
                        content += '<button class="btn-notif btn-notif-read mark-as-read-btn" data-id="' + notification.id + '"><i class="fas fa-check"></i> Mark as Read</button>';
                    }
                    content += '<button class="btn-notif btn-notif-delete delete-notification-btn" data-id="' + notification.id + '"><i class="fas fa-trash-alt"></i> Delete</button>';
                    content += '</div>';

                    notificationItem.innerHTML = content;
                    notificationList.appendChild(notificationItem);
                });

                if (notificationDropdownOpen) {
                    document.getElementById('notification-dropdown').classList.add('show');
                }
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function markAllNotificationsAsRead() {
    fetch('$markllNotificationAsReadURL')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount();
                fetchNotifications(activeNotificationFilter);
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
}

const notifLink     = document.getElementById('notification-link');
const notifDropdown = document.getElementById('notification-dropdown');

notifLink.addEventListener('click', function(event) {
    event.preventDefault();
    event.stopPropagation();
    notificationDropdownOpen = !notificationDropdownOpen;
    notifDropdown.classList.toggle('show');
    if (notificationDropdownOpen) fetchNotifications();
});

document.addEventListener('click', function(event) {
    if (notificationDropdownOpen && !notifDropdown.contains(event.target) && !notifLink.contains(event.target)) {
        notifDropdown.classList.remove('show');
        notificationDropdownOpen = false;
    }
});

notifDropdown.addEventListener('click', function(event) {
    event.stopPropagation();
});

document.getElementById('notification-dropdown').addEventListener('click', function(event) {

    /* Le changement d'onglet recharge uniquement le filtre choisi depuis le backend. */
    const filterTab = event.target.closest('.notification-filter-tab');
    if (filterTab) {
        const filter = filterTab.getAttribute('data-filter');
        selectNotificationFilter(filter);
        fetchNotifications(filter);
        return;
    }

    const markAsReadButton = event.target.closest('.mark-as-read-btn');
    if (markAsReadButton) {
        let notificationId = markAsReadButton.getAttribute('data-id');
        fetch('$notificationUpdateUrl?id=' + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                    fetchNotifications(activeNotificationFilter);
                }
            })
            .catch(error => console.error('Error marking as read:', error));
    }

    const deleteNotificationButton = event.target.closest('.delete-notification-btn');
    if (deleteNotificationButton) {
        let notificationId = deleteNotificationButton.getAttribute('data-id');
        fetch('$notificationDeleteUrl?id=' + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                    fetchNotifications(activeNotificationFilter);
                }
            })
            .catch(error => console.error('Error deleting:', error));
    }

    const markAllReadButton = event.target.closest('#mark-all-read-btn');
    if (markAllReadButton) markAllNotificationsAsRead();

    const deleteAllButton = event.target.closest('.delete-all');
    if (deleteAllButton) {
        fetch('$deleteAllNotificationsUrl')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                    fetchNotifications(activeNotificationFilter);
                }
            })
            .catch(error => console.error('Error deleting all:', error));
    }
});

/*
 * ACCORDEON DES SIDEBARS PAR ROLE : le fichier spur.js gere deja l'ouverture du groupe
 * clique et la fermeture de ses groupes voisins. Nous ne reproduisons donc pas ce
 * basculement ici : deux gestionnaires successifs ouvraient puis refermaient aussitot
 * le sous-menu. Ce bloc complete uniquement l'accessibilite et le positionnement actif.
 */
document.addEventListener('DOMContentLoaded', function() {
    const roleMenu = document.querySelector('.role-sidebar-menu');

    if (roleMenu) {
        roleMenu.querySelectorAll('.dash-nav-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                /*
                 * Le gestionnaire jQuery de spur.js s'execute sur le meme clic. Le report
                 * a la prochaine frame permet de lire son etat final, puis de synchroniser
                 * aria-expanded sans modifier une seconde fois la classe CSS `show`.
                */
                window.requestAnimationFrame(function() {
                    roleMenu.querySelectorAll('.dash-nav-dropdown').forEach(group => {
                        const groupToggle = group.querySelector(':scope > .dash-nav-dropdown-toggle');
                        if (groupToggle) {
                            groupToggle.setAttribute('aria-expanded', group.classList.contains('show') ? 'true' : 'false');
                        }
                    });
                });
            });
        });

        /* L'element actif est amene dans la zone visible sans deplacer toute la page. */
        const activeItem = roleMenu.querySelector('.dash-nav-dropdown-item.active, .dash-nav-item.active');
        if (activeItem) activeItem.scrollIntoView({block: 'nearest'});
        return;
    }

    document.querySelectorAll('.dash-nav-dropdown-menu').forEach(menu => {
        menu.style.display = 'block';
    });
    document.querySelectorAll('.dash-nav-dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        });
    });
});
JS;

$this->registerJs($js, yii\web\View::POS_END);
?>

<?php
$jsLogout = <<<JS
    \$(document).on('click', '.logout-form button[type="submit"]', function(e) {
        \$(this).closest('form').submit();
    });
JS;
$this->registerJs($jsLogout);
?>
