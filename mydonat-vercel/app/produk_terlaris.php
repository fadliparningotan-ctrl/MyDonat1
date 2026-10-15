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
    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='produk_terlaris.php';</script>";
    exit;
}

// 🔥 FILTER PERIODE
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'semua';
$where_sql = "";
$label_periode = "Semua Waktu";

switch ($periode) {
    case 'hari_ini':
        $tgl = date('Y-m-d');
        $where_sql = " AND DATE(t.tgl_transaksi) = '$tgl'";
        $label_periode = "Hari Ini (" . date('d/m/Y') . ")";
        break;
    case '7_hari':
        $tgl_7 = date('Y-m-d', strtotime('-7 days'));
        $where_sql = " AND DATE(t.tgl_transaksi) >= '$tgl_7'";
        $label_periode = "7 Hari Terakhir";
        break;
    case 'bulan_ini':
        $bulan = date('Y-m');
        $where_sql = " AND DATE_FORMAT(t.tgl_transaksi, '%Y-%m') = '$bulan'";
        $label_periode = "Bulan " . date('F Y');
        break;
    case 'tahun_ini':
        $tahun = date('Y');
        $where_sql = " AND YEAR(t.tgl_transaksi) = '$tahun'";
        $label_periode = "Tahun " . $tahun;
        break;
    default:
        $label_periode = "Semua Waktu";
        break;
}

