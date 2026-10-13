<?php
/** Ekspor laporan - FR-07, FR-12 (CSV, Excel, PDF/Cetak) */
require_role('panitia','pembina','admin');
$u = current_user();
$eventId = (int)($_GET['id'] ?? 0);
$format = $_GET['format'] ?? 'csv';
$ev = q_one("SELECT * FROM event WHERE id=?", [$eventId]);
if (!$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=report'); }

$peserta = q_all("
    SELECT m.nama, m.nim, m.divisi, a.status AS att_status, a.metode, a.checkin_at
    FROM registration r
    JOIN member m ON m.id=r.member_id
    LEFT JOIN attendance a ON a.registration_id=r.id
    WHERE r.event_id=? ORDER BY m.nama", [$eventId]);

audit('export', 'event', $eventId, "Ekspor laporan ($format)");

$hadir=0;$manual=0;$tidak=0;
foreach ($peserta as $p){ if($p['att_status']==='hadir')$hadir++; elseif($p['att_status']==='cadangan')$manual++; else $tidak++; }
$total = count($peserta);

$slug = preg_replace('/[^a-z0-9]+/i', '-', $ev['nama']);

/* ---------- CSV ---------- */
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap-' . $slug . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
    fputcsv($out, ['Laporan Kehadiran', $ev['nama']]);
    fputcsv($out, ['Tanggal', $ev['tanggal'], 'Lokasi', $ev['lokasi']]);
    fputcsv($out, ['Hadir', $hadir, 'Cadangan', $manual, 'Tidak Hadir', $tidak, 'Total', $total]);
    fputcsv($out, []);
    fputcsv($out, ['Nama','NIM','Divisi','Status','Metode','Check-in']);
    foreach ($peserta as $p) {
        $st = $p['att_status'] === 'hadir' ? 'Hadir' : ($p['att_status'] === 'cadangan' ? 'Hadir (cadangan)' : 'Tidak Hadir');
        fputcsv($out, [$p['nama'],$p['nim'],$p['divisi'],$st, $p['metode'] ?? '-', $p['checkin_at'] ?? '-']);
    }
    fclose($out); exit;
}

/* ---------- Excel (HTML table  .xls) ---------- */
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap-' . $slug . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<table border="1"><tr><th colspan="6">Laporan Kehadiran: ' . e($ev['nama']) . '</th></tr>';
    echo '<tr><td colspan="6">' . tgl_id($ev['tanggal']) . ' · ' . e($ev['lokasi']) . ' · Hadir: ' . $hadir . ' · Cadangan: ' . $manual . ' · Tidak Hadir: ' . $tidak . '</td></tr>';
    echo '<tr><th>Nama</th><th>NIM</th><th>Divisi</th><th>Status</th><th>Metode</th><th>Check-in</th></tr>';
    foreach ($peserta as $p) {
        $st = $p['att_status'] === 'hadir' ? 'Hadir' : ($p['att_status'] === 'cadangan' ? 'Hadir (cadangan)' : 'Tidak Hadir');
        echo '<tr><td>' . e($p['nama']) . '</td><td>' . e($p['nim']) . '</td><td>' . e($p['divisi']) . '</td><td>' . $st . '</td><td>' . ($p['metode'] ?? '-') . '</td><td>' . ($p['checkin_at'] ?? '-') . '</td></tr>';
    }
    echo '</table>'; exit;
}

/* ---------- Print / PDF ---------- */
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Laporan Kehadiran - <?= e($ev['nama']) ?></title>
<style>
body{font-family:Inter,system-ui,sans-serif;color:#0F172A;padding:32px;max-width:900px;margin:0 auto}
h1{font-size:20px;margin-bottom:4px}.sub{color:#64748B;font-size:13px;margin-bottom:20px}
.kpi{display:flex;gap:24px;margin-bottom:20px;font-size:14px}
.kpi b{display:block;font-size:22px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;background:#F1F5F9;padding:9px;border-bottom:2px solid #E2E8F0;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
td{padding:9px;border-bottom:1px solid #E2E8F0}
.num{text-align:right}
.hdr{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0F766E;padding-bottom:12px;margin-bottom:18px}
.foot{margin-top:26px;font-size:11px;color:#94a3b8;text-align:center}
@media print{.noprint{display:none}}
</style></head><body>
<div class="hdr">
  <div><h1>Laporan Kehadiran</h1><div class="sub"><?= e($ev['nama']) ?> · <?= tgl_id($ev['tanggal']) ?> · <?= e($ev['lokasi']) ?></div></div>
  <div style="text-align:right;font-size:12px;color:#64748B">Organisasi Mahasiswa<br>Universitas Bakrie</div>
</div>
<div class="kpi">
  <div>Hadir<b><?= $hadir ?></b></div>
  <div>Cadangan<b><?= $manual ?></b></div>
  <div>Tidak Hadir<b><?= $tidak ?></b></div>
  <div>Total<b><?= $total ?></b></div>
  <div>Persentase<b><?= $total>0?round(($hadir+$manual)/$total*100,1):0 ?>%</b></div>
</div>
<div class="table-scroll">
<table>
<thead><tr><th>Nama</th><th>NIM</th><th>Divisi</th><th>Status</th><th>Metode</th><th class="num">Check-in</th></tr></thead>
<tbody>
<?php foreach ($peserta as $p):
  $st = $p['att_status']==='hadir' ? 'Hadir' : ($p['att_status']==='cadangan' ? 'Hadir (cadangan)' : 'Tidak Hadir'); ?>
<tr><td><?= e($p['nama']) ?></td><td><?= e($p['nim']) ?></td><td><?= e($p['divisi']) ?></td><td><?= $st ?></td><td><?= $p['metode'] ?? '-' ?></td><td class="num"><?= $p['checkin_at'] ? date('H:i:s', strtotime($p['checkin_at'])) : '-' ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<p class="foot">Dicetak otomatis oleh <?= APP_NAME ?> · <?= date('d/m/Y H:i') ?></p>
<div class="noprint" style="text-align:center;margin-top:24px"><button onclick="window.print()" style="padding:12px 24px;background:#0F766E;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer">Cetak / Simpan PDF</button></div>
</body></html>
