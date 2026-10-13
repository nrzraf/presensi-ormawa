<?php
/** FR-03 - Halaman tinjau & putuskan proposal (UC-03) */
require_role('pembina','admin');
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$p = q_one("SELECT p.*, e.nama AS event_nama, e.jenis, e.tanggal, e.waktu, e.lokasi, e.kuota, e.deskripsi,
                   e.status AS event_status, m.nama AS pengaju, rv.nama AS reviewer
            FROM proposal p
            JOIN event e ON e.id=p.event_id
            LEFT JOIN member m ON m.id=p.diajukan_oleh
            LEFT JOIN member rv ON rv.id=p.reviewer_id
            WHERE p.id=?", [$id]);
if (!$p) { flash('error', 'Proposal tidak ditemukan.'); redirect('index.php?r=proposals'); }

$title = 'Tinjau Proposal';
$subtitle = e($p['judul'] ?? $p['event_nama']);
ob_start(); ?>
<div class="grid col-2">
  <div class="card">
    <div class="card-head"><h3><?= e($p['judul'] ?? $p['event_nama']) ?></h3><?= status_badge($p['status']) ?></div>
    <div class="table-scroll">
    <table>
      <tr><td class="muted">Acara</td><td><b><?= e($p['event_nama']) ?></b></td></tr>
      <tr><td class="muted">Jenis</td><td><?= e($p['jenis']) ?></td></tr>
      <tr><td class="muted">Pengaju</td><td><?= e($p['pengaju'] ?? '-') ?></td></tr>
      <tr><td class="muted">Jadwal</td><td><?= tgl_id($p['tanggal']) ?> · <?= substr($p['waktu'],0,5) ?></td></tr>
      <tr><td class="muted">Lokasi</td><td><?= e($p['lokasi']) ?></td></tr>
      <tr><td class="muted">Kuota</td><td class="mono"><?= $p['kuota'] ?></td></tr>
      <tr><td class="muted">Diajukan</td><td class="mono"><?= date('d/m/Y H:i', strtotime($p['diajukan_at'])) ?></td></tr>
      <?php if ($p['diputus_at']): ?>
      <tr><td class="muted">Diputus</td><td class="mono"><?= date('d/m/Y H:i', strtotime($p['diputus_at'])) ?> oleh <?= e($p['reviewer'] ?? '-') ?></td></tr>
      <?php endif; ?>
    </table>
    </div>
    <?php if ($p['catatan']): ?>
      <div class="callout" style="margin-top:14px"><b>Ringkasan / catatan:</b>&nbsp;<?= e($p['catatan']) ?></div>
    <?php endif; ?>
    <?php if ($p['file_url']): ?>
      <div style="margin-top:14px"><a class="btn btn-ghost btn-sm" href="<?= e($p['file_url']) ?>" target="_blank">Unduh Berkas</a></div>
    <?php endif; ?>
    <div style="margin-top:16px"><a class="btn btn-ghost btn-sm" href="index.php?r=event&id=<?= $p['event_id'] ?>">Lihat Acara</a></div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Keputusan Pembina</h3></div>
    <?php if (in_array($p['status'], ['disetujui','ditolak'], true)): ?>
      <div style="margin-top:14px"><a class="btn btn-ghost" href="index.php?r=proposals">Kembali</a></div>
    <?php else: ?>
      <form method="post" action="index.php?r=proposal_decide">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="field"><label>Catatan (wajib untuk revisi/tolak)</label>
          <textarea name="catatan" rows="4" placeholder="Catatan..."></textarea>
        </div>
        <div class="inline">
          <button class="btn btn-primary" name="keputusan" value="disetujui">Setujui</button>
          <button class="btn btn-ghost" name="keputusan" value="revisi">Minta Revisi</button>
          <button class="btn btn-danger" name="keputusan" value="ditolak" onclick="return confirm('Tolak proposal ini?')">Tolak</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
