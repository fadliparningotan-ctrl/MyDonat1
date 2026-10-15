<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

if (!isset($_SESSION['admin_status'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['submit'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga    = mysqli_real_escape_string($conn, $_POST['harga_produk']);
    $stok     = mysqli_real_escape_string($conn, $_POST['stok']);

    // Upload Gambar
    $filename = $_FILES['gambar']['name'];
    $tmp_name = $_FILES['gambar']['tmp_name'];
    $type1    = explode('.', $filename);
    $type2    = strtolower(end($type1));

    $newname  = 'donat_' . time() . '.' . $type2;
    $tipe_diizinkan = array('jpg', 'jpeg', 'png', 'webp');

    if (in_array($type2, $tipe_diizinkan)) {
        if (!is_dir('produk')) {
            @mkdir('produk', 0777, true);
        }
        simpan_upload($tmp_name, './produk/' . $newname);

        $insert = mysqli_query($conn, "INSERT INTO produk (nama_produk, kategori, harga_produk, stok, gambar) VALUES ('$nama', '$kategori', '$harga', '$stok', '$newname')");

        if ($insert) {
            echo '<script>alert("Varian donat berhasil ditambahkan!"); window.location="produk.php";</script>';
        } else {
            $error = "Gagal menyimpan ke database! Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Format file gambar tidak diizinkan! (Gunakan JPG, PNG, atau WEBP)";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Varian Donat | Admin My Donat</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        .navbar { background-color: #ff9800; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .container { max-width: 500px; margin: 40px auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-top: 0; }
        label { display: block; margin-top: 10px; font-weight: bold; color: #555; }
        .input-control { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #218838; }
        .error { color: red; margin-bottom: 15px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🍩 Admin My Donat</h2>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="produk.php">Data Donat</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Tambah Varian Donat</h2>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label>Nama Varian Donat</label>
        <input type="text" name="nama_produk" placeholder="Contoh: Donat Coklat Meises" class="input-control" required>

        <label>Kategori Donat</label>
        <select name="kategori" class="input-control" required>
            <option value="">-- Pilih Kategori --</option>
            <option value="Donat Cincin">Donat Cincin (Klasik)</option>
            <option value="Donat Bomboloni">Donat Bomboloni (Isian)</option>
            <option value="Donat Mini">Donat Mini</option>
            <option value="Donat Custom">Donat Custom / Tower</option>
            <option value="Minuman">Minuman Pendamping</option>
        </select>

        <label>Harga (Rp)</label>
        <input type="number" name="harga_produk" placeholder="Contoh: 5000" class="input-control" required>

        <label>Stok Donat</label>
        <input type="number" name="stok" placeholder="Contoh: 50" class="input-control" required>

        <label>Foto Donat</label>
        <input type="file" name="gambar" class="input-control" required>

        <button type="submit" name="submit" class="btn">Simpan Varian Donat</button>
    </form>
</div>

</body>
</html> 