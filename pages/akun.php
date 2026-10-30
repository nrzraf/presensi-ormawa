<?php
/**
 * Buat akun panitia / pembina - FR-09 (khusus admin).
 * Pendaftaran mandiri hanya untuk anggota.
 */
require_role('admin');
$u = current_user();

// kolom no_hp mungkin belum ada pada DB lama
try { q("ALTER TABLE member ADD COLUMN IF NOT EXISTS no_hp VARCHAR(24)"); } catch (Throwable $e) {}

$err = '';
$ok  = '';
$old = ['nama' => '', 'nim' => '', 'email' => '', 'divisi' => '', 'hp' => '', 'role_id' => '2'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $role  = (int)$old['role_id'];

    if (!in_array($role, [2, 3], true)) {
        $err = 'Peran harus Panitia atau Pembina.';
    } elseif ($old['nama'] === '' || $old['nim'] === '' || $old['email'] === '') {
        $err = 'Nama, NIM/NIP, dan email wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $err = 'Format email tidak valid.';
    } elseif (mb_strlen($pass) < 8) {
        $err = 'Kata sandi minimal 8 karakter.';
    } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $err = 'Kata sandi harus memuat huruf dan angka.';
    } elseif ($pass !== $pass2) {
        $err = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $dup = q_one("SELECT id FROM member WHERE email=? OR nim=?", [$old['email'], $old['nim']]);
        if ($dup) {
            $err = 'Email atau NIM/NIP sudah terdaftar.';
        } else {
            try {
                q("INSERT INTO member (nama,nim,email,password_hash,divisi,status,role_id,no_hp) VALUES (?,?,?,?,?,'aktif',?,?)",
                  [$old['nama'], $old['nim'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['divisi'], $role, $old['hp']]);
                $newId = q_id('member');
                $label = $role === 3 ? 'pembina' : 'panitia';
                audit('akun_create', 'member', $newId, "Buat akun $label: " . $old['nama']);
                $ok = 'Akun ' . $label . ' berhasil dibuat.';
                $old = ['nama' => '', 'nim' => '', 'email' => '', 'divisi' => '', 'hp' => '', 'role_id' => (string)$role];
            } catch (Throwable $e) {
                $err = 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
}

// daftar akun pengelola (panitia & pembina)
$pengelola = q_all("SELECT m.*, r.nama AS role FROM member m JOIN role r ON r.id=m.role_id
    WHERE m.role_id IN (2,3) ORDER BY m.role_id, m.nama");

$title = 'Buat Akun';
$subtitle = '';
ob_start(); ?>
<div class="grid col-2">
  <div class="card">
    <div class="card-head"><h3>Akun Pengelola Baru</h3></div>

    <?php if ($err): ?><div class="flash flash-error" style="margin-bottom:14px"><?= e($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="flash flash-ok" style="margin-bottom:14px"><?= e($ok) ?></div><?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Peran</label>
        <select name="role_id" required>
          <option value="2" <?= $old['role_id']==='2'?'selected':'' ?>>Panitia / Pengurus</option>
          <option value="3" <?= $old['role_id']==='3'?'selected':'' ?>>Pembina</option>
        </select>
      </div>
      <div class="field"><label>Nama Lengkap</label>
        <input name="nama" required value="<?= e($old['nama']) ?>" placeholder="Nama lengkap beserta gelar bila ada">
      </div>
      <div class="grid col-2e" style="gap:12px">
        <div class="field"><label>NIM / NIP</label>
          <input name="nim" required value="<?= e($old['nim']) ?>" placeholder="1202xxxxxxx">
        </div>
        <div class="field"><label>Nomor WhatsApp</label>
          <input name="hp" value="<?= e($old['hp']) ?>" placeholder="08xxxxxxxxxx">
        </div>
      </div>
      <div class="field"><label>Email</label>
        <input type="email" name="email" required value="<?= e($old['email']) ?>" placeholder="nama@student.bakrie.ac.id">
      </div>
      <div class="field"><label>Divisi / Unit</label>
        <input name="divisi" value="<?= e($old['divisi']) ?>" placeholder="Contoh: Acara">
      </div>
      <div class="grid col-2e" style="gap:12px">
        <div class="field"><label>Kata Sandi</label>
          <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter">
        </div>
        <div class="field"><label>Ulangi Kata Sandi</label>
          <input type="password" name="password2" required minlength="8" placeholder="Ulangi">
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Buat Akun</button>
    </form>
  </div>

  <div class="card">
    <div class="card-head"><h3>Akun Pengelola</h3><span class="chip"><?= count($pengelola) ?> akun</span></div>
    <?php if (!$pengelola): ?><div class="empty">Belum ada akun panitia atau pembina.</div><?php else: ?>
    <div class="table-scroll">
    <table>
      <thead><tr><th>Nama</th><th>Peran</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pengelola as $m): ?>
        <tr>
          <td><b><?= e($m['nama']) ?></b><div class="sub"><?= e($m['email']) ?></div></td>
          <td><span class="chip"><?= e(ucfirst($m['role'])) ?></span></td>
          <td><span class="badge <?= $m['status']==='aktif'?'b-present':'b-absent' ?>"><?= $m['status']==='aktif'?'Aktif':'Nonaktif' ?></span></td>
          <td class="num">
            <form method="post" action="index.php?r=akun_hapus" onsubmit="return confirm('Hapus akun <?= e($m['nama']) ?>? Tindakan ini permanen.')" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="btn btn-ghost btn-sm" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
    <div class="sub" style="margin-top:12px">Pendaftaran mandiri hanya tersedia untuk peran anggota.</div>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
