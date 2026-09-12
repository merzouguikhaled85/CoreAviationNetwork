<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use app\components\UrlIdHelper;

$this->title = 'CAN - Dashboard';

$user = Yii::$app->user->identity;

/*
 * Safe user label.
 * Priority:
 * 1. first_name + last_name
 * 2. username
 * 3. email
 * 4. Captain
 */
$userLabel = 'Captain';

if ($user) {
    $firstName = isset($user->first_name) ? $user->first_name : '';
    $lastName = isset($user->last_name) ? $user->last_name : '';

    $fullName = trim($firstName . ' ' . $lastName);

    if (!empty($fullName)) {
        $userLabel = $fullName;
    } elseif (!empty($user->username)) {
        $userLabel = $user->username;
    } elseif (!empty($user->email)) {
        $userLabel = $user->email;
    }
}

$userInitial = strtoupper(substr($userLabel ?: 'C', 0, 1));

/*
 * User type from session.
 * Expected values: ao / mro
 */
$userType = strtolower((string) Yii::$app->session->get('user_type'));

$isAO = $userType === 'ao';
$isMRO = $userType === 'mro';
$isAdmin = $userType === 'admin';
$adminDashboard = is_array($adminDashboard ?? null) ? $adminDashboard : [];
$systemStatusLabel = 'System status: Online';

/*
 * Dashboard content by role.
 * Keep your routes here only. The design and HTML will remain the same.
 */
if ($isAO) {
    $dashboardTitle = 'AO Dashboard';
    $dashboardRole = 'AIRCRAFT OPERATOR';
    $heroTitle = 'Aircraft operations';
    $heroAccent = 'managed safely.';
    $heroText = 'Manage your aviation requests, appointments, purchase orders, reports and feedback from one secure platform.';

    $mainPanelTitle = 'AO Operational Control';
    $mainPanelText = 'Aircraft operator workspace overview.';

    $stats = [
        ['value' => 'AO', 'label' => 'Workspace'],
        ['value' => 'Zulu', 'label' => 'Aviation UTC'],
        ['value' => 'Secure', 'label' => 'Platform'],
    ];

    $modules = [
        [
            'title' => 'My Requests',
            'text' => 'Create, update and follow your aviation service requests.',
            'icon' => 'request',
            'class' => '',
            'url' => ['/requests/new-requests'],
        ],
        // [
        //     'title' => 'MRO Quotes',
        //     'text' => 'Review MRO applications, quotations and service proposals.',
        //     'icon' => 'quote',
        //     'class' => 'gold',
        //     'url' => ['/ao-applications/index'],
        // ],
        [
            'title' => 'Appointments',
            'text' => 'Schedule and follow your operational appointments.',
            'icon' => 'calendar',
            'class' => 'green',
            'url' => ['/ao-appointments/index'],
        ],
        // [
        //     'title' => 'Reports & Feedback',
        //     'text' => 'View reports, validate work and provide feedback.',
        //     'icon' => 'report',
        //     'class' => '',
        //     'url' => ['/ao-reports/index'],
        // ],
    ];
} elseif ($isMRO) {
    $dashboardTitle = 'MRO Dashboard';
    $dashboardRole = 'MAINTENANCE REPAIR ORGANIZATION';
    $heroTitle = 'Maintenance operations';
    $heroAccent = 'controlled professionally.';
    $heroText = 'Manage applications, appointments, purchase orders, work progress, invoices and maintenance reports from one secure platform.';

    $mainPanelTitle = 'MRO Operational Control';
    $mainPanelText = 'Maintenance provider workspace overview.';

    $stats = [
        ['value' => 'MRO', 'label' => 'Workspace'],
        ['value' => 'Zulu', 'label' => 'Aviation UTC'],
        ['value' => 'Secure', 'label' => 'Platform'],
    ];

    $modules = [
        [
            'title' => 'Available Requests',
            'text' => 'Consult aviation requests and submit your MRO applications.',
            'icon' => 'request',
            'class' => '',
            'url' => ['/mro-requests/index'],
        ],
        [
            'title' => 'My Applications',
            'text' => 'Track submitted applications, quotations and decisions.',
            'icon' => 'tools',
            'class' => 'gold',
            'url' => ['/mro-applications/index'],
        ],
        [
            'title' => 'Appointments',
            'text' => 'Manage scheduled inspections and maintenance appointments.',
            'icon' => 'calendar',
            'class' => 'green',
            'url' => ['/mro-appointments/index'],
        ],
        [
            'title' => 'Reports & Invoices',
            'text' => 'Upload reports, follow PO files and manage invoices.',
            'icon' => 'report',
            'class' => '',
            'url' => ['/mro-reports/index'],
        ],
    ];
} elseif ($isAdmin) {
    /*
     * COCKPIT ADMINISTRATEUR : contrairement à l'ancien fallback générique,
     * cette branche affiche des données opérationnelles réellement calculées
     * par le contrôleur et relie chaque carte à une route administrative utile.
     */
    $adminSummary = $adminDashboard['summary'] ?? [];
    $dashboardTitle = 'Admin Dashboard';
    $dashboardRole = 'PLATFORM ADMINISTRATOR';
    $heroTitle = 'Platform operations';
    $heroAccent = 'under control.';
    $heroText = 'Monitor urgent maintenance demand, support workload, disputes and organization readiness from one operational cockpit.';

    $mainPanelTitle = 'Admin Operational Control';
    $mainPanelText = 'Live platform workload overview.';

    $stats = [
        ['value' => (int) ($adminSummary['activeAog'] ?? 0), 'label' => 'Active AOG'],
        ['value' => (int) ($adminSummary['supportWorkload'] ?? 0), 'label' => 'Support'],
        ['value' => (int) ($adminSummary['openDisputes'] ?? 0), 'label' => 'Disputes'],
    ];

    $attentionCount = (int) ($adminSummary['activeAog'] ?? 0)
        + (int) ($adminSummary['supportWorkload'] ?? 0)
        + (int) ($adminSummary['mailFailures'] ?? 0);
    $systemStatusLabel = $attentionCount > 0
        ? $attentionCount . ' item' . ($attentionCount === 1 ? '' : 's') . ' require attention'
        : 'No urgent item detected';

    $modules = [
        [
            'title' => 'Requests Control',
            'text' => 'Review aviation requests and delayed operational responses.',
            'icon' => 'request',
            'class' => '',
            'url' => ['/awaiting-request-response/index'],
        ],
        [
            'title' => 'Support Tickets',
            'text' => 'Process user issues, assignments and pending replies.',
            'icon' => 'quote',
            'class' => 'gold',
            'url' => ['/admin-support-tickets/index'],
        ],
        [
            'title' => 'Disputes',
            'text' => 'Review open AO/MRO disputes and administrative responses.',
            'icon' => 'tools',
            'class' => 'green',
            'url' => ['/admin-disputes/index'],
        ],
        [
            'title' => 'Organizations',
            'text' => 'Manage AO and MRO profiles, verification and access status.',
            'icon' => 'user',
            'class' => '',
            'url' => ['/mro-profile/index'],
        ],
    ];
} else {
    $dashboardTitle = 'CAN Dashboard';
    $dashboardRole = 'CAN USER';
    $heroTitle = 'Aviation operations';
    $heroAccent = 'connected safely.';
    $heroText = 'You are connected to the CAN dashboard. Manage aviation requests, workflows, appointments, reports and coordination from one secure platform.';

    $mainPanelTitle = 'Operational Control';
    $mainPanelText = 'Live aviation workspace overview.';

    $stats = [
        ['value' => 'CAN', 'label' => 'Network'],
        ['value' => 'Zulu', 'label' => 'Aviation UTC'],
        ['value' => 'Secure', 'label' => 'Platform'],
    ];

    $modules = [
        [
            'title' => 'Requests Management',
            'text' => 'Create, track and coordinate operational requests.',
            'icon' => 'request',
            'class' => '',
            'url' => '#',
        ],
        [
            'title' => 'MRO Operations',
            'text' => 'Follow maintenance, repair and overhaul actions.',
            'icon' => 'tools',
            'class' => 'gold',
            'url' => '#',
        ],
        [
            'title' => 'Appointments',
            'text' => 'Manage scheduled operations and coordination.',
            'icon' => 'calendar',
            'class' => 'green',
            'url' => '#',
        ],
        [
            'title' => 'Reports & Documents',
            'text' => 'Access reports, purchase orders and aviation documents.',
            'icon' => 'report',
            'class' => '',
            'url' => '#',
        ],
    ];
}