// 🔥 QUERY PRODUK TERLARIS
$query_terlaris = mysqli_query($conn, "
    SELECT 
        p.id_produk,
        p.nama_produk,
        p.kategori,
        p.gambar,
        SUM(dt.qty) AS total_terjual,
        SUM(dt.subtotal) AS total_pendapatan
    FROM detail_transaksi dt
    LEFT JOIN produk p ON dt.id_produk = p.id_produk
    LEFT JOIN transaksi t ON dt.kode_transaksi = CONCAT('TRX-', t.id_transaksi)
    WHERE 1=1 $where_sql
    GROUP BY dt.id_produk
    ORDER BY total_terjual DESC
    LIMIT 5
");

// HITUNG TOTAL UNTUK PROGRESS BAR
$max_terjual = 0;
$data_terlaris = [];
if ($query_terlaris && mysqli_num_rows($query_terlaris) > 0) {
    while ($row = mysqli_fetch_assoc($query_terlaris)) {
        $data_terlaris[] = $row;
        if ($row['total_terjual'] > $max_terjual) {
            $max_terjual = $row['total_terjual'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk Terlaris | Admin My Donat</title>
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

        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        h2.page-title { color: var(--pink-dark); margin-bottom: 20px; }
        
        /* FILTER */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { background: white; color: var(--pink-dark); border: 2px solid var(--pink-light); padding: 8px 18px; border-radius: 20px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 13px; transition: 0.2s; }
        .filter-btn:hover { background: var(--pink-soft); }
        .filter-btn.active { background: var(--pink-primary); color: white; border-color: var(--pink-primary); }
        
        .periode-label { text-align: center; padding: 12px; background: white; border-radius: 8px; color: var(--pink-dark); font-weight: bold; font-size: 14px; margin-bottom: 20px; border: 1px dashed var(--pink-primary); }
        
        /* PRODUK CARD */
        .produk-list { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        .produk-item { display: flex; align-items: center; gap: 20px; padding: 20px; border-bottom: 1px solid var(--pink-light); transition: 0.2s; }
        .produk-item:last-child { border-bottom: none; }
        .produk-item:hover { background: #FFF5F8; border-radius: 8px; }
        
        .rank-badge { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; color: white; flex-shrink: 0; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
        .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA000 100%); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
        .rank-4, .rank-5 { background: linear-gradient(135deg, #EC407A 0%, #C2185B 100%); }
        
        .produk-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; border: 2px solid var(--pink-light); flex-shrink: 0; }
        .produk-icon { width: 60px; height: 60px; border-radius: 10px; background: var(--pink-soft); display: flex; align-items: center; justify-content: center; font-size: 32px; flex-shrink: 0; }
        
        .produk-info { flex: 1; min-width: 0; }
        .produk-nama { font-weight: bold; color: var(--pink-dark); font-size: 16px; margin-bottom: 4px; }
        .produk-kategori { font-size: 12px; color: #888; margin-bottom: 8px; }
        
        .progress-bar { width: 100%; height: 10px; background: var(--pink-soft); border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #EC407A 0%, #F06292 100%); border-radius: 5px; transition: width 0.5s; }
        
        .produk-stats { text-align: right; flex-shrink: 0; }
        .produk-terjual { font-size: 22px; font-weight: bold; color: var(--pink-primary); }
        .produk-terjual-label { font-size: 11px; color: #888; text-transform: uppercase; }
        .produk-pendapatan { font-size: 13px; color: var(--pink-dark); font-weight: bold; margin-top: 5px; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #888; }
        .empty-state .emoji { font-size: 80px; display: block; margin-bottom: 20px; }
        
        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; }
        .modal-header h3 { color: var(--pink-dark); margin: 0; }
        .modal-close { font-size: 22px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: var(--pink-dark); margin-bottom: 4px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid var(--pink-light); border-radius: 6px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: var(--pink-primary); }
        .btn-simpan { background: var(--pink-primary); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-simpan:hover { background: var(--pink-dark); }
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
                <a href="pesanan.php">Pesanan</a>
                <a href="produk_terlaris.php" style="text-decoration: underline;">Terlaris</a>
                <a href="laporan.php">Laporan</a>
                <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
                <a href="logout.php" style="color:#FFE4EC;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="page-title">🏆 Produk Terlaris</h2>

        <!-- FILTER PERIODE -->
        <div class="filter-bar">
            <a href="produk_terlaris.php?periode=semua" class="filter-btn <?= ($periode == 'semua') ? 'active' : ''; ?>">📋 Semua Waktu</a>
            <a href="produk_terlaris.php?periode=hari_ini" class="filter-btn <?= ($periode == 'hari_ini') ? 'active' : ''; ?>">📅 Hari Ini</a>
            <a href="produk_terlaris.php?periode=7_hari" class="filter-btn <?= ($periode == '7_hari') ? 'active' : ''; ?>">🗓️ 7 Hari</a>
            <a href="produk_terlaris.php?periode=bulan_ini" class="filter-btn <?= ($periode == 'bulan_ini') ? 'active' : ''; ?>">📆 Bulan Ini</a>
            <a href="produk_terlaris.php?periode=tahun_ini" class="filter-btn <?= ($periode == 'tahun_ini') ? 'active' : ''; ?>">📅 Tahun Ini</a>
        </div>

        <div class="periode-label">
            🏆 Top 5 Donat Terlaris — <span style="color: var(--pink-primary);"><?= htmlspecialchars($label_periode); ?></span>
        </div>

        <div class="produk-list">
            <?php if (!empty($data_terlaris)): ?>
                <?php 
                $rank = 1;
                foreach ($data_terlaris as $p): 
                    $persen = ($max_terjual > 0) ? round(($p['total_terjual'] / $max_terjual) * 100) : 0;
                    $gambar_file = 'produk/' . ($p['gambar'] ?? '');
                    $gambar_ada = !empty($p['gambar']);
                ?>
                <div class="produk-item">
                    <div class="rank-badge rank-<?= $rank; ?>">
                        <?php 
                        if ($rank == 1) echo '🥇';
                        elseif ($rank == 2) echo '🥈';
                        elseif ($rank == 3) echo '🥉';
                        else echo $rank;
                        ?>
                    </div>
                    
                    <?php if ($gambar_ada): ?>
                        <img src="<?= $gambar_file; ?>" class="produk-img" alt="Donat">
                    <?php else: ?>
                        <div class="produk-icon">🍩</div>
                    <?php endif; ?>
                    
                    <div class="produk-info">
                        <div class="produk-nama"><?= htmlspecialchars($p['nama_produk'] ?? 'Donat'); ?></div>
                        <div class="produk-kategori">📁 <?= htmlspecialchars($p['kategori'] ?? 'Tanpa Kategori'); ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $persen; ?>%;"></div>
                        </div>
                    </div>
                    
                    <div class="produk-stats">
                        <div class="produk-terjual"><?= $p['total_terjual']; ?>x</div>
                        <div class="produk-terjual-label">Terjual</div>
                        <div class="produk-pendapatan">Rp <?= number_format($p['total_pendapatan'], 0, ',', '.'); ?></div>
                    </div>
                </div>
                <?php 
                $rank++;
                endforeach; 
                ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="emoji">🍩</span>
                    <h3 style="color: var(--pink-dark);">Belum Ada Penjualan</h3>
                    <p>Belum ada transaksi pada periode <strong><?= htmlspecialchars($label_periode); ?></strong>.</p>
                    <p style="font-size: 13px;">Coba pilih periode lain, atau buat transaksi baru di kasir.</p>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>