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

    echo "<script>alert('Profil Admin Berhasil Diperbarui!'); window.location.href='transaksi.php';</script>";
    exit;
}

if (isset($_GET['reset'])) {
    unset($_SESSION['cart']);
    header("Location: transaksi.php");
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Deteksi Kolom Tabel
$cols_transaksi = [];
$res_t = @mysqli_query($conn, "SHOW COLUMNS FROM transaksi");
if ($res_t) {
    while ($row = mysqli_fetch_assoc($res_t)) {
        $cols_transaksi[] = strtolower($row['Field']);
    }
}

$cols_produk = [];
$res_p = @mysqli_query($conn, "SHOW COLUMNS FROM produk");
if ($res_p) {
    while ($row = mysqli_fetch_assoc($res_p)) {
        $cols_produk[] = strtolower($row['Field']);
    }
}

$col_p_id    = in_array('id_produk', $cols_produk) ? 'id_produk' : (in_array('id', $cols_produk) ? 'id' : 'id_produk');
$col_p_harga = in_array('harga_produk', $cols_produk) ? 'harga_produk' : (in_array('harga', $cols_produk) ? 'harga' : 'harga_produk');
$col_p_kat   = in_array('kategori', $cols_produk) ? 'kategori' : '';

// 1. TAMBAH KE KERANJANG
if (isset($_POST['tambah_keranjang'])) {
    $id_produk = mysqli_real_escape_string($conn, $_POST['id_produk']);
    $jumlah    = (int)$_POST['jumlah'];

    if (!empty($id_produk) && $jumlah > 0) {
        $q_p = mysqli_query($conn, "SELECT * FROM produk WHERE $col_p_id = '$id_produk'");
        if ($q_p && mysqli_num_rows($q_p) > 0) {
            $p = mysqli_fetch_assoc($q_p);
            $stok_saat_ini = $p['stok'];
            $qty_di_cart   = (isset($_SESSION['cart'][$id_produk]) && is_array($_SESSION['cart'][$id_produk])) ? $_SESSION['cart'][$id_produk]['jumlah'] : 0;
            
            if (($qty_di_cart + $jumlah) > $stok_saat_ini) {
                echo "<script>alert('Stok tidak mencukupi! Stok tersisa: $stok_saat_ini'); window.location='transaksi.php';</script>";
                exit;
            }

            if (isset($_SESSION['cart'][$id_produk])) {
                $_SESSION['cart'][$id_produk]['jumlah'] += $jumlah;
            } else {
                $_SESSION['cart'][$id_produk] = [
                    'id_produk'   => $id_produk,
                    'nama_produk' => $p['nama_produk'],
                    'harga'       => (float)$p[$col_p_harga],
                    'jumlah'      => $jumlah
                ];
            }
        }
    }
    header("Location: transaksi.php");
    exit;
}

// 2. HAPUS ITEM
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    unset($_SESSION['cart'][$id_hapus]);
    header("Location: transaksi.php");
    exit;
}

// 3. BATALKAN KERANJANG
if (isset($_POST['batal_transaksi'])) {
    $_SESSION['cart'] = [];
    header("Location: transaksi.php");
    exit;
}

