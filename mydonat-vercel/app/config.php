<?php
// Konfigurasi utama (dipakai oleh db.php, koneksi.php, sesi.php)
// Semua pengaturan database dibaca dari Environment Variables Vercel.
date_default_timezone_set('Asia/Jakarta');

// Perilaku mysqli seperti PHP lama: tidak melempar exception
mysqli_report(MYSQLI_REPORT_OFF);

if (getenv('APP_DEBUG') === '1') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
}

function env_val($key, $default = '') {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function koneksi_db() {
    static $link = null;
    if ($link) return $link;

    $host = env_val('DB_HOST', 'localhost');
    $user = env_val('DB_USER', 'root');
    $pass = env_val('DB_PASS', '');
    $name = env_val('DB_NAME', 'mydonat');
    $port = (int) env_val('DB_PORT', '3306');
    $ssl  = strtolower(env_val('DB_SSL', 'true')) !== 'false' && $host !== 'localhost';

    $link = mysqli_init();
    mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $flags = 0;
    if ($ssl) {
        mysqli_ssl_set($link, null, null, null, null, null);
        $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
    }
    if (!@mysqli_real_connect($link, $host, $user, $pass, $name, $port, null, $flags)) {
        http_response_code(500);
        die("Koneksi database gagal: " . mysqli_connect_error() .
            "<br>Cek Environment Variables di Vercel (DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT).");
    }
    mysqli_set_charset($link, 'utf8mb4');
    mysqli_query($link, "SET time_zone = '+07:00'");
    return $link;
}

function base_url() {
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https';
    $host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $proto . '://' . $host;
}

function pastikan_tabel_file() {
    mysqli_query(koneksi_db(), "CREATE TABLE IF NOT EXISTS file_upload (
        path VARCHAR(255) NOT NULL PRIMARY KEY,
        mime VARCHAR(100) NOT NULL,
        data LONGBLOB NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
}

// Pengganti move_uploaded_file(): Vercel tidak punya disk permanen,
// jadi file upload disimpan di database dan disajikan lewat /uploads/... atau /produk/...
function simpan_upload($tmp, $path) {
    $path = ltrim(preg_replace('#^\./#', '', str_replace('\\', '/', $path)), '/');
    if (!preg_match('#^(uploads|produk)/[A-Za-z0-9_.\-]+$#', $path)) return false;
    if (!is_uploaded_file($tmp) && !is_file($tmp)) return false;
    if (filesize($tmp) > 3 * 1024 * 1024) return false; // maks 3 MB
    $data = file_get_contents($tmp);
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
    if (!isset($mimes[$ext])) return false;
    pastikan_tabel_file();
    $stmt = mysqli_prepare(koneksi_db(), "REPLACE INTO file_upload (path, mime, data) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'sss', $path, $mimes[$ext], $data);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
