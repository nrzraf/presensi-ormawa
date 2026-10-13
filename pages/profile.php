<?php
/** Pengaturan Profil - pengguna dapat memperbarui data & kata sandi sendiri. */
require_login();
$u = current_user();
$title = 'Pengaturan Profil';
$subtitle = 'Perbarui data diri dan kata sandi akun Anda.';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'profil') {
        $nama   = trim($_POST['nama'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $divisi = trim($_POST['divisi'] ?? '');
        if ($nama === '' || $email === '') {
            $err = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Format email tidak valid.';
        } else {
            $dup = q_val('SELECT id FROM member WHERE email=? AND id<>?', [$email, $u['id']]);
            if ($dup) {
                $err = 'Email sudah dipakai anggota lain.';
            } else {
                q('UPDATE member SET nama=?, email=?, divisi=? WHERE id=?', [$nama, $email, $divisi, $u['id']]);
                audit('ubah', 'member', (int)$u['id'], 'Memperbarui profil');
                flash('ok', 'Profil berhasil diperbarui.');
                redirect('index.php?r=profile');
            }
        }
    } elseif ($aksi === 'sandi') {
        $lama = $_POST['lama'] ?? '';
        $baru = $_POST['baru'] ?? '';
        $ulang = $_POST['ulang'] ?? '';
        if (!password_verify($lama, $u['password_hash'])) {
            $err = 'Kata sandi lama salah.';
        } elseif (strlen($baru) < 8) {
            $err = 'Kata sandi baru minimal 8 karakter.';
        } elseif ($baru !== $ulang) {
            $err = 'Konfirmasi kata sandi tidak sama.';
        } else {
            q('UPDATE member SET password_hash=? WHERE id=?', [password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
            audit('ubah', 'member', (int)$u['id'], 'Mengganti kata sandi');
            flash('ok', 'Kata sandi berhasil diganti.');
            redirect('index.php?r=profile');
        }
    }
}

$stat = [
    'diikuti' => (int) q_val('SELECT COUNT(*) FROM registration WHERE member_id=?', [$u['id']]),
    'hadir'   => (int) q_val('SELECT COUNT(*) FROM attendance WHERE member_id=?', [$u['id']]),
];
$pct = $stat['diikuti'] > 0 ? round($stat['hadir'] / $stat['diikuti'] * 100) : 0;

ob_start(); ?>
<?php if ($err): ?><div class="flash error"><?= e($err) ?></div><?php endif; ?>

<div class="grid col-2e">
  <div class="card">
    <div class="card-head"><h3>Data Diri</h3></div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="profil">
      <div class="field"><label>Nama Lengkap</label><input name="nama" value="<?= e($u['nama']) ?>" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
      <div class="field"><label>Divisi</label><input name="divisi" value="<?= e($u['divisi']) ?>" placeholder="Contoh: Acara"></div>
      <div class="inline" style="margin-bottom:14px">
        <div><label>NIM</label><input value="<?= e($u['nim']) ?>" disabled></div>
        <div><label>Peran</label><input value="<?= e(ucfirst($u['role'])) ?>" disabled></div>
      </div>
      <button class="btn btn-primary"><?= icon('check', 16) ?>Simpan Perubahan</button>
    </form>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>Ringkasan Aktivitas</h3></div>
      <div class="grid col-2e" style="gap:12px">
        <?= stat_card('calendar','blue', $stat['diikuti'], 'Acara Diikuti', '', true) ?>
        <?= stat_card('check','green', $stat['hadir'], 'Total Hadir', '', true) ?>
      </div>
      <div style="margin-top:16px">
        <div class="sub" style="margin-bottom:6px">Persentase kehadiran</div>
        <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
        <div class="sub mono" style="text-align:right;margin-top:5px"><?= $pct ?>%</div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Ganti Kata Sandi</h3></div>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="sandi">
        <div class="field"><label>Kata Sandi Lama</label><input type="password" name="lama" autocomplete="current-password" required></div>
        <div class="field"><label>Kata Sandi Baru</label><input type="password" name="baru" autocomplete="new-password" required></div>
        <div class="field"><label>Ulangi Kata Sandi Baru</label><input type="password" name="ulang" autocomplete="new-password" required></div>
        <button class="btn btn-primary"><?= icon('lock', 16) ?>Ganti Kata Sandi</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
