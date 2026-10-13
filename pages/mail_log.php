<?php
/** Outbox email - FR-10 (admin) */
require_role('admin');
$title = 'Email Terkirim';
$rows = q_all("SELECT * FROM mail_log ORDER BY id DESC LIMIT 200");
$kirim = (int) q_val("SELECT COUNT(*) FROM mail_log WHERE status='terkirim'");
$log   = (int) q_val("SELECT COUNT(*) FROM mail_log WHERE status='log'");
$gagal = (int) q_val("SELECT COUNT(*) FROM mail_log WHERE status='gagal'");
ob_start(); ?>
<div class="grid col-3" style="margin-bottom:16px">
  <div class="kpi"><div class="lab">Terkirim</div><div class="val"><?= $kirim ?></div></div>
  <div class="kpi"><div class="lab">Mode Log</div><div class="val"><?= $log ?></div></div>
  <div class="kpi"><div class="lab">Gagal</div><div class="val"><?= $gagal ?></div></div>
</div>
<div class="card">
  <div class="card-head"><h3>Outbox</h3><span class="chip">200 terakhir</span></div>
  <?php if (!$rows): ?><div class="empty">Belum ada email.</div><?php else: ?>
  <div class="table-scroll">
  <table>
    <thead><tr><th>Waktu</th><th>Penerima</th><th>Subjek</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $m): ?>
      <tr>
        <td class="mono muted"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
        <td class="muted"><?= e($m['penerima']) ?></td>
        <td><b><?= e($m['subjek']) ?></b><div class="sub"><?= e($m['tipe']) ?></div></td>
        <td><span class="badge <?= $m['status']==='terkirim'?'b-present':($m['status']==='gagal'?'b-absent':'b-muted') ?>"><?= e($m['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
