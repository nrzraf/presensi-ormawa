<?php
/** Menyajikan logo aplikasi (agar tidak bergantung pada penyajian berkas statis). */
$file = __DIR__ . '/../LOGO_UB.png';
if (!is_file($file)) { http_response_code(404); exit; }
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
readfile($file);
