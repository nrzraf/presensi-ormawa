<?php
/**
 * Cron / Scheduler — pengingat otomatis H-1 (FR-10).
 *
 * Jalankan manual:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\presensi-ormawa\cron_reminder.php
 *
 * Jadwalkan harian via Windows Task Scheduler (mis. tiap hari 08:00),
 * atau via URL: http://localhost:8080/presensi-ormawa/cron_reminder.php?key=...
 *
 * Kirim pengingat H-1 ke peserta acara yang berlangsung BESOK,
 * dan tandai sudah terkirim (idempotent — tidak dobel).
 */
require_once __DIR__ . '/mailer.php';

$isCli = (php_sapi_name() === 'cli');
$key = $_GET['key'] ?? '';
$allowedKey = 'ormawa-cron-2026';   // ubah sesuai kebutuhan

if (!$isCli && $key !== $allowedKey) {
    http_response_code(403);
    exit("403 — akses cron ditolak. Gunakan CLI atau ?key=...\n");
}

$besok = date('Y-m-d', strtotime('+1 day'));
$acara = q_all("SELECT * FROM event
    WHERE tanggal = ?
      AND status IN ('dipublikasikan','berlangsung')
      AND (h1_sent_at IS NULL OR h1_sent_at::date < CURRENT_DATE)", [$besok]);

$total = 0;
$detail = [];
foreach ($acara as $ev) {
    $n = notify_h1_reminder($ev);
    $total += $n;
    $detail[] = "- {$ev['nama']} ({$ev['tanggal']}): $n penerima";
}

$ts = date('Y-m-d H:i:s');
if ($isCli) {
    echo "[$ts] Pengingat H-1 diproses.\n";
    echo "Acara besok (" . $besok . "): " . count($acara) . "\n";
    foreach ($detail as $d) echo "  $d\n";
    echo "Total email/notifikasi: $total\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "[$ts] Pengingat H-1 diproses.\nAcara besok ($besok): " . count($acara) . "\n";
    foreach ($detail as $d) echo "  $d\n";
    echo "Total: $total\n";
}
