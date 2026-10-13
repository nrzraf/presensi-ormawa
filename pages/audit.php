<?php
/** Log audit - FR-14 */
require_role('admin');
$title = 'Log Audit';
$subtitle = 'Jejak aktivitas sistem';
$rows = q_all("
    SELECT a.*, m.nama AS actor FROM audit_log a
    LEFT JOIN member m ON m.id=a.actor_id
    ORDER BY a.id DESC LIMIT 200");
ob_start(); ?>
<div class="card">
  <div class="card-head"><h3>Aktivitas Terbaru</h3><span class="chip">200 terakhir</span></div>
  <?php if (!$rows): ?><div class="empty">Belum ada aktivitas.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Entitas</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $a): ?>
      <tr>
        <td class="mono muted"><?= date('d/m H:i:s', strtotime($a['created_at'])) ?></td>
        <td><?= e($a['actor'] ?? 'Sistem') ?></td>
        <td><span class="chip"><?= e($a['aksi']) ?></span></td>
        <td class="muted"><?= e($a['entitas']) ?><?= $a['entitas_id'] ? ' #'.$a['entitas_id'] : '' ?></td>
        <td class="muted"><?= e($a['detail']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
