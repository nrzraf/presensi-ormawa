<?php
/** Dasbor - FR-08, FR-11 */
$u = current_user();
$title = 'Dasbor';

/* ============================================================
   TAMPILAN ANGGOTA - riwayat & persentase kehadiran PRIBADI (FR-11)
   ============================================================ */
if (has_role('anggota')) {
    $subtitle = 'Riwayat kehadiran Anda';
    $mine = q_all("
        SELECT e.nama, e.tanggal, e.jenis, r.status AS reg_status,
               a.status AS att_status, a.metode, a.checkin_at
        FROM registration r
        JOIN event e ON e.id=r.event_id
        LEFT JOIN attendance a ON a.registration_id=r.id
        WHERE r.member_id=?
        ORDER BY e.tanggal DESC", [$u['id']]);
    $totalAcara = count($mine);
    $hadirPribadi = 0;
    foreach ($mine as $m) if (in_array($m['att_status'], ['hadir','cadangan'], true)) $hadirPribadi++;
    $pctPribadi = $totalAcara > 0 ? round($hadirPribadi / $totalAcara * 100) : 0;

    ob_start(); ?>
    <div class="grid col-4" style="margin-bottom:16px">
      <div class="kpi"><div class="lab">Acara Diikuti</div><div class="val"><?= $totalAcara ?></div><div class="delta">terdaftar</div></div>
      <div class="kpi"><div class="lab">Kehadiran</div><div class="val"><?= $hadirPribadi ?></div><div class="delta up">hadir</div></div>
      <div class="kpi"><div class="lab">Persentase</div><div class="val"><?= $pctPribadi ?>%</div><div class="delta">dari acara diikuti</div></div>
      <div class="kpi"><div class="lab">Status Aktif</div><div class="val" style="font-size:19px"><?= $pctPribadi >= 75 ? 'Baik' : 'Perlu Tingkatkan' ?></div><div class="delta">syarat keaktifan</div></div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Riwayat Kehadiran Saya</h3></div>
      <?php if (!$mine): ?><div class="empty">Belum ada riwayat.</div><?php else: ?>
      <div class="table-scroll">
      <table>
        <thead><tr><th>Acara</th><th>Tanggal</th><th>Jenis</th><th>Status</th><th>Metode</th><th class="num">Check-in</th></tr></thead>
        <tbody>
        <?php foreach ($mine as $m): ?>
          <tr>
            <td><b><?= e($m['nama']) ?></b></td>
            <td class="muted"><?= tgl_id($m['tanggal']) ?></td>
            <td class="muted"><?= e($m['jenis']) ?></td>
            <td><?= status_badge($m['att_status'] ?? 'tidak_hadir') ?></td>
            <td><?= $m['metode'] ? '<span class="chip">'.($m['metode']==='qr'?'QR':'Manual').'</span>' : '<span class="muted">-</span>' ?></td>
            <td class="num mono"><?= $m['checkin_at'] ? date('H:i:s', strtotime($m['checkin_at'])) : '<span class="muted">-</span>' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
    <?php $content = ob_get_clean();
    require __DIR__ . '/../layout.php';
    return;
}

$subtitle = 'Ringkasan partisipasi';

$totalAcara = (int) q_val("SELECT COUNT(*) FROM event WHERE status IN ('dipublikasikan','berlangsung','ditutup')");
$totalAnggota = (int) q_val("SELECT COUNT(*) FROM member WHERE status='aktif'");
$hadir = (int) q_val("SELECT COUNT(*) FROM attendance");
$terdaftar = (int) q_val("SELECT COUNT(*) FROM registration");
$avg = $terdaftar > 0 ? round($hadir / $terdaftar * 100) : 0;

// acara terbaru + rekap
$acara = q_all("
    SELECT e.*,
        (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
        (SELECT COUNT(*) FROM attendance a WHERE a.event_id=e.id) AS hadir
    FROM event e ORDER BY e.tanggal DESC, e.waktu DESC LIMIT 5");

// anggota teraktif
$top = q_all("
    SELECT m.nama, m.divisi,
        COUNT(a.id) AS hadir,
        (SELECT COUNT(*) FROM registration r2 WHERE r2.member_id=m.id) AS total
    FROM member m
    LEFT JOIN attendance a ON a.member_id=m.id
    WHERE m.status='aktif'
    GROUP BY m.id ORDER BY hadir DESC LIMIT 5");

ob_start(); ?>
<div class="grid col-4" style="margin-bottom:16px">
  <div class="kpi"><div class="lab">Total Acara</div><div class="val"><?= $totalAcara ?></div><div class="delta">aktif &amp; selesai</div></div>
  <div class="kpi"><div class="lab">Rata-rata Kehadiran</div><div class="val"><?= $avg ?>%</div><div class="delta up">dari <?= $terdaftar ?> pendaftaran</div></div>
  <div class="kpi"><div class="lab">Anggota Aktif</div><div class="val"><?= $totalAnggota ?></div><div class="delta">terdaftar</div></div>
  <div class="kpi"><div class="lab">Total Presensi</div><div class="val"><?= $hadir ?></div><div class="delta">tercatat</div></div>
</div>

<div class="grid col-2e">
  <div class="card">
    <div class="card-head"><h3>Acara Terbaru</h3></div>
    <?php if (!$acara): ?><div class="empty">Belum ada acara.</div><?php else: ?>
    <div class="table-scroll">
    <table>
      <thead><tr><th>Acara</th><th>Status</th><th>Tanggal</th><th class="num">Hadir</th><th class="num">Daftar</th><th class="num">%</th></tr></thead>
      <tbody>
      <?php foreach ($acara as $a): $pct = $a['reg']>0 ? round($a['hadir']/$a['reg']*100) : 0; ?>
        <tr>
          <td><a href="index.php?r=event&id=<?= $a['id'] ?>"><b><?= e($a['nama']) ?></b></a><div class="sub"><?= e($a['jenis']) ?></div></td>
          <td><?= status_badge($a['status']) ?></td>
          <td class="muted"><?= tgl_id($a['tanggal']) ?></td>
          <td class="num mono"><?= $a['hadir'] ?></td>
          <td class="num mono"><?= $a['reg'] ?></td>
          <td class="num mono"><?= $pct ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h3>Anggota Teraktif</h3></div>
    <?php if (!$top): ?><div class="empty">Belum ada data.</div><?php else: ?>
      <?php foreach ($top as $t): $p = $t['total']>0 ? round($t['hadir']/$t['total']*100) : 0; ?>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid var(--border)">
        <div><b><?= e($t['nama']) ?></b><div class="sub"><?= e($t['divisi']) ?></div></div>
        <div style="width:42%"><div class="bar"><i style="width:<?= $p ?>%"></i></div><div class="sub mono" style="text-align:right"><?= $p ?>%</div></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
