<?php
require_once __DIR__ . '/../sesi.php';

// Cegah akses tanpa login
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

include '../db.php';

// Ambil data user
$username = $_SESSION['user'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
$user = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard User | My Donat</title>
    <meta charset="UTF-8">
    <style>
        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#fff7f0;
        }
        .header{
            background:#ff9800;
            color:white;
            padding:15px;
            text-align:center;
        }
        .container{
            padding:20px;
        }
        .card{
            background:white;
            padding:20px;
            border-radius:15px;
            margin-bottom:20px;
            box-shadow:0 4px 8px rgba(0,0,0,0.1);
        }
        .menu{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(160px,1fr));
            gap:15px;
        }
        .menu a{
            text-decoration:none;
            background:#ff9800;
            color:white;
            padding:18px;
            border-radius:15px;
            text-align:center;
            font-weight:bold;
        }
        .menu a:hover{
            background:#fb8c00;
        }
        .logout{
            background:#e53935 !important;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>🍩 My Donat</h2>
    <p>Dashboard User</p>
</div>

<div class="container">

    <div class="card">
        <h3>Halo, <?= htmlspecialchars($user['nama']) ?> 👋</h3>
        <p>Username: <b><?= htmlspecialchars($user['username']) ?></b></p>
        <p>Selamat datang di aplikasi pemesanan My Donat.</p>
    </div>

    <div class="card">
        <h3>Menu Utama</h3>
        <div class="menu">
            <a href="#">🍩 Produk Donat</a>
            <a href="#">🛒 Keranjang</a>
            <a href="#">📦 Pesanan Saya</a>
            <a href="../logout.php" class="logout">🚪 Logout</a>
        </div>
    </div>

</div>

</body>
</html>
