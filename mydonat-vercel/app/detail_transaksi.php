<?php
include 'db.php';

$id_trans = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$kode_trx = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';

$query = false;
if (!empty($id_trans)) {
    $query = @mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id_trans'");
}
if (!$query || mysqli_num_rows($query) == 0) {
    $query = @mysqli_query($conn, "SELECT * FROM transaksi ORDER BY id_transaksi DESC LIMIT 1");
}

if ($query && mysqli_num_rows($query) > 0) {
    $trx = mysqli_fetch_assoc($query);
    
    $id_trx_val  = $trx['id_transaksi'];
    $kode        = 'TRX-' . $id_trx_val;
    $tgl_db      = $trx['tgl_transaksi'] ?? date('Y-m-d H:i');
    $tgl         = date('d/m/Y H:i', strtotime($tgl_db));
    $status      = $trx['status_pembayaran'] ?? $trx['status'] ?? 'Lunas';
    $total_bayar = (int)($trx['total_bayar'] ?? 0);
    $metode      = $trx['metode_pembayaran'] ?? 'Cash (Tunai)';
    
    $q_detail = @mysqli_query($conn, "SELECT dt.qty, dt.subtotal, 
                                             p.nama_produk, p.harga_produk 
                                      FROM detail_transaksi dt 
                                      LEFT JOIN produk p ON dt.id_produk = p.id_produk 
                                      WHERE dt.kode_transaksi = '$kode'
                                      ORDER BY dt.id_detail ASC");
?>

    <style>
        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 13px; }
        .detail-table th, .detail-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        .detail-table th { background-color: #f8f9fa; color: #555; width: 40%; }
        .badge-status { background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .item-box { background: #fff8e1; border-left: 4px solid #ff9800; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .item-list { list-style: none; padding: 0; margin: 10px 0 0 0; }
        .item-list li { padding: 8px 0; border-bottom: 1px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
        .item-list li:last-child { border-bottom: none; }
        .item-left { display: flex; flex-direction: column; }
        .item-name { font-weight: bold; color: #333; font-size: 13px; }
        .item-qty { font-size: 11px; color: #888; margin-top: 2px; }
        .item-price { color: #ff9800; font-weight: bold; font-size: 13px; }
        .item-total { text-align: right; margin-top: 10px; padding-top: 10px; border-top: 2px solid #ff9800; font-weight: bold; color: #333; font-size: 14px; }
        .info-empty { color: #999; font-style: italic; text-align: center; padding: 10px; }
    </style>

    <div class="item-box">
        <strong>🍩 Donat / Menu yang Dibeli:</strong>
        <ul class="item-list">
            <?php 
            $grand_total = 0;
            $has_item = false;
            if ($q_detail && mysqli_num_rows($q_detail) > 0) {
                while ($item = mysqli_fetch_assoc($q_detail)) {
                    $has_item = true;
                    $subtotal = (int)$item['subtotal'];
                    $grand_total += $subtotal;
            ?>
                <li>
                    <div class="item-left">
                        <span class="item-name"><?= htmlspecialchars($item['nama_produk'] ?? 'Donat'); ?></span>
                        <span class="item-qty"><?= $item['qty']; ?>x @ Rp <?= number_format($item['harga_produk'] ?? 0, 0, ',', '.'); ?></span>
                    </div>
                    <span class="item-price">Rp <?= number_format($subtotal, 0, ',', '.'); ?></span>
                </li>
            <?php 
                }
            }
            if (!$has_item) {
                echo "<li class='info-empty'>Rincian item tidak tersedia untuk transaksi <strong>" . htmlspecialchars($kode) . "</strong>.</li>";
            }
            ?>
        </ul>
        <div class="item-total">
            Total Item: Rp <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>
    </div>

    <table class="detail-table">
        <tr><th>🔑 Kode Transaksi</th><td><strong><?= htmlspecialchars($kode); ?></strong></td></tr>
        <tr><th>📅 Tanggal & Jam</th><td><?= $tgl; ?></td></tr>
        <tr><th>💳 Metode Pembayaran</th><td><?= htmlspecialchars($metode); ?></td></tr>
        <tr><th>📌 Status Pembayaran</th><td><span class="badge-status"><?= htmlspecialchars($status); ?></span></td></tr>
        <tr><th>💰 Total Bayar</th><td><strong>Rp <?= number_format($total_bayar, 0, ',', '.'); ?></strong></td></tr>
    </table>

<?php
} else {
    echo "<p style='color:red; text-align:center;'>Data transaksi tidak ditemukan.</p>";
}
?>