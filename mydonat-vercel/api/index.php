<?php
// Router tunggal: semua request masuk ke sini, lalu diteruskan ke file di folder /app
$p = trim((string)($_GET['__p'] ?? ''), '/');
unset($_GET['__p'], $_REQUEST['__p']);

$app = realpath(__DIR__ . '/../app');

if ($p === '') $p = 'login.php';

// Gambar upload yang disimpan di database
if (preg_match('#^(uploads|produk)/[A-Za-z0-9_.\-]+$#', $p) && !preg_match('#\.php$#i', $p)) {
    $GLOBALS['__file_path'] = $p;
    chdir($app);
    require $app . '/_file.php';
    exit;
}

$name = preg_replace('#\.php$#i', '', $p);
$blocked = ['config', 'sesi', 'db', 'koneksi', '_file'];

if (!preg_match('#^[A-Za-z0-9_\-/]+$#', $name) || in_array(basename($name), $blocked, true)) {
    http_response_code(404);
    exit('404 Not Found');
}

$target = realpath($app . '/' . $name . '.php');
if ($target === false || strpos($target, $app . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('404 Not Found');
}

chdir(dirname($target));
require $target;
