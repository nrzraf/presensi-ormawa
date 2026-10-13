<?php
/** Daftar Peserta - FR-09 (panitia: lihat saja; admin: kelola penuh) */
require_role('panitia', 'admin');
$bolehKelola = has_role('admin');
$title = 'Peserta';
$subtitle = '';

$cari = trim($_GET['cari'] ?? '');
$sql = "SELECT m.*, r.nama AS role FROM member m JOIN role r ON r.id=m.role_id";
$params = [];
if ($cari !== '') { $sql .= " WHERE m.nama LIKE ? OR m.nim LIKE ? OR m.email LIKE ?"; $params = ["%$cari%","%$cari%","%$cari%"]; }
$sql .= " ORDER BY m.role_id, m.nama";
$rows = q_all($sql, $params);

ob_start(); ?>
<div class="toolbar">
  <div class="search"><?= icon('search') ?><input id="q" placeholder="Cari nama / NIM / email"></div>
  <?php if ($bolehKelola): ?>
    <a class="btn btn-primary" href="index.php?r=member_form"><?= icon('plus', 16) ?>Tambah Anggota</a>
  <?php endif; ?>
</div>

<div class="card">
  <?php if (!$rows): ?><div class="empty">Tidak ada data ditemukan.</div><?php else: ?>
  <div class="table-scroll">
  <table id="tbl">
    <thead><tr><th>Nama</th><th>NIM</th><th>Email</th><th>Divisi</th><th>Peran</th><th>Status</th><?php if ($bolehKelola): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $m): ?>
      <tr data-nama="<?= e(strtolower($m['nama'].' '.$m['nim'].' '.$m['email'])) ?>">
        <td><b><?= e($m['nama']) ?></b></td>
        <td class="mono"><?= e($m['nim']) ?></td>
        <td class="muted"><?= e($m['email']) ?></td>
        <td class="muted"><?= e($m['divisi']) ?></td>
        <td><span class="chip"><?= e($m['role']) ?></span></td>
        <td><span class="badge <?= $m['status']==='aktif'?'b-present':'b-absent' ?>"><?= $m['status']==='aktif'?'Aktif':'Nonaktif' ?></span></td>
        <?php if ($bolehKelola): ?>
          <td class="num"><a class="btn btn-ghost btn-sm" href="index.php?r=member_form&id=<?= $m['id'] ?>">Ubah</a></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<script>
(function(){
  var q=document.getElementById("q"), rows=document.querySelectorAll("#tbl tbody tr");
  q.addEventListener("input",function(){
    var s=q.value.toLowerCase();
    rows.forEach(function(r){ r.style.display=(r.dataset.nama||"").indexOf(s)>-1?"":"none"; });
  });
})();
</script>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
