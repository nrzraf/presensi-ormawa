<?php
/** QR presensi pribadi - FR-04, FR-05 */
$u = current_user();
$rid = (int)($_GET['reg'] ?? 0);
$reg = q_one("
    SELECT r.*, e.nama AS event_nama, e.tanggal, e.waktu, e.lokasi, e.status AS event_status, m.nama AS member_nama, m.nim
    FROM registration r
    JOIN event e ON e.id=r.event_id
    JOIN member m ON m.id=r.member_id
    WHERE r.id=?", [$rid]);
if (!$reg) { flash('error', 'Data pendaftaran tidak ditemukan.'); redirect('index.php?r=events'); }
// anggota hanya boleh melihat QR miliknya
if (!has_role('panitia','admin','pembina') && (int)$reg['member_id'] !== (int)$u['id']) {
    http_response_code(403); die('Akses ditolak.');
}
$qrData = 'ORMAWA|EV' . $reg['event_id'] . '|REG' . $reg['id'] . '|' . $reg['qr_token'];

$title = 'QR Presensi Saya';
$subtitle = 'Tunjukkan QR ke panitia';
ob_start(); ?>
<div class="grid col-2e">
  <div class="card">
    <div class="card-head">
      <div><h3><?= e($reg['event_nama']) ?></h3><div class="sub"><?= tgl_id($reg['tanggal']) ?> · <?= substr($reg['waktu'],0,5) ?> · <?= e($reg['lokasi']) ?></div></div>
      <?= status_badge($reg['status']) ?>
    </div>
    <div style="display:grid;place-items:center;padding:20px 0">
      <div style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px">
        <img src="<?= qr_img_url($qrData, 240) ?>" width="240" height="240" alt="QR Presensi">
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>Detail Pendaftaran</h3></div>
      <div class="table-scroll">
      <table>
        <tr><td class="muted">Nama</td><td><b><?= e($reg['member_nama']) ?></b></td></tr>
        <tr><td class="muted">NIM</td><td class="mono"><?= e($reg['nim']) ?></td></tr>
        <tr><td class="muted">Status</td><td><?= status_badge($reg['status']) ?></td></tr>
        <tr><td class="muted">Kode manual</td><td class="mono" style="font-size:20px;letter-spacing:.2em"><b><?= e($reg['kode_presensi']) ?></b></td></tr>
        <tr><td class="muted">Terdaftar</td><td class="mono"><?= date('d/m/Y H:i', strtotime($reg['registered_at'])) ?></td></tr>
      </table>
      </div>
    </div>
    <div class="card" style="text-align:center">
      <a class="btn btn-ghost btn-block" href="index.php?r=events">Kembali ke Daftar Acara</a>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
