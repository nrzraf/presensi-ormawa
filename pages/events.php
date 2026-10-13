<?php
/** Daftar Acara - FR-04, FR-13 */
$u = current_user();
$title = 'Daftar Acara';
$subtitle = 'Acara terbuka · daftar & dapatkan QR';

$cari = trim($_GET['cari'] ?? '');
$sql = "SELECT e.*,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
    (SELECT id FROM registration r2 WHERE r2.event_id=e.id AND r2.member_id=?) AS my_reg
    FROM event e WHERE e.status IN ('dipublikasikan','berlangsung','ditutup')";
$params = [$u['id']];
if ($cari !== '') { $sql .= " AND e.nama LIKE ?"; $params[] = "%$cari%"; }
$sql .= " ORDER BY e.tanggal ASC";
$acara = q_all($sql, $params);

ob_start(); ?>
<div class="card" style="margin-bottom:16px">
  <form method="get" class="inline">
    <input type="hidden" name="r" value="events">
    <div style="flex:1;min-width:220px"><label>Cari acara</label><input name="cari" value="<?= e($cari) ?>" placeholder="Nama acara..."></div>
    <button class="btn btn-primary">Cari</button>
    <?php if ($cari): ?><a class="btn btn-ghost" href="index.php?r=events">Reset</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <?php if (!$acara): ?><div class="empty">Tidak ada acara ditemukan.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Acara</th><th>Status</th><th>Jadwal</th><th>Lokasi</th><th class="num">Kuota</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($acara as $a):
      $penuh = $a['reg'] >= $a['kuota']; ?>
      <tr>
        <td><b><?= e($a['nama']) ?></b><div class="sub"><?= e($a['jenis']) ?></div></td>
        <td><?= status_badge($a['status']) ?></td>
        <td class="muted"><?= tgl_id($a['tanggal']) ?><br><span class="mono"><?= substr($a['waktu'],0,5) ?></span></td>
        <td class="muted"><?= e($a['lokasi']) ?></td>
        <td class="num mono"><?= $a['reg'] ?>/<?= $a['kuota'] ?></td>
        <td class="num">
          <?php if ($a['my_reg']): ?>
            <a class="btn btn-ghost btn-sm" href="index.php?r=myqr&reg=<?= $a['my_reg'] ?>">Lihat QR</a>
          <?php elseif ($penuh): ?>
            <button class="btn btn-ghost btn-sm" disabled>Kuota Penuh</button>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="index.php?r=rsvp&id=<?= $a['id'] ?>">Daftar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
