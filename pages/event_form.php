<?php
/** Form buat/edit acara - UC-02, FR-02 */
require_role('panitia','admin');
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$ev = $id ? q_one("SELECT * FROM event WHERE id=?", [$id]) : null;
if ($id && !$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=events'); }

$title = $id ? 'Edit Acara' : 'Buat Acara Baru';
$subtitle = 'Buat / ubah acara';
ob_start(); ?>
<form method="post" action="index.php?r=event_save" class="grid col-2">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $id ?>">

  <div class="card">
    <div class="field"><label>Nama Acara</label><input name="nama" required value="<?= e($ev['nama'] ?? '') ?>" placeholder="mis. Seminar Nasional Kepemimpinan"></div>
    <div class="grid col-2e" style="gap:14px">
      <div class="field"><label>Jenis Acara</label>
        <select name="jenis">
          <?php foreach (['Seminar','Pelatihan','Rapat Kerja','Pelantikan','Pengabdian Masyarakat','Lainnya'] as $j): ?>
            <option <?= ($ev['jenis']??'')===$j?'selected':'' ?>><?= $j ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Kuota Peserta</label><input type="number" name="kuota" min="1" required value="<?= e($ev['kuota'] ?? '100') ?>"></div>
    </div>
    <div class="grid col-2e" style="gap:14px">
      <div class="field"><label>Tanggal</label><input type="date" name="tanggal" required value="<?= e($ev['tanggal'] ?? date('Y-m-d')) ?>"></div>
      <div class="field"><label>Waktu</label><input type="time" name="waktu" required value="<?= e(substr($ev['waktu'] ?? '09:00',0,5)) ?>"></div>
    </div>
    <div class="field"><label>Lokasi</label><input name="lokasi" required value="<?= e($ev['lokasi'] ?? '') ?>"></div>
    <div class="field"><label>Deskripsi</label><textarea name="deskripsi" rows="3"><?= e($ev['deskripsi'] ?? '') ?></textarea></div>
    <div class="inline">
      <button class="btn btn-primary" name="aksi" value="draft">Simpan sebagai Draft</button>
      <button class="btn btn-ghost" name="aksi" value="publish">Simpan &amp; Publikasikan</button>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>Status Acara</h3><?= status_badge($ev['status'] ?? 'draft') ?></div>
    </div>
  </div>
</form>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
