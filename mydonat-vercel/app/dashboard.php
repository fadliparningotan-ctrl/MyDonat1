<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['admin_nama'])) {
    $_SESSION['admin_nama'] = 'Fadli Parningotan Tambunan';
}
if (!isset($_SESSION['admin_npm'])) {
    $_SESSION['admin_npm'] = '2455201155';
}
if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'fadli.tambunan@example.com';
}
if (!isset($_SESSION['admin_foto'])) {
    $_SESSION['admin_foto'] = '';
}

if (isset($_POST['update_profil'])) {
    $_SESSION['admin_nama']  = mysqli_real_escape_string($conn, $_POST['nama']);
    $_SESSION['admin_npm']   = mysqli_real_escape_string($conn, $_POST['npm']);
    $_SESSION['admin_email'] = mysqli_real_escape_string($conn, $_POST['email']);

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { @mkdir($target_dir, 0777, true); }
        $file_extension = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $new_filename = "admin_foto_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        if (simpan_upload($_FILES["foto"]["tmp_name"], $target_file)) {
            $_SESSION['admin_foto'] = $target_file;
        }
    }
    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='dashboard.php';</script>";
    exit;
}

if (isset($_POST['edit_transaksi'])) {
    $id_trx   = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    $total_b  = (float)$_POST['total_bayar'];
    $metode_p = mysqli_real_escape_string($conn, $_POST['metode']);
    $status_p = mysqli_real_escape_string($conn, $_POST['status']);

    $cols_transaksi = [];
    $res_t = @mysqli_query($conn, "SHOW COLUMNS FROM transaksi");
    if ($res_t) {
        while ($row = mysqli_fetch_assoc($res_t)) { $cols_transaksi[] = strtolower($row['Field']); }
    }

    $set_sql_parts = [];
    foreach (['total_harga', 'total_bayar', 'total'] as $c) {
        if (in_array($c, $cols_transaksi)) { $set_sql_parts[] = "$c = '$total_b'"; }
    }
    foreach (['metode_pembayaran', 'metode'] as $c) {
        if (in_array($c, $cols_transaksi)) { $set_sql_parts[] = "$c = '$metode_p'"; }
    }
    foreach (['status', 'status_pembayaran'] as $c) {
        if (in_array($c, $cols_transaksi)) { $set_sql_parts[] = "$c = '$status_p'"; }
    }

    if (!empty($set_sql_parts)) {
        $str_set = implode(', ', $set_sql_parts);
        @mysqli_query($conn, "UPDATE transaksi SET $str_set WHERE id_transaksi = '$id_trx'");
    }
    echo "<script>alert('Revisi Transaksi Berhasil Disimpan!'); window.location.href='dashboard.php';</script>";
    exit;
}

$total_pendapatan = 0;
$q_pendapatan = mysqli_query($conn, "SELECT SUM(total_bayar) AS total FROM transaksi");
if ($q_pendapatan && $data = mysqli_fetch_assoc($q_pendapatan)) { $total_pendapatan = $data['total'] ?? 0; }

$total_transaksi = 0;
$q_transaksi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
if ($q_transaksi && $data_t = mysqli_fetch_assoc($q_transaksi)) { $total_transaksi = $data_t['total'] ?? 0; }

$total_donat = 0;
$q_donat = mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk");
if ($q_donat && $data_d = mysqli_fetch_assoc($q_donat)) { $total_donat = $data_d['total'] ?? 0; }

