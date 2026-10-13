<?php
/** FR-03 - Unduh berkas proposal (disimpan di database). */
require_login();
$id = (int)($_GET['id'] ?? 0);
$f = q_one("SELECT * FROM proposal_file WHERE id=?", [$id]);
if (!$f) { http_response_code(404); exit('Berkas tidak ditemukan.'); }

$u = current_user();
$boleh = has_role('pembina', 'admin');
if (!$boleh) {
    // panitia hanya boleh mengunduh berkas proposal yang dia sendiri ajukan
    $prop = q_one("SELECT diajukan_oleh FROM proposal WHERE file_url = ?", ['index.php?r=proposal_file&id=' . $id]);
    $boleh = $prop && (int)$prop['diajukan_oleh'] === (int)$u['id'];
}
if (!$boleh) { http_response_code(403); exit('Akses ditolak.'); }

$isi = base64_decode((string)$f['isi']);

header('Content-Type: ' . ($f['mime'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($f['nama']) . '"');
header('Content-Length: ' . strlen($isi));
header('X-Content-Type-Options: nosniff');
echo $isi;
