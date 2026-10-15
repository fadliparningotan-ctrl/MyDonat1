<?php
// Menyajikan gambar hasil upload yang disimpan di database
require_once __DIR__ . '/config.php';
$p = $GLOBALS['__file_path'] ?? '';
if (!preg_match('#^(uploads|produk)/[A-Za-z0-9_.\-]+$#', $p)) { http_response_code(404); exit('Not found'); }
pastikan_tabel_file();
$stmt = mysqli_prepare(koneksi_db(), "SELECT mime, data FROM file_upload WHERE path = ?");
mysqli_stmt_bind_param($stmt, 's', $p);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $mime, $data);
if (!mysqli_stmt_fetch($stmt)) { http_response_code(404); exit('Not found'); }
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
echo $data;