/*
 * PRÉPARATION DE L'AFFICHAGE ADMIN : les tableaux restent vides pour les autres
 * rôles. Cette séparation empêche toute donnée administrative d'être rendue dans
 * le HTML d'un compte AO/MRO, même si le template général est partagé.
 */
$adminSummaryCards = [];
$adminActivity = [];
$adminPriorities = [];
$adminAogQueue = [];
$adminSupportQueue = [];
$adminDisputeQueue = [];
$adminGeneratedAt = null;

if ($isAdmin) {
    $adminSummary = $adminDashboard['summary'] ?? [];
    $adminSummaryCards = [
        ['value' => (int) ($adminSummary['activeAog'] ?? 0), 'label' => 'Active AOG requests', 'icon' => 'warning', 'color' => '#dc2626'],
        ['value' => (int) ($adminSummary['activeRequests'] ?? 0), 'label' => 'Active requests', 'icon' => 'request', 'color' => '#0284c7'],
        ['value' => (int) ($adminSummary['openDisputes'] ?? 0), 'label' => 'Open disputes', 'icon' => 'shield', 'color' => '#7c3aed'],
        ['value' => (int) ($adminSummary['supportWorkload'] ?? 0), 'label' => 'Support workload', 'icon' => 'headset', 'color' => '#0891b2'],
        ['value' => (int) ($adminSummary['unverifiedProfiles'] ?? 0), 'label' => 'Unverified profiles', 'icon' => 'user', 'color' => '#d97706'],
        ['value' => (int) ($adminSummary['mailFailures'] ?? 0), 'label' => 'Mail delivery failures', 'icon' => 'mail', 'color' => '#be123c'],
    ];
    $adminActivity = $adminDashboard['activity'] ?? [];
    $adminPriorities = $adminDashboard['priorityDistribution'] ?? [];
    $adminAogQueue = $adminDashboard['aogQueue'] ?? [];
    $adminSupportQueue = $adminDashboard['supportQueue'] ?? [];
    $adminDisputeQueue = $adminDashboard['disputeQueue'] ?? [];
    $adminGeneratedAt = $adminDashboard['generatedAt'] ?? null;
}

/*
 * FORMATAGE AOG : l'échéance est enregistrée en UTC par le métier. Le libellé
 * montre le temps restant ou le dépassement sans recalculer ni modifier la date
 * de référence stockée dans la demande.
 */
$formatAogDeadline = static function ($deadline) {
    if (!$deadline) {
        return ['label' => 'No response deadline', 'danger' => false];
    }

    $deadlineTimestamp = strtotime((string) $deadline . ' UTC');
    if ($deadlineTimestamp === false) {
        return ['label' => 'Deadline unavailable', 'danger' => false];
    }

    $remainingSeconds = $deadlineTimestamp - time();
    $absoluteMinutes = (int) floor(abs($remainingSeconds) / 60);
    $days = intdiv($absoluteMinutes, 1440);
    $hours = intdiv($absoluteMinutes % 1440, 60);
    $minutes = $absoluteMinutes % 60;
    $parts = [];
    if ($days > 0) {
        $parts[] = $days . 'd';
    }
    if ($hours > 0 || $days > 0) {
        $parts[] = $hours . 'h';
    }
    $parts[] = $minutes . 'm';

    return [
        'label' => implode(' ', $parts) . ($remainingSeconds < 0 ? ' overdue' : ' remaining'),
        'danger' => $remainingSeconds < 0,
    ];
};

$backgroundUrl = Url::to('@web/img/world-day.webp');
$clientLocationUrl = Url::to(['/site/client-location']);

/*
 * Inline SVG icons.
 * They do not require Bootstrap Icons.
 */
