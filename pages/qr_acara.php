<?php
/**
 * QR Sesi Acara - ditampilkan panitia, dipindai peserta (FR-05).
 * Token sesi dibuat di server dan disimpan di event.qr_token.
 */
require_role('panitia', 'admin');
$u = current_user();

$eventId = (int)($_GET['event'] ?? 0);
$acara = q_all("SELECT e.id, e.nama, e.tanggal, e.status, e.qr_token,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
    (SELECT COUNT(*) FROM attendance a WHERE a.event_id=e.id) AS hadir
    FROM event e WHERE e.status IN ('dipublikasikan','berlangsung')
    ORDER BY e.tanggal DESC");
if (!$eventId && $acara) $eventId = (int)$acara[0]['id'];
$ev = $eventId ? q_one("SELECT * FROM event WHERE id=?", [$eventId]) : null;

// pastikan kolom qr_token ada (DB lama), lalu buat token sesi bila belum ada
if ($ev) {
    try { q("ALTER TABLE event ADD COLUMN IF NOT EXISTS qr_token VARCHAR(64) UNIQUE"); }
    catch (Throwable $e) { /* sudah ada / tidak berhak */ }
    $ev = q_one("SELECT * FROM event WHERE id=?", [$eventId]);
}
if ($ev && empty($ev['qr_token'])) {
    $tok = 'EVT' . $eventId . '-' . strtoupper(bin2hex(random_bytes(8)));
    q("UPDATE event SET qr_token=? WHERE id=?", [$tok, $eventId]);
    $ev['qr_token'] = $tok;
}

$hadir = $ev ? (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=?", [$eventId]) : 0;
$total = $ev ? (int) q_val("SELECT COUNT(*) FROM registration WHERE event_id=?", [$eventId]) : 0;
$qrData = $ev ? ('ORMAWA-SESI|EV' . $eventId . '|' . $ev['qr_token']) : '';

$title = 'QR Presensi Acara';
$subtitle = '';
ob_start(); ?>
<div class="card" style="margin-bottom:16px">
  <form method="get" class="inline">
    <input type="hidden" name="r" value="qr_acara">
    <div style="flex:1;min-width:240px">
      <label>Pilih acara</label>
      <select name="event" onchange="this.form.submit()">
        <?php if (!$acara): ?><option>- tidak ada acara aktif -</option><?php endif; ?>
        <?php foreach ($acara as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id']==$eventId?'selected':'' ?>><?= e($a['nama']) ?> (<?= $a['status'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if (!$ev): ?>
  <div class="card"><div class="empty">Belum ada acara aktif.</div></div>
<?php else: ?>
<div class="grid col-2">
  <div class="card" style="text-align:center">
    <div class="card-head" style="justify-content:center"><h3><?= e($ev['nama']) ?></h3></div>
    <div class="sub" style="margin-bottom:14px"><?= tgl_id($ev['tanggal']) ?> · <?= substr($ev['waktu'],0,5) ?> · <?= e($ev['lokasi']) ?></div>
    <div style="display:grid;place-items:center;padding:8px 0 16px">
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px">
        <img src="<?= qr_img_url($qrData, 300) ?>" width="300" height="300" alt="QR Sesi Acara">
      </div>
    </div>
    <div class="sub">Peserta memindai QR ini lewat menu Scan Presensi.</div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-head"><h3>Sesi Berjalan</h3><span class="badge b-info">Live</span></div>
      <div style="display:flex;justify-content:space-between;gap:12px;text-align:center">
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800"><?= $hadir ?></div><div class="sub">Hadir</div></div>
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800"><?= max(0,$total-$hadir) ?></div><div class="sub">Belum</div></div>
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800"><?= $total ?></div><div class="sub">Terdaftar</div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Kode Sesi</h3></div>
      <div class="mono" style="font-size:13px;word-break:break-all;background:var(--bg);border:1px solid var(--line);border-radius:10px;padding:12px"><?= e($ev['qr_token']) ?></div>
      <div class="sub" style="margin-top:10px">Bisa dibacakan bila kamera peserta bermasalah.</div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
