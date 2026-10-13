<?php
/**
 * Konfigurasi aplikasi — Event Attendance Universitas Bakrie
 * Project Capstone Kelompok 7 — Universitas Bakrie
 *
 * Database: PostgreSQL (Supabase). Konfigurasi diambil dari environment
 * variable agar kredensial tidak tersimpan di dalam kode.
 */

// ---- Database ----
// Prioritas: DATABASE_URL (Supabase) -> PGHOST/PGPORT/... -> default lokal.
$__db = [];
$__url = getenv('DATABASE_URL');
if ($__url) {
    $p = parse_url($__url);
    $__db = [
        'host' => $p['host'] ?? 'localhost',
        'port' => (string)($p['port'] ?? 5432),
        'name' => ltrim($p['path'] ?? '', '/'),
        'user' => urldecode($p['user'] ?? ''),
        'pass' => urldecode($p['pass'] ?? ''),
        'sslmode' => 'require',
    ];
} else {
    $__db = [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'postgres',
        'user' => getenv('DB_USER') ?: 'postgres',
        'pass' => getenv('DB_PASS') ?: '',
        'sslmode' => getenv('DB_SSLMODE') ?: 'prefer',
    ];
}

define('DB_HOST', $__db['host']);
define('DB_PORT', $__db['port']);
define('DB_NAME', $__db['name']);
define('DB_USER', $__db['user']);
define('DB_PASS', $__db['pass']);
define('DB_SSLMODE', $__db['sslmode']);

// ---- Aplikasi ----
define('APP_NAME', 'Event Attendance Universitas Bakrie');
define('APP_ORG', 'Organisasi Mahasiswa · Universitas Bakrie');
define('BASE_URL', getenv('APP_BASE_URL') !== false ? getenv('APP_BASE_URL') : '');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 900);

// ---- SMTP (opsional, FR-10) ----
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'no-reply@ormawa.bakrie.ac.id');
define('SMTP_FROM_NAME', 'Event Attendance Universitas Bakrie');

// ---- Zona waktu ----
date_default_timezone_set('Asia/Jakarta');

// ---- Error ----
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
