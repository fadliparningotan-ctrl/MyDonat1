<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

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
    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='kategori.php';</script>";
    exit;
}

$message = "";

// PROSES TAMBAH KATEGORI
if (isset($_POST['tambah_kategori'])) {
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    if (!empty($nama)) {
        $cek = mysqli_query($conn, "SELECT * FROM kategori WHERE LOWER(nama_kategori) = LOWER('$nama')");
        if (mysqli_num_rows($cek) > 0) {
            $message = "❌ Kategori '$nama' sudah ada!";
        } else {
            mysqli_query($conn, "INSERT INTO kategori (nama_kategori, deskripsi) VALUES ('$nama', '$deskripsi')");
            $message = "✅ Kategori '$nama' berhasil ditambahkan!";
        }
    }
}

// PROSES EDIT KATEGORI
if (isset($_POST['edit_kategori'])) {
    $id        = (int)$_POST['id_kategori'];
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_kategori']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    mysqli_query($conn, "UPDATE kategori SET nama_kategori = '$nama', deskripsi = '$deskripsi' WHERE id_kategori = '$id'");
    $message = "✅ Kategori '$nama' berhasil diperbarui!";
}

// PROSES HAPUS KATEGORI
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id_kategori = '$id_hapus'");
    header("Location: kategori.php");
    exit;
}

// AMBIL SEMUA KATEGORI
$query_kategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id_kategori DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kategori Donat | Admin My Donat</title>
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
        
        .btn-add { background: var(--pink-primary); color: white; padding: 10px 18px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 20px; transition: 0.2s; }
        .btn-add:hover { background: var(--pink-dark); }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--pink-light); font-size: 14px; vertical-align: middle; }
        th { background: var(--pink-soft); color: var(--pink-dark); font-weight: bold; }
        tr:hover { background: #FFF5F8; }
        
        .badge-count { background: var(--pink-primary); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        
        .btn-action { font-weight: bold; text-decoration: none; font-size: 13px; margin-right: 10px; }
        .btn-edit-kat { color: var(--pink-primary); }
        .btn-edit-kat:hover { text-decoration: underline; }
        .btn-hapus-kat { color: #E57373; }
        .btn-hapus-kat:hover { text-decoration: underline; }
        
        .alert { padding: 12px; background: var(--pink-soft); color: var(--pink-dark); border-radius: 8px; margin-bottom: 20px; font-weight: bold; border-left: 4px solid var(--pink-primary); }
        
        .empty-state { text-align: center; padding: 40px; color: #888; }
        .empty-state .emoji { font-size: 60px; display: block; margin-bottom: 15px; }

        /* MODAL */
        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; margin-bottom: 15px; }
        .modal-header h3 { color: var(--pink-dark); margin: 0; }
        .modal-close { font-size: 22px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: var(--pink-dark); margin-bottom: 4px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--pink-light); border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--pink-primary); }
        .btn-simpan { background: var(--pink-primary); color: white; border: none; padding: 12px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; transition: 0.2s; }
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
                <a href="kategori.php" style="text-decoration: underline;">Kategori</a>
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
        <h2 class="page-title">🗂️ Manajemen Kategori Donat</h2>

        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <button class="btn-add" onclick="bukaModalTambah()">+ Tambah Kategori Baru</button>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Produk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($query_kategori && mysqli_num_rows($query_kategori) > 0) {
                        while ($row = mysqli_fetch_assoc($query_kategori)) { 
                            $id_kat = $row['id_kategori'];
                            // Hitung jumlah produk di kategori ini (berdasarkan nama kategori)
                            $nama_kat = $row['nama_kategori'];
                            $q_count = mysqli_query($conn, "SELECT COUNT(*) as c FROM produk WHERE kategori = '$nama_kat'");
                            $count = mysqli_fetch_assoc($q_count)['c'] ?? 0;
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_kategori']); ?></strong></td>
                            <td><?= htmlspecialchars($row['deskripsi'] ?: '-'); ?></td>
                            <td><span class="badge-count"><?= $count; ?> Produk</span></td>
                            <td>
                                <a href="javascript:void(0)" onclick="bukaModalEdit(<?= $id_kat; ?>, '<?= htmlspecialchars($row['nama_kategori'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['deskripsi'], ENT_QUOTES); ?>')" class="btn-action btn-edit-kat">✏️ Edit</a>
                                <a href="kategori.php?hapus=<?= $id_kat; ?>" class="btn-action btn-hapus-kat" onclick="return confirm('Yakin ingin menghapus kategori ini?');">🗑️ Hapus</a>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='5'><div class='empty-state'><span class='emoji'>🍩</span>Belum ada kategori donat. Klik 'Tambah Kategori Baru' untuk mulai.</div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH KATEGORI -->
    <div id="modalTambah" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Kategori Baru</h3>
                <button class="modal-close" onclick="tutupModalTambah()">&times;</button>
            </div>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Nama Kategori:</label>
                    <input type="text" name="nama_kategori" placeholder="Contoh: Donat Premium" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi (Opsional):</label>
                    <textarea name="deskripsi" rows="3" placeholder="Contoh: Donat dengan topping mewah"></textarea>
                </div>
                <button type="submit" name="tambah_kategori" class="btn-simpan">💾 Simpan Kategori</button>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KATEGORI -->
    <div id="modalEdit" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Kategori</h3>
                <button class="modal-close" onclick="tutupModalEdit()">&times;</button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="id_kategori" id="edit_id">
                <div class="form-group">
                    <label>Nama Kategori:</label>
                    <input type="text" name="nama_kategori" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi:</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_kategori" class="btn-simpan">💾 Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- MODAL PROFIL -->
    <div id="modalProfil" class="modal-bg">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👤 Profil Admin Sistem</h3>
                <button class="modal-close" onclick="tutupModalProfil()">&times;</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
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
    function bukaModalTambah() { document.getElementById('modalTambah').style.display = 'block'; }
    function tutupModalTambah() { document.getElementById('modalTambah').style.display = 'none'; }

    function bukaModalEdit(id, nama, deskripsi) {
        document.getElementById('modalEdit').style.display = 'block';
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_deskripsi').value = deskripsi;
    }
    function tutupModalEdit() { document.getElementById('modalEdit').style.display = 'none'; }

    function bukaModalProfil() { document.getElementById('modalProfil').style.display = 'block'; }
    function tutupModalProfil() { document.getElementById('modalProfil').style.display = 'none'; }
    </script>
</body>
</html>