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
        'hadir'          => ['b-present', 'Hadir'],
        'cadangan'       => ['b-fallback', 'Cadangan'],
        'tidak_hadir'    => ['b-absent', 'Tidak Hadir'],
        'terdaftar'      => ['b-muted', 'Terdaftar'],
        'draft'          => ['b-muted', 'Draft'],
        'dipublikasikan' => ['b-info', 'Akan Datang'],
        'berlangsung'    => ['b-fallback', 'Berlangsung'],
        'ditutup'        => ['b-muted', 'Ditutup'],
        'dibatalkan'     => ['b-absent', 'Dibatalkan'],
        'diajukan'       => ['b-info', 'Diajukan'],
        'disetujui'      => ['b-present', 'Disetujui'],
        'ditolak'        => ['b-absent', 'Ditolak'],
        'revisi'         => ['b-warn', 'Revisi'],
    ];
    [$cls, $label] = $map[$status] ?? ['b-muted', ucfirst($status)];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

/** URL file QR (menggunakan layanan QR berbasis teks, tanpa dependency). */
function qr_img_url(string $data, int $size = 220): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}

/* ---------------- Ikon & komponen tampilan ---------------- */

/** Ikon garis (stroke) tanpa dependency eksternal. */
function icon(string $name, int $s = 18): string {
    static $paths = [
        'grid'     => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.8"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.8"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.8"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.8"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        'clip'     => '<rect x="4" y="3.5" width="16" height="17" rx="2.5"/><path d="M9 3.5h6M8.5 10l2 2 4-4M8.5 16h7"/>',
        'users'    => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0M16 5.2a3 3 0 0 1 0 5.6M17 20a5.5 5.5 0 0 0-2-4.2"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16M14 21V9h4a2 2 0 0 1 2 2v10M2 21h20M7 7h2M7 11h2M7 15h2"/>',
        'shield'   => '<path d="M12 3l7 3v5.5c0 4.3-2.9 7.6-7 8.5-4.1-.9-7-4.2-7-8.5V6z"/><circle cx="12" cy="10.5" r="2.2"/>',
        'gear'     => '<circle cx="12" cy="12" r="3.1"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 13.6H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 7l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 2.9-1.2V3a2 2 0 1 1 4 0v.1A1.7 1.7 0 0 0 17 4.6l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1.1z"/>',
        'bell'     => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'exit'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'pin'      => '<path d="M20 10.5c0 6-8 11.5-8 11.5S4 16.5 4 10.5a8 8 0 0 1 16 0z"/><circle cx="12" cy="10.5" r="2.6"/>',
        'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'check'    => '<path d="M20 6.5 9.5 17 4 11.5"/>',
        'file'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'trend'    => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'eye'      => '<path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/>',
        'user'     => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'alert'    => '<path d="M12 3.5 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>',
        'chev'     => '<path d="m6 9 6 6 6-6"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'lock'     => '<rect x="4" y="10.5" width="16" height="10.5" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'mail'     => '<rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3 6.5 9 6 9-6"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="i" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Kartu statistik (ikon + tren + nilai + label). */
function stat_card(string $ic, string $color, $val, string $lab, string $trend = '', bool $trendMute = false): string {
    $t = $trend !== ''
        ? '<span class="trend' . ($trendMute ? ' mute' : '') . '">' . ($trendMute ? '' : icon('trend', 13)) . $trend . '</span>'
        : '';
    return '<div class="stat"><div class="top"><span class="sic ' . $color . '">' . icon($ic, 20) . '</span>' . $t . '</div>'
         . '<div class="val">' . $val . '</div><div class="lab">' . e($lab) . '</div></div>';
}

/** "2 jam lalu" dari timestamp. */
function waktu_lalu(string $ts): string {
    $d = time() - strtotime($ts);
    if ($d < 60) return 'baru saja';
    if ($d < 3600) return floor($d / 60) . ' menit lalu';
    if ($d < 86400) return floor($d / 3600) . ' jam lalu';
    if ($d < 604800) return floor($d / 86400) . ' hari lalu';
    return date('d/m/Y', strtotime($ts));
}

/** Tanggal pendek: 20 Okt */
function tgl_short(string $date): array {
    $bln = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($date);
    return [date('j', $t), strtoupper($bln[(int)date('n', $t)])];
}
