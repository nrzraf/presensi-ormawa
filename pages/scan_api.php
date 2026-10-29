<?php
/**
 * API verifikasi presensi - FR-05, FR-06.
 * Peserta mengirim token sesi acara (dari QR yang ditampilkan panitia).
 * Balas JSON.
 */
require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'pesan'=>'Metode tidak diizinkan']); exit; }
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) { echo json_encode(['ok'=>false,'pesan'=>'Sesi tidak valid, muat ulang.']); exit; }

$u = current_user();
$code = trim($_POST['code'] ?? '');

// terima format "ORMAWA-SESI|EV<id>|<token>" atau token mentah
$token = $code;
if (str_contains($code, '|')) {
    $parts = explode('|', $code);
    $token = end($parts);
}
$token = trim($token);
if ($token === '') { echo json_encode(['ok'=>false,'pesan'=>'Kode kosong']); exit; }

$ev = q_one("SELECT * FROM event WHERE qr_token=?", [$token]);
if (!$ev) { echo json_encode(['ok'=>false,'pesan'=>'QR tidak dikenali']); exit; }
if (!in_array($ev['status'], ['dipublikasikan','berlangsung'], true)) {
    echo json_encode(['ok'=>false,'pesan'=>'Presensi belum dibuka untuk acara ini']); exit;
}

$reg = q_one("SELECT * FROM registration WHERE event_id=? AND member_id=?", [$ev['id'], $u['id']]);
if (!$reg) { echo json_encode(['ok'=>false,'pesan'=>'Anda belum terdaftar di acara ini']); exit; }

$exists = q_one("SELECT * FROM attendance WHERE registration_id=?", [$reg['id']]);
if ($exists) { echo json_encode(['ok'=>false,'pesan'=>'Anda sudah tercatat hadir']); exit; }

q("INSERT INTO attendance (registration_id, event_id, member_id, status, metode, scanned_by, checkin_at) VALUES (?,?,?,'hadir','qr',?,NOW())",
  [$reg['id'], $ev['id'], $u['id'], $u['id']]);
q("UPDATE registration SET status='hadir' WHERE id=?", [$reg['id']]);
audit('presensi', 'attendance', q_id('attendance'), 'Presensi mandiri: ' . $u['nama'] . ' @ ' . $ev['nama']);

echo json_encode([
    'ok'    => true,
    'nama'  => $u['nama'],
    'pesan' => $ev['nama'] . ' · ' . date('H:i:s'),
]);
