<?php
/**
 * Pendaftaran mandiri anggota - FR-01.
 * Hanya menghasilkan akun berperan "anggota"; panitia & pembina dibuat admin.
 */
if (is_logged_in()) redirect('index.php?r=dashboard');

$err = '';
$ok = '';
$old = ['nama' => '', 'nim' => '', 'email' => '', 'divisi' => '', 'hp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $setuju = isset($_POST['setuju']);

    if ($old['nama'] === '' || $old['nim'] === '' || $old['email'] === '') {
        $err = 'Nama, NIM, dan email wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $err = 'Format email tidak valid.';
    } elseif (!preg_match('/^\d{6,15}$/', $old['nim'])) {
        $err = 'NIM harus berupa angka (6-15 digit).';
    } elseif (!preg_match('/^[0-9+\-\s]{9,20}$/', $old['hp'])) {
        $err = 'Nomor WhatsApp tidak valid.';
    } elseif (mb_strlen($pass) < 8) {
        $err = 'Kata sandi minimal 8 karakter.';
    } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $err = 'Kata sandi harus memuat huruf dan angka.';
    } elseif ($pass !== $pass2) {
        $err = 'Konfirmasi kata sandi tidak sama.';
    } elseif (!$setuju) {
        $err = 'Anda harus menyetujui pernyataan data.';
    } else {
        $dup = q_one("SELECT id FROM member WHERE email=? OR nim=?", [$old['email'], $old['nim']]);
        if ($dup) {
            $err = 'Email atau NIM sudah terdaftar.';
        } else {
            try {
                q("INSERT INTO member (nama,nim,email,password_hash,divisi,status,role_id,no_hp) VALUES (?,?,?,?,?,'aktif',1,?)",
                  [$old['nama'], $old['nim'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['divisi'], $old['hp']]);
                $newId = q_id('member');
                audit('register', 'member', $newId, 'Pendaftaran mandiri: ' . $old['nama']);
                q("INSERT INTO notification (member_id, tipe, isi) VALUES (?, 'sistem', ?)",
                  [$newId, 'Selamat datang. Akun anggota Anda sudah aktif.']);
                $ok = 'Pendaftaran berhasil. Silakan masuk.';
                $old = ['nama' => '', 'nim' => '', 'email' => '', 'divisi' => '', 'hp' => ''];
            } catch (Throwable $e) {
                $err = 'Pendaftaran gagal. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Anggota - <?= APP_NAME ?></title>
<meta name="theme-color" content="#580018">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,sans-serif;color:#2D2D2D;background:#F6FAFB;-webkit-font-smoothing:antialiased}
.wrap{max-width:760px;margin:0 auto;padding:38px 20px 56px}
.top{display:flex;align-items:center;gap:14px;margin-bottom:26px}
.top img{width:44px;height:44px;object-fit:contain}
.top b{font-size:17px;font-weight:800;line-height:1.2;display:block}
.top small{color:#7A7A7A;font-size:12px}
h1{font-size:26px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
.lead{color:#7A7A7A;font-size:13.5px;margin-bottom:22px}
.card{background:#fff;border:1px solid #E4E4E4;border-radius:14px;padding:24px}
.sec{font-size:10.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#80011F;margin:0 0 12px}
.sec:not(:first-child){margin-top:24px;padding-top:20px;border-top:1px solid #EEE}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{margin-bottom:14px}
.field label{display:block;font-size:12.5px;font-weight:700;margin-bottom:6px}
.field .req{color:#B91C1C}
.field input,.field select{width:100%;font-family:inherit;font-size:14px;padding:11px 12px;border:1px solid #E0E0E0;border-radius:9px;outline:none;background:#fff;transition:.15s}
.field input:focus,.field select:focus{border-color:#580018;box-shadow:0 0 0 3px rgba(88,0,24,.12)}
.hint{font-size:11.5px;color:#8a8a8a;margin-top:5px}
.check{display:flex;gap:10px;align-items:flex-start;font-size:12.5px;color:#555;line-height:1.5;margin:16px 0 20px}
.check input{width:16px;height:16px;margin-top:2px;accent-color:#580018;flex:0 0 auto}
.btn{width:100%;height:48px;border:none;border-radius:10px;background:#580018;color:#fff;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer}
.btn:hover{background:#4A0E17}
.err{background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;padding:11px 13px;border-radius:9px;font-size:13px;font-weight:600;margin-bottom:16px}
.ok{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857;padding:11px 13px;border-radius:9px;font-size:13px;font-weight:600;margin-bottom:16px}
.foot{text-align:center;font-size:12.5px;color:#7A7A7A;margin-top:18px}
.foot a{color:#580018;font-weight:700;text-decoration:none}
@media(max-width:560px){.wrap{padding:24px 14px 40px}.row{grid-template-columns:1fr}h1{font-size:22px}.card{padding:18px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <img src="index.php?r=logo" alt="Logo">
    <div><b><?= APP_NAME ?></b><small>Pendaftaran Anggota</small></div>
  </div>

  <h1>Daftar sebagai Anggota</h1>
  <div class="lead">Isi data berikut untuk membuat akun keanggotaan ormawa.</div>

  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="ok"><?= e($ok) ?></div><?php endif; ?>

  <form method="post" class="card">
    <?= csrf_field() ?>

    <div class="sec">Data Diri</div>
    <div class="field">
      <label>Nama Lengkap <span class="req">*</span></label>
      <input name="nama" required value="<?= e($old['nama']) ?>" placeholder="Nama sesuai KTM">
    </div>
    <div class="row">
      <div class="field">
        <label>NIM <span class="req">*</span></label>
        <input name="nim" required inputmode="numeric" value="<?= e($old['nim']) ?>" placeholder="1202xxxxxxx">
        <div class="hint">Angka, 6-15 digit.</div>
      </div>
      <div class="field">
        <label>Nomor WhatsApp <span class="req">*</span></label>
        <input name="hp" required inputmode="tel" value="<?= e($old['hp']) ?>" placeholder="08xxxxxxxxxx">
      </div>
    </div>
    <div class="row">
      <div class="field">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" required value="<?= e($old['email']) ?>" placeholder="nama@student.bakrie.ac.id">
      </div>
      <div class="field">
        <label>Divisi</label>
        <select name="divisi">
          <?php foreach (['', 'Acara', 'Dana Usaha', 'Hubungan Masyarakat', 'Sumber Daya Manusia', 'Minat dan Bakat'] as $d): ?>
            <option value="<?= e($d) ?>" <?= $old['divisi'] === $d ? 'selected' : '' ?>><?= $d === '' ? 'Belum ditentukan' : e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="sec">Keamanan Akun</div>
    <div class="row">
      <div class="field">
        <label>Kata Sandi <span class="req">*</span></label>
        <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter">
        <div class="hint">Wajib memuat huruf dan angka.</div>
      </div>
      <div class="field">
        <label>Ulangi Kata Sandi <span class="req">*</span></label>
        <input type="password" name="password2" required minlength="8" placeholder="Ulangi kata sandi">
      </div>
    </div>

    <label class="check">
      <input type="checkbox" name="setuju" value="1" required>
      <span>Saya menyatakan data yang diisi benar dan bersedia data ini digunakan untuk keperluan keanggotaan ormawa.</span>
    </label>

    <button class="btn" type="submit">Buat Akun Anggota</button>
  </form>

  <div class="foot">Sudah punya akun? <a href="index.php?r=login">Masuk di sini</a></div>
</div>
</body>
</html>