$icons = [
    'plane' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M21.7 13.35c-.22.45-.7.72-1.2.66l-6.25-.75-3.52 5.96c-.2.34-.56.55-.95.55h-1.2c-.46 0-.78-.45-.63-.88l2.05-6.03-5.42-.65-1.55 2.18c-.2.29-.54.46-.9.46h-.85c-.4 0-.7-.38-.6-.77L1.7 12 .78 9.92c-.17-.39.12-.77.55-.77h.86c.36 0 .7.17.9.46l1.55 2.18 5.42-.65-2.05-6.03c-.15-.43.17-.88.63-.88h1.2c.39 0 .75.21.95.55l3.52 5.96 6.25-.75c.5-.06.98.21 1.2.66.36.72.36 1.56 0 2.7Z"/>
        </svg>
    ',
    'request' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M3.4 20.6c-.42.18-.84-.24-.66-.66l2.1-5.02L13.55 6.2a3.1 3.1 0 0 1 4.38 4.38l-8.7 8.72-5.83 1.3Zm2.95-3.25 1.7-.38 8.25-8.25a.8.8 0 0 0-1.13-1.13l-8.25 8.25-.57 1.51ZM19 20.75H12.5a1.1 1.1 0 1 1 0-2.2H19a1.1 1.1 0 1 1 0 2.2Z"/>
        </svg>
    ',
    'tools' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M21.1 18.7 15.8 13.4a6.4 6.4 0 0 1-7.5-8.9c.22-.44.84-.47 1.16-.09l3.03 3.58 2.2-2.2-3.58-3.04c-.38-.32-.35-.94.09-1.16a6.4 6.4 0 0 1 8.9 7.5l5.3 5.3a2.7 2.7 0 0 1 0 3.82l-.48.48a2.7 2.7 0 0 1-3.82 0Z"/>
        </svg>
    ',
    'calendar' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M7 2.25c.62 0 1.13.5 1.13 1.12v.88h7.74v-.88a1.13 1.13 0 0 1 2.26 0v.88h.62A3.25 3.25 0 0 1 22 7.5v10.25A3.25 3.25 0 0 1 18.75 21H5.25A3.25 3.25 0 0 1 2 17.75V7.5a3.25 3.25 0 0 1 3.25-3.25h.62v-.88c0-.62.51-1.12 1.13-1.12ZM4.25 9.5v8.25c0 .55.45 1 1 1h13.5c.55 0 1-.45 1-1V9.5H4.25Zm11.55 2.26c.43.43.43 1.15 0 1.58l-3.28 3.28c-.43.43-1.15.43-1.58 0l-1.74-1.74a1.12 1.12 0 1 1 1.58-1.58l.95.95 2.49-2.49c.43-.43 1.15-.43 1.58 0Z"/>
        </svg>
    ',
    'report' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M6.25 2h7.4c.6 0 1.18.24 1.6.66l3.1 3.1c.42.42.65 1 .65 1.6v11.39A3.25 3.25 0 0 1 15.75 22h-9.5A3.25 3.25 0 0 1 3 18.75V5.25A3.25 3.25 0 0 1 6.25 2Zm7.25 2.35V7c0 .28.22.5.5.5h2.65L13.5 4.35ZM7.5 11.25a1.1 1.1 0 1 0 0 2.2h7a1.1 1.1 0 1 0 0-2.2h-7Zm0 4a1.1 1.1 0 1 0 0 2.2h4.5a1.1 1.1 0 1 0 0-2.2H7.5Z"/>
        </svg>
    ',
    'quote' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M5.25 3h13.5A3.25 3.25 0 0 1 22 6.25v8.5A3.25 3.25 0 0 1 18.75 18H9.6l-4.35 3.25c-.82.62-2 .03-2-1v-14A3.25 3.25 0 0 1 5.25 3Zm2.25 5.5a1.1 1.1 0 0 0 0 2.2h9a1.1 1.1 0 0 0 0-2.2h-9Zm0 4a1.1 1.1 0 0 0 0 2.2h5.5a1.1 1.1 0 0 0 0-2.2H7.5Z"/>
        </svg>
    ',
    'user' => '
        <svg viewBox="0 0 24 24" class="can-small-svg" aria-hidden="true">
            <path d="M12 12.25A4.6 4.6 0 1 0 12 3a4.6 4.6 0 0 0 0 9.25Zm0 2.25c-4.22 0-7.75 2.12-7.75 4.75 0 .69.56 1.25 1.25 1.25h13c.69 0 1.25-.56 1.25-1.25 0-2.63-3.53-4.75-7.75-4.75Z"/>
        </svg>
    ',
    'clock' => '
        <svg viewBox="0 0 24 24" class="can-small-svg" aria-hidden="true">
            <path d="M12 2.25A9.75 9.75 0 1 0 12 21.75 9.75 9.75 0 0 0 12 2.25Zm0 2.25a7.5 7.5 0 1 1 0 15 7.5 7.5 0 0 1 0-15Zm1.05 3.25a1.05 1.05 0 0 0-2.1 0v4.45c0 .36.18.7.48.9l3.15 2.1a1.05 1.05 0 1 0 1.16-1.75l-2.69-1.79V7.75Z"/>
        </svg>
    ',
    /*
     * ICÔNES DU COCKPIT ADMIN : ces SVG locaux remplacent les lettres servant
     * auparavant de repères temporaires. Ils héritent de la couleur de chaque
     * indicateur et ne dépendent d'aucune bibliothèque ou ressource distante.
     */
    'warning' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M10.02 3.44a2.28 2.28 0 0 1 3.96 0l8.02 14A2.29 2.29 0 0 1 20.02 21H3.98A2.29 2.29 0 0 1 2 17.44l8.02-14ZM12 8a1.1 1.1 0 0 0-1.1 1.1v4.3a1.1 1.1 0 1 0 2.2 0V9.1A1.1 1.1 0 0 0 12 8Zm0 8.15a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z"/>
        </svg>
    ',
    'shield' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M12 2.2c.2 0 .4.04.58.13l7 3.2c.44.2.72.64.72 1.12v4.48c0 4.86-2.93 8.91-7.72 10.67-.38.14-.8.14-1.16 0C6.63 20.04 3.7 16 3.7 11.13V6.65c0-.48.28-.92.72-1.12l7-3.2c.18-.09.38-.13.58-.13Zm0 5.2a1.08 1.08 0 0 0-1.08 1.08v3.65a1.08 1.08 0 1 0 2.16 0V8.48A1.08 1.08 0 0 0 12 7.4Zm0 7.3a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z"/>
        </svg>
    ',
    'headset' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M12 2.5a8.5 8.5 0 0 0-8.5 8.5v5.25A2.75 2.75 0 0 0 6.25 19H8a1 1 0 0 0 1-1v-5.5a1 1 0 0 0-1-1H5.75V11a6.25 6.25 0 0 1 12.5 0v.5H16a1 1 0 0 0-1 1V18a1 1 0 0 0 1 1h2.15c-.42 1-1.4 1.7-2.55 1.7h-2.1a1.1 1.1 0 1 0 0 2.2h2.1a4.95 4.95 0 0 0 4.9-4.25V11A8.5 8.5 0 0 0 12 2.5Z"/>
        </svg>
    ',
    'mail' => '
        <svg viewBox="0 0 24 24" class="can-svg-icon" aria-hidden="true">
            <path d="M4.75 4h14.5A3.75 3.75 0 0 1 23 7.75v8.5A3.75 3.75 0 0 1 19.25 20H4.75A3.75 3.75 0 0 1 1 16.25v-8.5A3.75 3.75 0 0 1 4.75 4Zm.13 2.25 6.36 5.09c.45.36 1.07.36 1.52 0l6.36-5.09H4.88Zm15.87 2.16-6.58 5.26a3.45 3.45 0 0 1-4.34 0L3.25 8.41v7.84c0 .83.67 1.5 1.5 1.5h14.5c.83 0 1.5-.67 1.5-1.5V8.41Z"/>
        </svg>
    ',
];
?>

