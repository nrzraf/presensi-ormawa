<?php
/**
 * Mailer & Notification helper — FR-10.
 * - Bila SMTP dikonfigurasi di config.php  kirim email sungguhan (SMTP socket, tanpa library).
 * - Bila tidak  catat ke mail_log + tabel notification (in-app) agar tetap bisa diverifikasi.
 */
require_once __DIR__ . '/helpers.php';

/** Kirim email. Mengembalikan [status, error]. status: terkirim|log|gagal */
function send_mail(string $to, string $subject, string $body, string $tipe = '', ?int $eventId = null): array {
    $status = 'log'; $error = null;

    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        [$ok, $error] = smtp_send($to, $subject, $body);
        $status = $ok ? 'terkirim' : 'gagal';
    }

    q('INSERT INTO mail_log (penerima, subjek, isi, tipe, event_id, status, error) VALUES (?,?,?,?,?,?,?)',
      [$to, $subject, $body, $tipe, $eventId, $status, $error]);

    return [$status, $error];
}

/** Kirim email via SMTP socket (auth opsional). */
function smtp_send(string $to, string $subject, string $body): array {
    $host = SMTP_HOST; $port = SMTP_PORT; $user = SMTP_USER; $pass = SMTP_PASS;
    $from = SMTP_FROM; $fromName = SMTP_FROM_NAME;
    $secure = defined('SMTP_SECURE') ? SMTP_SECURE : ''; // '', 'ssl', 'tls'
    $prefix = ($secure === 'ssl') ? 'ssl://' : '';

    $fp = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 15);
    if (!$fp) return [false, "Koneksi SMTP gagal: $errstr ($errno)"];

    $read = function() use ($fp) { return fgets($fp, 515); };
    $cmd  = function($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };

    $read(); // banner
    $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if ($secure === 'tls') { $cmd('STARTTLS'); stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT); $cmd('EHLO localhost'); }
    if ($user !== '') {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (strpos($r, '235') === false) { fclose($fp); return [false, 'Autentikasi SMTP gagal']; }
    }
    $cmd('MAIL FROM:<' . $from . '>');
    $cmd('RCPT TO:<' . $to . '>');
    $cmd('DATA');
    $headers  = "From: $fromName <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $data = $headers . "\r\n" . $body . "\r\n.";
    fwrite($fp, $data . "\r\n");
    $r = $read();
    $cmd('QUIT');
    fclose($fp);
    return strpos($r, '250') !== false ? [true, null] : [false, 'Server menolak pesan'];
}

/** Template HTML email. */
function email_template(string $judul, string $isi, ?string $cta = null, ?string $ctaUrl = null): string {
    $ctaHtml = $cta ? '<p style="margin:24px 0"><a href="' . $ctaUrl . '" style="background:#0F766E;color:#fff;padding:12px 22px;border-radius:10px;text-decoration:none;font-weight:600">' . $cta . '</a></p>' : '';
    return '<div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;color:#0F172A">'
        . '<div style="background:#0F766E;color:#fff;padding:18px 24px;border-radius:14px 14px 0 0;font-weight:700">' . APP_NAME . '</div>'
        . '<div style="border:1px solid #E2E8F0;border-top:none;border-radius:0 0 14px 14px;padding:24px">'
        . '<h2 style="font-size:19px;margin:0 0 12px">' . htmlspecialchars($judul) . '</h2>'
        . '<div style="color:#334155;font-size:14px;line-height:1.6">' . $isi . '</div>'
        . $ctaHtml
        . '<hr style="border:none;border-top:1px solid #E2E8F0;margin:22px 0"><p style="color:#94a3b8;font-size:12px">Email otomatis dari ' . APP_NAME . '. Mohon tidak dibalas.</p>'
        . '</div></div>';
}

/**
 * Notifikasi acara baru ke seluruh anggota aktif (FR-10).
 * Menulis in-app + mengirim email. Mengembalikan jumlah penerima.
 */
function notify_new_event(array $ev): int {
    $anggota = q_all("SELECT id, nama, email FROM member WHERE status='aktif' AND role_id=1");
    $isi = 'Acara baru: <b>' . htmlspecialchars($ev['nama']) . '</b><br>' . tgl_id($ev['tanggal']) . ' · ' . substr($ev['waktu'],0,5) . ' · ' . htmlspecialchars($ev['lokasi']);
    $body = email_template('Acara Baru: ' . $ev['nama'], $isi, 'Lihat & Daftar', BASE_URL . '/index.php?r=events');
    foreach ($anggota as $a) {
        q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?,'acara_baru',?)",
          [$a['id'], $ev['id'], 'Acara baru: ' . $ev['nama']]);
        send_mail($a['email'], '[Acara Baru] ' . $ev['nama'], $body, 'acara_baru', (int)$ev['id']);
    }
    return count($anggota);
}

/** Notifikasi perubahan jadwal acara ke peserta terdaftar (FR-10). */
function notify_event_change(array $ev, string $perubahan = 'Perubahan jadwal acara'): int {
    $peserta = q_all("SELECT DISTINCT m.id, m.nama, m.email FROM registration r JOIN member m ON m.id=r.member_id WHERE r.event_id=?", [$ev['id']]);
    $isi = $perubahan . ': <b>' . htmlspecialchars($ev['nama']) . '</b><br>Jadwal terbaru: ' . tgl_id($ev['tanggal']) . ' · ' . substr($ev['waktu'],0,5) . ' · ' . htmlspecialchars($ev['lokasi']);
    $body = email_template('Perubahan Acara: ' . $ev['nama'], $isi, 'Lihat Acara', BASE_URL . '/index.php?r=event&id=' . $ev['id']);
    foreach ($peserta as $p) {
        q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?,'perubahan',?)",
          [$p['id'], $ev['id'], 'Perubahan: ' . $ev['nama']]);
        send_mail($p['email'], '[Perubahan] ' . $ev['nama'], $body, 'perubahan', (int)$ev['id']);
    }
    return count($peserta);
}

/** Pengingat H-1 ke peserta terdaftar. Idempotent via event.h1_sent_at. */
function notify_h1_reminder(array $ev): int {
    $peserta = q_all("SELECT DISTINCT m.id, m.nama, m.email FROM registration r JOIN member m ON m.id=r.member_id WHERE r.event_id=?", [$ev['id']]);
    $isi = 'Pengingat: acara <b>' . htmlspecialchars($ev['nama']) . '</b> akan dilaksanakan <b>besok</b>.<br>' . tgl_id($ev['tanggal']) . ' · ' . substr($ev['waktu'],0,5) . ' · ' . htmlspecialchars($ev['lokasi']) . '<br>Jangan lupa membawa QR presensi Anda.';
    $body = email_template('Pengingat H-1: ' . $ev['nama'], $isi, 'Lihat QR Saya', BASE_URL . '/index.php?r=events');
    foreach ($peserta as $p) {
        q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?,'pengingat',?)",
          [$p['id'], $ev['id'], 'Pengingat H-1: ' . $ev['nama']]);
        send_mail($p['email'], '[Pengingat H-1] ' . $ev['nama'], $body, 'pengingat', (int)$ev['id']);
    }
    q("UPDATE event SET h1_sent_at=NOW() WHERE id=?", [$ev['id']]);
    return count($peserta);
}
