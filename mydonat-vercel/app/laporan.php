<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['admin_nama']))  { $_SESSION['admin_nama']  = 'Fadli Parningotan Tambunan'; }
if (!isset($_SESSION['admin_npm']))   { $_SESSION['admin_npm']   = '2455201155'; }
if (!isset($_SESSION['admin_email'])) { $_SESSION['admin_email'] = 'fadli.tambunan@example.com'; }
if (!isset($_SESSION['admin_foto']))  { $_SESSION['admin_foto']  = ''; }

// PROSES UPDATE PROFIL
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
    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='laporan.php';</script>";
    exit;
}

// PROSES REVISI TRANSAKSI
if (isset($_POST['edit_transaksi'])) {
    $id_trx   = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    $total_b  = mysqli_real_escape_string($conn, $_POST['total_bayar']);
    $metode_p = mysqli_real_escape_string($conn, $_POST['metode']);
    $status_p = mysqli_real_escape_string($conn, $_POST['status']);

    @mysqli_query($conn, "UPDATE transaksi SET total_bayar = '$total_b', metode_pembayaran = '$metode_p', status_pembayaran = '$status_p' WHERE id_transaksi = '$id_trx'");
    @mysqli_query($conn, "UPDATE transaksi SET status = '$status_p' WHERE id_transaksi = '$id_trx'");
    
    echo "<script>alert('Transaksi berhasil diperbarui!'); window.location.href='laporan.php';</script>";
    exit;
}

// 🔥 PROSES FILTER TANGGAL
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'semua';
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

$where_sql = "";
$label_periode = "Semua Waktu";

switch ($filter_type) {
    case 'hari_ini':
        $tgl_hari = date('Y-m-d');
        $where_sql = " WHERE DATE(tgl_transaksi) = '$tgl_hari'";
        $label_periode = "Hari Ini (" . date('d/m/Y') . ")";
        break;

    case '7_hari':
        $tgl_7 = date('Y-m-d', strtotime('-7 days'));
        $where_sql = " WHERE DATE(tgl_transaksi) >= '$tgl_7'";
        $label_periode = "7 Hari Terakhir";
        break;

    case 'bulan_ini':
        $bulan = date('Y-m');
        $where_sql = " WHERE DATE_FORMAT(tgl_transaksi, '%Y-%m') = '$bulan'";
        $label_periode = "Bulan " . date('F Y');
        break;

    case 'tahun_ini':
        $tahun = date('Y');
        $where_sql = " WHERE YEAR(tgl_transaksi) = '$tahun'";
        $label_periode = "Tahun " . $tahun;
        break;

    case 'custom':
        if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
            $where_sql = " WHERE DATE(tgl_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
            $label_periode = date('d/m/Y', strtotime($tgl_mulai)) . " s/d " . date('d/m/Y', strtotime($tgl_selesai));
        }
        break;

    default:
        $where_sql = "";
        $label_periode = "Semua Waktu";
        break;
}

// AMBIL DATA TRANSAKSI SESUAI FILTER
$query_laporan = mysqli_query($conn, "SELECT * FROM transaksi $where_sql ORDER BY id_transaksi DESC");