<style>
/* ==========================================================
   CAN DASHBOARD - LIGHT TRANSPARENT AVIATION DESIGN
   ========================================================== */

:root {
    --can-navy: #0b1f35;
    --can-blue: #0284c7;
    --can-gold: #f2b705;
    --can-green: #16a34a;
    --can-text: #0f172a;
    --can-muted: #475569;
    --can-border: rgba(255, 255, 255, 0.82);
    --can-shadow: 0 24px 70px rgba(15, 23, 42, 0.20);
}

/* Main background */
.dash-content {
    position: relative;
    min-height: calc(100vh - 100px);
    padding: 34px 28px 38px;
    overflow: hidden;
    background-image:
        linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04)),
        url('<?= Html::encode($backgroundUrl) ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    justify-content: center;
}

/* Light overlay to keep the aircraft image visible */
.dash-content::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background:
        radial-gradient(circle at 10% 14%, rgba(14, 165, 233, 0.13), transparent 32%),
        radial-gradient(circle at 90% 18%, rgba(250, 204, 21, 0.11), transparent 30%),
        linear-gradient(to bottom, rgba(255,255,255,0.02), rgba(255,255,255,0.08));
}

.dash-container {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1380px;
}

.can-svg-icon {
    width: 25px;
    height: 25px;
    display: block;
    fill: currentColor;
}

.can-small-svg {
    width: 15px;
    height: 15px;
    display: block;
    fill: currentColor;
}

/* Flash messages */
.flash-wrapper {
    max-width: 840px;
    margin: 0 auto 20px;
}

.flash-wrapper .alert {
    border: 1px solid var(--can-border);
    border-radius: 16px;
    padding: 13px 16px;
    color: var(--can-text);
    font-size: 0.95rem;
    font-weight: 700;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 14px 38px rgba(15, 23, 42, 0.16);
}

.alert-success {
    background: rgba(220, 252, 231, 0.72);
}

.alert-danger,
.alert-error {
    background: rgba(254, 226, 226, 0.72);
}

/* Hero card */
.can-hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    border: 1px solid var(--can-border);
    background: linear-gradient(135deg, rgba(255,255,255,0.72), rgba(255,255,255,0.46));
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    box-shadow: var(--can-shadow);
}

.can-hero-card::after {
    content: "";
    position: absolute;
    left: 34px;
    right: 34px;
    top: 0;
    height: 3px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, var(--can-gold), var(--can-blue), transparent);
}

.can-hero-inner {
    position: relative;
    z-index: 2;
    padding: 30px;
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
    gap: 28px;
    align-items: stretch;
}

.can-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 8px 14px;
    margin-bottom: 16px;
    border-radius: 999px;
    border: 1px solid rgba(14, 165, 233, 0.35);
    background: rgba(255, 255, 255, 0.70);
    color: var(--can-navy);
    font-size: 0.78rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.can-eyebrow-dot {
    width: 9px;
    height: 9px;
    border-radius: 9px;
    background: var(--can-green);
    box-shadow: 0 0 0 6px rgba(22, 163, 74, 0.16);
}

.can-hero-title {
    margin: 0;
    color: var(--can-navy);
    font-size: clamp(2rem, 3.4vw, 3.65rem);
    font-weight: 950;
    line-height: 1.03;
    letter-spacing: -0.055em;
}

.can-hero-title span {
    color: var(--can-gold);
}

.can-hero-subtitle {
    max-width: 780px;
    margin: 16px 0 0;
    color: #263445;
    font-size: 1.02rem;
    line-height: 1.7;
    font-weight: 600;
}

/* User badges */
.can-user-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 22px;
}

.can-user-badge,
.can-role-badge,
.can-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 42px;
    padding: 10px 15px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.10);
    background: rgba(255, 255, 255, 0.72);
    color: var(--can-navy);
    font-size: 0.9rem;
    font-weight: 850;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.10);
}

.can-user-avatar {
    width: 27px;
    height: 27px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, var(--can-gold), #fb923c);
    color: #111827;
    font-size: 0.78rem;
    font-weight: 950;
}

.can-status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: var(--can-green);
    box-shadow: 0 0 18px rgba(22, 163, 74, 0.85);
}

/* Local and UTC date/time */
.can-time-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
    max-width: 760px;
}

.can-time-card {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 76px;
    padding: 13px 15px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,0.72);
    background: rgba(255, 255, 255, 0.68);
    backdrop-filter: blur(7px);
    -webkit-backdrop-filter: blur(7px);
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.11);
}

.can-time-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: rgba(14,165,233,0.16);
    border: 1px solid rgba(14,165,233,0.28);
    color: var(--can-blue);
}

.can-time-content {
    min-width: 0;
}

.can-time-label {
    display: block;
    color: var(--can-muted);
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 3px;
}

.can-time-value {
    display: block;
    color: var(--can-blue);
    font-size: 1.02rem;
    font-weight: 950;
    line-height: 1.2;
}

