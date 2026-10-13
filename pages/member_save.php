<?php
/** Simpan anggota - FR-09 */
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php?r=members');
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$nama = trim($_POST['nama'] ?? '');
$nim = trim($_POST['nim'] ?? '');
$email = trim($_POST['email'] ?? '');
$divisi = trim($_POST['divisi'] ?? '');
$role = (int)($_POST['role_id'] ?? 1);
$status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
$pass = $_POST['password'] ?? '';

if ($nama==='' || $nim==='' || $email==='') { flash('error','Nama, NIM, dan email wajib diisi.'); redirect('index.php?r=member_form'.($id?"&id=$id":'')); }

try {
    if ($id) {
        if ($pass !== '') {
            q("UPDATE member SET nama=?,nim=?,email=?,divisi=?,role_id=?,status=?,password_hash=? WHERE id=?",
              [$nama,$nim,$email,$divisi,$role,$status,password_hash($pass, PASSWORD_DEFAULT),$id]);
        } else {
            q("UPDATE member SET nama=?,nim=?,email=?,divisi=?,role_id=?,status=? WHERE id=?",
              [$nama,$nim,$email,$divisi,$role,$status,$id]);
        }
        audit('member_update','member',$id,"Perbarui anggota: $nama");
        flash('ok','Data anggota diperbarui.');
    } else {
        q("INSERT INTO member (nama,nim,email,password_hash,divisi,status,role_id) VALUES (?,?,?,?,?,?,?)",
          [$nama,$nim,$email,password_hash($pass, PASSWORD_DEFAULT),$divisi,$status,$role]);
        $newId = q_id('member');
        audit('member_create','member',$newId,"Tambah anggota: $nama");
        flash('ok','Anggota baru ditambahkan.');
    }
} catch (PDOException $e) {
    flash('error','NIM atau email sudah digunakan.');
}
redirect('index.php?r=members');
