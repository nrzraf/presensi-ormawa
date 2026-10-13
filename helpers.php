<?php
/**
 * Helper umum: session, auth, CSRF, flash, audit, format.
 */
require_once __DIR__ . '/db.php';

// Sesi berbasis database (serverless/Vercel tidak menyimpan file sesi antar-instance).
require_once __DIR__ . '/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------- Auth & Sesi ---------------- */

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    // auto-logout setelah 30 menit idle (NFR-04)
    if (isset($_SESSION['last']) && (time() - $_SESSION['last']) > SESSION_TIMEOUT) {
        logout();
        return null;
    }
    $_SESSION['last'] = time();
    static $u = null;
    if ($u === null || ($u['id'] ?? 0) !== $_SESSION['uid']) {
        $u = q_one('SELECT m.*, r.nama AS role FROM member m JOIN role r ON r.id=m.role_id WHERE m.id=?', [$_SESSION['uid']]);
    }
    return $u ?: null;
}

function is_logged_in(): bool { return current_user() !== null; }

function user_role(): string { return current_user()['role'] ?? 'guest'; }

function has_role(string ...$roles): bool { return in_array(user_role(), $roles, true); }

function require_login(): void {
    if (!is_logged_in()) { flash('error', 'Silakan masuk terlebih dahulu.'); redirect('index.php?r=login'); }
}

function require_role(string ...$roles): void {
    require_login();
    if (!has_role(...$roles)) { http_response_code(403); die('<h1>403</h1><p>Akses ditolak untuk peran Anda.</p>'); }
}

function login(string $email, string $password): array {
    // rate-limit: 5 gagal / 10 menit  kunci 15 menit (FR-01)
    $fails = (int) q_val("SELECT COUNT(*) FROM login_attempt WHERE email=? AND berhasil=0 AND created_at > (NOW() - INTERVAL '10 minutes')", [$email]);
    if ($fails >= MAX_LOGIN_ATTEMPTS) {
        $last = q_val('SELECT created_at FROM login_attempt WHERE email=? AND berhasil=0 ORDER BY id DESC LIMIT 1', [$email]);
        if ($last && (time() - strtotime($last)) < LOCKOUT_SECONDS) {
            return [false, 'Terlalu banyak percobaan gagal. Akun terkunci sementara 15 menit.'];
        }
    }
    $u = q_one('SELECT * FROM member WHERE email=?', [$email]);
    if (!$u || $u['status'] !== 'aktif' || !password_verify($password, $u['password_hash'])) {
        q('INSERT INTO login_attempt (email, ip, berhasil) VALUES (?,?,0)', [$email, $_SERVER['REMOTE_ADDR'] ?? '-']);
        return [false, 'Email atau kata sandi salah.'];
    }
    q('INSERT INTO login_attempt (email, ip, berhasil) VALUES (?,?,1)', [$email, $_SERVER['REMOTE_ADDR'] ?? '-']);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['last'] = time();
    audit('login', 'member', (int)$u['id'], 'Berhasil masuk');
    return [true, ''];
}

function logout(): void {
    if (!empty($_SESSION['uid'])) audit('logout', 'member', (int)$_SESSION['uid'], 'Keluar');
    $_SESSION = [];
    session_destroy();
    session_start();
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(419); die('CSRF token tidak valid. Muat ulang halaman.');
    }
}

/* ---------------- Flash & redirect ---------------- */

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function redirect(string $url): void { header('Location: ' . $url); exit; }

/* ---------------- Audit (FR-14) ---------------- */

function audit(string $aksi, string $entitas, ?int $entitasId = null, string $detail = ''): void {
    $uid = $_SESSION['uid'] ?? null;
    q('INSERT INTO audit_log (actor_id, aksi, entitas, entitas_id, detail) VALUES (?,?,?,?,?)',
      [$uid, $aksi, $entitas, $entitasId, $detail]);
}

/* ---------------- Format & utilitas ---------------- */

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function kode_presensi(): string { return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT); }
function qr_token(): string { return strtoupper(bin2hex(random_bytes(16))); }

function tgl_id(string $date): string {
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function status_badge(string $status): string {
    $map = [
        'hadir'          => ['b-present', ' Hadir'],
        'cadangan'       => ['b-fallback', '! Cadangan'],
        'tidak_hadir'    => ['b-absent', ' Tidak Hadir'],
        'terdaftar'      => ['b-muted', 'Terdaftar'],
        'draft'          => ['b-muted', 'Draft'],
        'dipublikasikan' => ['b-info', 'Dipublikasikan'],
        'berlangsung'    => ['b-present', 'Berlangsung'],
        'ditutup'        => ['b-muted', 'Ditutup'],
        'dibatalkan'     => ['b-absent', 'Dibatalkan'],
    ];
    [$cls, $label] = $map[$status] ?? ['b-muted', ucfirst($status)];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

/** URL file QR (menggunakan layanan QR berbasis teks, tanpa dependency). */
function qr_img_url(string $data, int $size = 220): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}
