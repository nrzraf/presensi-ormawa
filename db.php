<?php
/**
 * Koneksi database (PDO PostgreSQL) + helper query.
 */
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSLMODE;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<h1>Gagal terhubung ke database</h1><p>Periksa environment variable <b>DATABASE_URL</b> (Supabase) dan pastikan database sudah diinisialisasi lewat <b>install.php</b>.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
        }
    }
    return $pdo;
}

/** Jalankan query dengan parameter, kembalikan statement. */
function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/** Ambil satu baris. */
function q_one(string $sql, array $params = []): ?array {
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Ambil semua baris. */
function q_all(string $sql, array $params = []): array {
    return q($sql, $params)->fetchAll();
}

/** Ambil satu nilai kolom. */
function q_val(string $sql, array $params = []) {
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** ID dari baris yang baru disisipkan. */
function q_id(?string $table = null): int {
    if ($table !== null) {
        return (int) db()->lastInsertId($table . '_id_seq');
    }
    return (int) db()->lastInsertId();
}
