<?php
/** Layout utama - navigasi responsif (desktop sidebar / mobile drawer). */
require_once __DIR__ . '/helpers.php';
$u = current_user();
$r = $_GET['r'] ?? '';
$flash = take_flash();

function nav_item(string $href, string $icon, string $label, string $active, string $key): void {
    $cls = $active === $key ? 'nav-a active' : 'nav-a';
    echo '<a class="' . $cls . '" href="' . $href . '">' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0F766E">
<title><?= e($title ?? APP_NAME) ?> - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#0F172A;--secondary:#64748B;--tertiary:#0F766E;--neutral:#F8FAFC;--surface:#FFFFFF;
  --border:#E2E8F0;--success:#047857;--warning:#B45309;--danger:#BE123C;--info:#0369A1;--tint:#F1F5F9;
  --r-sm:6px;--r-md:10px;--r-lg:16px;--r-pill:999px;--sh2:0 8px 24px rgba(15,23,42,.16);
  --sidebar:250px;
}
*{margin:0;padding:0;box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{font-family:Inter,system-ui,sans-serif;background:var(--tint);color:var(--primary);font-size:15px;line-height:1.55;-webkit-font-smoothing:antialiased;overflow-x:hidden}
.mono{font-family:'JetBrains Mono',monospace}
a{color:var(--tertiary);text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none}
img{max-width:100%;height:auto}

.layout{display:grid;grid-template-columns:var(--sidebar) minmax(0,1fr);min-height:100vh}
.side{background:var(--surface);border-right:1px solid var(--border);padding:18px 14px;display:flex;flex-direction:column;gap:4px;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:40}
.main{padding:clamp(16px,2.4vw,30px);width:100%;max-width:1280px;min-width:0}

.brand{display:flex;align-items:center;gap:10px;padding:6px 8px 16px;border-bottom:1px solid var(--border);margin-bottom:8px}
.logo{width:38px;height:38px;object-fit:contain;flex:0 0 auto;display:block}
.brand b{font-size:14px;line-height:1.2;display:block}
.brand small{color:var(--secondary);font-size:11px}

.nav-a{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:var(--r-md);color:var(--secondary);font-size:14px;font-weight:600;transition:.15s}
.nav-a:hover{background:var(--tint);color:var(--primary)}
.nav-a.active{background:var(--tertiary);color:#fff}
.nav-a .ic{width:20px;text-align:center;font-size:15px;flex:0 0 auto}
.nav-sec{font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;padding:12px 12px 5px}

.appbar{display:none}
.menu-btn{width:44px;height:44px;border-radius:var(--r-md);display:grid;place-items:center;background:var(--surface);border:1px solid var(--border)}
.bars,.bars:before,.bars:after{display:block;width:18px;height:2px;background:var(--primary);border-radius:2px;content:""}
.bars{position:relative}
.bars:before{position:absolute;top:-6px}
.bars:after{position:absolute;top:6px}
.backdrop{display:none}

@media(max-width:1023px){
  .layout{grid-template-columns:minmax(0,1fr)}
  .appbar{display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:30;background:var(--surface);border-bottom:1px solid var(--border);padding:10px 14px;padding-top:calc(10px + env(safe-area-inset-top))}
  .appbar .logo{width:32px;height:32px}
  .appbar b{font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .appbar .spacer{flex:1}
  .side{position:fixed;top:0;left:0;height:100dvh;width:min(82vw,300px);transform:translateX(-104%);transition:transform .24s ease;box-shadow:var(--sh2);padding-top:calc(18px + env(safe-area-inset-top))}
  body.nav-open .side{transform:translateX(0)}
  body.nav-open .backdrop{display:block;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:35}
  .side .close-x{margin-left:auto;width:36px;height:36px;border-radius:var(--r-md);display:grid;place-items:center;color:var(--secondary);font-size:20px}
}
@media(min-width:1024px){ .side .close-x{display:none} }

.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:20px;flex-wrap:wrap}
.userchip{display:flex;align-items:center;gap:10px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-pill);padding:6px 14px 6px 6px;max-width:100%}
.avatar{width:30px;height:30px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;font-size:13px;font-weight:700;flex:0 0 auto}
.userchip b{font-size:13px}.userchip small{display:block;color:var(--secondary);font-size:11px;line-height:1.1}
.userchip .txt{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.page-title{font-size:clamp(19px,2.6vw,24px);font-weight:800;letter-spacing:-.02em}
.page-sub{color:var(--secondary);font-size:clamp(12px,1.5vw,13.5px);margin-top:3px}
h3{font-size:clamp(14.5px,1.9vw,16px);font-weight:700}
.grid{display:grid;gap:14px;min-width:0}
.col-2{grid-template-columns:minmax(0,1.7fr) minmax(0,1fr)}.col-2e{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
.col-3{grid-template-columns:repeat(3,minmax(0,1fr))}.col-4{grid-template-columns:repeat(4,minmax(0,1fr))}
@media(max-width:900px){.col-2,.col-2e,.col-3,.col-4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.col-2,.col-2e,.col-3,.col-4{grid-template-columns:minmax(0,1fr)}}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:clamp(16px,2.4vw,22px)}
.card+.card{margin-top:14px}
.grid>.card{margin-top:0}
.card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.sub{color:var(--secondary);font-size:clamp(11.5px,1.4vw,13px);margin-top:2px}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:16px}
.kpi .lab{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--secondary)}
.kpi .val{font-size:clamp(22px,3vw,29px);font-weight:800;letter-spacing:-.03em;margin-top:6px}
.kpi .delta{font-size:12px;font-weight:600;margin-top:4px;color:var(--secondary)}
.up{color:var(--success)}.down{color:var(--danger)}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13.5px;font-weight:600;padding:0 16px;min-height:44px;border-radius:var(--r-md);transition:.15s;white-space:nowrap}
.btn-primary{background:var(--tertiary);color:#fff}.btn-primary:hover{background:var(--primary)}
.btn-ghost{background:var(--surface);color:var(--primary);border:1px solid var(--border)}.btn-ghost:hover{background:var(--tint)}
.btn-danger{background:var(--danger);color:#fff}.btn-danger:hover{filter:brightness(.92)}
.btn-sm{min-height:38px;padding:0 13px;font-size:12.5px}
.btn-block{width:100%}
.btn[disabled]{opacity:.5;cursor:not-allowed}
@media(max-width:480px){.btn{width:100%}.btn-sm{width:auto}}

.table-scroll{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.table-scroll table{min-width:520px}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--secondary);text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);white-space:nowrap}
td{font-size:13.5px;padding:12px;border-bottom:1px solid var(--border)}
tr:last-child td{border-bottom:none}
th.num,td.num{text-align:right}
.muted{color:var(--secondary)}
@media(max-width:640px){ th,td{padding:9px 8px;font-size:12.5px} }

.badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 10px;border-radius:var(--r-pill);color:#fff;text-transform:uppercase;white-space:nowrap}
.b-present{background:var(--success)}.b-absent{background:var(--danger)}.b-fallback{background:var(--warning)}.b-info{background:var(--info)}.b-muted{background:var(--tint);color:var(--secondary)}

label{display:block;font-size:12px;font-weight:700;margin-bottom:6px}
input,select,textarea{width:100%;font-family:inherit;font-size:16px;color:var(--primary);background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:11px 12px;outline:none;transition:.15s}
input:focus,select:focus,textarea:focus{border-color:var(--tertiary);box-shadow:0 0 0 3px rgba(15,118,110,.15)}
.field{margin-bottom:14px}
.hint{font-size:12px;color:var(--secondary);margin-top:5px}

.flash{padding:12px 14px;border-radius:var(--r-md);font-size:13.5px;font-weight:600;margin-bottom:14px;display:flex;gap:9px;align-items:center}
.flash.ok{background:#ECFDF5;border:1px solid #A7F3D0;color:var(--success)}
.flash.error{background:#FEF2F2;border:1px solid #FECACA;color:var(--danger)}
.flash.info{background:#EFF6FF;border:1px solid #BFDBFE;color:var(--info)}
.callout{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:var(--secondary);background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-md);padding:12px 14px}
.chip{font-size:11px;font-weight:700;color:var(--secondary);background:var(--tint);padding:4px 10px;border-radius:var(--r-pill);white-space:nowrap}
.bar{height:8px;background:var(--tint);border-radius:var(--r-pill);overflow:hidden}.bar>i{display:block;height:100%;background:var(--tertiary);border-radius:var(--r-pill)}
.empty{text-align:center;color:var(--secondary);padding:36px 10px;font-size:14px}
.inline{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.foot{text-align:center;color:var(--secondary);font-size:11.5px;padding:24px 0 6px}
</style>
</head>
<body>

<div class="appbar">
  <button class="menu-btn" onclick="toggleNav()" aria-label="Menu"><span class="bars"></span></button>
  <img class="logo" src="index.php?r=logo" alt="Logo Universitas Bakrie">
  <b><?= e($title ?? APP_NAME) ?></b>
  <span class="spacer"></span>
  <?php if ($u): ?><div class="avatar"><?= strtoupper(substr($u['nama'],0,1)) ?></div><?php endif; ?>
</div>
<div class="backdrop" onclick="toggleNav()"></div>

<div class="layout">
  <aside class="side">
    <div class="brand">
      <img class="logo" src="index.php?r=logo" alt="Logo Universitas Bakrie">
      <div><b><?= APP_NAME ?></b></div>
      <button class="close-x" onclick="toggleNav()" aria-label="Tutup">&#215;</button>
    </div>

    <?php if ($u): ?>
      <?php nav_item('index.php?r=dashboard', '', 'Dasbor', $r, 'dashboard'); ?>
      <?php nav_item('index.php?r=events', '', 'Acara', $r, 'events'); ?>
      <?php nav_item('index.php?r=notifications', '', 'Notifikasi', $r, 'notifications'); ?>

      <?php if (has_role('panitia','admin')): ?>
        <div class="nav-sec">Panitia</div>
        <?php nav_item('index.php?r=event_form', '', 'Buat Acara', $r, 'event_form'); ?>
        <?php nav_item('index.php?r=scan', '', 'Scan Presensi', $r, 'scan'); ?>
      <?php endif; ?>

      <?php if (has_role('pembina','admin')): ?>
        <?php nav_item('index.php?r=proposals', '', 'Proposal', $r, 'proposals'); ?>
      <?php endif; ?>

      <?php if (has_role('panitia','pembina','admin')): ?>
        <?php nav_item('index.php?r=report', '', 'Laporan', $r, 'report'); ?>
      <?php endif; ?>

      <?php if (has_role('admin')): ?>
        <div class="nav-sec">Admin</div>
        <?php nav_item('index.php?r=members', '', 'Anggota', $r, 'members'); ?>
        <?php nav_item('index.php?r=mail_log', '', 'Email', $r, 'mail_log'); ?>
        <?php nav_item('index.php?r=audit', '', 'Audit', $r, 'audit'); ?>
      <?php endif; ?>

      <div style="flex:1"></div>
      <?php nav_item('index.php?r=logout', '', 'Keluar', $r, 'logout'); ?>
    <?php else: ?>
      <?php nav_item('index.php?r=login', '', 'Masuk', $r, 'login'); ?>
    <?php endif; ?>
  </aside>

  <main class="main">
    <div class="topbar">
      <div style="min-width:0">
        <div class="page-title"><?= e($title ?? '') ?></div>
        <?php if (!empty($subtitle)): ?><div class="page-sub"><?= $subtitle ?></div><?php endif; ?>
      </div>
      <?php if ($u): ?>
      <div class="userchip">
        <div class="avatar"><?= strtoupper(substr($u['nama'], 0, 1)) ?></div>
        <div class="txt"><b><?= e($u['nama']) ?></b><small><?= ucfirst(e($u['role'])) ?> · <?= e($u['divisi']) ?></small></div>
      </div>
      <?php endif; ?>
    </div>

    <?php foreach ($flash as $f): ?>
      <div class="flash <?= $f['type'] === 'error' ? 'error' : ($f['type'] === 'ok' ? 'ok' : 'info') ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <?= $content ?? '' ?>
    <p class="foot">Capstone Kelompok 7 · Universitas Bakrie 2026</p>
  </main>
</div>

<script>
function toggleNav(){ document.body.classList.toggle('nav-open'); }
document.addEventListener('keydown', e => { if(e.key === 'Escape') document.body.classList.remove('nav-open'); });
document.querySelectorAll('.side .nav-a').forEach(a => a.addEventListener('click', () => document.body.classList.remove('nav-open')));
</script>
</body>
</html>
