<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['admin_status'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['admin_nama']))  { $_SESSION['admin_nama']  = 'Fadli Parningotan Tambunan'; }
if (!isset($_SESSION['admin_npm']))   { $_SESSION['admin_npm']   = '2455201155'; }
if (!isset($_SESSION['admin_email'])) { $_SESSION['admin_email'] = 'fadli.tambunan@example.com'; }
if (!isset($_SESSION['admin_foto']))  { $_SESSION['admin_foto']  = ''; }

// PROSES UPDATE PROFIL ADMIN
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
    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='pesanan.php';</script>";
    exit;
}

// PROSES UBAH STATUS PESANAN
if (isset($_POST['ubah_status'])) {
    $id_trx     = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    $status_new = mysqli_real_escape_string($conn, $_POST['status_baru']);
    
    @mysqli_query($conn, "UPDATE transaksi SET status_pembayaran = '$status_new' WHERE id_transaksi = '$id_trx'");
    @mysqli_query($conn, "UPDATE transaksi SET status = '$status_new' WHERE id_transaksi = '$id_trx'");
    
    echo "<script>alert('Status pesanan berhasil diubah menjadi: $status_new'); window.location.href='pesanan.php';</script>";
    exit;
}

// 🔥 PROSES HAPUS PESANAN (transaksi + detail)
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Hapus detail transaksi dulu
    $kode_trx = 'TRX-' . $id_hapus;
    @mysqli_query($conn, "DELETE FROM detail_transaksi WHERE kode_transaksi = '$kode_trx'");
    
    // Hapus transaksi utama
    @mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = '$id_hapus'");
    
    echo "<script>alert('Pesanan berhasil dihapus!'); window.location.href='pesanan.php';</script>";
    exit;
}

// FILTER STATUS
$filter_status = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'semua';
$where_sql = "";
if ($filter_status != 'semua') {
    $where_sql = " WHERE (status = '$filter_status' OR status_pembayaran = '$filter_status')";
}

// AMBIL SEMUA PESANAN
$query_pesanan = mysqli_query($conn, "SELECT * FROM transaksi $where_sql ORDER BY id_transaksi DESC");