.can-time-zone {
    display: block;
    margin-top: 3px;
    color:var(--can-dark-blue);
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.can-time-location {
    display: block;
    margin-top: 3px;
    color:var(--can-green);
    font-size: 0.78rem;
    font-weight: 850;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Operation panel */
.can-operation-panel {
    border-radius: 24px;
    padding: 22px;
    border: 1px solid rgba(255,255,255,0.72);
    background: linear-gradient(180deg, rgba(255,255,255,0.70), rgba(255,255,255,0.46));
    backdrop-filter: blur(7px);
    -webkit-backdrop-filter: blur(7px);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.72), 0 18px 45px rgba(15,23,42,0.12);
}

.can-operation-header {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 22px;
}

.can-operation-title {
    color: var(--can-navy);
    font-size: 1.02rem;
    font-weight: 950;
    margin: 0 0 4px;
}

.can-operation-text {
    margin: 0;
    color: var(--can-muted);
    font-size: 0.88rem;
    font-weight: 650;
}

.can-operation-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: rgba(242, 183, 5, 0.16);
    border: 1px solid rgba(242, 183, 5, 0.38);
    color: #b7791f;
}

.can-flight-line {
    position: relative;
    margin: 26px 0;
    height: 72px;
}

.can-flight-line::before {
    content: "";
    position: absolute;
    left: 15px;
    right: 15px;
    top: 36px;
    height: 2px;
    background: linear-gradient(90deg, rgba(2,132,199,0.35), rgba(242,183,5,0.95), rgba(2,132,199,0.35));
}

.can-flight-point {
    position: absolute;
    top: 25px;
    width: 24px;
    height: 24px;
    border-radius: 999px;
    background: rgba(255,255,255,0.82);
    border: 3px solid #0284c7;
}

.can-flight-point.start {
    left: 0;
}

.can-flight-point.middle {
    left: calc(50% - 12px);
    border-color: var(--can-gold);
}

.can-flight-point.end {
    right: 0;
}

.can-plane {
    position: absolute;
    left: calc(50% - 20px);
    top: 2px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    color: var(--can-navy);
    transform: rotate(45deg);
}

.can-mini-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.can-mini-stat {
    border-radius: 18px;
    padding: 14px 12px;
    background: rgba(255,255,255,0.68);
    border: 1px solid rgba(15,23,42,0.08);
}

.can-mini-stat strong {
    display: block;
    color: var(--can-navy);
    font-size: 1.05rem;
    font-weight: 950;
}

.can-mini-stat span {
    color: var(--can-muted);
    font-size: 0.75rem;
    font-weight: 800;
}

/* Module cards */
.can-modules-grid {
    margin-top: 24px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.can-module-card {
    display: block;
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
    min-height: 165px;
    border-radius: 22px;
    padding: 20px;
    border: 1px solid rgba(255,255,255,0.72);
    background: linear-gradient(135deg, rgba(255,255,255,0.70), rgba(255,255,255,0.48));
    backdrop-filter: blur(7px);
    -webkit-backdrop-filter: blur(7px);
    box-shadow: 0 18px 44px rgba(15,23,42,0.14);
    transition: transform 0.22s ease, border-color 0.22s ease, background 0.22s ease;
}

.can-module-card:hover {
    transform: translateY(-5px);
    border-color: rgba(242,183,5,0.55);
    background: linear-gradient(135deg, rgba(255,255,255,0.82), rgba(255,255,255,0.58));
    text-decoration: none;
    color: inherit;
}

.can-module-card::before {
    content: "→";
    position: absolute;
    right: 18px;
    bottom: 16px;
    z-index: 3;
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: rgba(255,255,255,0.82);
    color: var(--can-navy);
    font-weight: 950;
    box-shadow: 0 8px 20px rgba(15,23,42,0.12);
    transition: transform 0.2s ease;
}

.can-module-card:hover::before {
    transform: translateX(4px);
}

.can-module-icon {
    position: relative;
    z-index: 2;
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    margin-bottom: 18px;
    background: rgba(14,165,233,0.16);
    border: 1px solid rgba(14,165,233,0.30);
    color: var(--can-blue);
}

.can-module-card.gold .can-module-icon {
    background: rgba(242,183,5,0.17);
    border-color: rgba(242,183,5,0.36);
    color: #b7791f;
}

.can-module-card.green .can-module-icon {
    background: rgba(22,163,74,0.14);
    border-color: rgba(22,163,74,0.28);
    color: var(--can-green);
}

.can-module-card h3 {
    position: relative;
    z-index: 2;
    margin: 0 0 8px;
    color: var(--can-navy);
    font-size: 1.04rem;
    font-weight: 950;
}

.can-module-card p {
    position: relative;
    z-index: 2;
    margin: 0;
    padding-right: 28px;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.55;
}

/*
 * COCKPIT ADMINISTRATEUR : ces composants complètent le visuel historique
 * sans créer une seconde charte graphique. Les fonds translucides, rayons et
 * ombres reprennent exactement le langage visuel des cartes du dashboard.
 */
.can-module-icon .can-small-svg {
    width: 25px;
    height: 25px;
}

.admin-dashboard-zone {
    margin-top: 24px;
    padding: 20px;
    border: 1px solid rgba(203, 213, 225, 0.9);
    border-radius: 26px;
    background: rgba(241, 245, 249, 0.94);
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.17);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.admin-section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin: 0 2px 14px;
    color: var(--can-navy);
    text-shadow: none;
}

.admin-section-heading h2 {
    margin: 0 0 3px;
    font-size: 1.18rem;
    font-weight: 950;
}

.admin-section-heading p,
.admin-section-heading time {
    margin: 0;
    font-size: 0.78rem;
    font-weight: 750;
    color: #52647b;
}

.admin-section-heading time {
    flex: 0 0 auto;
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    background: #fff;
    color: #334155;
}

.admin-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
}

.admin-kpi-card,
.admin-insight-card,
.admin-queue-card {
    min-width: 0;
    border: 1px solid rgba(255, 255, 255, 0.76);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.14);
}

.admin-kpi-card {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    padding: 16px;
}

.admin-kpi-card::after {
    content: "";
    position: absolute;
    inset: auto 0 0;
    height: 3px;
    background: var(--kpi-color, var(--can-blue));
}

.admin-kpi-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    margin-bottom: 12px;
    border-radius: 11px;
    color: var(--kpi-color, var(--can-blue));
    background: color-mix(in srgb, var(--kpi-color, var(--can-blue)) 12%, white);
    font-weight: 950;
}

.admin-kpi-icon .can-svg-icon,
.admin-kpi-icon .can-small-svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.admin-kpi-card strong {
    display: block;
    color: var(--can-navy);
    font-size: 1.55rem;
    font-weight: 950;
    line-height: 1;
}

.admin-kpi-card span {
    display: block;
    margin-top: 7px;
    color: var(--can-muted);
    font-size: 0.75rem;
    font-weight: 850;
    line-height: 1.3;
}

