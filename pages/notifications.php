<?php
/** Notifikasi pengguna - FR-10 */
$u = current_user();
$title = 'Notifikasi';
$subtitle = 'Pemberitahuan & pengingat';
$rows = q_all("SELECT * FROM notification WHERE member_id=? ORDER BY id DESC LIMIT 100", [$u['id']]);
// tandai dibaca
q("UPDATE notification SET dibaca=1 WHERE member_id=?", [$u['id']]);
ob_start(); ?>
<div class="card">
  <div class="card-head"><h3>Kotak Masuk</h3><span class="chip"><?= count($rows) ?> pesan</span></div>
  <?php if (!$rows): ?><div class="empty">Belum ada notifikasi.</div><?php else: ?>
    <?php foreach ($rows as $n): ?>
    <div style="display:flex;gap:12px;padding:14px 0;border-bottom:1px solid var(--border)">
      <div style="flex:1">
        <div style="font-weight:600"><?= e($n['isi']) ?></div>
        <div class="sub"><?= e($n['tipe']) ?> · <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></div>
      </div>
      <?php if (!$n['dibaca']): ?><span class="badge b-info">Baru</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
