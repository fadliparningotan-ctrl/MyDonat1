<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

if (!isset($_SESSION['admin_status'])) {
    header("Location: login.php");
    exit;
}

// Inisialisasi data profil admin jika belum ada
if (!isset($_SESSION['admin_nama']))  { $_SESSION['admin_nama']  = 'Fadli Parningotan Tambunan'; }
if (!isset($_SESSION['admin_npm']))   { $_SESSION['admin_npm']   = '2455201155'; }
if (!isset($_SESSION['admin_email'])) { $_SESSION['admin_email'] = 'fadli.tambunan@example.com'; }
if (!isset($_SESSION['admin_foto']))  { $_SESSION['admin_foto']  = ''; }

// PROSES UPDATE / EDIT PROFIL
if (isset($_POST['update_profil'])) {
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $npm   = mysqli_real_escape_string($conn, $_POST['npm']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $_SESSION['admin_nama']  = $nama;
    $_SESSION['admin_npm']   = $npm;
    $_SESSION['admin_email'] = $email;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { @mkdir($target_dir, 0777, true); }
        $file_extension = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $new_filename = "admin_foto_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        if (simpan_upload($_FILES["foto"]["tmp_name"], $target_file)) {
            $_SESSION['admin_foto'] = $target_file;
            @mysqli_query($conn, "UPDATE admin SET nama = '$nama', npm = '$npm', email = '$email', foto = '$target_file'");
        }
    } else {
        @mysqli_query($conn, "UPDATE admin SET nama = '$nama', npm = '$npm', email = '$email'");
    }

    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='produk.php';</script>";
    exit;
}

$message = "";

// Cek struktur kolom tabel produk
$cols = [];
$res_cols = mysqli_query($conn, "SHOW COLUMNS FROM produk");
if ($res_cols) {
    while ($row = mysqli_fetch_assoc($res_cols)) {
        $cols[] = strtolower($row['Field']);
    }
}

// Deteksi nama kolom Primary Key & Harga
$col_id    = in_array('id_produk', $cols) ? 'id_produk' : (in_array('id', $cols) ? 'id' : 'id_produk');
$col_harga = in_array('harga_produk', $cols) ? 'harga_produk' : (in_array('harga', $cols) ? 'harga' : 'harga_produk');

// 1. PROSES SIMPAN / TAMBAH / UPDATE / EDIT STOK
if (isset($_POST['simpan_produk'])) {
    $id_edit     = isset($_POST['id_produk']) ? mysqli_real_escape_string($conn, $_POST['id_produk']) : '';
    $mode        = isset($_POST['mode_aksi']) ? $_POST['mode_aksi'] : 'tambah';
    $nama_produk = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $harga       = (float)$_POST['harga'];
    $stok_input  = (int)$_POST['stok'];

    // Process Upload Gambar
    $nama_gambar = "";
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $nama_gambar = time() . '_' . rand(100, 999) . '.' . $ext;
        if (!is_dir('produk')) {
            @mkdir('produk', 0777, true);
        }
        simpan_upload($_FILES['gambar']['tmp_name'], 'produk/' . $nama_gambar);
    }

    // A. JIKA MODE EDIT
    if ($mode == 'edit' && !empty($id_edit)) {
        if (!empty($nama_gambar) && in_array('gambar', $cols)) {
            $u_sql = "UPDATE produk SET nama_produk = '$nama_produk', $col_harga = '$harga', stok = '$stok_input', gambar = '$nama_gambar' WHERE $col_id = '$id_edit'";
        } else {
            $u_sql = "UPDATE produk SET nama_produk = '$nama_produk', $col_harga = '$harga', stok = '$stok_input' WHERE $col_id = '$id_edit'";
        }
        mysqli_query($conn, $u_sql);
        $message = "Varian donat '$nama_produk' berhasil diperbarui!";
    } 
    // B. JIKA DIPILIH DARI "+ Add Stok"
    else if ($mode == 'add_stok' && !empty($id_edit)) {
        if (!empty($nama_gambar) && in_array('gambar', $cols)) {
            $u_sql = "UPDATE produk SET stok = stok + $stok_input, $col_harga = '$harga', gambar = '$nama_gambar' WHERE $col_id = '$id_edit'";
        } else {
            $u_sql = "UPDATE produk SET stok = stok + $stok_input, $col_harga = '$harga' WHERE $col_id = '$id_edit'";
        }
        mysqli_query($conn, $u_sql);
        $message = "Stok untuk '$nama_produk' berhasil ditambah (+ $stok_input pcs)!";
    } 
    // C. JIKA DARI FORM TAMBAH BARU
    else {
        $q_cek = mysqli_query($conn, "SELECT * FROM produk WHERE LOWER(nama_produk) = LOWER('$nama_produk')");
        
        if ($q_cek && mysqli_num_rows($q_cek) > 0) {
            $data_exist = mysqli_fetch_assoc($q_cek);
            $id_exist   = $data_exist[$col_id];
            
            if (!empty($nama_gambar) && in_array('gambar', $cols)) {
                $u_sql = "UPDATE produk SET stok = stok + $stok_input, $col_harga = '$harga', gambar = '$nama_gambar' WHERE $col_id = '$id_exist'";
            } else {
                $u_sql = "UPDATE produk SET stok = stok + $stok_input, $col_harga = '$harga' WHERE $col_id = '$id_exist'";
            }
            mysqli_query($conn, $u_sql);
            $message = "Stok donat '$nama_produk' berhasil digabungkan (+ $stok_input pcs)!";
        } else {
            if (!empty($nama_gambar) && in_array('gambar', $cols)) {
                $i_sql = "INSERT INTO produk (nama_produk, $col_harga, stok, gambar) VALUES ('$nama_produk', '$harga', '$stok_input', '$nama_gambar')";
            } else {
                $i_sql = "INSERT INTO produk (nama_produk, $col_harga, stok) VALUES ('$nama_produk', '$harga', '$stok_input')";
            }
            mysqli_query($conn, $i_sql);
            $message = "Varian donat baru '$nama_produk' berhasil ditambahkan!";
        }
    }
}

