<?php
/**
 * Router utama.
 * Semua request lewat index.php?r=<route>
 */
require_once __DIR__ . '/helpers.php';

$r = $_GET['r'] ?? 'dashboard';

// Route yang butuh login (default semua kecuali login/logout/install)
$public = ['login', 'logout', 'install', 'cron', 'logo'];
if (!in_array($r, $public, true)) {
    require_login();
}

switch ($r) {
    case 'login':   require __DIR__ . '/pages/login.php';   break;
    case 'logout':  require __DIR__ . '/pages/logout.php';  break;
    case 'logo':    require __DIR__ . '/pages/logo.php';    break;
    case 'install': require __DIR__ . '/install.php';       break;
    case 'cron':    require __DIR__ . '/cron_reminder.php'; break;

    case 'dashboard': require __DIR__ . '/pages/dashboard.php'; break;
    case 'events':    require __DIR__ . '/pages/events.php';    break;
    case 'event':     require __DIR__ . '/pages/event_detail.php'; break;
    case 'event_form':require __DIR__ . '/pages/event_form.php'; break;
    case 'event_save':require __DIR__ . '/pages/event_save.php'; break;
    case 'rsvp':      require __DIR__ . '/pages/rsvp.php';      break;
    case 'myqr':      require __DIR__ . '/pages/myqr.php';      break;

    case 'scan':        require __DIR__ . '/pages/scan.php';        break;
    case 'scan_api':    require __DIR__ . '/pages/scan_api.php';    break;

    case 'report':    require __DIR__ . '/pages/report.php';    break;
    case 'export':    require __DIR__ . '/pages/export.php';    break;

    case 'members':      require __DIR__ . '/pages/members.php';      break;
    case 'member_form':  require __DIR__ . '/pages/member_form.php';  break;
    case 'member_save':  require __DIR__ . '/pages/member_save.php';  break;
    case 'audit':        require __DIR__ . '/pages/audit.php';        break;

    case 'notifications': require __DIR__ . '/pages/notifications.php'; break;
    case 'profile':       require __DIR__ . '/pages/profile.php';       break;
    case 'organisasi':    require __DIR__ . '/pages/organisasi.php';    break;

    case 'proposal_form':   require __DIR__ . '/pages/proposal_form.php';   break;
    case 'proposal_save':   require __DIR__ . '/pages/proposal_save.php';   break;
    case 'proposals':       require __DIR__ . '/pages/proposals.php';       break;
    case 'proposal_review': require __DIR__ . '/pages/proposal_review.php'; break;
    case 'proposal_decide': require __DIR__ . '/pages/proposal_decide.php'; break;
    case 'proposal_file':   require __DIR__ . '/pages/proposal_file.php';   break;

    case 'mail_log': require __DIR__ . '/pages/mail_log.php'; break;

    default:
        http_response_code(404);
        $title = '404';
        $content = '<div class="card"><div class="empty">Halaman tidak ditemukan.</div></div>';
        require __DIR__ . '/layout.php';
}
