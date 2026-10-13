<?php
/** Manajemen / Daftar Acara - FR-04, FR-13 */
$u = current_user();
$isAnggota = has_role('anggota');
$title = $isAnggota ? 'Daftar Acara' : 'Manajemen Acara';
$subtitle = '';
$cari = trim($_GET['cari'] ?? '');
$jenis = trim($_GET['jenis'] ?? '');

$base = "SELECT e.*,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
    (SELECT COUNT(*) FROM attendance a WHERE a.event_id=e.id) AS hadir,
    (SELECT id FROM registration r2 WHERE r2.event_id=e.id AND r2.member_id=?) AS my_reg
    FROM event e";
$params = [$u['id']];

if ($isAnggota) {
    $where = " WHERE e.status IN ('dipublikasikan','berlangsung','ditutup')";
} else {
    $where = " WHERE 1=1";
}
if ($cari !== '') { $where .= " AND e.nama LIKE ?"; $params[] = "%$cari%"; }
if ($jenis !== '') { $where .= " AND e.jenis = ?"; $params[] = $jenis; }
$acara = q_all($base . $where . " ORDER BY e.tanggal ASC", $params);
$kategori = q_all("SELECT DISTINCT jenis FROM event ORDER BY jenis");

$aksi = has_role('panitia','admin')
    ? '<a class="btn btn-primary" href="index.php?r=event_form">' . icon('plus', 17) . 'Buat Acara</a>'
    : '';

ob_start(); ?>
<div class="toolbar">
  <div class="search">
    <?= icon('search') ?>
    <input id="q" placeholder="Cari acara" value="<?= e($cari) ?>">
  </div>
  <div class="pills" id="pills">
    <button type="button" class="pill <?= $jenis === '' ? 'active' : '' ?>" data-j="">Semua</button>
    <?php foreach ($kategori as $k): ?>
      <button type="button" class="pill <?= $jenis === $k['jenis'] ? 'active' : '' ?>" data-j="<?= e(strtolower($k['jenis'])) ?>"><?= e($k['jenis']) ?></button>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($isAnggota): ?>
  <div class="evgrid" id="cards">
    <?php if (!$acara): ?><div class="card"><div class="empty">Tidak ada acara ditemukan.</div></div><?php endif; ?>
    <?php foreach ($acara as $a): [$d, $m] = tgl_short($a['tanggal']); $penuh = $a['reg'] >= $a['kuota']; ?>
      <article class="evcard" data-jenis="<?= e(strtolower($a['jenis'])) ?>" data-nama="<?= e(strtolower($a['nama'])) ?>">
        <div class="cover">
          <span class="big"><?= e(strtoupper(substr($a['nama'],0,1))) ?></span>
          <span class="st"><?= status_badge($a['status']) ?></span>
          <span class="pill"><?= e($a['jenis']) ?></span>
        </div>
        <div class="body">
          <h4><?= e($a['nama']) ?></h4>
          <div class="line"><?= icon('calendar', 15) ?><?= $d ?> <?= ucfirst(strtolower($m)) ?> <?= date('Y', strtotime($a['tanggal'])) ?> · <?= substr($a['waktu'],0,5) ?></div>
          <div class="line"><?= icon('pin', 15) ?><?= e($a['lokasi']) ?></div>
          <div class="line"><?= icon('users', 15) ?><?= $a['reg'] ?>/<?= $a['kuota'] ?> peserta</div>
          <div class="acts">
            <?php if ($a['my_reg']): ?>
              <a class="btn btn-ghost btn-sm" href="index.php?r=myqr&reg=<?= $a['my_reg'] ?>"><?= icon('star', 15) ?>Lihat QR</a>
            <?php elseif ($penuh): ?>
              <button class="btn btn-ghost btn-sm" disabled>Kuota Penuh</button>
            <?php else: ?>
              <a class="btn btn-primary btn-sm" href="index.php?r=rsvp&id=<?= $a['id'] ?>">Daftar Sekarang</a>
            <?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="index.php?r=event&id=<?= $a['id'] ?>" aria-label="Detail"><?= icon('eye', 15) ?></a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

<?php else: ?>
  <div class="card">
    <?php if (!$acara): ?><div class="empty">Tidak ada acara ditemukan.</div><?php else: ?>
    <div class="table-scroll">
      <table id="tbl">
        <thead><tr><th>Acara</th><th>Status</th><th>Jadwal</th><th>Lokasi</th><th class="num">Peserta</th><th class="num">Hadir</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($acara as $a): ?>
          <tr data-jenis="<?= e(strtolower($a['jenis'])) ?>" data-nama="<?= e(strtolower($a['nama'])) ?>">
            <td><b><?= e($a['nama']) ?></b><div class="sub"><?= e($a['jenis']) ?></div></td>
            <td><?= status_badge($a['status']) ?></td>
            <td class="muted"><?= tgl_id($a['tanggal']) ?><br><span class="mono"><?= substr($a['waktu'],0,5) ?></span></td>
            <td class="muted"><?= e($a['lokasi']) ?></td>
            <td class="num mono"><?= $a['reg'] ?>/<?= $a['kuota'] ?></td>
            <td class="num mono"><?= $a['hadir'] ?></td>
            <td class="num">
              <?php if (has_role('panitia','admin')): ?>
                <a class="btn btn-ghost btn-sm" href="index.php?r=scan&event=<?= $a['id'] ?>">Presensi</a>
              <?php endif; ?>
              <a class="btn btn-ghost btn-sm" href="index.php?r=event&id=<?= $a['id'] ?>">Detail</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<script>
(function(){
  var pills=document.querySelectorAll("#pills .pill"), q=document.getElementById("q"),
      cards=document.querySelectorAll("#cards .evcard, #tbl tbody tr"), aktif="<?= e(strtolower($jenis)) ?>";
  function filter(){
    var s=(q.value||"").toLowerCase();
    cards.forEach(function(c){
      var ok=(!aktif||c.dataset.jenis===aktif)&&(!s||(c.dataset.nama||"").indexOf(s)>-1);
      c.style.display=ok?"":"none";
    });
  }
  pills.forEach(function(b){ b.addEventListener("click",function(){
    pills.forEach(function(x){x.classList.remove("active")}); b.classList.add("active");
    aktif=b.dataset.j||""; filter();
  });});
  q.addEventListener("input",filter);
})();
</script>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