.admin-insights-grid,
.admin-queues-grid {
    display: grid;
    gap: 14px;
    margin-top: 14px;
}

.admin-insights-grid {
    grid-template-columns: 1fr 1fr;
}

.admin-queues-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
}

.admin-insight-card,
.admin-queue-card {
    border-radius: 20px;
    padding: 18px;
}

.admin-card-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.admin-card-title h3 {
    margin: 0;
    color: var(--can-navy);
    font-size: 0.95rem;
    font-weight: 950;
}

.admin-card-title a {
    color: var(--can-blue);
    font-size: 0.72rem;
    font-weight: 900;
    text-decoration: none;
}

.admin-activity-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.admin-activity-item {
    padding: 12px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 13px;
    background: rgba(248, 250, 252, 0.72);
}

.admin-activity-item strong,
.admin-activity-item span {
    display: block;
}

.admin-activity-item strong {
    color: var(--can-navy);
    font-size: 1.1rem;
    font-weight: 950;
}

.admin-activity-item span {
    margin-top: 4px;
    color: var(--can-muted);
    font-size: 0.7rem;
    font-weight: 800;
}

.admin-priority-row {
    display: grid;
    grid-template-columns: 62px minmax(0, 1fr) 28px;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    color: var(--can-muted);
    font-size: 0.74rem;
    font-weight: 850;
}

.admin-priority-track {
    height: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.2);
}

.admin-priority-fill {
    display: block;
    min-width: 3px;
    height: 100%;
    border-radius: inherit;
    background: var(--priority-color, var(--can-blue));
}

.admin-queue-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.admin-queue-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 0;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}

.admin-queue-item:first-child {
    border-top: 0;
    padding-top: 0;
}

.admin-queue-item:last-child {
    padding-bottom: 0;
}

.admin-queue-copy {
    min-width: 0;
}

.admin-queue-copy strong,
.admin-queue-copy span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-queue-copy strong {
    color: var(--can-navy);
    font-size: 0.78rem;
    font-weight: 950;
}

.admin-queue-copy span {
    margin-top: 3px;
    color: var(--can-muted);
    font-size: 0.68rem;
    font-weight: 750;
}

.admin-queue-badge {
    flex: 0 0 auto;
    padding: 6px 8px;
    border-radius: 999px;
    color: #0369a1;
    background: #e0f2fe;
    font-size: 0.65rem;
    font-weight: 900;
}

.admin-queue-badge.danger {
    color: #b91c1c;
    background: #fee2e2;
}

.admin-empty-state {
    padding: 18px 8px;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 800;
    text-align: center;
}

