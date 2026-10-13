<?php
/**
 * Front controller untuk Vercel (runtime vercel-php).
 * - Menyajikan berkas statis (logo, css, js) langsung.
 * - Menentukan route dari query (?r=...) atau dari path (mis. /install.php).
 * - Meneruskan ke router aplikasi (index.php).
 */
$root = dirname(__DIR__);
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri  = $uri === null || $uri === '' ? '/' : urldecode($uri);

// --- berkas statis ---
$static = ['png','jpg','jpeg','gif','svg','ico','webp','css','js','txt','woff','woff2','ttf','map'];
$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
if ($uri !== '/' && in_array($ext, $static, true)) {
    $file = realpath($root . $uri);
    if ($file && strncmp($file, $root, strlen($root)) === 0 && is_file($file)) {
        $mime = [
            'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
            'svg'=>'image/svg+xml','ico'=>'image/x-icon','webp'=>'image/webp','css'=>'text/css',
            'js'=>'application/javascript','txt'=>'text/plain','woff'=>'font/woff',
            'woff2'=>'font/woff2','ttf'=>'font/ttf','map'=>'application/json',
        ][$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        return;
    }
}

// --- route dari query (?r=...) ---
if (!isset($_GET['r']) || $_GET['r'] === '') {
    // fallback: tentukan dari path (mis. /install.php, /cron_reminder.php)
    if (preg_match('#/install\.php$#', $uri)) {
        $_GET['r'] = 'install';
    } elseif (preg_match('#/cron_reminder\.php$#', $uri)) {
        $_GET['r'] = 'cron';
    } else {
        $_GET['r'] = 'dashboard';
    }
}

chdir($root);
require $root . '/index.php';
