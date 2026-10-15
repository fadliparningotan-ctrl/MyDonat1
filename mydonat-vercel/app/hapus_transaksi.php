<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

if (!isset($_SESSION['admin_status'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_transaksi = mysqli_real_escape_string($conn, $_GET['id']);

    // Hapus data transaksi dari database
    $delete = mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi='$id_transaksi'");
    if (!$delete) {
        $delete = mysqli_query($conn, "DELETE FROM transaksi WHERE id='$id_transaksi'");
    }

    if ($delete) {
        echo "<script>alert('Transaksi TRX-$id_transaksi berhasil dihapus!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus transaksi!'); window.location='dashboard.php';</script>";
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>      