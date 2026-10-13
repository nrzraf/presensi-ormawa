<?php
/** Laporan kehadiran - UC-06, FR-07, FR-08, FR-11, FR-12 */
require_role('panitia','pembina','admin');   // UC-06 aktor: Pengurus Inti & Pembina
$u = current_user();
$eventId = (int)($_GET['id'] ?? 0);
$acara = q_all("SELECT e.id, e.nama,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg
    FROM event e ORDER BY reg DESC, e.tanggal DESC");
if (!$eventId && $acara) $eventId = (int)$acara[0]['id'];
$ev = $eventId ? q_one("SELECT * FROM event WHERE id=?", [$eventId]) : null;

$hadir = $manual = $tidak = 0; $peserta = [];
if ($ev) {
    $peserta = q_all("
        SELECT m.nama, m.nim, m.divisi, r.status AS reg_status,
               a.status AS att_status, a.metode, a.checkin_at
        FROM registration r
        JOIN member m ON m.id=r.member_id
        LEFT JOIN attendance a ON a.registration_id=r.id
        WHERE r.event_id=? ORDER BY m.nama", [$eventId]);
    foreach ($peserta as $p) {
        if ($p['att_status'] === 'hadir') $hadir++;
        elseif ($p['att_status'] === 'cadangan') $manual++;
        else $tidak++;
    }
}
$totalReg = $hadir + $manual + $tidak;
$pct = $totalReg > 0 ? round(($hadir + $manual) / $totalReg * 100, 1) : 0;

$title = 'Laporan Kehadiran';
$subtitle = 'Rekap kehadiran per acara';
ob_start(); ?>
<div class="card" style="margin-bottom:16px">
  <form method="get" class="inline">
    <input type="hidden" name="r" value="report">
    <div style="flex:1;min-width:240px"><label>Pilih acara</label>
      <select name="id" onchange="this.form.submit()">
        <?php foreach ($acara as $a): ?><option value="<?= $a['id'] ?>" <?= $a['id']==$eventId?'selected':'' ?>><?= e($a['nama']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if ($ev): ?>
      <a class="btn btn-ghost" href="index.php?r=export&id=<?= $eventId ?>&format=csv">CSV</a>
      <a class="btn btn-ghost" href="index.php?r=export&id=<?= $eventId ?>&format=excel">Excel</a>
      <a class="btn btn-primary" href="index.php?r=export&id=<?= $eventId ?>&format=print" target="_blank">PDF / Cetak</a>
    <?php endif; ?>
  </form>
</div>

<?php if (!$ev): ?>
  <div class="card"><div class="empty">Belum ada acara.</div></div>
<?php else: ?>
<div class="grid col-4" style="margin-bottom:16px">
  <div class="kpi"><div class="lab">Hadir</div><div class="val mono"><?= $hadir ?></div></div>
  <div class="kpi"><div class="lab">Mode Cadangan</div><div class="val mono"><?= $manual ?></div></div>
  <div class="kpi"><div class="lab">Tidak Hadir</div><div class="val mono"><?= $tidak ?></div></div>
  <div class="kpi"><div class="lab">Persentase</div><div class="val mono"><?= $pct ?>%</div></div>
</div>

<div class="card">
  <div class="card-head"><div><h3><?= e($ev['nama']) ?></h3><div class="sub"><?= tgl_id($ev['tanggal']) ?> · <?= e($ev['lokasi']) ?></div></div><?= status_badge($ev['status']) ?></div>
  <?php if (!$peserta): ?><div class="empty">Tidak ada data.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Nama</th><th>NIM</th><th>Divisi</th><th>Status</th><th>Metode</th><th class="num">Check-in</th></tr></thead>
    <tbody>
    <?php foreach ($peserta as $p): ?>
      <tr>
        <td><b><?= e($p['nama']) ?></b></td>
        <td class="mono"><?= e($p['nim']) ?></td>
        <td class="muted"><?= e($p['divisi']) ?></td>
        <td><?= status_badge($p['att_status'] ?? 'tidak_hadir') ?></td>
        <td><?= $p['metode'] ? '<span class="chip">'.($p['metode']==='qr'?'QR':'Manual').'</span>' : '<span class="muted">-</span>' ?></td>
        <td class="num mono"><?= $p['checkin_at'] ? date('H:i:s', strtotime($p['checkin_at'])) : '<span class="muted">-</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
