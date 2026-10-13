<?php
/** RSVP / Pendaftaran acara - UC-04, FR-04 */
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$ev = q_one("SELECT * FROM event WHERE id=?", [$id]);
if (!$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=events'); }
if (!in_array($ev['status'], ['dipublikasikan','berlangsung'], true)) { flash('error', 'Acara tidak dibuka untuk pendaftaran.'); redirect('index.php?r=events'); }

// sudah terdaftar?  tampilkan QR yang ada (anti-duplikasi A2)
$reg = q_one("SELECT * FROM registration WHERE event_id=? AND member_id=?", [$id, $u['id']]);
if ($reg) { redirect('index.php?r=myqr&reg=' . $reg['id']); }

$regCount = (int) q_val("SELECT COUNT(*) FROM registration WHERE event_id=?", [$id]);
if ($regCount >= $ev['kuota']) { flash('error', 'Kuota acara sudah penuh.'); redirect('index.php?r=events'); }

// buat pendaftaran + QR unik
$kode = kode_presensi();
$token = qr_token();
q("INSERT INTO registration (event_id, member_id, kode_presensi, qr_token, status) VALUES (?,?,?,?,'terdaftar')",
  [$id, $u['id'], $kode, $token]);
$rid = q_id('registration');
audit('rsvp', 'registration', $rid, 'Mendaftar acara: ' . $ev['nama']);
// notifikasi konfirmasi (FR-10)
q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?, 'konfirmasi', ?)",
  [$u['id'], $id, 'Pendaftaran berhasil: ' . $ev['nama']]);
flash('ok', 'Pendaftaran berhasil! QR presensi Anda sudah dibuat.');
redirect('index.php?r=myqr&reg=' . $rid);