/* Responsive */
@media (max-width: 1200px) {
    .can-modules-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-kpi-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .admin-queues-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 992px) {
    .can-hero-inner {
        grid-template-columns: 1fr;
    }

    .admin-insights-grid,
    .admin-queues-grid {
        grid-template-columns: 1fr;
    }

    .admin-activity-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .dash-content {
        padding: 26px 12px 30px;
        min-height: calc(100vh - 76px);
    }

    .can-hero-inner {
        padding: 22px;
    }

    .can-hero-title {
        font-size: 2.1rem;
    }

    .can-user-badge,
    .can-role-badge,
    .can-status-badge {
        width: 100%;
        justify-content: center;
    }

    .can-time-strip {
        grid-template-columns: 1fr;
    }

    .can-mini-stats {
        grid-template-columns: 1fr;
    }

    .can-modules-grid {
        grid-template-columns: 1fr;
    }

    .admin-kpi-grid {
        grid-template-columns: 1fr 1fr;
    }

    .admin-section-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<main class="dash-content">
    <div class="dash-container">

        <!-- Flash messages -->
        <!-- <div class="flash-wrapper">
            <?php if (Yii::$app->session->hasFlash('message')): ?>
                <div class="alert alert-success">
                    <?= Html::encode(Yii::$app->session->getFlash('message')) ?>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
                </div>
            <?php endif; ?>
        </div> -->

        <!-- Main dashboard card -->
        <section class="can-hero-card">
            <div class="can-hero-inner">

                <div class="can-hero-left">
                    <div class="can-eyebrow">
                        <span class="can-eyebrow-dot"></span>
                        <?= Html::encode($dashboardTitle) ?>
                    </div>

                    <h1 class="can-hero-title">
                        <?= Html::encode($heroTitle) ?><br>
                        <span><?= Html::encode($heroAccent) ?></span>
                    </h1>

                    <p class="can-hero-subtitle">
                        Welcome, <span style="color: var(--can-blue);text-transform: uppercase;"><strong><?= Html::encode($userLabel) ?></strong></span>.
                        <?= Html::encode($heroText) ?>
                    </p>

                    <div class="can-user-row">
                        <div class="can-user-badge">
                            <span class="can-user-avatar">
                                <?= Html::encode($userInitial) ?>
                            </span>
                            <span style="color: var(--can-dark);text-transform: uppercase;"><strong><?= Html::encode($userLabel) ?></strong></span>
                        </div>

                        <div class="can-role-badge">
                            <?= $icons['user'] ?>
                            <span style="color: var(--can-dark);text-transform: uppercase;"><strong><?= Html::encode($dashboardRole) ?></strong></span>
                        </div>

                        <div class="can-status-badge">
                            <span class="can-status-indicator"></span>
                            <?= Html::encode($systemStatusLabel) ?>
                        </div>
                    </div>

                    <!-- Local and UTC date/time -->
                    <div class="can-time-strip">
                        <div class="can-time-card">
                            <div class="can-time-icon">
                                <?= $icons['clock'] ?>
                            </div>

                            <div class="can-time-content">
                                <span class="can-time-label">Local Date & Time</span>
                                <span class="can-time-value" id="canLocalDateTime">--</span>
                                <span class="can-time-zone" id="canLocalTimeZone">Local timezone</span>
                                <span style="display:none;" class="can-time-location" id="canLocalLocation">Detecting city / country...</span>
                            </div>
                        </div>

                        <div class="can-time-card">
                            <div class="can-time-icon">
                                <?= $icons['clock'] ?>
                            </div>

                            <div class="can-time-content">
                                <span class="can-time-label">UTC Date & Time</span>
                                <span class="can-time-value" id="canUtcDateTime">--</span>
                                <span class="can-time-zone">Zulu / Aviation UTC</span>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="can-operation-panel">
                    <div class="can-operation-header">
                        <div>
                            <h2 class="can-operation-title">
                                <?= Html::encode($mainPanelTitle) ?>
                            </h2>
                            <p class="can-operation-text">
                                <?= Html::encode($mainPanelText) ?>
                            </p>
                        </div>

                        <div class="can-operation-icon">
                            <?= $icons['plane'] ?>
                        </div>
                    </div>

                    <div class="can-flight-line">
                        <span class="can-flight-point start"></span>
                        <span class="can-flight-point middle"></span>
                        <span class="can-flight-point end"></span>
                        <span class="can-plane">
                            <?= $icons['plane'] ?>
                        </span>
                    </div>

                    <div class="can-mini-stats">
                        <?php foreach ($stats as $stat): ?>
                            <div class="can-mini-stat">
                                <strong><?= Html::encode($stat['value']) ?></strong>
                                <span><?= Html::encode($stat['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </aside>

            </div>
        </section>

        <!-- Dashboard modules by role -->
        <section class="can-modules-grid">
            <?php foreach ($modules as $module): ?>
                <?php
                    $moduleUrl = $module['url'] === '#' ? '#' : Url::to($module['url']);
                ?>

                <a href="<?= Html::encode($moduleUrl) ?>" class="can-module-card <?= Html::encode($module['class']) ?>">
                    <div class="can-module-icon">
                        <?= $icons[$module['icon']] ?? $icons['request'] ?>
                    </div>

                    <h3><?= Html::encode($module['title']) ?></h3>

                    <p><?= Html::encode($module['text']) ?></p>
                </a>
            <?php endforeach; ?>
        </section>

        <?php if ($isAdmin): ?>
            <!--
                COCKPIT ADMINISTRATEUR : cette zone est volontairement rendue
                uniquement pour le rôle admin. Elle synthétise la charge réelle
                puis propose des accès directs aux écrans de traitement existants.
            -->
            <section class="admin-dashboard-zone" aria-labelledby="adminOperationalOverview">
                <div class="admin-section-heading">
                    <div>
                        <h2 id="adminOperationalOverview">Operational overview</h2>
                        <p>Priority workload and platform activity requiring administrator attention.</p>
                    </div>
                    <?php if ($adminGeneratedAt): ?>
                        <time datetime="<?= Html::encode($adminGeneratedAt . 'Z') ?>">
                            Snapshot <?= Html::encode(gmdate('d M Y H:i', strtotime($adminGeneratedAt . ' UTC'))) ?> UTC
                        </time>
                    <?php endif; ?>
                </div>

                <div class="admin-kpi-grid">
                    <?php foreach ($adminSummaryCards as $card): ?>
                        <article class="admin-kpi-card" style="--kpi-color: <?= Html::encode($card['color']) ?>;">
                            <!-- L'icône provient exclusivement du catalogue SVG statique défini dans cette vue. -->
                            <span class="admin-kpi-icon" aria-hidden="true"><?= $icons[$card['icon']] ?? $icons['report'] ?></span>
                            <strong><?= Html::encode((string) $card['value']) ?></strong>
                            <span><?= Html::encode($card['label']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="admin-insights-grid">
                    <article class="admin-insight-card">
                        <div class="admin-card-title">
                            <h3>Platform activity</h3>
                            <a href="<?= Html::encode(Url::to(['/mro-profile/index'])) ?>">Manage organizations →</a>
                        </div>
                        <div class="admin-activity-grid">
                            <div class="admin-activity-item">
                                <strong><?= Html::encode((string) ($adminActivity['requests7Days'] ?? 0)) ?></strong>
                                <span>Requests / 7 days</span>
                            </div>
                            <div class="admin-activity-item">
                                <strong><?= Html::encode((string) ($adminActivity['requests30Days'] ?? 0)) ?></strong>
                                <span>Requests / 30 days</span>
                            </div>
                            <div class="admin-activity-item">
                                <strong><?= Html::encode((string) ($adminActivity['activeOrganizations'] ?? 0)) ?></strong>
                                <span>Active organizations</span>
                            </div>
                            <div class="admin-activity-item">
                                <strong>
                                    <?= Html::encode((string) ($adminActivity['verifiedOrganizations'] ?? 0)) ?>
                                    / <?= Html::encode((string) ($adminActivity['activeOrganizations'] ?? 0)) ?>
                                </strong>
                                <span>Verified organizations</span>
                            </div>
                        </div>
                    </article>

                    <article class="admin-insight-card">
                        <div class="admin-card-title">
                            <h3>Active requests by priority</h3>
                            <a href="<?= Html::encode(Url::to(['/awaiting-request-response/index'])) ?>">Open requests →</a>
                        </div>
                        <?php
                        /*
                         * BARRES DE PRIORITÉ : la largeur est relative à la plus
                         * grande catégorie active et reste valide lorsque tous les
                         * compteurs valent zéro grâce au dénominateur minimum de 1.
                         */
                        $priorityLabels = ['aog' => 'AOG', 'urgent' => 'Urgent', 'routine' => 'Routine'];
                        $priorityColors = ['aog' => '#dc2626', 'urgent' => '#d97706', 'routine' => '#0284c7'];
                        $priorityMaximum = max(1, ...array_map('intval', array_values($adminPriorities ?: [0])));
                        ?>
                        <?php foreach ($priorityLabels as $priorityKey => $priorityLabel): ?>
                            <?php
                            $priorityTotal = (int) ($adminPriorities[$priorityKey] ?? 0);
                            $priorityWidth = $priorityTotal === 0 ? 0 : max(4, ($priorityTotal / $priorityMaximum) * 100);
                            ?>
                            <div class="admin-priority-row">
                                <span><?= Html::encode($priorityLabel) ?></span>
                                <span class="admin-priority-track">
                                    <span class="admin-priority-fill" style="width: <?= Html::encode(number_format($priorityWidth, 2, '.', '')) ?>%; --priority-color: <?= Html::encode($priorityColors[$priorityKey]) ?>;"></span>
                                </span>
                                <strong><?= Html::encode((string) $priorityTotal) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </article>
                </div>

                <div class="admin-queues-grid">
                    <article class="admin-queue-card">
                        <div class="admin-card-title">
                            <h3>AOG response queue</h3>
                            <a href="<?= Html::encode(Url::to(['/awaiting-request-response/index'])) ?>">Review →</a>
                        </div>
                        <?php if (!$adminAogQueue): ?>
                            <div class="admin-empty-state">No active AOG request.</div>
                        <?php else: ?>
                            <ul class="admin-queue-list">
                                <?php foreach ($adminAogQueue as $request): ?>
                                    <?php $deadline = $formatAogDeadline($request['response_due_at_utc'] ?? null); ?>
                                    <li class="admin-queue-item">
                                        <div class="admin-queue-copy">
                                            <strong>Request #<?= Html::encode((string) $request['request_id']) ?></strong>
                                            <span><?= Html::encode(ucwords(str_replace('_', ' ', (string) $request['status']))) ?></span>
                                        </div>
                                        <span class="admin-queue-badge <?= $deadline['danger'] ? 'danger' : '' ?>">
                                            <?= Html::encode($deadline['label']) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>

                    <article class="admin-queue-card">
                        <div class="admin-card-title">
                            <h3>Support to process</h3>
                            <a href="<?= Html::encode(Url::to(['/admin-support-tickets/index'])) ?>">All tickets →</a>
                        </div>
                        <?php if (!$adminSupportQueue): ?>
                            <div class="admin-empty-state">No support ticket awaiting action.</div>
                        <?php else: ?>
                            <ul class="admin-queue-list">
                                <?php foreach ($adminSupportQueue as $ticket): ?>
                                    <li class="admin-queue-item">
                                        <div class="admin-queue-copy">
                                            <strong>
                                                <a href="<?= Html::encode(Url::to(['/admin-support-tickets/view', 'id' => UrlIdHelper::encode((int) $ticket['id'])])) ?>">
                                                    <?= Html::encode((string) $ticket['subject']) ?>
                                                </a>
                                            </strong>
                                            <span><?= Html::encode(Yii::$app->formatter->asDatetime($ticket['created_at'], 'php:d M Y H:i')) ?></span>
                                        </div>
                                        <span class="admin-queue-badge <?= ($ticket['priority'] ?? '') === 'urgent' ? 'danger' : '' ?>">
                                            <?= Html::encode(ucwords(str_replace('_', ' ', (string) $ticket['status']))) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>

                    <article class="admin-queue-card">
                        <div class="admin-card-title">
                            <h3>Oldest open disputes</h3>
                            <a href="<?= Html::encode(Url::to(['/admin-disputes/index'])) ?>">All disputes →</a>
                        </div>
                        <?php if (!$adminDisputeQueue): ?>
                            <div class="admin-empty-state">No open dispute.</div>
                        <?php else: ?>
                            <ul class="admin-queue-list">
                                <?php foreach ($adminDisputeQueue as $dispute): ?>
                                    <li class="admin-queue-item">
                                        <div class="admin-queue-copy">
                                            <strong>Dispute #<?= Html::encode((string) $dispute['dispute_id']) ?></strong>
                                            <span>
                                                Request #<?= Html::encode((string) ($dispute['request_id'] ?: 'N/A')) ?> ·
                                                <?= Html::encode(Yii::$app->formatter->asDatetime($dispute['timestamp'], 'php:d M Y H:i')) ?>
                                            </span>
                                        </div>
                                        <span class="admin-queue-badge"><?= Html::encode(strtoupper((string) $dispute['created_by'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<script>
/*
 * Live Local and UTC date/time.
 * Local time uses the user's browser timezone.
 * UTC time is also called Zulu Time in aviation operations.
 *
 * City / country detection:
 * - First fallback: browser timezone, for example Africa/Tunis => Tunis / Africa.
 * - Optional IP geolocation: shows approximate city and country if the external service is available.
 * - For exact GPS location, you need browser geolocation permission plus a reverse geocoding service.
 */
(function () {
    const localDateTimeElement = document.getElementById('canLocalDateTime');
    const localTimeZoneElement = document.getElementById('canLocalTimeZone');
    const localLocationElement = document.getElementById('canLocalLocation');
    const utcDateTimeElement = document.getElementById('canUtcDateTime');

    if (!localDateTimeElement || !localTimeZoneElement || !utcDateTimeElement) {
        return;
    }

    const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local timezone';

    function cleanTimeZonePart(value) {
        return String(value || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
    }

    function getLocationFromTimeZone(timeZone) {
        if (!timeZone || timeZone.indexOf('/') === -1) {
            return 'Location based on browser timezone';
        }

        const parts = timeZone.split('/');
        const city = cleanTimeZonePart(parts[parts.length - 1]);
        const region = cleanTimeZonePart(parts[0]);

        return city + ' / ' + region;
    }

    localTimeZoneElement.textContent = 'Time zone: ' + userTimeZone;

    if (localLocationElement) {
        localLocationElement.textContent = getLocationFromTimeZone(userTimeZone);
    }

    const clientLocationUrl = <?= Json::htmlEncode($clientLocationUrl) ?>;

    function formatLocalDateTime(date) {
        return new Intl.DateTimeFormat('en-GB', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(date);
    }

    function formatUtcDateTime(date) {
        return new Intl.DateTimeFormat('en-GB', {
            timeZone: 'UTC',
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(date) + ' UTC';
    }

    function updateClocks() {
        const now = new Date();

        localDateTimeElement.textContent = formatLocalDateTime(now);
        utcDateTimeElement.textContent = formatUtcDateTime(now);
    }

    /*
     * Approximate city/country by IP address.
     * The browser calls the local Yii action to avoid CORS issues.
     */
    function detectApproximateCityCountry() {
        if (!localLocationElement || !window.fetch) {
            return;
        }

        const controller = window.AbortController ? new AbortController() : null;
        const timeoutId = controller ? setTimeout(function () {
            controller.abort();
        }, 3500) : null;

        fetch(clientLocationUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            cache: 'no-store',
            signal: controller ? controller.signal : undefined
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Location service unavailable');
                }

                return response.json();
            })
            .then(function (data) {
                if (!data || data.success === false) {
                    return;
                }

                const city = data.city ? data.city : '';
                const country = data.country ? data.country : '';
                const timezone = data.timezone ? data.timezone : userTimeZone;

                if (city || country) {
                    localLocationElement.textContent = [city, country].filter(Boolean).join(', ');
                }

                if (timezone) {
                    localTimeZoneElement.textContent = 'Time zone: ' + timezone;
                }
            })
            .catch(function () {
                /*
                 * Keep the timezone fallback if IP geolocation fails.
                 * No visual error is shown because the clock itself still works.
                 */
            })
            .finally(function () {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
            });
    }

    updateClocks();
    setInterval(updateClocks, 1000);
    detectApproximateCityCountry();
})();
</script>
