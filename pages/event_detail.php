<?php
/** Detail acara + peserta (panitia/admin) */
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$ev = q_one("SELECT * FROM event WHERE id=?", [$id]);
if (!$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=events'); }

// aksi panitia: ubah status / batalkan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role('panitia','admin')) {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'status') {
        $s = $_POST['status'] ?? '';
        if (in_array($s, ['draft','dipublikasikan','berlangsung','ditutup'], true)) {
            q("UPDATE event SET status=? WHERE id=?", [$s, $id]);
            audit('event_status', 'event', $id, "Ubah status ke $s");
            if ($s === 'dipublikasikan') {
                // notifikasi acara baru ke anggota (FR-10)
                require_once __DIR__ . '/../mailer.php';
                notify_new_event(q_one("SELECT * FROM event WHERE id=?", [$id]));
            }
            flash('ok', 'Status acara diperbarui.');
        }
    } elseif ($act === 'cancel') {
        q("UPDATE event SET status='dibatalkan', alasan_batal=? WHERE id=?", [trim($_POST['alasan'] ?? ''), $id]);
        audit('event_cancel', 'event', $id, 'Acara dibatalkan: ' . ($_POST['alasan'] ?? ''));
        flash('ok', 'Acara dibatalkan.');
    }
    redirect('index.php?r=event&id=' . $id);
}

$reg = (int) q_val("SELECT COUNT(*) FROM registration WHERE event_id=?", [$id]);
$hadir = (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=?", [$id]);
$peserta = q_all("
    SELECT r.*, m.nama, m.nim, m.divisi, a.status AS att_status, a.metode, a.checkin_at
    FROM registration r JOIN member m ON m.id=r.member_id
    LEFT JOIN attendance a ON a.registration_id=r.id
    WHERE r.event_id=? ORDER BY m.nama", [$id]);

$title = 'Detail Acara';
$subtitle = e($ev['nama']);
ob_start(); ?>
<div class="grid col-4" style="margin-bottom:16px">
  <div class="kpi"><div class="lab">Terdaftar</div><div class="val"><?= $reg ?>/<?= $ev['kuota'] ?></div></div>
  <div class="kpi"><div class="lab">Hadir</div><div class="val"><?= $hadir ?></div><div class="delta"><?= $reg>0?round($hadir/$reg*100):0 ?>% kehadiran</div></div>
  <div class="kpi"><div class="lab">Jenis</div><div class="val" style="font-size:19px"><?= e($ev['jenis']) ?></div></div>
  <div class="kpi"><div class="lab">Status</div><div class="val" style="font-size:18px"><?= status_badge($ev['status']) ?></div></div>
</div>

<div class="grid col-2">
  <div class="card">
    <div class="card-head"><h3>Informasi Acara</h3></div>
    <div class="table-scroll">
    <table>
      <tr><td class="muted">Tanggal</td><td><?= tgl_id($ev['tanggal']) ?></td></tr>
      <tr><td class="muted">Waktu</td><td class="mono"><?= substr($ev['waktu'],0,5) ?></td></tr>
      <tr><td class="muted">Lokasi</td><td><?= e($ev['lokasi']) ?></td></tr>
      <tr><td class="muted">Deskripsi</td><td><?= e($ev['deskripsi']) ?></td></tr>
      <?php if ($ev['status']==='dibatalkan' && $ev['alasan_batal']): ?>
      <tr><td class="muted">Alasan batal</td><td class="down"><?= e($ev['alasan_batal']) ?></td></tr>
      <?php endif; ?>
    </table>
    </div>
    <?php if (has_role('panitia','admin')): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
      <a class="btn btn-primary btn-sm" href="index.php?r=scan&event=<?= $id ?>">Scan Presensi</a>
      <a class="btn btn-ghost btn-sm" href="index.php?r=proposal_form&event=<?= $id ?>">Ajukan Proposal</a>
      <a class="btn btn-ghost btn-sm" href="index.php?r=event_form&id=<?= $id ?>">Edit Acara</a>
      <a class="btn btn-ghost btn-sm" href="index.php?r=export&id=<?= $id ?>">Unduh Rekap</a>
    </div>
    <?php endif; ?>
  </div>

  <?php if (has_role('panitia','admin')): ?>
  <div class="card">
    <div class="card-head"><h3>Kelola Status</h3></div>
    <form method="post" class="inline" style="margin-bottom:12px">
      <?= csrf_field() ?><input type="hidden" name="act" value="status">
      <div style="flex:1"><label>Ubah status acara</label>
        <select name="status">
          <?php foreach (['draft'=>'Draft','dipublikasikan'=>'Dipublikasikan','berlangsung'=>'Berlangsung (buka presensi)','ditutup'=>'Ditutup'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $ev['status']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary">Simpan</button>
    </form>
    <?php if ($ev['status'] !== 'dibatalkan'): ?>
    <form method="post" onsubmit="return confirm('Batalkan acara ini?')">
      <?= csrf_field() ?><input type="hidden" name="act" value="cancel">
      <label>Alasan pembatalan</label>
      <input name="alasan" placeholder="mis. jadwal bentrok" required>
      <button class="btn btn-danger btn-sm" style="margin-top:10px">Batalkan Acara</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (has_role('panitia','pembina','admin')): ?>
<div class="card">
  <div class="card-head"><h3>Peserta &amp; Kehadiran</h3><span class="chip"><?= $reg ?> peserta</span></div>
  <?php if (!$peserta): ?><div class="empty">Belum ada peserta terdaftar.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Nama</th><th>NIM</th><th>Divisi</th><th>Status</th><th>Metode</th><th class="num">Check-in</th></tr></thead>
    <tbody>
    <?php foreach ($peserta as $p): ?>
      <tr>
        <td><b><?= e($p['nama']) ?></b></td>
        <td class="mono"><?= e($p['nim']) ?></td>
        <td class="muted"><?= e($p['divisi']) ?></td>
        <td><?= status_badge($p['att_status'] ?? $p['status']) ?></td>
        <td><?= $p['metode'] ? '<span class="chip">'.($p['metode']==='qr'?'QR':'Manual').'</span>' : '<span class="muted">-</span>' ?></td>
        <td class="num mono"><?= $p['checkin_at'] ? date('H:i:s', strtotime($p['checkin_at'])) : '<span class="muted">-</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php else:
  // Anggota: tampilkan status keikutsertaan PRIBADI, bukan daftar peserta lain
  $myreg = q_one("SELECT r.*, a.status AS att_status, a.checkin_at
                  FROM registration r LEFT JOIN attendance a ON a.registration_id=r.id
                  WHERE r.event_id=? AND r.member_id=?", [$id, $u['id']]); ?>
<div class="card">
  <div class="card-head"><h3>Keikutsertaan Saya</h3></div>
  <?php if ($myreg): ?>
    <div class="table-scroll">
    <table>
      <tr><td class="muted">Status</td><td><?= status_badge($myreg['att_status'] ?? 'terdaftar') ?></td></tr>
      <tr><td class="muted">Check-in</td><td class="mono"><?= $myreg['checkin_at'] ? date('d/m/Y H:i:s', strtotime($myreg['checkin_at'])) : '-' ?></td></tr>
    </table>
    </div>
    <a class="btn btn-primary btn-sm" style="margin-top:14px" href="index.php?r=myqr&reg=<?= $myreg['id'] ?>">Lihat QR Presensi</a>
  <?php else: ?>
    <div class="empty">Anda belum terdaftar. <a href="index.php?r=events">Daftar</a>.</div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
