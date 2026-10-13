<?php
/** Organisasi - profil & statistik organisasi (admin). */
require_role('admin');
$title = 'Organisasi';
$subtitle = '';

$divisi = q_all("SELECT COALESCE(NULLIF(divisi,''),'Tanpa Divisi') AS divisi, COUNT(*) AS n
    FROM member WHERE status='aktif' GROUP BY 1 ORDER BY n DESC");
$roleCount = q_all("SELECT r.nama, COUNT(m.id) AS n FROM role r
    LEFT JOIN member m ON m.role_id=r.id GROUP BY r.id, r.nama ORDER BY r.id");
$totalAnggota = (int) q_val("SELECT COUNT(*) FROM member");
$aktif = (int) q_val("SELECT COUNT(*) FROM member WHERE status='aktif'");
$totalAcara = (int) q_val("SELECT COUNT(*) FROM event");
$totalHadir = (int) q_val("SELECT COUNT(*) FROM attendance");
$first = q_val("SELECT MIN(created_at) FROM member");
$tahun = $first ? date('Y', strtotime($first)) : date('Y');

ob_start(); ?>
<div class="grid col-4" style="margin-bottom:18px">
  <?= stat_card('building','rose', $totalAnggota, 'Total Anggota', '', true) ?>
  <?= stat_card('users','blue', $aktif, 'Anggota Aktif', '', true) ?>
  <?= stat_card('calendar','violet', $totalAcara, 'Total Acara', '', true) ?>
  <?= stat_card('check','green', $totalHadir, 'Kehadiran', '', true) ?>
</div>

<div class="grid col-2e">
  <div class="card">
    <div class="card-head"><h3>Profil Organisasi</h3></div>
    <div style="display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid var(--line)">
      <img src="index.php?r=logo" alt="Logo" style="width:56px;height:56px;object-fit:contain">
      <div><b style="font-size:16px"><?= APP_NAME ?></b><div class="sub">Organisasi Mahasiswa Universitas Bakrie</div></div>
    </div>
    <table style="margin-top:6px">
      <tr><td class="muted">Nama Organisasi</td><td><b><?= APP_NAME ?></b></td></tr>
      <tr><td class="muted">Institusi</td><td>Universitas Bakrie</td></tr>
      <tr><td class="muted">Sejak</td><td><?= $tahun ?></td></tr>
      <tr><td class="muted">Jumlah Divisi</td><td><?= count($divisi) ?></td></tr>
      <tr><td class="muted">Total Anggota</td><td><?= $totalAnggota ?> orang</td></tr>
    </table>
  </div>

  <div class="card">
    <div class="card-head"><h3>Anggota per Divisi</h3></div>
    <?php if (!$divisi): ?><div class="empty">Belum ada data.</div><?php else: ?>
      <?php $max = max(array_column($divisi,'n')); foreach ($divisi as $d): $p = $max>0 ? round($d['n']/$max*100) : 0; ?>
        <div style="padding:10px 0">
          <div style="display:flex;justify-content:space-between;gap:10px"><b style="font-size:13.5px"><?= e($d['divisi']) ?></b><span class="sub mono"><?= $d['n'] ?> orang</span></div>
          <div class="bar" style="margin-top:6px"><i style="width:<?= $p ?>%"></i></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-head"><h3>Komposisi Peran</h3><a class="link-more" href="index.php?r=members">Kelola Anggota <?= icon('chev', 14) ?></a></div>
  <div class="table-scroll">
    <table>
      <thead><tr><th>Peran</th><th class="num">Jumlah</th><th>Deskripsi</th></tr></thead>
      <tbody>
      <?php foreach ($roleCount as $rc): ?>
        <tr><td><span class="chip"><?= e($rc['nama']) ?></span></td><td class="num mono"><?= $rc['n'] ?></td>
        <td class="muted"><?= e(['anggota'=>'Anggota / kader organisasi','panitia'=>'Panitia / pengurus acara','pembina'=>'Pembina ormawa (approver)','admin'=>'Admin sistem'][$rc['nama']] ?? '') ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
