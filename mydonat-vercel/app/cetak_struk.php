<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

if (!isset($_GET['id'])) {
    echo "ID Transaksi tidak ditemukan!";
    exit;
}

$id_transaksi = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data transaksi dari database
$q_trx = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi='$id_transaksi'");

// Mengambil data item dari session
$items = isset($_SESSION['last_trx']['items']) ? $_SESSION['last_trx']['items'] : [];
$total_bayar = isset($_SESSION['last_trx']['total_bayar']) ? $_SESSION['last_trx']['total_bayar'] : 0;
$uang_bayar  = isset($_SESSION['last_trx']['uang_bayar']) ? $_SESSION['last_trx']['uang_bayar'] : 0;
$kembalian   = isset($_SESSION['last_trx']['kembalian']) ? $_SESSION['last_trx']['kembalian'] : 0;
$metode      = isset($_SESSION['last_trx']['metode']) ? $_SESSION['last_trx']['metode'] : 'Cash';

// Jika data dari database ditemukan
if ($q_trx && mysqli_num_rows($q_trx) > 0) {
    $d_trx = mysqli_fetch_assoc($q_trx);
    if ($total_bayar == 0) {
        $total_bayar = $d_trx['total_bayar'];
        $metode      = $d_trx['metode_pembayaran'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Struk Pembayaran #<?= htmlspecialchars($id_transaksi) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 280px;
            margin: 0 auto;
            padding: 10px;
            color: #000;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header h3 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .table-item {
            width: 100%;
            font-size: 12px;
            border-collapse: collapse;
        }
        .table-item td { padding: 3px 0; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 12px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h3>MY DONAT</h3>
        <p>Jl. Lezat No. 123</p>
        <p>Telp: 0812-3456-7890</p>
        <p>No. TRX: #<?= htmlspecialchars($id_transaksi) ?> | <?= date('d/m/Y H:i') ?></p>
    </div>

    <table class="table-item">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="2"><strong><?= htmlspecialchars($item['nama']) ?></strong></td>
                </tr>
                <tr>
                    <td><?= $item['qty'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align: center;">-- Detail Item --</td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="line"></div>

    <table class="table-item">
        <tr>
            <td>Total</td>
            <td class="text-right"><strong>Rp <?= number_format($total_bayar, 0, ',', '.') ?></strong></td>
        </tr>
        <tr>
            <td>Bayar (<?= htmlspecialchars($metode) ?>)</td>
            <td class="text-right">Rp <?= number_format($uang_bayar, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="text-right">Rp <?= number_format($kembalian, 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="footer">
        <p>--- Terima Kasih ---</p>
        <p>Selamat Menikmati!</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer;">🖨️ Cetak</button>
        <button onclick="window.close()" style="padding: 8px 15px; cursor: pointer;">Tutup</button>
    </div>

</body>
</html>