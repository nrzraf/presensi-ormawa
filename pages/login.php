<?php
/** Login - UC-01, FR-01 */
if (is_logged_in()) redirect('index.php?r=dashboard');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$ok, $msg] = login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($ok) redirect('index.php?r=dashboard');
    $err = $msg;
}
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - <?= APP_NAME ?></title>
<meta name="theme-color" content="#580018">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,-apple-system,sans-serif;color:#2D2D2D;background:#fff;-webkit-font-smoothing:antialiased}
.auth{display:grid;grid-template-columns:minmax(0,0.86fr) minmax(0,1.14fr);min-height:100vh}

.brandside{position:relative;overflow:hidden;background:#580018;color:#fff;padding:clamp(28px,4vw,54px);display:flex;flex-direction:column}
.blob{position:absolute;border-radius:50%;background:#6A001C;opacity:.55;pointer-events:none}
.blob.b1{width:420px;height:420px;top:-120px;right:-140px}
.blob.b2{width:260px;height:260px;bottom:-90px;left:-80px;opacity:.4}
.blob.b3{width:180px;height:180px;top:38%;right:8%;opacity:.3}
.brandside .inner{position:relative;z-index:1;display:flex;flex-direction:column;height:100%;gap:28px}
.logo-row{display:flex;align-items:center;gap:12px}
.logo-badge{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);display:grid;place-items:center;flex:0 0 auto}
.logo-badge img{width:26px;height:26px;object-fit:contain}
.logo-row b{font-size:19px;font-weight:800;letter-spacing:.02em}
.headline{margin-top:auto}
.headline h1{font-size:clamp(26px,3.4vw,40px);font-weight:800;line-height:1.18;letter-spacing:-.02em}
.headline p{color:rgba(255,255,255,.82);font-size:clamp(13.5px,1.5vw,15.5px);line-height:1.65;margin-top:16px;max-width:44ch}
.feats{display:flex;flex-direction:column;gap:14px;margin-top:34px}
.feat{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;color:rgba(255,255,255,.94)}
.feat .fi{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);display:grid;place-items:center;flex:0 0 auto}

