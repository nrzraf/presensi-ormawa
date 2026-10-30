<?php
/**
 * Hapus akun panitia / pembina - FR-09 (khusus admin).
 * Akun admin dan akun diri sendiri tidak dapat dihapus dari sini.
 */
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php?r=akun');
csrf_check();

$u  = current_user();
$id = (int)($_POST['id'] ?? 0);
$m  = $id ? q_one("SELECT m.*, r.nama AS role FROM member m JOIN role r ON r.id=m.role_id WHERE m.id=?", [$id]) : null;

if (!$m) {
    flash('error', 'Akun tidak ditemukan.');
} elseif ($id === (int)$u['id']) {
    flash('error', 'Anda tidak dapat menghapus akun sendiri.');
} elseif (!in_array($m['role'], ['panitia', 'pembina'], true)) {
    flash('error', 'Hanya akun panitia atau pembina yang dapat dihapus di halaman ini.');
} else {
    // lepaskan referensi agar penghapusan tidak gagal
    try { q("UPDATE event SET dibuat_oleh=NULL WHERE dibuat_oleh=?", [$id]); } catch (Throwable $e) {}
    try { q("DELETE FROM notification WHERE member_id=?", [$id]); } catch (Throwable $e) {}
    try { q("DELETE FROM app_session WHERE id LIKE ?", ['%']); } catch (Throwable $e) {}
    q("DELETE FROM member WHERE id=?", [$id]);
    audit('akun_delete', 'member', $id, 'Hapus akun ' . $m['role'] . ': ' . $m['nama']);
    flash('ok', 'Akun ' . $m['nama'] . ' dihapus.');
}
redirect('index.php?r=akun');
