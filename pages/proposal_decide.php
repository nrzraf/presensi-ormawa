<?php
/** FR-03 - Proses keputusan pembina atas proposal (UC-03) */
require_role('pembina','admin');
require_once __DIR__ . '/../mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php?r=proposals');
csrf_check();

$u = current_user();
$id = (int)($_POST['id'] ?? 0);
$keputusan = $_POST['keputusan'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');

if (!in_array($keputusan, ['disetujui','ditolak','revisi'], true)) {
    flash('error', 'Keputusan tidak valid.');
    redirect('index.php?r=proposals');
}

$p = q_one("SELECT p.*, e.nama AS event_nama FROM proposal p JOIN event e ON e.id=p.event_id WHERE p.id=?", [$id]);
if (!$p) { flash('error', 'Proposal tidak ditemukan.'); redirect('index.php?r=proposals'); }

if ($keputusan !== 'disetujui' && $catatan === '') {
    flash('error', 'Catatan wajib diisi untuk revisi / penolakan.');
    redirect('index.php?r=proposal_review&id=' . $id);
}

q("UPDATE proposal SET status=?, catatan=?, diputus_at=NOW(), reviewer_id=? WHERE id=?",
  [$keputusan, $catatan !== '' ? $catatan : $p['catatan'], $u['id'], $id]);

$label = ['disetujui'=>'Disetujui','ditolak'=>'Ditolak','revisi'=>'Perlu Revisi'][$keputusan];
audit('proposal_' . $keputusan, 'proposal', $id, "Proposal {$label}: " . $p['event_nama']);

// Notifikasi ke pengaju (FR-10)
if ($p['diajukan_oleh']) {
    $pengaju = q_one("SELECT id, nama, email FROM member WHERE id=?", [$p['diajukan_oleh']]);
    if ($pengaju) {
        $isi = 'Proposal untuk acara <b>' . htmlspecialchars($p['event_nama']) . '</b> berstatus: <b>' . $label . '</b>'
             . ($catatan !== '' ? '<br>Catatan pembina: ' . htmlspecialchars($catatan) : '');
        q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?,'proposal_status',?)",
          [$pengaju['id'], $p['event_id'], "Proposal $label: " . $p['event_nama']]);
        send_mail($pengaju['email'], "[Proposal $label] " . $p['event_nama'],
            email_template('Proposal ' . $label, $isi, 'Lihat Acara', BASE_URL . '/index.php?r=event&id=' . $p['event_id']),
            'proposal_status', (int)$p['event_id']);
    }
}

flash('ok', "Proposal berhasil di-$label.");
redirect('index.php?r=proposals');