// HITUNG STATISTIK
$total_semua    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transaksi"))['c'] ?? 0;
$total_lunas    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transaksi WHERE status = 'Lunas' OR status_pembayaran = 'Lunas'"))['c'] ?? 0;
$total_pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM transaksi WHERE status = 'Pending' OR status_pembayaran = 'Pending'"))['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan | Admin My Donat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --pink-primary: #EC407A;
            --pink-dark: #C2185B;
            --pink-light: #F8BBD0;
            --pink-soft: #FCE4EC;
            --pink-bg: #FFF5F8;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--pink-bg); margin: 0; }
        
        .navbar { background: linear-gradient(135deg, #EC407A 0%, #F06292 100%); color: white; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(236,64,122,0.3); }
        .header-menu { display: flex; align-items: center; gap: 20px; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; }
        .navbar a:hover { text-decoration: underline; opacity: 0.9; }
        
        .admin-info { display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.15); padding: 5px 12px; border-radius: 20px; cursor: pointer; }
        .admin-info img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid white; }
        .admin-text { text-align: left; line-height: 1.2; }
        .admin-name { font-weight: bold; font-size: 13px; color: #fff; }
        .admin-npm { font-size: 11px; color: #FFE4EC; }
        
        .btn-edit-profil { background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.4); }

        .container { padding: 30px; max-width: 1100px; margin: 0 auto; }
        h2.page-title { color: var(--pink-dark); margin-bottom: 20px; }
        
        /* STAT CARDS */
        .stats { display: flex; gap: 15px; margin-bottom: 25px; }
        .stat-card { flex: 1; background: white; padding: 18px 20px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); border-left: 5px solid var(--pink-primary); }
        .stat-card h4 { margin: 0 0 6px 0; color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card h2 { margin: 0; color: var(--pink-dark); font-size: 26px; }
        .stat-card.green { border-left-color: #4CAF50; }
        .stat-card.orange { border-left-color: #FFB74D; }
        
        /* FILTER */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { background: white; color: var(--pink-dark); border: 2px solid var(--pink-light); padding: 8px 18px; border-radius: 20px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 13px; transition: 0.2s; }
        .filter-btn:hover { background: var(--pink-soft); }
        .filter-btn.active { background: var(--pink-primary); color: white; border-color: var(--pink-primary); }
        
        /* CARD TABLE */
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--pink-light); vertical-align: middle; }
        th { background: var(--pink-soft); color: var(--pink-dark); font-weight: bold; }
        tr:hover { background: #FFF5F8; }
        
        .badge-lunas { background: var(--pink-soft); color: var(--pink-dark); padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-pending { background: #FFF3CD; color: #856404; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-proses { background: #E3F2FD; color: #1565C0; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        
        .btn-proses { background: var(--pink-primary); color: white; border: none; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; transition: 0.2s; }
        .btn-proses:hover { background: var(--pink-dark); }
        .btn-detail { background: #FFB74D; color: white; border: none; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; margin-left: 5px; transition: 0.2s; }
        .btn-detail:hover { background: #F57C00; }
        /* 🔥 TOMBOL HAPUS BARU */
        .btn-hapus { background: #E57373; color: white; border: none; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; margin-left: 5px; transition: 0.2s; display: inline-block; }
        .btn-hapus:hover { background: #D32F2F; }
        
        /* MODAL */
        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; }
        .modal-header h3 { color: var(--pink-dark); margin: 0; }
        .modal-close { font-size: 22px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: var(--pink-dark); margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--pink-light); border-radius: 6px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--pink-primary); }
        .btn-simpan { background: var(--pink-primary); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-simpan:hover { background: var(--pink-dark); }
        
        .empty-state { text-align: center; padding: 40px; color: #888; }
        .empty-state .emoji { font-size: 60px; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="navbar">
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
                <a href="pesanan.php" style="text-decoration: underline;">Pesanan</a>
                <a href="produk_terlaris.php">Terlaris</a>
                <a href="laporan.php">Laporan</a>
                <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
                <a href="logout.php" style="color:#FFE4EC;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="page-title">📋 Manajemen Pesanan</h2>

        <!-- STATISTIK -->
        <div class="stats">
            <div class="stat-card">
                <h4>Total Semua Pesanan</h4>
                <h2><?= $total_semua; ?> Pesanan</h2>
            </div>
            <div class="stat-card green">
                <h4>Sudah Lunas</h4>
                <h2><?= $total_lunas; ?> Pesanan</h2>
            </div>
            <div class="stat-card orange">
                <h4>Masih Pending</h4>
                <h2><?= $total_pending; ?> Pesanan</h2>
            </div>
        </div>

        <!-- FILTER STATUS -->
        <div class="filter-bar">
            <a href="pesanan.php?filter=semua" class="filter-btn <?= ($filter_status == 'semua') ? 'active' : ''; ?>">Semua</a>
            <a href="pesanan.php?filter=Lunas" class="filter-btn <?= ($filter_status == 'Lunas') ? 'active' : ''; ?>">✅ Lunas</a>
            <a href="pesanan.php?filter=Pending" class="filter-btn <?= ($filter_status == 'Pending') ? 'active' : ''; ?>">⏳ Pending</a>
            <a href="pesanan.php?filter=Diproses" class="filter-btn <?= ($filter_status == 'Diproses') ? 'active' : ''; ?>">🔄 Diproses</a>
        </div>

        <!-- TABEL PESANAN -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Pesanan</th>
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
                    if ($query_pesanan && mysqli_num_rows($query_pesanan) > 0) {
                        while ($row = mysqli_fetch_assoc($query_pesanan)) { 
                            $id_trx     = $row['id_transaksi'];
                            $kode_trx   = 'TRX-' . $id_trx;
                            $tgl_db     = $row['tgl_transaksi'] ?? date('Y-m-d H:i');
                            $tanggal_formatted = date('d/m/Y H:i', strtotime($tgl_db));
                            $total_bayar = $row['total_bayar'] ?? 0;
                            $metode      = $row['metode_pembayaran'] ?? 'Cash (Tunai)';
                            $status      = $row['status_pembayaran'] ?? $row['status'] ?? 'Lunas';
                            if (empty(trim($status))) { $status = 'Lunas'; }
                            
                            if (strtolower($status) == 'lunas') {
                                $badge_class = 'badge-lunas';
                            } elseif (strtolower($status) == 'diproses') {
                                $badge_class = 'badge-proses';
                            } else {
                                $badge_class = 'badge-pending';
                            }
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($kode_trx); ?></strong></td>
                            <td><?= $tanggal_formatted; ?></td>
                            <td>Rp <?= number_format($total_bayar, 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($metode); ?></td>
                            <td><span class="<?= $badge_class; ?>"><?= htmlspecialchars($status); ?></span></td>
                            <td>
                                <button class="btn-proses" onclick="bukaModalProses('<?= $id_trx; ?>', '<?= $kode_trx; ?>', '<?= htmlspecialchars($status, ENT_QUOTES); ?>')">🔄 Proses</button>
                                <button class="btn-detail" onclick="lihatDetail('<?= $id_trx; ?>', '<?= htmlspecialchars($kode_trx, ENT_QUOTES); ?>')">📄 Detail</button>
                                <!-- 🔥 TOMBOL HAPUS BARU -->
                                <a href="pesanan.php?hapus=<?= $id_trx; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus pesanan <?= $kode_trx; ?>? Data detail transaksi juga akan terhapus.');">🗑️ Hapus</a>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='7'><div class='empty-state'><span class='emoji'>🍩</span>Belum ada pesanan dengan status ini.</div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL PROSES PESANAN -->
    <div id="modalProses" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔄 Proses Pesanan</h3>
                <button class="modal-close" onclick="tutupModalProses()">&times;</button>
            </div>
            <form action="" method="POST" style="padding-top:15px;">
                <input type="hidden" name="id_transaksi" id="proses_id">
                
                <div class="form-group">
                    <label>Kode Pesanan:</label>
                    <input type="text" id="proses_kode" disabled style="background:#FCE4EC;">
                </div>
                
                <div class="form-group">
                    <label>Ubah Status Menjadi:</label>
                    <select name="status_baru" id="proses_status" required>
                        <option value="Pending">⏳ Pending (Belum Diproses)</option>
                        <option value="Diproses">🔄 Diproses (Sedang Dibuat)</option>
                        <option value="Lunas">✅ Lunas (Selesai)</option>
                    </select>
                </div>
                
                <button type="submit" name="ubah_status" class="btn-simpan">💾 Simpan Perubahan Status</button>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL PESANAN -->
    <div id="modalDetail" class="modal-bg">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>📄 Detail Pesanan</h3>
                <button class="modal-close" onclick="tutupModalDetail()">&times;</button>
            </div>
            <div id="modalBody" style="padding-top: 15px;">
                <p>Memuat item pesanan...</p>
            </div>
        </div>
    </div>

    <!-- MODAL PROFIL -->
    <div id="modalProfil" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👤 Profil Admin Sistem</h3>
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

    function bukaModalProses(id, kode, status) {
        document.getElementById('modalProses').style.display = 'block';
        document.getElementById('proses_id').value = id;
        document.getElementById('proses_kode').value = kode;
        document.getElementById('proses_status').value = status;
    }
    function tutupModalProses() { document.getElementById('modalProses').style.display = 'none'; }

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