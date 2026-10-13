<?php
/**
 * Sesi berbasis database.
 * Vercel bersifat serverless: filesystem /tmp tidak awet antar-instance,
 * jadi sesi disimpan di PostgreSQL (Supabase) agar login tidak acak keluar.
 *
 * Handler dibuat tahan-gagal: bila tabel belum ada (mis. sebelum install.php
 * dijalankan), sesi tidak memunculkan error 500.
 */
require_once __DIR__ . '/db.php';

class DbSessionHandler implements SessionHandlerInterface
{
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string
    {
        try {
            $row = q_one('SELECT data FROM app_session WHERE id=?', [$id]);
            return $row ? (string)$row['data'] : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            q('INSERT INTO app_session (id, data, last_access) VALUES (?,?,?)
               ON CONFLICT (id) DO UPDATE SET data=EXCLUDED.data, last_access=EXCLUDED.last_access',
              [$id, $data, time()]);
        } catch (Throwable $e) {
            // tabel belum ada / DB sedang tak tersedia
        }
        return true;
    }

    public function destroy($id): bool
    {
        try { q('DELETE FROM app_session WHERE id=?', [$id]); } catch (Throwable $e) {}
        return true;
    }

    public function gc($max_lifetime): int
    {
        try {
            $st = q('DELETE FROM app_session WHERE last_access < ?', [time() - (int)$max_lifetime]);
            return $st->rowCount();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (PHP_SAPI !== 'cli') {
    $__handler = new DbSessionHandler();
    session_set_save_handler($__handler, true);
}
