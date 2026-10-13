<?php
/** FR-03 - Pembina meninjau & memutuskan proposal (UC-03) */
require_role('pembina','admin');
$u = current_user();
$title = 'Persetujuan Proposal';
$subtitle = 'Tinjau proposal kegiatan';

$filter = $_GET['status'] ?? 'diajukan';
$sql = "SELECT p.*, e.nama AS event_nama, e.jenis, e.tanggal, e.waktu, e.lokasi, e.kuota, e.status AS event_status,
               m.nama AS pengaju
        FROM proposal p
        JOIN event e ON e.id=p.event_id
        LEFT JOIN member m ON m.id=p.diajukan_oleh";
$params = [];
if (in_array($filter, ['diajukan','disetujui','ditolak','revisi'], true)) { $sql .= " WHERE p.status=?"; $params[] = $filter; }
$sql .= " ORDER BY p.diajukan_at DESC";
$rows = q_all($sql, $params);

$counts = [];
foreach (['diajukan','disetujui','ditolak','revisi'] as $s) {
    $counts[$s] = (int) q_val("SELECT COUNT(*) FROM proposal WHERE status=?", [$s]);
}

ob_start(); ?>
<div class="grid col-4" style="margin-bottom:16px">
  <?php foreach (['diajukan'=>'Menunggu','disetujui'=>'Disetujui','revisi'=>'Revisi','ditolak'=>'Ditolak'] as $k=>$lab): ?>
    <a href="index.php?r=proposals&status=<?= $k ?>" class="kpi" style="text-decoration:none;color:inherit;<?= $filter===$k?'border-color:var(--tertiary);box-shadow:0 0 0 3px rgba(15,118,110,.12)':'' ?>">
      <div class="lab"><?= $lab ?></div><div class="val"><?= $counts[$k] ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><h3>Daftar Proposal - <?= ucfirst($filter) ?></h3><span class="chip"><?= count($rows) ?> proposal</span></div>
  <?php if (!$rows): ?><div class="empty">Tidak ada proposal.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Proposal / Acara</th><th>Pengaju</th><th>Jadwal</th><th>Status</th><th class="num">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td><b><?= e($p['judul'] ?? $p['event_nama']) ?></b>
            <div class="sub"><?= e($p['jenis']) ?> · <?= e($p['event_nama']) ?>
            <?php if ($p['file_url']): ?> · <a href="<?= e($p['file_url']) ?>" target="_blank">Berkas</a><?php endif; ?></div></td>
        <td class="muted"><?= e($p['pengaju'] ?? '-') ?></td>
        <td class="muted"><?= tgl_id($p['tanggal']) ?><br><span class="mono"><?= substr($p['waktu'],0,5) ?></span></td>
        <td><?= status_badge($p['status']) ?></td>
        <td class="num"><a class="btn btn-primary btn-sm" href="index.php?r=proposal_review&id=<?= $p['id'] ?>">Tinjau</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
