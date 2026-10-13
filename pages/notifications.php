<?php
/** Notifikasi pengguna - FR-10 */
$u = current_user();
$title = 'Notifikasi';
$subtitle = 'Pemberitahuan dan pengingat acara.';
$rows = q_all("SELECT n.*, e.nama AS ev FROM notification n
    LEFT JOIN event e ON e.id=n.event_id
    WHERE n.member_id=? ORDER BY n.id DESC LIMIT 100", [$u['id']]);
q("UPDATE notification SET dibaca=1 WHERE member_id=?", [$u['id']]);

$ikonTipe = ['event_baru'=>'calendar','perubahan'=>'edit','pengingat'=>'clock','proposal'=>'file','presensi'=>'check'];
ob_start(); ?>
<div class="card">
  <div class="card-head"><h3>Kotak Masuk</h3><span class="chip"><?= count($rows) ?> pesan</span></div>
  <?php if (!$rows): ?>
    <div class="empty">Belum ada notifikasi.</div>
  <?php else: ?>
    <?php foreach ($rows as $n): $ic = $ikonTipe[$n['tipe']] ?? 'bell'; ?>
      <div class="act">
        <span class="aic sic rose" style="width:38px;height:38px;border-radius:50%"><?= icon($ic, 17) ?></span>
        <div class="txt" style="flex:1">
          <div style="font-weight:600"><?= e($n['isi']) ?></div>
          <div class="time"><?= e(str_replace('_',' ', ucfirst($n['tipe']))) ?><?= $n['ev'] ? ' · ' . e($n['ev']) : '' ?> · <?= waktu_lalu($n['created_at']) ?></div>
        </div>
        <?php if (!$n['dibaca']): ?><span class="badge b-info">Baru</span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
