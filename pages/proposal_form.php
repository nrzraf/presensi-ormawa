<?php
/** FR-03 - Panitia mengajukan proposal digital untuk sebuah acara (UC-03) */
require_role('panitia','admin');
$u = current_user();
$eventId = (int)($_GET['event'] ?? 0);
$ev = q_one("SELECT * FROM event WHERE id=?", [$eventId]);
if (!$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=events'); }

$prop = q_one("SELECT * FROM proposal WHERE event_id=? ORDER BY id DESC LIMIT 1", [$eventId]);
$bolehAjukan = !$prop || in_array($prop['status'], ['revisi'], true);

$title = 'Ajukan Proposal';
$subtitle = e($ev['nama']);
ob_start(); ?>
<div class="grid col-2">
  <div class="card">
    <?php if (!$bolehAjukan): ?>
      <div class="card-head"><h3>Proposal Sudah Diajukan</h3><?= status_badge($prop['status']) ?></div>
      <div style="margin-top:16px"><a class="btn btn-ghost" href="index.php?r=event&id=<?= $eventId ?>">Kembali ke Acara</a></div>
    <?php else: ?>
      <div class="card-head"><h3>Formulir Proposal</h3><?= $prop ? status_badge($prop['status']) : '<span class="badge b-muted">Belum diajukan</span>' ?></div>
      <?php if ($prop && $prop['status'] === 'revisi' && $prop['catatan']): ?>
        <div class="callout" style="margin-bottom:16px;border-color:#FED7AA;background:#FFF7ED">
          <b>Catatan pembina (revisi):</b>&nbsp;<?= e($prop['catatan']) ?>
        </div>
      <?php endif; ?>
      <form method="post" action="index.php?r=proposal_save" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        <div class="field"><label>Judul Proposal</label>
          <input name="judul" required value="<?= e($prop['judul'] ?? ('Proposal ' . $ev['nama'])) ?>" placeholder="mis. Proposal Kegiatan Seminar Nasional">
        </div>
        <div class="field"><label>Ringkasan / Latar Belakang</label>
          <textarea name="ringkasan" rows="4" placeholder="Ringkasan kegiatan..."><?= e($prop['catatan'] ?? '') ?></textarea>
        </div>
        <div class="field"><label>Berkas Proposal (PDF/DOC, maks 10MB)</label>
          <input type="file" name="file" accept=".pdf,.doc,.docx">
        </div>
        <button class="btn btn-primary" name="aksi" value="ajukan">Ajukan</button>
        <a class="btn btn-ghost" href="index.php?r=event&id=<?= $eventId ?>">Batal</a>
      </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h3>Detail Acara</h3><?= status_badge($ev['status']) ?></div>
    <div class="table-scroll">
    <table>
      <tr><td class="muted">Nama</td><td><b><?= e($ev['nama']) ?></b></td></tr>
      <tr><td class="muted">Jenis</td><td><?= e($ev['jenis']) ?></td></tr>
      <tr><td class="muted">Jadwal</td><td><?= tgl_id($ev['tanggal']) ?> · <?= substr($ev['waktu'],0,5) ?></td></tr>
      <tr><td class="muted">Lokasi</td><td><?= e($ev['lokasi']) ?></td></tr>
      <tr><td class="muted">Kuota</td><td class="mono"><?= $ev['kuota'] ?></td></tr>
    </table>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