// 4. PROSES PEMBAYARAN
if (isset($_POST['proses_pembayaran'])) {
    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Keranjang belanja kosong!'); window.location='transaksi.php';</script>";
        exit;
    }

    date_default_timezone_set('Asia/Jakarta');
    $bayar  = (float)$_POST['bayar'];
    $metode = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $tgl_sekarang = date('Y-m-d H:i:s');

    $total_belanja = 0;
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item)) {
            $total_belanja += ($item['harga'] * $item['jumlah']);
        }
    }

    if ($bayar < $total_belanja) {
        echo "<script>alert('Nominal uang pembeli kurang!'); window.location='transaksi.php';</script>";
        exit;
    }

    $kembalian = $bayar - $total_belanja;

    $struk_cart = $_SESSION['cart'];

    // Update stok
    $list_produk_arr = [];
    foreach ($struk_cart as $item) {
        if (is_array($item)) {
            $list_produk_arr[] = $item['nama_produk'] . " (" . $item['jumlah'] . "x)";
            $id_p = $item['id_produk'];
            $qty  = $item['jumlah'];
            mysqli_query($conn, "UPDATE produk SET stok = stok - $qty WHERE $col_p_id = '$id_p'");
        }
    }
    $gabungan_produk = mysqli_real_escape_string($conn, implode(', ', $list_produk_arr));

    // INSERT KE TABEL transaksi
    $sql_insert = "INSERT INTO transaksi (tgl_transaksi, total_bayar, metode_pembayaran, status_pembayaran, status) 
                   VALUES ('$tgl_sekarang', '$total_belanja', '$metode', 'Lunas', 'Lunas')";
    mysqli_query($conn, $sql_insert);

    $new_id = mysqli_insert_id($conn);
    $kode_trx = 'TRX-' . $new_id;

    // SIMPAN DETAIL SETIAP ITEM
    foreach ($struk_cart as $item) {
        if (!is_array($item)) continue;
        $id_p = (int)$item['id_produk'];
        $qty  = (int)$item['jumlah'];
        $sub  = (int)($item['harga'] * $item['jumlah']);
        mysqli_query($conn, "INSERT INTO detail_transaksi (kode_transaksi, id_produk, qty, subtotal) 
                             VALUES ('$kode_trx', '$id_p', '$qty', '$sub')");
    }

    $_SESSION['cart'] = [];

    // 🔥 GENERATE URL VERIFIKASI UNTUK QR CODE
    // Ganti "localhost/mydonat" dengan domain Anda kalau sudah online
    $url_verifikasi = base_url() . "/verifikasi.php?kode=" . urlencode($kode_trx);
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($url_verifikasi);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Struk Pembayaran - My Donat</title>
        <style>
            body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 20px auto; padding: 15px; border: 1px dashed #F8BBD0; background: #FFF5F8; }
            .text-center { text-align: center; }
            .line { border-top: 1px dashed #EC407A; margin: 10px 0; }
            table { width: 100%; font-size: 13px; border-collapse: collapse; }
            table td { padding: 3px 0; }
            .btn-print { background: #EC407A; color: white; border: none; padding: 12px; width: 100%; font-weight: bold; cursor: pointer; margin-top: 15px; border-radius: 6px; font-size: 14px; }
            .btn-print:hover { background: #C2185B; }
            .qr-box { text-align: center; margin: 15px 0; padding: 10px; background: white; border-radius: 10px; }
            .qr-box img { width: 140px; height: 140px; border: 2px solid #EC407A; border-radius: 8px; padding: 5px; }
            .qr-label { font-size: 10px; color: #666; margin-top: 5px; }
            @media print { 
                .btn-print { display: none; }
                body { background: white; border: none; width: 80mm; margin: 0 auto; }
            }
        </style>
    </head>
    <body onload="window.print();">
        <div class="text-center">
            <h2 style="margin:0; color:#C2185B;">🍩 MY DONAT</h2>
            <p style="margin:5px 0; font-size:12px;"><?= date('d-m-Y H:i') ?></p>
            <p style="margin:0; font-size:11px;"><strong><?= $kode_trx ?></strong></p>
        </div>
        <div class="line"></div>
        <table>
            <?php foreach ($struk_cart as $sc): 
                if (!is_array($sc)) continue;
                $sub = $sc['harga'] * $sc['jumlah'];
            ?>
            <tr>
                <td colspan="2"><strong><?= htmlspecialchars($sc['nama_produk']) ?></strong></td>
            </tr>
            <tr>
                <td><?= $sc['jumlah'] ?> x Rp <?= number_format($sc['harga'],0,',','.') ?></td>
                <td style="text-align:right;">Rp <?= number_format($sub,0,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="line"></div>
        <table>
            <tr>
                <td>Total Belanja</td>
                <td style="text-align:right;"><strong>Rp <?= number_format($total_belanja,0,',','.') ?></strong></td>
            </tr>
            <tr>
                <td>Bayar (<?= htmlspecialchars($metode) ?>)</td>
                <td style="text-align:right;">Rp <?= number_format($bayar,0,',','.') ?></td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td style="text-align:right;">Rp <?= number_format($kembalian,0,',','.') ?></td>
            </tr>
        </table>
        <div class="line"></div>
        
        <!-- 🔥 QR CODE VERIFIKASI -->
        <div class="qr-box">
            <p style="font-size: 11px; margin: 0 0 8px 0; color: #C2185B; font-weight: bold;">Scan untuk Verifikasi Struk</p>
            <img src="<?= $qr_url; ?>" alt="QR Code Struk">
            <p class="qr-label"><?= $kode_trx; ?></p>
        </div>
        
        <div class="line"></div>
        <p class="text-center" style="font-size:11px;">-- Terima Kasih --<br>Selamat Menikmati!</p>
        <button class="btn-print" onclick="window.location='transaksi.php';">Kembali ke Kasir</button>
    </body>
    </html>
    <?php
    exit;
}

$total_belanja_saat_ini = 0;
foreach ($_SESSION['cart'] as $item) {
    if (is_array($item)) {
        $total_belanja_saat_ini += ($item['harga'] * $item['jumlah']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transaksi Kasir | Admin My Donat</title>
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

        .container { padding: 30px; max-width: 650px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(236,64,122,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 6px; color: var(--pink-dark); }
        .form-control { width: 100%; padding: 10px; border: 1px solid #F8BBD0; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .form-control:focus { outline: none; border-color: var(--pink-primary); }
        
        /* STYLE DROPDOWN KATEGORI */
        select optgroup {
            font-weight: bold;
            color: var(--pink-dark);
            background: var(--pink-soft);
            padding: 5px;
            font-size: 14px;
        }
        select option {
            font-weight: normal;
            color: #333;
            padding: 6px 10px;
            font-size: 13px;
        }
        
        .row-add { display: flex; gap: 10px; }
        .row-add .input-qty { width: 35%; }
        .row-add .btn-add { width: 65%; background: var(--pink-primary); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .row-add .btn-add:hover { background: var(--pink-dark); }
        .cart-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; color: var(--pink-dark); }
        .table-cart { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        .table-cart th { text-align: left; padding: 10px; background: var(--pink-soft); color: var(--pink-dark); border-bottom: 2px solid #F8BBD0; }
        .table-cart td { padding: 10px; border-bottom: 1px solid #F8BBD0; }
        .btn-delete { color: #E57373; text-decoration: none; font-weight: bold; font-size: 16px; }
        .btn-delete:hover { color: #D32F2F; }
        .box-total { background: var(--pink-soft); border: 1px dashed var(--pink-primary); padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px; }
        .box-total .total-text { color: var(--pink-dark); font-weight: bold; font-size: 18px; margin-bottom: 4px; }
        .box-total .kembali-text { color: #2e7d32; font-weight: bold; font-size: 16px; }
        .row-action { display: flex; gap: 10px; margin-top: 20px; }
        .btn-batal { background: #BDBDBD; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; width: 35%; cursor: pointer; }
        .btn-batal:hover { background: #9E9E9E; }
        .btn-proses { background: var(--pink-primary); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; width: 65%; cursor: pointer; font-size: 15px; }
        .btn-proses:hover { background: var(--pink-dark); }

        .modal-bg { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; max-width: 450px; margin: 60px auto; padding: 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(236,64,122,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--pink-soft); padding-bottom: 10px; }
        .modal-header h3 { color: var(--pink-dark); }
        .modal-close { font-size: 22px; font-weight: bold; cursor: pointer; border: none; background: none; color: var(--pink-primary); }
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
            <a href="transaksi.php" style="text-decoration: underline;">Transaksi Kasir</a>
            <a href="pesanan.php">Pesanan</a>
            <a href="produk_terlaris.php">Terlaris</a>
            <a href="laporan.php">Laporan</a>
            <a href="#" onclick="bukaModalProfil(); return false;" class="btn-edit-profil">⚙️ Edit Profil</a>
            <a href="logout.php" style="color: #FFE4EC;">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="card">
        <form action="" method="POST">
            <div class="form-group">
                <label>Pilih Donat</label>
                <select name="id_produk" class="form-control" required>
                    <option value="">-- Pilih Varian Donat --</option>
                    <?php
                    if (!empty($col_p_kat)) {
                        $q_pro = mysqli_query($conn, "SELECT * FROM produk WHERE stok > 0 ORDER BY $col_p_kat ASC, nama_produk ASC");
                    } else {
                        $q_pro = mysqli_query($conn, "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");
                    }
                    
                    $kategori_saat_ini = '';
                    $ada_grup = false;
                    
                    while ($r = mysqli_fetch_assoc($q_pro)) {
                        $id_p = $r[$col_p_id];
                        $hrg  = $r[$col_p_harga];
                        $kategori = !empty($col_p_kat) && !empty($r[$col_p_kat]) ? $r[$col_p_kat] : 'Tanpa Kategori';
                        
                        if ($kategori != $kategori_saat_ini) {
                            if ($ada_grup) {
                                echo "</optgroup>";
                            }
                            echo "<optgroup label='📁 " . htmlspecialchars($kategori) . "'>";
                            $kategori_saat_ini = $kategori;
                            $ada_grup = true;
                        }
                        
                        echo "<option value='$id_p'>" . htmlspecialchars($r['nama_produk']) . " (Stok: {$r['stok']} - Rp ".number_format($hrg,0,',','.').")</option>";
                    }
                    
                    if ($ada_grup) {
                        echo "</optgroup>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jumlah (Pcs)</label>
                <div class="row-add">
                    <input type="number" name="jumlah" class="form-control input-qty" min="1" value="1" required>
                    <button type="submit" name="tambah_keranjang" class="btn-add">+ Tambah ke Nota</button>
                </div>
            </div>
        </form>

        <hr style="border:0; border-top:1px solid #F8BBD0; margin:20px 0;">

        <div class="cart-title">🛒 Keranjang Belanja Pelanggan</div>
        <table class="table-cart">
            <thead>
                <tr>
                    <th>Donat</th>
                    <th>Harga</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $has_items = false;
                foreach ($_SESSION['cart'] as $item) {
                    if (is_array($item)) {
                        $has_items = true;
                        $sub = $item['harga'] * $item['jumlah'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                            <td>Rp <?= number_format($item['harga'],0,',','.') ?></td>
                            <td><?= $item['jumlah'] ?></td>
                            <td>Rp <?= number_format($sub,0,',','.') ?></td>
                            <td><a href="transaksi.php?hapus=<?= $item['id_produk'] ?>" class="btn-delete" onclick="return confirm('Hapus item ini?')">❌</a></td>
                        </tr>
                        <?php
                    }
                }
                if (!$has_items): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#C2185B;">Belum ada item ditambahkan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <form action="" method="POST">
            <div class="form-group">
                <label>Nominal Uang Pembeli (Rp)</label>
                <input type="number" name="bayar" id="bayar" class="form-control" placeholder="Masukkan nominal uang pembeli" oninput="hitungKembalian()" required>
            </div>

            <div class="box-total">
                <div class="total-text">Total Belanja: Rp <span id="text_total_val"><?= number_format($total_belanja_saat_ini,0,',','.') ?></span></div>
                <div class="kembali-text" id="text_kembali">Kembalian: Rp 0</div>
            </div>

            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="metode_pembayaran" class="form-control">
                    <option value="Cash (Tunai)">Cash (Tunai)</option>
                    <option value="QRIS">QRIS</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                </select>
            </div>

            <div class="row-action">
                <button type="submit" name="batal_transaksi" class="btn-batal" formnovalidate onclick="return confirm('Yakin ingin membatalkan transaksi?')">Batal</button>
                <button type="submit" name="proses_pembayaran" class="btn-proses">Proses Pembayaran</button>
            </div>
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

            <div class="form-group"><label>Upload / Ganti Foto Profil:</label><input type="file" name="foto" accept="image/*" class="form-control"></div>
            <div class="form-group"><label>Nama Lengkap Admin:</label><input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['admin_nama']); ?>" required class="form-control"></div>
            <div class="form-group"><label>NPM Admin:</label><input type="text" name="npm" value="<?= htmlspecialchars($_SESSION['admin_npm']); ?>" required class="form-control"></div>
            <div class="form-group"><label>Email Admin:</label><input type="email" name="email" value="<?= htmlspecialchars($_SESSION['admin_email']); ?>" required class="form-control"></div>

            <button type="submit" name="update_profil" class="btn-simpan">💾 Simpan Perubahan Profil</button>
        </form>
    </div>
</div>

<script>
function bukaModalProfil() { document.getElementById('modalProfil').style.display = 'block'; }
function tutupModalProfil() { document.getElementById('modalProfil').style.display = 'none'; }

let totalBelanja = <?= $total_belanja_saat_ini ?>;
function hitungKembalian() {
    let bayar = parseFloat(document.getElementById('bayar').value) || 0;
    let kembali = bayar - totalBelanja;
    let textKembali = document.getElementById('text_kembali');

    if (bayar > 0) {
        if (kembali >= 0) {
            textKembali.innerText = 'Kembalian: Rp ' + kembali.toLocaleString('id-ID');
            textKembali.style.color = '#2e7d32';
        } else {
            textKembali.innerText = 'Uang Kurang (Rp ' + Math.abs(kembali).toLocaleString('id-ID') + ')';
            textKembali.style.color = '#d32f2f';
        }
    } else {
        textKembali.innerText = 'Kembalian: Rp 0';
        textKembali.style.color = '#2e7d32';
    }
}
</script>
</body>
</html>