<?php
/**
 * API verifikasi presensi - FR-05, FR-06.
 * Terima QR token (ORMAWA|EV..|REG..|TOKEN) atau kode manual 6 digit.
 * Balas JSON.
 */
require_role('panitia','admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'pesan'=>'Metode tidak diizinkan']); exit; }
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) { echo json_encode(['ok'=>false,'pesan'=>'Sesi tidak valid, muat ulang.']); exit; }

$u = current_user();
$eventId = (int)($_POST['event_id'] ?? 0);
$code = trim($_POST['code'] ?? '');
$metode = ($_POST['metode'] ?? 'qr') === 'manual' ? 'manual' : 'qr';

$ev = q_one("SELECT * FROM event WHERE id=?", [$eventId]);
if (!$ev) { echo json_encode(['ok'=>false,'pesan'=>'Acara tidak ditemukan']); exit; }
if (!in_array($ev['status'], ['dipublikasikan','berlangsung'], true)) {
    echo json_encode(['ok'=>false,'pesan'=>'Presensi belum dibuka untuk acara ini']); exit;
}

// Cari pendaftaran: via token QR atau kode manual
$reg = null;
if ($metode === 'qr') {
    // format: ORMAWA|EV<id>|REG<id>|<token>
    $token = $code;
    if (str_contains($code, '|')) {
        $parts = explode('|', $code);
        $token = end($parts);
    }
    $reg = q_one("SELECT r.*, m.nama, m.nim, m.divisi FROM registration r JOIN member m ON m.id=r.member_id WHERE r.qr_token=? AND r.event_id=?", [$token, $eventId]);
} else {
    if (!preg_match('/^\d{6}$/', $code)) { echo json_encode(['ok'=>false,'pesan'=>'Kode harus 6 digit angka']); exit; }
    $reg = q_one("SELECT r.*, m.nama, m.nim, m.divisi FROM registration r JOIN member m ON m.id=r.member_id WHERE r.kode_presensi=? AND r.event_id=?", [$code, $eventId]);
}

if (!$reg) { echo json_encode(['ok'=>false,'pesan'=>'Kode tidak valid / bukan peserta acara ini']); exit; }

// cegah ganda
$exists = q_one("SELECT * FROM attendance WHERE registration_id=?", [$reg['id']]);
if ($exists) {
    echo json_encode(['ok'=>false,'pesan'=>$reg['nama'].' sudah tercatat hadir sebelumnya', 'nama'=>$reg['nama']]);
    exit;
}

// catat presensi (immutable)
$status = $metode === 'manual' ? 'cadangan' : 'hadir';
q("INSERT INTO attendance (registration_id, event_id, member_id, status, metode, scanned_by, checkin_at) VALUES (?,?,?,?,?,?,NOW())",
  [$reg['id'], $eventId, $reg['member_id'], $status, $metode, $u['id']]);
q("UPDATE registration SET status='hadir' WHERE id=?", [$reg['id']]);
audit('presensi', 'attendance', q_id('attendance'), "Presensi {$reg['nama']} via $metode");

$hadir  = (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=?", [$eventId]);
$manual = (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=? AND metode='manual'", [$eventId]);

echo json_encode([
    'ok'     => true,
    'nama'   => $reg['nama'],
    'nim'    => $reg['nim'],
    'divisi' => $reg['divisi'],
    'metode' => $metode,
    'waktu'  => date('H:i:s'),
    'hadir'  => $hadir,
    'manual' => $manual,
]);