// HITUNG TOTAL PENDAPATAN SESUAI FILTER
$total_pendapatan = 0;
$total_trx = 0;
$q_sum = mysqli_query($conn, "SELECT SUM(total_bayar) AS total, COUNT(*) as jml FROM transaksi $where_sql");
if ($q_sum && $d_sum = mysqli_fetch_assoc($q_sum)) {
    $total_pendapatan = $d_sum['total'] ?? 0;
    $total_trx = $d_sum['jml'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - My Donat</title>
    <style>
        :root {
            --pink-primary: #EC407A;
            --pink-dark: #C2185B;
            --pink-light: #F8BBD0;
            --pink-soft: #FCE4EC;
            --pink-bg: #FFF5F8;
        }
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
        .report-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        
        .report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 15px; margin-bottom: 20px; }
        .report-header h2 { color: var(--pink-dark); margin: 0 0 5px 0; }
        .report-header p { color: #888; margin: 0; font-size: 13px; }
        .btn-print { background: var(--pink-primary); color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-print:hover { background: var(--pink-dark); }
        
        /* 🔥 FILTER TANGGAL */
        .filter-section { background: var(--pink-soft); padding: 18px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid var(--pink-primary); }
        .filter-section h4 { color: var(--pink-dark); margin: 0 0 12px 0; font-size: 14px; }
        .filter-buttons { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .filter-btn { background: white; color: var(--pink-dark); border: 2px solid var(--pink-light); padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 13px; transition: 0.2s; }
        .filter-btn:hover { background: var(--pink-soft); }
        .filter-btn.active { background: var(--pink-primary); color: white; border-color: var(--pink-primary); }
        
        .custom-range { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 10px; padding-top: 12px; border-top: 1px dashed var(--pink-light); }
        .custom-range label { font-size: 13px; font-weight: bold; color: var(--pink-dark); }
        .custom-range input[type="date"] { padding: 8px 10px; border: 1px solid var(--pink-light); border-radius: 6px; font-size: 13px; }
        .custom-range input[type="date"]:focus { outline: none; border-color: var(--pink-primary); }
        .btn-terapkan { background: var(--pink-primary); color: white; border: none; padding: 9px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-terapkan:hover { background: var(--pink-dark); }
        
        .periode-label { text-align: center; padding: 10px; background: white; border-radius: 6px; color: var(--pink-dark); font-weight: bold; font-size: 14px; margin-bottom: 15px; border: 1px dashed var(--pink-primary); }
        
        .summary-cards { display: flex; gap: 20px; margin-bottom: 20px; }
        .s-card { background: var(--pink-soft); border-left: 4px solid var(--pink-primary); padding: 15px; flex: 1; border-radius: 6px; }
        .s-card h4 { margin: 0 0 5px 0; color: var(--pink-dark); font-size: 13px; letter-spacing: 0.5px; }
        .s-card h2 { margin: 0; color: #333; font-size: 22px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--pink-light); font-size: 14px; }
        th { background: var(--pink-soft); color: var(--pink-dark); }
        tr:hover { background: #FFF5F8; }
        
        .badge-lunas { background: var(--pink-soft); color: var(--pink-dark); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #FFF3CD; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        
        .btn-edit { background: #FFB74D; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.2s; }
        .btn-edit:hover { background: #F57C00; }

        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; }
        .modal-header h3 { color: var(--pink-dark); }
        .modal-close { font-size: 22px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: var(--pink-dark); margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid var(--pink-light); border-radius: 6px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--pink-primary); }
        .btn-simpan { background: var(--pink-primary); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-simpan:hover { background: var(--pink-dark); }

        @media print {
            body { background: white; }
            .header, .btn-print, .modal-bg, .header-menu a, .btn-edit, .filter-section { display: none !important; }
            .container { padding: 0; }
            .report-box { box-shadow: none; padding: 0; }
            .periode-label { border: 1px solid #ccc; }
        }
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
                <a href="dashboard.php">Dashboard</a>
                <a href="produk.php">Data Donat</a>
                <a href="kategori.php">Kategori</a>
                <a href="transaksi.php">Transaksi Kasir</a>
                <a href="pesanan.php">Pesanan</a>
                <a href="produk_terlaris.php">Terlaris</a>
                <a href="laporan.php" style="text-decoration: underline;">Laporan</a>
                <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
                <a href="logout.php" style="color:#FFE4EC;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="report-box">
            <div class="report-header">
                <div>
                    <h2>📊 Laporan Rekapitulasi Penjualan</h2>
                    <p>Sistem POS My Donat | Admin: <?= htmlspecialchars($_SESSION['admin_nama']); ?></p>
                </div>
                <button class="btn-print" onclick="window.print()">🖨️ Cetak / Print Laporan</button>
            </div>

            <!-- 🔥 FILTER TANGGAL -->
            <div class="filter-section">
                <h4>📅 Filter Periode Laporan:</h4>
                <div class="filter-buttons">
                    <a href="laporan.php?filter_type=semua" class="filter-btn <?= ($filter_type == 'semua') ? 'active' : ''; ?>">📋 Semua Waktu</a>
                    <a href="laporan.php?filter_type=hari_ini" class="filter-btn <?= ($filter_type == 'hari_ini') ? 'active' : ''; ?>">📅 Hari Ini</a>
                    <a href="laporan.php?filter_type=7_hari" class="filter-btn <?= ($filter_type == '7_hari') ? 'active' : ''; ?>">🗓️ 7 Hari Terakhir</a>
                    <a href="laporan.php?filter_type=bulan_ini" class="filter-btn <?= ($filter_type == 'bulan_ini') ? 'active' : ''; ?>">📆 Bulan Ini</a>
                    <a href="laporan.php?filter_type=tahun_ini" class="filter-btn <?= ($filter_type == 'tahun_ini') ? 'active' : ''; ?>">📅 Tahun Ini</a>
                    <a href="laporan.php?filter_type=custom" class="filter-btn <?= ($filter_type == 'custom') ? 'active' : ''; ?>">🎯 Custom Range</a>
                </div>
                
                <?php if ($filter_type == 'custom'): ?>
                <form method="GET" action="laporan.php" class="custom-range">
                    <input type="hidden" name="filter_type" value="custom">
                    <label>Dari:</label>
                    <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
                    <label>Sampai:</label>
                    <input type="date" name="tgl_selesai" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
                    <button type="submit" class="btn-terapkan">🔍 Terapkan</button>
                </form>
                <?php endif; ?>
            </div>

            <div class="periode-label">
                📅 Periode: <span style="color: var(--pink-primary);"><?= htmlspecialchars($label_periode); ?></span>
            </div>

            <div class="summary-cards">
                <div class="s-card">
                    <h4>TOTAL PENDAPATAN PERIODE INI</h4>
                    <h2>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h2>
                </div>
                <div class="s-card">
                    <h4>JUMLAH TRANSAKSI</h4>
                    <h2><?= $total_trx; ?> Transaksi</h2>
                </div>
            </div>

            <h3 style="color: var(--pink-dark);">Rincian Riwayat Transaksi</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Transaksi</th>
                        <th>Tanggal & Jam</th>
                        <th>Total Bayar</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($query_laporan && mysqli_num_rows($query_laporan) > 0) {
                        while ($row = mysqli_fetch_assoc($query_laporan)) { 
                            $id_trans   = $row['id_transaksi'];
                            $kode_trx   = 'TRX-' . $id_trans;
                            $tgl_db     = $row['tgl_transaksi'] ?? date('Y-m-d H:i');
                            $total_bayar= $row['total_bayar'] ?? 0;
                            $metode     = $row['metode_pembayaran'] ?? 'Cash (Tunai)';
                            $status     = $row['status_pembayaran'] ?? $row['status'] ?? 'Lunas';
                            if (empty(trim($status))) { $status = 'Lunas'; }
                            
                            $badge_class= (strtolower($status) == 'lunas') ? 'badge-lunas' : 'badge-pending';
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($kode_trx); ?></strong></td>
                            <td><?= htmlspecialchars($tgl_db); ?></td>
                            <td>Rp <?= number_format($total_bayar, 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($metode); ?></td>
                            <td><span class="<?= $badge_class; ?>"><?= htmlspecialchars($status); ?></span></td>
                            <td>
                                <button class="btn-edit" onclick="bukaModalEdit('<?= $id_trans; ?>', '<?= $kode_trx; ?>', '<?= $total_bayar; ?>', '<?= htmlspecialchars($metode, ENT_QUOTES); ?>', '<?= htmlspecialchars($status, ENT_QUOTES); ?>')">✏️ Revisi</button>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; color:#888; padding:30px;'>Tidak ada transaksi pada periode ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL EDIT / REVISI TRANSAKSI -->
    <div id="modalEdit" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;" id="titleEdit">✏️ Revisi Transaksi</h3>
                <button class="modal-close" onclick="tutupModalEdit()">&times;</button>
            </div>
            
            <form action="" method="POST" style="padding-top:15px;">
                <input type="hidden" name="id_transaksi" id="edit_id">

                <div class="form-group">
                    <label>Kode Transaksi:</label>
                    <input type="text" id="edit_kode" disabled style="background:#FCE4EC;">
                </div>

                <div class="form-group">
                    <label>Total Bayar (Rp):</label>
                    <input type="number" name="total_bayar" id="edit_total" required>
                </div>

                <div class="form-group">
                    <label>Metode Pembayaran:</label>
                    <input type="text" name="metode" id="edit_metode" required>
                </div>

                <div class="form-group">
                    <label>Status Pembayaran:</label>
                    <select name="status" id="edit_status" required>
                        <option value="Lunas">Lunas</option>
                        <option value="Pending">Pending</option>
                        <option value="Diproses">Diproses</option>
                    </select>
                </div>

                <button type="submit" name="edit_transaksi" class="btn-simpan">💾 Simpan Revisi</button>
            </form>
        </div>
    </div>

    <!-- MODAL PROFIL -->
    <div id="modalProfil" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;">👤 Profil Admin Sistem</h3>
                <button class="modal-close" onclick="tutupModalProfil()">&times;</button>
            </div>
            
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

    <script>
    function bukaModalProfil() { document.getElementById('modalProfil').style.display = 'block'; }
    function tutupModalProfil() { document.getElementById('modalProfil').style.display = 'none'; }

    function bukaModalEdit(id, kode, total, metode, status) {
        document.getElementById('modalEdit').style.display = 'block';
        document.getElementById('edit_id').value = id;
        document.getElementById('titleEdit').innerText = '✏️ Revisi ' + kode;
        document.getElementById('edit_kode').value = kode;
        document.getElementById('edit_total').value = total;
        document.getElementById('edit_metode').value = metode;
        document.getElementById('edit_status').value = status;
    }
    function tutupModalEdit() {
        document.getElementById('modalEdit').style.display = 'none';
    }
    </script>
</body>
</html>