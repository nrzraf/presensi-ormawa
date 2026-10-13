<?php
/** Kelola data anggota - UC-07, FR-09 */
require_role('admin');
$title = 'Kelola Anggota';
$subtitle = 'Kelola anggota & hak akses';

$cari = trim($_GET['cari'] ?? '');
$sql = "SELECT m.*, r.nama AS role FROM member m JOIN role r ON r.id=m.role_id";
$params = [];
if ($cari !== '') { $sql .= " WHERE m.nama LIKE ? OR m.nim LIKE ? OR m.email LIKE ?"; $params = ["%$cari%","%$cari%","%$cari%"]; }
$sql .= " ORDER BY m.role_id, m.nama";
$rows = q_all($sql, $params);

ob_start(); ?>
<div class="card" style="margin-bottom:16px">
  <form method="get" class="inline">
    <input type="hidden" name="r" value="members">
    <div style="flex:1;min-width:220px"><label>Cari anggota</label><input name="cari" value="<?= e($cari) ?>" placeholder="Nama / NIM / email..."></div>
    <button class="btn btn-primary">Cari</button>
    <a class="btn btn-ghost" href="index.php?r=member_form">Tambah Anggota</a>
  </form>
</div>

<div class="card">
  <div class="table-scroll">
  <table>
    <thead><tr><th>Nama</th><th>NIM</th><th>Email</th><th>Divisi</th><th>Peran</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $m): ?>
      <tr>
        <td><b><?= e($m['nama']) ?></b></td>
        <td class="mono"><?= e($m['nim']) ?></td>
        <td class="muted"><?= e($m['email']) ?></td>
        <td class="muted"><?= e($m['divisi']) ?></td>
        <td><span class="chip"><?= e($m['role']) ?></span></td>
        <td><span class="badge <?= $m['status']==='aktif'?'b-present':'b-absent' ?>"><?= $m['status']==='aktif'?'Aktif':'Nonaktif' ?></span></td>
        <td class="num"><a class="btn btn-ghost btn-sm" href="index.php?r=member_form&id=<?= $m['id'] ?>">Ubah</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
