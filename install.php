<?php
/**
 * Installer — membuat tabel + data contoh (PostgreSQL / Supabase).
 * Buka: /install.php
 */
require_once __DIR__ . '/config.php';

$msg = [];
$err = null;
$ok  = false;

if (isset($_POST['do_install'])) {
    try {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSLMODE;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // ---- Tabel ----
        $schema = [
            "DROP TABLE IF EXISTS audit_log CASCADE",
            "DROP TABLE IF EXISTS notification CASCADE",
            "DROP TABLE IF EXISTS attendance CASCADE",
            "DROP TABLE IF EXISTS registration CASCADE",
            "DROP TABLE IF EXISTS proposal_file CASCADE",
            "DROP TABLE IF EXISTS proposal CASCADE",
            "DROP TABLE IF EXISTS event CASCADE",
            "DROP TABLE IF EXISTS member CASCADE",
            "DROP TABLE IF EXISTS role CASCADE",
            "DROP TABLE IF EXISTS login_attempt CASCADE",
            "DROP TABLE IF EXISTS mail_log CASCADE",
            "DROP TABLE IF EXISTS app_session CASCADE",

            "CREATE TABLE role (
                id SERIAL PRIMARY KEY,
                nama VARCHAR(30) NOT NULL UNIQUE,
                deskripsi VARCHAR(120)
            )",

            "CREATE TABLE member (
                id SERIAL PRIMARY KEY,
                nama VARCHAR(120) NOT NULL,
                nim VARCHAR(30) NOT NULL UNIQUE,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                divisi VARCHAR(80),
                no_hp VARCHAR(24),
                status VARCHAR(10) NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif','nonaktif')),
                role_id INT NOT NULL REFERENCES role(id),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE event (
                id SERIAL PRIMARY KEY,
                nama VARCHAR(180) NOT NULL,
                jenis VARCHAR(60) NOT NULL,
                tanggal DATE NOT NULL,
                waktu TIME NOT NULL,
                lokasi VARCHAR(180) NOT NULL,
                kuota INT NOT NULL DEFAULT 0,
                deskripsi TEXT,
                status VARCHAR(20) NOT NULL DEFAULT 'draft'
                    CHECK (status IN ('draft','dipublikasikan','berlangsung','ditutup','dibatalkan')),
                alasan_batal VARCHAR(255),
                dibuat_oleh INT REFERENCES member(id) ON DELETE SET NULL,
                h1_sent_at TIMESTAMP,
                perubahan_dikirim_at TIMESTAMP,
                qr_token VARCHAR(64) UNIQUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE registration (
                id SERIAL PRIMARY KEY,
                event_id INT NOT NULL REFERENCES event(id) ON DELETE CASCADE,
                member_id INT NOT NULL REFERENCES member(id) ON DELETE CASCADE,
                kode_presensi CHAR(6) NOT NULL,
                qr_token CHAR(32) NOT NULL UNIQUE,
                status VARCHAR(12) NOT NULL DEFAULT 'terdaftar'
                    CHECK (status IN ('terdaftar','hadir','tidak_hadir')),
                registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (event_id, member_id)
            )",

            "CREATE TABLE attendance (
                id SERIAL PRIMARY KEY,
                registration_id INT NOT NULL UNIQUE REFERENCES registration(id) ON DELETE CASCADE,
                event_id INT NOT NULL REFERENCES event(id) ON DELETE CASCADE,
                member_id INT NOT NULL REFERENCES member(id) ON DELETE CASCADE,
                status VARCHAR(10) NOT NULL DEFAULT 'hadir' CHECK (status IN ('hadir','cadangan')),
                metode VARCHAR(10) NOT NULL DEFAULT 'qr' CHECK (metode IN ('qr','manual')),
                scanned_by INT REFERENCES member(id) ON DELETE SET NULL,
                checkin_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE proposal (
                id SERIAL PRIMARY KEY,
                event_id INT NOT NULL REFERENCES event(id) ON DELETE CASCADE,
                judul VARCHAR(180),
                file_url VARCHAR(255),
                diajukan_oleh INT REFERENCES member(id) ON DELETE SET NULL,
                status VARCHAR(12) NOT NULL DEFAULT 'diajukan'
                    CHECK (status IN ('diajukan','disetujui','ditolak','revisi')),
                catatan VARCHAR(255),
                diajukan_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                diputus_at TIMESTAMP,
                reviewer_id INT REFERENCES member(id) ON DELETE SET NULL
            )",

            "CREATE TABLE proposal_file (
                id SERIAL PRIMARY KEY,
                proposal_id INT NOT NULL REFERENCES proposal(id) ON DELETE CASCADE,
                nama VARCHAR(200) NOT NULL,
                mime VARCHAR(120),
                isi TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE notification (
                id SERIAL PRIMARY KEY,
                member_id INT NOT NULL REFERENCES member(id) ON DELETE CASCADE,
                event_id INT REFERENCES event(id) ON DELETE SET NULL,
                tipe VARCHAR(40) NOT NULL,
                isi VARCHAR(255) NOT NULL,
                dibaca SMALLINT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE audit_log (
                id SERIAL PRIMARY KEY,
                actor_id INT,
                aksi VARCHAR(60) NOT NULL,
                entitas VARCHAR(40) NOT NULL,
                entitas_id INT,
                detail VARCHAR(255),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE login_attempt (
                id SERIAL PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                berhasil SMALLINT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX idx_login_email_time ON login_attempt (email, created_at)",

            "CREATE TABLE mail_log (
                id SERIAL PRIMARY KEY,
                penerima VARCHAR(150) NOT NULL,
                subjek VARCHAR(200) NOT NULL,
                isi TEXT,
                tipe VARCHAR(40),
                event_id INT,
                status VARCHAR(10) NOT NULL DEFAULT 'log' CHECK (status IN ('terkirim','gagal','log')),
                error VARCHAR(255),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX idx_mail_tipe ON mail_log (tipe)",

            "CREATE TABLE app_session (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT NOT NULL,
                last_access BIGINT NOT NULL DEFAULT 0
            )",
            "CREATE INDEX idx_session_access ON app_session (last_access)",
        ];
        foreach ($schema as $sql) { $pdo->exec($sql); }
        $msg[] = 'Tabel dibuat (10 tabel).';

        // ---- Seed ----
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        $rs = $pdo->prepare('INSERT INTO role (nama, deskripsi) VALUES (?, ?)');
        foreach ([
            ['anggota', 'Anggota / kader organisasi'],
            ['panitia', 'Panitia / pengurus acara'],
            ['pembina', 'Pembina ormawa (approver)'],
            ['admin',   'Admin sistem'],
        ] as $r) { $rs->execute($r); }
        $msg[] = '4 peran dibuat.';

        $ms = $pdo->prepare('INSERT INTO member (nama, nim, email, password_hash, divisi, status, role_id) VALUES (?,?,?,?,?,?,?)');
        foreach ([
            ['Admin Sistem',      '1000000001', 'admin@ormawa.bakrie.ac.id',   'IT/PSDM',      'aktif', 4],
            ['Budi Santoso',      '1232001006', 'panitia@student.bakrie.ac.id', 'Acara',        'aktif', 2],
            ['Dr. Ratna Pembina', '1000000002', 'pembina@ormawa.bakrie.ac.id',  'Kemahasiswaan','aktif', 3],
            ['Siti Aminah',       '1232001012', 'anggota@student.bakrie.ac.id', 'Humas',        'aktif', 1],
            ['Andi Pratama',      '1212001004', 'andi@student.bakrie.ac.id',    'Acara',        'aktif', 1],
            ['Dewi Lestari',      '1202721002', 'dewi@student.bakrie.ac.id',    'Dana Usaha',   'aktif', 1],
            ['Rafi Ibnusalis',    '1232001018', 'rafi@student.bakrie.ac.id',    'PSDM',         'aktif', 1],
        ] as $m) { $ms->execute([$m[0],$m[1],$m[2],$hash,$m[3],$m[4],$m[5]]); }
        $msg[] = '7 akun contoh dibuat (password: password123).';

        $es = $pdo->prepare('INSERT INTO event (nama, jenis, tanggal, waktu, lokasi, kuota, deskripsi, status, dibuat_oleh) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ([
            ['Seminar Nasional Kepemimpinan', 'Seminar', '2026-05-12', '09:00:00', 'Aula Utama Universitas Bakrie', 150, 'Seminar kepemimpinan untuk seluruh pengurus dan anggota ormawa.', 'berlangsung', 2],
            ['Pelatihan Manajemen Organisasi', 'Pelatihan', '2026-05-18', '13:00:00', 'Ruang 302', 100, 'Pelatihan manajemen untuk pengurus baru.', 'dipublikasikan', 2],
            ['Rapat Kerja Tahunan', 'Rapat Kerja', '2026-05-20', '08:00:00', 'Ruang Rapat', 80, 'Rapat kerja penyusunan program kerja tahunan.', 'dipublikasikan', 2],
            ['Pengabdian Masyarakat Desa', 'Pengabdian Masyarakat', '2026-05-25', '07:00:00', 'Desa Sukamaju', 60, 'Kegiatan pengabdian masyarakat.', 'draft', 2],
        ] as $e) { $es->execute($e); }
        $msg[] = '4 acara contoh dibuat.';

        $reg = $pdo->prepare('INSERT INTO registration (event_id, member_id, kode_presensi, qr_token, status) VALUES (?,?,?,?,?) RETURNING id');
        $att = $pdo->prepare('INSERT INTO attendance (registration_id, event_id, member_id, status, metode, scanned_by, checkin_at) VALUES (?,?,?,?,?,?,?)');
        $upd = $pdo->prepare("UPDATE registration SET status='hadir' WHERE id=?");
        $n = 0;
        foreach ([[1,4],[1,5],[1,6],[1,7],[2,4],[2,5],[3,4]] as $r) {
            $kode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $reg->execute([$r[0], $r[1], $kode, strtoupper(bin2hex(random_bytes(16))), 'terdaftar']);
            $rid = (int) $reg->fetchColumn();
            $n++;
            if ($r[0] == 1 && $r[1] != 6) {
                $manual = ($r[1] == 5);
                $att->execute([$rid, 1, $r[1], $manual ? 'cadangan' : 'hadir', $manual ? 'manual' : 'qr', 2,
                    '2026-05-12 09:1' . random_int(0,9) . ':0' . random_int(0,9)]);
                $upd->execute([$rid]);
            }
        }
        $msg[] = "$n pendaftaran contoh dibuat.";

        $pdo->exec("INSERT INTO proposal (event_id, judul, status, catatan, diputus_at, reviewer_id) VALUES (1, 'Proposal Seminar Nasional Kepemimpinan', 'disetujui', 'Disetujui untuk dilaksanakan', NOW(), 3)");
        $msg[] = '1 proposal contoh dibuat.';

        $ok = true;
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,sans-serif;background:#F6FAFB;color:#2D2D2D;display:grid;place-items:center;min-height:100vh;padding:24px}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;max-width:560px;width:100%;box-shadow:0 8px 24px rgba(15,23,42,.06)}
h1{font-size:22px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
p.sub{color:#64748B;font-size:14px;margin-bottom:22px}
.btn{display:inline-flex;align-items:center;justify-content:center;width:100%;height:46px;border:none;border-radius:10px;background:#580018;color:#fff;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit}
.btn:hover{background:#4A0E17}
ul{list-style:none;margin:18px 0}
li{padding:9px 0;font-size:14px;border-bottom:1px solid #F1F5F9}
.msg{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:14px 16px;margin-bottom:18px}
.err{background:#FEF2F2;border-color:#FECACA;color:#BE123C}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13.5px}
td{padding:8px;border-bottom:1px solid #F1F5F9}
td:first-child{color:#64748B;width:44%}
code{background:#F1F5F9;padding:2px 6px;border-radius:5px;font-size:13px}
a.go{display:inline-block;margin-top:16px;background:#580018;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600}
</style>
</head>
<body>
<div class="card">
<?php if ($ok): ?>
  <div class="msg"><b>Instalasi berhasil!</b></div>
  <ul><?php foreach ($msg as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
  <p style="font-size:14px;margin-top:6px">Akun demo (semua password <code>password123</code>):</p>
  <table>
    <tr><td>Admin</td><td><code>admin@ormawa.bakrie.ac.id</code></td></tr>
    <tr><td>Panitia</td><td><code>panitia@student.bakrie.ac.id</code></td></tr>
    <tr><td>Pembina</td><td><code>pembina@ormawa.bakrie.ac.id</code></td></tr>
    <tr><td>Anggota</td><td><code>anggota@student.bakrie.ac.id</code></td></tr>
  </table>
  <a class="go" href="index.php">Buka Aplikasi</a>
<?php else: ?>
  <h1>Instalasi</h1>
  <p class="sub">Membuat 10 tabel dan data contoh pada database <code><?= htmlspecialchars(DB_NAME) ?></code>.</p>
  <?php if ($err): ?><div class="msg err"><b>Gagal:</b> <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post">
    <button class="btn" name="do_install" value="1">Jalankan Instalasi</button>
  </form>
  <p class="sub" style="margin-top:16px;font-size:12.5px">Menjalankan ulang akan menghapus &amp; membuat ulang seluruh data.</p>
<?php endif; ?>
</div>
</body>
</html>
