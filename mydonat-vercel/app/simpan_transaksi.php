<?php
include 'koneksi.php';

// ... (kode untuk mendapatkan data dari keranjang/session) ...

$kode_transaksi = "TRX-" . rand(100, 999); // Contoh generate kode
$total_bayar = 0;

// 1. Simpan ke tabel transaksi (induk)
// ... query insert ke tabel transaksi ...

// 2. Simpan ke tabel detail_transaksi (anak)
// Misalkan Anda punya array keranjang seperti $keranjang = [['id_produk'=>1, 'qty'=>2, 'harga'=>20000], ...]
foreach ($keranjang as $item) {
    $id_produk = $item['id_produk'];
    $qty = $item['qty'];
    $harga = $item['harga'];
    $subtotal = $harga * $qty;
    
    // Insert ke tabel detail_transaksi
    mysqli_query($koneksi, "INSERT INTO detail_transaksi (kode_transaksi, id_produk, qty, subtotal) 
                            VALUES ('$kode_transaksi', '$id_produk', '$qty', '$subtotal')");
}
?>