.formside{display:flex;align-items:center;justify-content:center;background:#fff;padding:clamp(24px,4vw,54px)}
.formbox{width:100%;max-width:440px}
.formbox h2{font-size:clamp(23px,2.6vw,28px);font-weight:800;letter-spacing:-.02em}
.formbox .lead{color:#666;font-size:14px;margin-top:8px}

.role-box{background:#F5F5F5;border-radius:12px;padding:14px;margin:24px 0 20px}
.role-box .lab{font-size:10.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#8a8a8a;margin-bottom:10px}
.roles{display:flex;gap:6px;flex-wrap:wrap}
.role{flex:1 1 auto;min-width:88px;text-align:center;font-family:inherit;font-size:13px;font-weight:700;color:#666;background:transparent;border:none;border-radius:999px;padding:10px 14px;cursor:pointer;transition:.15s;white-space:nowrap}
.role:hover{color:#580018}
.role.active{background:#580018;color:#fff}

.field{margin-bottom:16px}
.field label{display:block;font-size:12.5px;font-weight:700;margin-bottom:7px}
.ctrl{position:relative;display:flex;align-items:center}
.ctrl .ic{position:absolute;left:13px;color:#9aa0a6;display:grid;place-items:center;pointer-events:none}
.ctrl input{width:100%;font-family:inherit;font-size:15px;color:#2D2D2D;background:#fff;border:1px solid #E0E0E0;border-radius:9px;padding:13px 14px 13px 42px;outline:none;transition:.15s}
.ctrl input:focus{border-color:#580018;box-shadow:0 0 0 3px rgba(88,0,24,.12)}
.ctrl .toggle{position:absolute;right:12px;background:none;border:none;font-family:inherit;font-size:12.5px;font-weight:700;color:#580018;cursor:pointer;padding:4px}

.row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 20px;flex-wrap:wrap}
.remember{display:flex;align-items:center;gap:8px;font-size:13px;color:#666;cursor:pointer}
.remember input{width:16px;height:16px;accent-color:#580018;cursor:pointer}
.link{color:#580018;font-size:13px;font-weight:600;text-decoration:none}
.link:hover{text-decoration:underline}

.btn{width:100%;height:50px;border:none;border-radius:10px;background:#580018;color:#fff;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;transition:.15s}
.btn:hover{background:#4A0E17}

.err{background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;padding:12px 14px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:18px}
.foot{text-align:center;color:#666;font-size:13px;margin-top:26px}
.foot a{color:#580018;font-weight:700;text-decoration:none}
.foot a:hover{text-decoration:underline}

@media(max-width:900px){
  .auth{grid-template-columns:minmax(0,1fr)}
  .brandside{padding:26px 22px 30px}
  .brandside .headline{margin-top:22px}
  .headline h1{font-size:24px}
  .headline p{font-size:13.5px}
  .feats{flex-direction:row;flex-wrap:wrap;gap:10px;margin-top:22px}
  .feat{font-size:12.5px}
  .feat .fi{width:32px;height:32px}
  .formside{padding:26px 20px 40px}
}
</style>
</head>
<body>
<div class="auth">

  <section class="brandside">
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
    <div class="inner">
      <div class="logo-row">
        <span class="logo-badge"><img src="index.php?r=logo" alt="Logo"></span>
        <b><?= APP_NAME ?></b>
      </div>

      <div class="headline">
        <h1>Kelola Acara Kampus<br>Lebih Efisien</h1>
        <p>Platform terpadu untuk pengelolaan acara, registrasi, dan kehadiran organisasi mahasiswa Universitas Bakrie.</p>

        <div class="feats">
          <div class="feat">
            <span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg></span>
            Manajemen acara terpusat
          </div>
          <div class="feat">
            <span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h.01"/></svg></span>
            Presensi QR Code real-time
          </div>
          <div class="feat">
            <span class="fi"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
            Laporan dan statistik lengkap
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="formside">
    <div class="formbox">
      <h2>Selamat Datang di <?= APP_NAME ?></h2>
      <p class="lead">Masuk untuk mengelola acara dan kehadiran organisasi.</p>

      <div class="role-box">
        <div class="lab">Demo - Pilih Role Pengguna</div>
        <div class="roles" id="roles">
          <button type="button" class="role active" data-email="panitia@student.bakrie.ac.id">Pengurus</button>
          <button type="button" class="role" data-email="admin@ormawa.bakrie.ac.id">Admin</button>
          <button type="button" class="role" data-email="pembina@ormawa.bakrie.ac.id">Pembina</button>
          <button type="button" class="role" data-email="anggota@student.bakrie.ac.id">Anggota</button>
        </div>
      </div>

      <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>

      <form method="post" id="f">
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email atau Username</label>
          <div class="ctrl">
            <span class="ic"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3 6.5 9 6 9-6"/></svg></span>
            <input type="email" name="email" id="email" placeholder="nama@student.bakrie.ac.id" autocomplete="username" required autofocus>
          </div>
        </div>

        <div class="field">
          <label for="password">Kata Sandi</label>
          <div class="ctrl">
            <span class="ic"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10.5" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg></span>
            <input type="password" name="password" id="password" placeholder="Kata sandi" autocomplete="current-password" required>
            <button type="button" class="toggle" id="toggle">Tampilkan</button>
          </div>
        </div>

        <div class="row">
          <label class="remember"><input type="checkbox" checked> Ingat saya</label>
          <a class="link" href="#" onclick="return false">Lupa kata sandi?</a>
        </div>

        <button class="btn" type="submit">Masuk</button>
      </form>

      <p class="foot">Butuh akses? <a href="#" onclick="return false">Hubungi Administrator</a></p>
    </div>
  </section>

</div>
<script>
(function(){
  var email = document.getElementById('email');
  var pass  = document.getElementById('password');
  var roles = document.querySelectorAll('.role');
  roles.forEach(function(b){
    b.addEventListener('click', function(){
      roles.forEach(function(x){ x.classList.remove('active'); });
      b.classList.add('active');
      email.value = b.dataset.email;
      pass.value  = 'password123';
      email.focus();
    });
  });
  var t = document.getElementById('toggle');
  t.addEventListener('click', function(){
    var show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    t.textContent = show ? 'Sembunyikan' : 'Tampilkan';
  });
})();
</script>
</body>
</html>
