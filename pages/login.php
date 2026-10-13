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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,sans-serif;background:#F1F5F9;color:#0F172A;min-height:100vh;display:grid;place-items:center;padding:24px}
.wrap{width:100%;max-width:410px}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.brand{text-align:center;margin-bottom:24px}
.logo{width:64px;height:64px;object-fit:contain;display:block;margin:0 auto 12px}
h1{font-size:20px;font-weight:800;letter-spacing:-.02em}
.sub{color:#64748B;font-size:13.5px;margin-top:3px}
label{display:block;font-size:12px;font-weight:700;margin-bottom:6px}
input{width:100%;font-family:inherit;font-size:14px;border:1px solid #E2E8F0;border-radius:6px;padding:12px;outline:none}
input:focus{border-color:#0F766E;box-shadow:0 0 0 3px rgba(15,118,110,.15)}
.field{margin-bottom:16px}
.btn{width:100%;height:46px;border:none;border-radius:10px;background:#0F766E;color:#fff;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit}
.btn:hover{background:#0F172A}
.hint{font-size:12px;color:#64748B;margin-top:6px}
.err{background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;padding:12px 14px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:16px}
.demo{margin-top:20px;padding-top:18px;border-top:1px solid #F1F5F9}
.demo p{font-size:12px;color:#64748B;margin-bottom:8px;font-weight:700}
.demo button{display:block;width:100%;text-align:left;font-family:inherit;font-size:13px;padding:9px 12px;margin-bottom:6px;border:1px solid #E2E8F0;border-radius:8px;background:#F8FAFC;cursor:pointer;color:#0F172A}
.demo button:hover{background:#F1F5F9}
.demo b{color:#0F766E}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="brand">
      <img class="logo" src="index.php?r=logo" alt="Logo Universitas Bakrie">
      <h1><?= APP_NAME ?></h1>
      <p class="sub">Masuk untuk melanjutkan</p>
    </div>
    <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" id="f">
      <?= csrf_field() ?>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" id="email" placeholder="nama@student.bakrie.ac.id" required autofocus>
      </div>
      <div class="field">
        <label>Kata Sandi</label>
        <input type="password" name="password" id="password" placeholder="••••••••" required>
      </div>
      <button class="btn" type="submit">Masuk</button>
    </form>
    <div class="demo">
      <p>AKUN DEMO (klik untuk mengisi)</p>
      <button type="button" onclick="fill('admin@ormawa.bakrie.ac.id')"><b>Admin</b> - admin@ormawa.bakrie.ac.id</button>
      <button type="button" onclick="fill('panitia@student.bakrie.ac.id')"><b>Panitia</b> - panitia@student.bakrie.ac.id</button>
      <button type="button" onclick="fill('pembina@ormawa.bakrie.ac.id')"><b>Pembina</b> - pembina@ormawa.bakrie.ac.id</button>
      <button type="button" onclick="fill('anggota@student.bakrie.ac.id')"><b>Anggota</b> - anggota@student.bakrie.ac.id</button>
      <p style="margin-top:8px;font-weight:400">Semua password: <b>password123</b></p>
    </div>
  </div>
</div>
<script>
function fill(e){document.getElementById('email').value=e;document.getElementById('password').value='password123';}
</script>
</body>
</html>