$query_terbaru = mysqli_query($conn, "SELECT * FROM transaksi ORDER BY id_transaksi DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - My Donat</title>
    <style>
        :root { --pink-primary: #EC407A; --pink-dark: #C2185B; --pink-light: #F8BBD0; --pink-soft: #FCE4EC; --pink-bg: #FFF5F8; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--pink-bg); margin: 0; }
        .header { background: linear-gradient(135deg, #EC407A 0%, #F06292 100%); color: white; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(236,64,122,0.3); }
        .header-menu { display: flex; align-items: center; gap: 20px; }
        .header a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; }
        .header a:hover { text-decoration: underline; opacity: 0.9; }
        .admin-info { display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.15); padding: 5px 12px; border-radius: 20px; cursor: pointer; }
        .admin-info img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid white; }
        .admin-text { text-align: left; line-height: 1.2; }
        .admin-name { font-weight: bold; font-size: 13px; color: #fff; }
        .admin-npm { font-size: 11px; color: #FFE4EC; }
        .btn-edit-profil { background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.4); }
        .container { padding: 30px; }
        h2.page-title { color: var(--pink-dark); }
        .cards { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 12px; flex: 1; box-shadow: 0 3px 10px rgba(236,64,122,0.1); border-top: 4px solid var(--pink-primary); }
        .card h4 { margin: 0 0 10px 0; color: #888; font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card h2 { margin: 0; color: var(--pink-primary); font-size: 28px; }
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        .table-container h3 { color: var(--pink-dark); margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #F8BBD0; font-size: 14px; }
        th { background: var(--pink-soft); color: var(--pink-dark); font-weight: bold; }
        tr:hover { background: #FFF5F8; }
        .badge-lunas { background: #FCE4EC; color: #C2185B; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #FFF3CD; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .btn-detail { background: var(--pink-primary); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 4px; }
        .btn-detail:hover { background: var(--pink-dark); }
        .btn-revisi { background: #FFB74D; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 4px; }
        .btn-revisi:hover { background: #F57C00; }
        .btn-hapus { background: #E57373; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; }
        .btn-hapus:hover { background: #D32F2F; }
        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; }
        .modal-header h3 { color: var(--pink-dark); }
        .modal-close { font-size: 20px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: var(--pink-dark); margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #F8BBD0; border-radius: 6px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--pink-primary); }
        .btn-simpan { background: var(--pink-primary); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-simpan:hover { background: var(--pink-dark); }
    </style>
</head>
<body>

    <div class="header">
        <h2>🍩 Admin My Donat</h2>
        <div class="header-menu">
            <div class="admin-info" onclick="bukaModalProfil()">
                <?php if (!empty($_SESSION['admin_foto'])): ?>
                    <img src="<?= $_SESSION['admin_foto']; ?>" alt="Foto Admin">
                <?php else: ?>
                    <div style="width:35px; height:35px; border-radius:50%; background:#fff; color:#EC407A; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:16px;">👨‍💻</div>
                <?php endif; ?>
                <div class="admin-text">
                    <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_nama']); ?></div>
                    <div class="admin-npm">NPM: <?= htmlspecialchars($_SESSION['admin_npm']); ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="text-decoration: underline;">Dashboard</a>
                <a href="produk.php">Data Donat</a>
                <a href="kategori.php">Kategori</a>
                <a href="transaksi.php">Transaksi Kasir</a>
                <a href="pesanan.php">Pesanan</a>
                <a href="produk_terlaris.php">Terlaris</a>
                <a href="laporan.php">Laporan</a>
                <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
                <a href="logout.php" style="color:#FFE4EC;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="page-title">Dashboard Ringkasan Penjualan</h2>
        <div class="cards">
            <div class="card"><h4>Total Pendapatan</h4><h2>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h2></div>
            <div class="card"><h4>Total Transaksi</h4><h2><?= $total_transaksi; ?> Pesanan</h2></div>
            <div class="card"><h4>Jumlah Varian Donat</h4><h2><?= $total_donat; ?> Menu</h2></div>
        </div>

        <div class="table-container">
            <h3>Transaksi Pembayaran Terbaru</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Kode Transaksi</th><th>Tanggal</th><th>Total Bayar</th><th>Metode</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($query_terbaru && mysqli_num_rows($query_terbaru) > 0) {
                        while ($row = mysqli_fetch_assoc($query_terbaru)) { 
                            $id_trx     = $row['id_transaksi'];
                            $kode_trx   = 'TRX-' . $id_trx;
                            $tgl_db     = $row['tgl_transaksi'] ?? date('Y-m-d H:i');
                            $tanggal_formatted = date('d/m/Y H:i', strtotime($tgl_db));
                            $total_bayar = $row['total_bayar'] ?? 0;
                            $metode      = $row['metode_pembayaran'] ?? 'Cash (Tunai)';
                            $status      = $row['status_pembayaran'] ?? $row['status'] ?? 'Lunas';
                            if (empty(trim($status))) { $status = 'Lunas'; }
                            $badge_class = (strtolower($status) == 'lunas') ? 'badge-lunas' : 'badge-pending';
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($kode_trx); ?></strong></td>
                            <td><?= $tanggal_formatted; ?></td>
                            <td>Rp <?= number_format($total_bayar, 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($metode); ?></td>
                            <td><span class="<?= $badge_class; ?>"><?= htmlspecialchars($status); ?></span></td>
                            <td>
                                <button class="btn-detail" onclick="lihatDetail('<?= $id_trx; ?>', '<?= htmlspecialchars($kode_trx, ENT_QUOTES); ?>')">Detail</button>
                                <button class="btn-revisi" onclick="bukaModalEdit('<?= $id_trx; ?>', '<?= htmlspecialchars($kode_trx, ENT_QUOTES); ?>', '<?= $total_bayar; ?>', '<?= htmlspecialchars($metode, ENT_QUOTES); ?>', '<?= htmlspecialchars($status, ENT_QUOTES); ?>')">✏️ Revisi</button>
                                <a href="hapus_transaksi.php?id=<?= $id_trx; ?>" onclick="return confirm('Yakin ingin menghapus transaksi ini?');" class="btn-hapus">Hapus</a>
                            </td>
                        </tr>
                    <?php } } else { echo "<tr><td colspan='7' style='text-align:center; color:#888;'>Belum ada transaksi</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalEdit" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header"><h3 style="margin:0;" id="titleEdit">✏️ Revisi Transaksi</h3><button class="modal-close" onclick="tutupModalEdit()">&times;</button></div>
            <form action="" method="POST" style="padding-top:15px;">
                <input type="hidden" name="id_transaksi" id="edit_id">
                <div class="form-group"><label>Kode Transaksi:</label><input type="text" id="edit_kode" disabled style="background:#FCE4EC;"></div>
                <div class="form-group"><label>Total Bayar (Rp):</label><input type="number" name="total_bayar" id="edit_total" required></div>
                <div class="form-group"><label>Metode Pembayaran:</label><input type="text" name="metode" id="edit_metode" required></div>
                <div class="form-group"><label>Status Pembayaran:</label>
                    <select name="status" id="edit_status" required>
                        <option value="Lunas">Lunas</option><option value="Pending">Pending</option><option value="Diproses">Diproses</option>
                    </select>
                </div>
                <button type="submit" name="edit_transaksi" class="btn-simpan">💾 Simpan Revisi</button>
            </form>
        </div>
    </div>

    <div id="modalProfil" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header"><h3 style="margin:0;">👤 Profil Admin Sistem</h3><button class="modal-close" onclick="tutupModalProfil()">&times;</button></div>
            <form action="" method="POST" enctype="multipart/form-data" style="padding-top:15px;">
                <div style="text-align:center; margin-bottom:15px;">
                    <?php if (!empty($_SESSION['admin_foto'])): ?>
                        <img src="<?= $_SESSION['admin_foto']; ?>" alt="Foto Admin" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #EC407A;">
                    <?php else: ?>
                        <div style="width:80px; height:80px; border-radius:50%; background:#EC407A; color:white; display:flex; align-items:center; justify-content:center; font-size:40px; margin:0 auto;">👨‍💻</div>
                    <?php endif; ?>
                </div>
                <div class="form-group"><label>Upload / Ganti Foto Profil:</label><input type="file" name="foto" accept="image/*"></div>
                <div class="form-group"><label>Nama Lengkap Admin:</label><input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['admin_nama']); ?>" required></div>
                <div class="form-group"><label>NPM Admin:</label><input type="text" name="npm" value="<?= htmlspecialchars($_SESSION['admin_npm']); ?>" required></div>
                <div class="form-group"><label>Email Admin:</label><input type="email" name="email" value="<?= htmlspecialchars($_SESSION['admin_email']); ?>" required></div>
                <button type="submit" name="update_profil" class="btn-simpan">💾 Simpan Perubahan Profil</button>
            </form>
        </div>
    </div>

    <div id="modalDetail" class="modal-bg">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header"><h3 style="margin:0;">📜 Detail Rincian Pesanan</h3><button class="modal-close" onclick="tutupModalDetail()">&times;</button></div>
            <div id="modalBody" style="padding-top: 15px;"><p>Memuat item pesanan...</p></div>
        </div>
    </div>

    <script>
    function bukaModalProfil() { document.getElementById('modalProfil').style.display = 'block'; }
    function tutupModalProfil() { document.getElementById('modalProfil').style.display = 'none'; }
    function bukaModalEdit(identifier, kode, total, metode, status) {
        document.getElementById('modalEdit').style.display = 'block';
        document.getElementById('edit_id').value = identifier;
        document.getElementById('titleEdit').innerText = '✏️ Revisi ' + kode;
        document.getElementById('edit_kode').value = kode;
        document.getElementById('edit_total').value = total;
        document.getElementById('edit_metode').value = metode;
        document.getElementById('edit_status').value = status;
    }
    function tutupModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }
    function lihatDetail(idTrans, kodeTrx) {
        document.getElementById('modalDetail').style.display = 'block';
        document.getElementById('modalBody').innerHTML = '<p>Memuat item pesanan...</p>';
        fetch('detail_transaksi.php?id=' + idTrans + '&kode=' + encodeURIComponent(kodeTrx))
            .then(response => response.text())
            .then(data => { document.getElementById('modalBody').innerHTML = data; })
            .catch(error => { document.getElementById('modalBody').innerHTML = '<p style="color:red;">Gagal mengambil data detail.</p>'; });
    }
    function tutupModalDetail() { document.getElementById('modalDetail').style.display = 'none'; }
    </script>
</body>
</html>