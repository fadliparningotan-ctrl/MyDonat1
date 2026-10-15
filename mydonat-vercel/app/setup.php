<?php
// Membuat semua tabel database. Buka SEKALI: /setup.php?key=ISI_SETUP_KEY
require_once __DIR__ . '/config.php';
$key = env_val('SETUP_KEY', '');
if ($key === '' || !hash_equals($key, $_GET['key'] ?? '')) {
    http_response_code(403);
    exit('Forbidden. Set env SETUP_KEY di Vercel lalu buka /setup.php?key=ISI_SETUP_KEY');
}
header('Content-Type: text/plain; charset=utf-8');
$sql = file_get_contents(__DIR__ . '/../schema.sql');
$conn = koneksi_db();
if (mysqli_multi_query($conn, $sql)) {
    do { if ($r = mysqli_store_result($conn)) mysqli_free_result($r); } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}
echo mysqli_errno($conn) ? "Error: " . mysqli_error($conn) : "OK - semua tabel berhasil dibuat.\nSekarang buka /login.php dan daftar akun admin.\nDisarankan hapus env SETUP_KEY setelah ini.";
