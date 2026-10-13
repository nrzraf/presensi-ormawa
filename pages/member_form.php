<?php
/** Form tambah/ubah anggota - FR-09 */
require_role('admin');
$id = (int)($_GET['id'] ?? 0);
$m = $id ? q_one("SELECT * FROM member WHERE id=?", [$id]) : null;
if ($id && !$m) { flash('error', 'Anggota tidak ditemukan.'); redirect('index.php?r=members'); }
$roles = q_all("SELECT * FROM role ORDER BY id");

$title = $id ? 'Ubah Anggota' : 'Tambah Anggota';
$subtitle = 'Tambah / ubah anggota';
ob_start(); ?>
<form method="post" action="index.php?r=member_save" class="card" style="max-width:640px">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="grid col-2e" style="gap:14px">
    <div class="field"><label>Nama Lengkap</label><input name="nama" required value="<?= e($m['nama'] ?? '') ?>"></div>
    <div class="field"><label>NIM</label><input name="nim" required value="<?= e($m['nim'] ?? '') ?>"></div>
  </div>
  <div class="field"><label>Email</label><input type="email" name="email" required value="<?= e($m['email'] ?? '') ?>"></div>
  <div class="grid col-2e" style="gap:14px">
    <div class="field"><label>Divisi</label><input name="divisi" value="<?= e($m['divisi'] ?? '') ?>"></div>
    <div class="field"><label>Peran</label>
      <select name="role_id">
        <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= ($m['role_id']??1)==$r['id']?'selected':'' ?>><?= ucfirst(e($r['nama'])) ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field"><label>Status</label>
    <select name="status">
      <option value="aktif" <?= ($m['status']??'aktif')==='aktif'?'selected':'' ?>>Aktif</option>
      <option value="nonaktif" <?= ($m['status']??'')==='nonaktif'?'selected':'' ?>>Nonaktif</option>
    </select>
  </div>
  <div class="field"><label>Kata Sandi <?= $id ? '(kosongkan bila tidak diubah)' : '' ?></label>
    <input type="password" name="password" <?= $id?'':'required' ?> placeholder="••••••••">
  </div>
  <div class="inline">
    <button class="btn btn-primary">Simpan</button>
    <a class="btn btn-ghost" href="index.php?r=members">Batal</a>
  </div>
</form>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
