<?php
/**
 * Konfigurasi aplikasi — Event Attendance Universitas Bakrie
 * Project Capstone Kelompok 7 — Universitas Bakrie
 *
 * Database: PostgreSQL (Supabase). Konfigurasi diambil dari environment
 * variable agar kredensial tidak tersimpan di dalam kode.
 */

// ---- Database ----
// Prioritas: DATABASE_URL -> POSTGRES_URL (Supabase via Vercel Marketplace)
// -> DB_HOST/DB_PORT/... -> default lokal.
$__env = static fn(string $k): string => (string)(getenv($k) ?: '');

$__db = null;
foreach (['DATABASE_URL', 'POSTGRES_URL_NON_POOLING', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL'] as $__key) {
    $__url = $__env($__key);
    if ($__url !== '' && str_starts_with($__url, 'postgres')) {
        $p = parse_url($__url);
        if (!empty($p['host'])) {
            $__db = [
                'host' => $p['host'],
                'port' => (string)($p['port'] ?? 5432),
                'name' => ltrim($p['path'] ?? '', '/') ?: 'postgres',
                'user' => urldecode($p['user'] ?? ''),
                'pass' => urldecode($p['pass'] ?? ''),
                'sslmode' => 'require',
            ];
            break;
        }
    }
}

if ($__db === null && $__env('POSTGRES_HOST') !== '') {
    $__db = [
        'host' => $__env('POSTGRES_HOST'),
        'port' => $__env('POSTGRES_PORT') ?: '5432',
        'name' => $__env('POSTGRES_DATABASE') ?: 'postgres',
        'user' => $__env('POSTGRES_USER') ?: 'postgres',
        'pass' => $__env('POSTGRES_PASSWORD'),
        'sslmode' => 'require',
    ];
}

if ($__db === null) {
    $__db = [
        'host' => $__env('DB_HOST') ?: '127.0.0.1',
        'port' => $__env('DB_PORT') ?: '5432',
        'name' => $__env('DB_NAME') ?: 'postgres',
        'user' => $__env('DB_USER') ?: 'postgres',
        'pass' => $__env('DB_PASS'),
        'sslmode' => $__env('DB_SSLMODE') ?: 'prefer',
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
