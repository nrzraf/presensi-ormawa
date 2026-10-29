<?php
/**
 * Layout utama - sidebar berikon + top bar + komponen kartu.
 */
require_once __DIR__ . '/helpers.php';
$u = current_user();
$r = $_GET['r'] ?? '';
$flash = take_flash();

function nav_item(string $href, string $ic, string $label, string $active, string $key): void {
    $cls = $active === $key ? 'nav-a active' : 'nav-a';
    echo '<a class="' . $cls . '" href="' . $href . '">' . icon($ic) . '<span>' . $label . '</span></a>';
}

$unread = 0;
if ($u) {
    try { $unread = (int) q_val('SELECT COUNT(*) FROM notification WHERE member_id=? AND dibaca=0', [$u['id']]); } catch (Throwable $e) { $unread = 0; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#80011F">
<title><?= e($title ?? APP_NAME) ?> - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{
  --brand:#80011F;--brand-dark:#5E0116;--brand-soft:#FCE8EC;--brand-tint:#F8F2F4;
  --ink:#16181D;--muted:#6B7280;--line:#E9ECF1;--bg:#F6F7F9;--surface:#FFFFFF;
  --ok:#047857;--warn:#C2410C;--danger:#BE123C;--info:#0369A1;
  --r-sm:8px;--r-md:10px;--r-lg:14px;--r-pill:999px;
  --sh:0 1px 2px rgba(16,24,40,.05);--sh2:0 12px 32px rgba(128,1,31,.16);
  --sidebar:258px;
  /* alias agar halaman lama tetap konsisten */
  --border:var(--line);--tertiary:var(--brand);--tint:var(--bg);--neutral:var(--bg);
  --primary:var(--ink);--secondary:var(--muted);--success:var(--ok);--warning:var(--warn);
}
*{margin:0;padding:0;box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{font-family:Inter,system-ui,sans-serif;background:var(--bg);color:var(--ink);font-size:15px;line-height:1.55;-webkit-font-smoothing:antialiased;overflow-x:hidden}
.mono{font-family:'JetBrains Mono',monospace}
a{color:var(--brand);text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}
img{max-width:100%;height:auto}
.i{flex:0 0 auto}

.layout{display:grid;grid-template-columns:var(--sidebar) minmax(0,1fr);min-height:100vh}
.side{background:var(--surface);border-right:1px solid var(--line);padding:18px 14px;display:flex;flex-direction:column;gap:3px;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:40}
.brand{display:flex;align-items:center;gap:11px;padding:4px 6px 18px;margin-bottom:6px;border-bottom:1px solid var(--line)}
.logo{width:40px;height:40px;object-fit:contain;flex:0 0 auto;display:block}
.brand b{font-size:15px;font-weight:800;letter-spacing:-.01em;line-height:1.15;display:block}
.brand small{color:var(--muted);font-size:11px;display:block}
.nav-a{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:var(--r-md);color:var(--muted);font-size:14px;font-weight:600;transition:.14s}
.nav-a:hover{background:var(--bg);color:var(--ink)}
.nav-a.active{background:var(--brand);color:#fff}
.nav-sec{font-size:10.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#9CA3AF;padding:14px 12px 5px}
.side-foot{margin-top:auto;padding-top:14px;border-top:1px solid var(--line)}
.prof{display:flex;align-items:center;gap:11px;padding:8px 6px;border-radius:var(--r-md);width:100%;text-align:left}
.prof:hover{background:var(--bg)}
.prof .txt{min-width:0;flex:1}
.prof b{font-size:13.5px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prof small{color:var(--muted);font-size:11.5px;display:block}

.topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;background:var(--surface);border-bottom:1px solid var(--line);padding:0 24px;height:66px;position:sticky;top:0;z-index:30}
.crumbs{display:flex;align-items:center;gap:8px;font-size:14px;min-width:0}
.crumbs .sep{color:#C4C9D2}
.crumbs .cur{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top-actions{display:flex;align-items:center;gap:8px}
.iconbtn{width:40px;height:40px;border-radius:var(--r-md);display:grid;place-items:center;color:var(--muted);position:relative}
.iconbtn:hover{background:var(--bg);color:var(--ink)}
.iconbtn .dot{position:absolute;top:6px;right:6px;min-width:16px;height:16px;padding:0 4px;border-radius:var(--r-pill);background:var(--brand);color:#fff;font-size:10px;font-weight:800;display:grid;place-items:center;border:2px solid var(--surface)}
.avatar{width:34px;height:34px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:13px;font-weight:800;flex:0 0 auto}
.menu-btn{width:42px;height:42px;border-radius:var(--r-md);display:grid;place-items:center;background:var(--surface);border:1px solid var(--line)}
.bars,.bars:before,.bars:after{display:block;width:18px;height:2px;background:var(--ink);border-radius:2px;content:""}
.bars{position:relative}
.bars:before{position:absolute;top:-6px}
.bars:after{position:absolute;top:6px}
.appbar{display:none}
.backdrop{display:none}
@media(max-width:1023px){
  .layout{grid-template-columns:minmax(0,1fr)}
  .appbar{display:flex;align-items:center;gap:12px;background:var(--surface);border-bottom:1px solid var(--line);padding:10px 14px;padding-top:calc(10px + env(safe-area-inset-top))}
  .appbar .logo{width:32px;height:32px}
  .side{position:fixed;top:0;left:0;height:100dvh;width:min(84vw,300px);transform:translateX(-104%);transition:transform .24s ease;box-shadow:var(--sh2);padding-top:calc(18px + env(safe-area-inset-top))}
  body.nav-open .side{transform:translateX(0)}
  body.nav-open .backdrop{display:block;position:fixed;inset:0;background:rgba(16,24,40,.45);z-index:35}
  .side .close-x{margin-left:auto;width:36px;height:36px;border-radius:var(--r-md);display:grid;place-items:center;color:var(--muted)}
  .topbar{padding:0 14px;height:auto;padding-top:12px;padding-bottom:12px}
  .topbar .top-actions{display:none}
}
@media(min-width:1024px){ .side .close-x{display:none} }

.main{padding:30px 34px;width:100%;min-width:0}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:20px;flex-wrap:wrap}
.page-title{font-size:25px;font-weight:800;letter-spacing:-.025em}
.page-sub{color:var(--muted);font-size:13.5px;margin-top:4px}

.grid{display:grid;gap:18px;min-width:0}
.col-2{grid-template-columns:minmax(0,1.7fr) minmax(0,1fr)}
.col-2e{grid-template-columns:repeat(2,minmax(0,1fr))}
.col-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.col-4{grid-template-columns:repeat(4,minmax(0,1fr))}
@media(max-width:1024px){
  .main{padding:22px 20px}
  .col-2,.col-2e{grid-template-columns:minmax(0,1fr)}
  .col-3{grid-template-columns:repeat(2,minmax(0,1fr))}
  .col-4{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:640px){
  .main{padding:14px 12px 18px}
  .col-3,.col-4{grid-template-columns:minmax(0,1fr)}
}

.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:20px;box-shadow:var(--sh);min-width:0}
.card+.card{margin-top:16px}
.grid>.card{margin-top:0}
.card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.card-head h3{font-size:15.5px;font-weight:700}
.link-more{color:var(--brand);font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:4px}

.stat{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:18px;box-shadow:var(--sh);min-width:0;display:flex;flex-direction:column}
.stat .top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.stat .sic{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;flex:0 0 auto}
.sic.blue{background:#E0F2FE;color:#0369A1}
.sic.amber{background:#FEF3C7;color:#B45309}
.sic.violet{background:#EDE9FE;color:#6D28D9}
.sic.green{background:#D1FAE5;color:#047857}
.sic.rose{background:var(--brand-soft);color:var(--brand)}
.sic.gray{background:#F1F5F9;color:#475569}
.stat .trend{font-size:11.5px;font-weight:700;color:var(--ok);display:inline-flex;align-items:center;gap:4px;text-align:right;min-width:0;flex-wrap:wrap;justify-content:flex-end}
.stat .trend.mute{color:var(--muted)}
.stat .val{font-size:28px;font-weight:800;letter-spacing:-.03em;line-height:1;margin-top:auto}
.stat .lab{color:var(--muted);font-size:12.5px;font-weight:600;margin-top:7px}

.table-scroll{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.table-scroll table{min-width:560px}
table{width:100%;border-collapse:collapse}
th{font-size:10.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);text-align:left;padding:10px 12px;border-bottom:1px solid var(--line);white-space:nowrap}
td{font-size:13.5px;padding:13px 12px;border-bottom:1px solid var(--line);vertical-align:middle}
tr:last-child td{border-bottom:none}
th.num,td.num{text-align:right}
.muted{color:var(--muted)}
@media(max-width:640px){ th,td{padding:9px 8px;font-size:12.5px} }

.evrow{display:flex;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid var(--line)}
.evrow:last-child{border-bottom:none}
.datebox{width:58px;min-width:58px;background:var(--brand-soft);border-radius:var(--r-md);text-align:center;padding:8px 4px}
.datebox .d{font-size:19px;font-weight:800;color:var(--brand);line-height:1.05}
.datebox .m{font-size:10.5px;font-weight:800;color:var(--brand);text-transform:uppercase;letter-spacing:.05em}
.evrow .meta{min-width:0;flex:1}
.evrow .meta b{font-size:14.5px;font-weight:700;display:block}
.evrow .loc{color:var(--muted);font-size:12.5px;margin-top:3px;display:flex;align-items:center;gap:5px}
.evrow .right{display:flex;align-items:center;gap:12px;flex:0 0 auto}

.evgrid{display:grid;gap:18px;grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1024px){.evgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.evgrid{grid-template-columns:minmax(0,1fr)}}
.evcard{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh);display:flex;flex-direction:column}
.evcard .cover{position:relative;height:118px;background:var(--brand-tint);display:grid;place-items:center}
.evcard .cover .big{font-size:52px;font-weight:800;color:var(--brand);opacity:.16;line-height:1}
.evcard .cover .pill{position:absolute;bottom:10px;left:12px;background:#fff;color:var(--brand);font-size:11px;font-weight:800;padding:4px 11px;border-radius:var(--r-pill);box-shadow:var(--sh)}
.evcard .st{position:absolute;top:10px;right:10px}
.evcard .body{padding:16px;display:flex;flex-direction:column;gap:9px;flex:1}
.evcard .body h4{font-size:15px;font-weight:700;line-height:1.35}
.evcard .line{display:flex;align-items:center;gap:7px;color:var(--muted);font-size:12.5px}
.evcard .acts{display:flex;gap:9px;margin-top:auto;padding-top:6px}

.badge{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:800;padding:5px 10px;border-radius:var(--r-pill);text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
.b-present{background:#D1FAE5;color:#047857}
.b-absent{background:#FEE2E2;color:#B91C1C}
.b-fallback{background:#FFEDD5;color:#C2410C}
.b-info{background:#E0F2FE;color:#0369A1}
.b-muted{background:#F1F5F9;color:#475569}
.b-warn{background:#FEF3C7;color:#B45309}

.act{display:flex;gap:12px;padding:11px 0;border-bottom:1px solid var(--line)}
.act:last-child{border-bottom:none}
.act .aic{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;flex:0 0 auto}
.act .txt{min-width:0;font-size:13.5px;line-height:1.5}
.act .time{color:var(--muted);font-size:11.5px;margin-top:2px}

.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:20px}
.search{position:relative;flex:1 1 240px;min-width:200px}
.search .i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted)}
.search input{width:100%;font-family:inherit;font-size:14.5px;border:1px solid var(--line);background:var(--surface);border-radius:var(--r-pill);padding:12px 16px 12px 42px;outline:none}
.search input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(128,1,31,.12)}
.pills{display:flex;gap:8px;flex-wrap:wrap}
.pill{font-size:12.5px;font-weight:700;padding:9px 15px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--line);color:var(--muted);white-space:nowrap;transition:.14s}
.pill:hover{color:var(--brand);border-color:var(--brand)}
.pill.active{background:var(--brand);border-color:var(--brand);color:#fff}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13.5px;font-weight:700;padding:0 16px;min-height:42px;border-radius:var(--r-md);transition:.14s;white-space:nowrap}
.btn-primary{background:var(--brand);color:#fff}.btn-primary:hover{background:var(--brand-dark)}
.btn-ghost{background:var(--surface);color:var(--ink);border:1px solid var(--line)}.btn-ghost:hover{background:var(--bg)}
.btn-danger{background:var(--danger);color:#fff}.btn-danger:hover{filter:brightness(.93)}
.btn-sm{min-height:36px;padding:0 13px;font-size:12.5px}
.btn-block{width:100%}
.btn[disabled]{opacity:.5;cursor:not-allowed}
@media(max-width:480px){.btn{width:100%}.btn-sm{width:auto}}

label{display:block;font-size:12.5px;font-weight:700;margin-bottom:6px}
input,select,textarea{width:100%;font-family:inherit;font-size:15px;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:var(--r-sm);padding:11px 12px;outline:none;transition:.14s}
input:focus,select:focus,textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(128,1,31,.13)}
.field{margin-bottom:14px}
.hint{font-size:12px;color:var(--muted);margin-top:5px}

.flash{padding:12px 14px;border-radius:var(--r-md);font-size:13.5px;font-weight:600;margin-bottom:16px;display:flex;gap:9px;align-items:center}
.flash.ok{background:#ECFDF5;border:1px solid #A7F3D0;color:var(--ok)}
.flash.error{background:#FEF2F2;border:1px solid #FECACA;color:var(--danger)}
.flash.info{background:#EFF6FF;border:1px solid #BFDBFE;color:var(--info)}
.callout{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:var(--muted);background:var(--bg);border:1px solid var(--line);border-radius:var(--r-md);padding:12px 14px}
.chip{font-size:11px;font-weight:700;color:var(--muted);background:#F1F5F9;padding:4px 10px;border-radius:var(--r-pill);white-space:nowrap}
.bar{height:8px;background:#F1F5F9;border-radius:var(--r-pill);overflow:hidden}.bar>i{display:block;height:100%;background:var(--brand);border-radius:var(--r-pill)}
.empty{text-align:center;color:var(--muted);padding:36px 10px;font-size:14px}
.inline{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.sub{color:var(--muted);font-size:12.5px;margin-top:2px}
.foot{text-align:center;color:var(--muted);font-size:11.5px;padding:24px 0 6px}

/* Mobile compact */
@media(max-width:640px){
  body{font-size:14px}
  .main{padding:14px 12px 18px}
  .appbar{padding:9px 12px;padding-top:calc(9px + env(safe-area-inset-top))}
  .appbar .logo{width:28px;height:28px}
  .menu-btn{width:38px;height:38px}
  .iconbtn{width:36px;height:36px}
  .topbar{padding:10px 12px;gap:10px}
  .crumbs{font-size:12.5px}
  .page-head{margin-bottom:14px;gap:10px}
  .page-title{font-size:19px;letter-spacing:-.02em}
  .page-sub{font-size:12px;margin-top:2px}
  .grid{gap:11px}
  .card{padding:14px;border-radius:12px}
  .card+.card{margin-top:11px}
  .card-head{margin-bottom:12px}
  .card-head h3{font-size:14.5px}
  .stat{padding:14px}
  .stat .top{margin-bottom:10px}
  .stat .sic{width:36px;height:36px;border-radius:10px}
  .stat .val{font-size:23px}
  .stat .lab{font-size:11.5px;margin-top:5px}
  .table-scroll table{min-width:520px}
  th,td{padding:9px 8px;font-size:12px}
  .evgrid{gap:12px}
  .evcard .cover{height:96px}
  .evcard .body{padding:13px;gap:7px}
  .evcard .body h4{font-size:14px}
  .toolbar{gap:9px;margin-bottom:14px}
  .search{flex:1 1 100%;min-width:0}
  .search input{padding:10px 14px 10px 38px;font-size:14px}
  .pill{font-size:12px;padding:8px 13px}
  .btn{min-height:40px;font-size:13px}
  .btn-sm{min-height:34px;font-size:12px}
  .evrow{gap:11px;padding:11px 0;align-items:flex-start}
  .evrow .right{flex-direction:column;align-items:flex-end;gap:5px}
  .evrow .meta b{font-size:13.5px}
  .evrow .loc{font-size:12px}
  .datebox{width:50px;min-width:50px;padding:6px 3px}
  .datebox .d{font-size:17px}
  .act{padding:9px 0}
  .act .txt{font-size:13px}
  .flash{padding:10px 12px;font-size:12.5px;margin-bottom:12px}
  .empty{padding:26px 8px;font-size:13px}
  input,select,textarea{font-size:15px;padding:10px 11px}
  .foot{padding:18px 0 4px;font-size:11px}
}
@media(max-width:380px){
  .main{padding:12px 10px 16px}
  .page-title{font-size:18px}
  .stat .val{font-size:21px}
  .pill{font-size:11.5px;padding:7px 11px}
}
</style>
</head>
<body>

<div class="appbar">
  <button class="menu-btn" onclick="toggleNav()" aria-label="Menu"><span class="bars"></span></button>
  <img class="logo" src="index.php?r=logo" alt="Logo Universitas Bakrie">
  <span style="flex:1"></span>
  <?php if ($u): ?>
    <a class="iconbtn" href="index.php?r=notifications" aria-label="Notifikasi"><?= icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></a>
    <div class="avatar"><?= strtoupper(substr($u['nama'],0,1)) ?></div>
  <?php endif; ?>
</div>
<div class="backdrop" onclick="toggleNav()"></div>

<div class="layout">
  <aside class="side">
    <div class="brand">
      <img class="logo" src="index.php?r=logo" alt="Logo Universitas Bakrie">
      <div style="min-width:0"><b><?= APP_NAME ?></b></div>
      <button class="close-x" onclick="toggleNav()" aria-label="Tutup">&#215;</button>
    </div>

    <?php if ($u): ?>
      <?php nav_item('index.php?r=dashboard', 'grid', 'Dashboard', $r, 'dashboard'); ?>

      <?php if (has_role('anggota')): ?>
        <?php nav_item('index.php?r=events', 'star', 'Daftar Acara', $r, 'events'); ?>
        <?php nav_item('index.php?r=scan', 'clip', 'Scan Presensi', $r, 'scan'); ?>
        <?php nav_item('index.php?r=notifications', 'bell', 'Notifikasi', $r, 'notifications'); ?>
        <?php nav_item('index.php?r=profile', 'gear', 'Pengaturan Profil', $r, 'profile'); ?>
      <?php else: ?>
        <?php nav_item('index.php?r=events', 'calendar', 'Manajemen Acara', $r, 'events'); ?>
        <?php if (has_role('panitia','admin')): ?>
          <?php nav_item('index.php?r=qr_acara', 'clip', 'QR Presensi', $r, 'qr_acara'); ?>
        <?php endif; ?>
        <?php if (has_role('pembina','admin')): ?>
          <?php nav_item('index.php?r=proposals', 'file', 'Proposal', $r, 'proposals'); ?>
        <?php endif; ?>
        <?php if (has_role('panitia','admin')): ?>
          <?php nav_item('index.php?r=members', 'users', 'Peserta', $r, 'members'); ?>
        <?php endif; ?>
        <?php nav_item('index.php?r=report', 'chart', 'Laporan', $r, 'report'); ?>
        <?php nav_item('index.php?r=notifications', 'bell', 'Notifikasi', $r, 'notifications'); ?>

        <?php if (has_role('admin')): ?>
          <div class="nav-sec">Administrasi</div>
          <?php nav_item('index.php?r=organisasi', 'building', 'Organisasi', $r, 'organisasi'); ?>
          <?php nav_item('index.php?r=audit', 'shield', 'Audit Log', $r, 'audit'); ?>
          <?php nav_item('index.php?r=mail_log', 'file', 'Email Log', $r, 'mail_log'); ?>
        <?php endif; ?>

        <?php nav_item('index.php?r=profile', 'gear', 'Pengaturan Profil', $r, 'profile'); ?>
      <?php endif; ?>

      <div class="side-foot">
        <a class="prof" href="index.php?r=profile">
          <span class="avatar"><?= strtoupper(substr($u['nama'],0,1)) ?></span>
          <span class="txt"><b><?= e($u['nama']) ?></b><small><?= ucfirst(e($u['role'])) ?></small></span>
          <?= icon('chev', 16) ?>
        </a>
        <a class="nav-a" href="index.php?r=logout"><?= icon('exit') ?><span>Keluar</span></a>
      </div>
    <?php else: ?>
      <?php nav_item('index.php?r=login', 'user', 'Masuk', $r, 'login'); ?>
    <?php endif; ?>
  </aside>

  <main>
    <div class="topbar">
      <div class="crumbs">
        <a class="muted" href="index.php?r=dashboard">Beranda</a><span class="sep">/</span><span class="cur"><?= e($title ?? '') ?></span>
      </div>
      <div class="top-actions">
        <?php if ($u): ?>
          <a class="iconbtn" href="index.php?r=notifications" aria-label="Notifikasi"><?= icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></a>
          <a class="iconbtn" href="index.php?r=profile" aria-label="Profil"><span class="avatar"><?= strtoupper(substr($u['nama'],0,1)) ?></span></a>
        <?php endif; ?>
      </div>
    </div>

    <div class="main">
      <div class="page-head">
        <div style="min-width:0">
          <div class="page-title"><?= e($heading ?? ($title ?? '')) ?></div>
          <?php if (!empty($subtitle)): ?><div class="page-sub"><?= $subtitle ?></div><?php endif; ?>
        </div>
        <?php if (!empty($actions)): ?><div style="display:flex;gap:10px;flex-wrap:wrap"><?= $actions ?></div><?php endif; ?>
      </div>

      <?php foreach ($flash as $f): ?>
        <div class="flash <?= $f['type'] === 'error' ? 'error' : ($f['type'] === 'ok' ? 'ok' : 'info') ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <?= $content ?? '' ?>
      <p class="foot">Capstone Kelompok 7 · Universitas Bakrie 2026</p>
    </div>
  </main>
</div>

<script>
function toggleNav(){ document.body.classList.toggle('nav-open'); }
document.addEventListener('keydown', e => { if(e.key === 'Escape') document.body.classList.remove('nav-open'); });
document.querySelectorAll('.side .nav-a, .side .prof').forEach(a => a.addEventListener('click', () => document.body.classList.remove('nav-open')));
</script>
</body>
</html>