// 2. PROSES HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM produk WHERE $col_id = '$id_hapus'");
    header("Location: produk.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Donat | Admin My Donat</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --pink-primary: #EC407A;
            --pink-dark: #C2185B;
            --pink-light: #F8BBD0;
            --pink-soft: #FCE4EC;
            --pink-bg: #FFF5F8;
        }
        body { font-family: Arial, sans-serif; background: var(--pink-bg); margin: 0; }
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
        .container h2 { color: var(--pink-dark); }
        .btn-add { background: var(--pink-primary); color: white; padding: 10px 18px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 20px; transition: 0.2s; }
        .btn-add:hover { background: var(--pink-dark); }
        .table-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #F8BBD0; font-size: 14px; vertical-align: middle; }
        th { background-color: var(--pink-soft); color: var(--pink-dark); font-weight: bold; }
        tr:hover { background: #FFF5F8; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(236,64,122,0.15); }
        .btn-danger { color: #E57373; font-weight: bold; text-decoration: none; margin-left: 8px; }
        .btn-danger:hover { text-decoration: underline; }
        .btn-warning { color: var(--pink-primary); font-weight: bold; text-decoration: none; margin-left: 8px; }
        .btn-warning:hover { text-decoration: underline; }
        .alert { padding: 12px; background: var(--pink-soft); color: var(--pink-dark); border-radius: 8px; margin-bottom: 20px; font-weight: bold; border-left: 4px solid var(--pink-primary); }
        
        /* Modal Form */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; width: 400px; margin: 10% auto; padding: 25px; border-radius: 12px; position: relative; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-content h3 { color: var(--pink-dark); margin-top: 0; }
        .close-btn { position: absolute; right: 15px; top: 10px; font-size: 22px; cursor: pointer; border: none; background: none; font-weight: bold; color: var(--pink-primary); }
        .form-group { margin-bottom: 12px; text-align: left; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; color: var(--pink-dark); }
        .form-control { width: 100%; padding: 8px 10px; border: 1px solid #F8BBD0; border-radius: 6px; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--pink-primary); }
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
            <a href="produk.php" style="text-decoration: underline;">Data Donat</a>
            <a href="kategori.php">Kategori</a>
            <a href="transaksi.php">Transaksi Kasir</a>
            <a href="pesanan.php">Pesanan</a>
            <a href="produk_terlaris.php">Terlaris</a>
            <a href="laporan.php">Laporan</a>
            <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
            <a href="logout.php" style="color: #FFE4EC;">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h2>Daftar Varian Donat</h2>

    <?php if ($message): ?>
        <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <button class="btn-add" onclick="openModalTambah()">+ Tambah Donat Baru</button>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>Nama Donat</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q_produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY $col_id DESC");
                if ($q_produk && mysqli_num_rows($q_produk) > 0) {
                    $no = 1;
                    while ($p = mysqli_fetch_assoc($q_produk)) {
                        $id_p    = isset($p[$col_id]) ? $p[$col_id] : '';
                        $harga_p = isset($p[$col_harga]) ? $p[$col_harga] : 0;
                        $stok_p  = isset($p['stok']) ? $p['stok'] : 0;
                        $gambar  = (!empty($p['gambar'])) ? 'produk/' . $p['gambar'] : 'https://via.placeholder.com/50';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><img src="<?= $gambar ?>" class="img-thumb" alt="Donat" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div style="display:none; width:50px; height:50px; border-radius:8px; background:#FCE4EC; align-items:center; justify-content:center; font-size:26px;">🍩</div></td>
                            <td><strong><?= htmlspecialchars($p['nama_produk']) ?></strong></td>
                            <td>Rp <?= number_format($harga_p, 0, ',', '.') ?></td>
                            <td><?= $stok_p ?> pcs</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModalStok('<?= $id_p ?>', '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>', '<?= $harga_p ?>')" style="color:#42A5F5; font-weight:bold; text-decoration:none;">+ Add Stok</a>
                                <a href="javascript:void(0)" onclick="openModalEdit('<?= $id_p ?>', '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>', '<?= $harga_p ?>', '<?= $stok_p ?>')" class="btn-warning">✏️ Edit</a>
                                <a href="produk.php?hapus=<?= $id_p ?>" class="btn-danger" onclick="return confirm('Yakin hapus varian donat ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;'>Belum ada data varian donat.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah/Stok/Edit Produk -->
<div id="modalProduk" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal()">&times;</button>
        <h3 id="modalTitle">Tambah Donat Baru</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_produk" id="id_produk" value="">
            <input type="hidden" name="mode_aksi" id="mode_aksi" value="tambah">
            
            <div class="form-group">
                <label>Nama Donat</label>
                <input type="text" name="nama_produk" id="nama_produk" class="form-control" placeholder="Contoh: Donat Keju" required>
            </div>
            <div class="form-group">
                <label>Harga Satuan (Rp)</label>
                <input type="number" name="harga" id="harga" class="form-control" placeholder="5000" required>
            </div>
            <div class="form-group">
                <label id="labelStok">Stok Awal</label>
                <input type="number" name="stok" id="stok" class="form-control" placeholder="10" min="0" required>
            </div>
            <div class="form-group">
                <label>Foto Donat (Opsional)</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
            </div>
            <button type="submit" name="simpan_produk" class="btn-add" style="width:100%; margin-top:10px;">Simpan Data</button>
        </form>
    </div>
</div>

<!-- Modal Profil Admin -->
<div id="modalProfil" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="tutupModalProfil()">&times;</button>
        <h3>👤 Profil Admin Sistem</h3>
        
        <form action="" method="POST" enctype="multipart/form-data" style="padding-top:10px;">
            <div style="text-align:center; margin-bottom:15px;">
                <?php if (!empty($_SESSION['admin_foto'])): ?>
                    <img src="<?= $_SESSION['admin_foto']; ?>" alt="Foto Admin" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #EC407A;">
                <?php else: ?>
                    <div style="width:80px; height:80px; border-radius:50%; background:#EC407A; color:white; display:flex; align-items:center; justify-content:center; font-size:40px; margin:0 auto;">👨‍💻</div>
                <?php endif; ?>
            </div>

            <div class="form-group"><label>Upload / Ganti Foto Profil:</label><input type="file" name="foto" accept="image/*" class="form-control"></div>
            <div class="form-group"><label>Nama Lengkap Admin:</label><input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['admin_nama']); ?>" required class="form-control"></div>
            <div class="form-group"><label>NPM Admin:</label><input type="text" name="npm" value="<?= htmlspecialchars($_SESSION['admin_npm']); ?>" required class="form-control"></div>
            <div class="form-group"><label>Email Admin:</label><input type="email" name="email" value="<?= htmlspecialchars($_SESSION['admin_email']); ?>" required class="form-control"></div>

            <button type="submit" name="update_profil" class="btn-add" style="width:100%; margin-top:10px;">💾 Simpan Perubahan Profil</button>
        </form>
    </div>
</div>

<script>
function openModalTambah() {
    document.getElementById('modalProduk').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Tambah Donat Baru';
    document.getElementById('labelStok').innerText = 'Stok Awal';
    document.getElementById('id_produk').value = '';
    document.getElementById('mode_aksi').value = 'tambah';
    document.getElementById('nama_produk').value = '';
    document.getElementById('nama_produk').readOnly = false;
    document.getElementById('harga').value = '';
    document.getElementById('stok').value = '';
}

function openModalStok(id, nama, harga) {
    document.getElementById('modalProduk').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Tambah Stok - ' + nama;
    document.getElementById('labelStok').innerText = 'Jumlah Stok Ditambahkan';
    document.getElementById('id_produk').value = id;
    document.getElementById('mode_aksi').value = 'add_stok';
    document.getElementById('nama_produk').value = nama;
    document.getElementById('nama_produk').readOnly = true;
    document.getElementById('harga').value = harga;
    document.getElementById('stok').value = '';
    document.getElementById('stok').focus();
}

function openModalEdit(id, nama, harga, stok) {
    document.getElementById('modalProduk').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Edit Varian - ' + nama;
    document.getElementById('labelStok').innerText = 'Stok Total (Ubah)';
    document.getElementById('id_produk').value = id;
    document.getElementById('mode_aksi').value = 'edit';
    document.getElementById('nama_produk').value = nama;
    document.getElementById('nama_produk').readOnly = false;
    document.getElementById('harga').value = harga;
    document.getElementById('stok').value = stok;
    document.getElementById('nama_produk').focus();
}

function closeModal() {
    document.getElementById('modalProduk').style.display = 'none';
}

function bukaModalProfil() { document.getElementById('modalProfil').style.display = 'block'; }
function tutupModalProfil() { document.getElementById('modalProfil').style.display = 'none'; }

window.onclick = function(event) {
    var modalP = document.getElementById('modalProduk');
    var modalPr = document.getElementById('modalProfil');
    if (event.target == modalP) { modalP.style.display = 'none'; }
    if (event.target == modalPr) { modalPr.style.display = 'none'; }
}
</script>

</body>
</html>