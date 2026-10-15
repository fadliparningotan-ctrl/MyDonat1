<?php
include 'db.php';

$kode = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';

if (empty($kode)) {
    echo "<h2 style='color:red; text-align:center;'>Kode transaksi tidak valid!</h2>";
    exit;
}

$q_trx = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$kode' OR kode_transaksi = '$kode'");
$trx = mysqli_fetch_assoc($q_trx);

if (!$trx) {
    echo "<h2 style='color:red; text-align:center;'>Transaksi tidak ditemukan!</h2>";
    exit;
}

$id_trx      = $trx['id_transaksi'];
$kode_trx    = 'TRX-' . $id_trx;
$tgl_db      = $trx['tgl_transaksi'] ?? date('Y-m-d H:i');
$tgl         = date('d/m/Y H:i', strtotime($tgl_db));
$total_bayar = $trx['total_bayar'] ?? 0;
$metode      = $trx['metode_pembayaran'] ?? 'Cash';
$status      = $trx['status_pembayaran'] ?? $trx['status'] ?? 'Lunas';

$q_detail = mysqli_query($conn, "SELECT dt.qty, dt.subtotal, p.nama_produk, p.harga_produk 
                                 FROM detail_transaksi dt 
                                 LEFT JOIN produk p ON dt.id_produk = p.id_produk 
                                 WHERE dt.kode_transaksi = '$kode_trx'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Struk - My Donat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #EC407A 0%, #F06292 100%); margin: 0; padding: 20px; min-height: 100vh; }
        .card { background: white; max-width: 400px; margin: 20px auto; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .header { text-align: center; border-bottom: 2px dashed #F8BBD0; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #C2185B; margin: 0 0 5px 0; font-size: 24px; }
        .header p { color: #EC407A