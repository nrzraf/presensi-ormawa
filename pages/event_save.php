<?php
/** Simpan acara (insert/update) - FR-02, FR-10 */
require_role('panitia','admin');
require_once __DIR__ . '/../mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php?r=events');
csrf_check();

$u = current_user();
$id = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$jenis = trim($_POST['jenis'] ?? 'Lainnya');
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$waktu = $_POST['waktu'] ?? '09:00';
$lokasi = trim($_POST['lokasi'] ?? '');
$kuota = max(1, (int)($_POST['kuota'] ?? 1));
$deskripsi = trim($_POST['deskripsi'] ?? '');
$status = ($_POST['aksi'] ?? 'draft') === 'publish' ? 'dipublikasikan' : 'draft';

if ($nama === '' || $lokasi === '') { flash('error', 'Nama dan lokasi wajib diisi.'); redirect('index.php?r=event_form' . ($id?"&id=$id":'')); }

if ($id) {
    $lama = q_one("SELECT * FROM event WHERE id=?", [$id]);
    q("UPDATE event SET nama=?,jenis=?,tanggal=?,waktu=?,lokasi=?,kuota=?,deskripsi=?,status=? WHERE id=?",
      [$nama,$jenis,$tanggal,$waktu,$lokasi,$kuota,$deskripsi,$status,$id]);
    audit('event_update', 'event', $id, 'Perbarui acara: ' . $nama);
    // FR-10: beri tahu peserta bila jadwal/lokasi berubah
    $waktuLama = substr((string)($lama['waktu'] ?? ''), 0, 5);
    if ($lama && ($lama['tanggal'] != $tanggal || $waktuLama !== $waktu || $lama['lokasi'] != $lokasi)) {
        notify_event_change(q_one("SELECT * FROM event WHERE id=?", [$id]), 'Perubahan jadwal acara');
    }
    flash('ok', 'Acara berhasil diperbarui.');
} else {
    q("INSERT INTO event (nama,jenis,tanggal,waktu,lokasi,kuota,deskripsi,status,dibuat_oleh) VALUES (?,?,?,?,?,?,?,?,?)",
      [$nama,$jenis,$tanggal,$waktu,$lokasi,$kuota,$deskripsi,$status,$u['id']]);
    $id = q_id('event');
    audit('event_create', 'event', $id, 'Buat acara: ' . $nama);
    // FR-10: notifikasi + email acara baru ke anggota
    if ($status === 'dipublikasikan') notify_new_event(q_one("SELECT * FROM event WHERE id=?", [$id]));
    flash('ok', 'Acara berhasil dibuat.');
}
redirect('index.php?r=event&id=' . $id);
