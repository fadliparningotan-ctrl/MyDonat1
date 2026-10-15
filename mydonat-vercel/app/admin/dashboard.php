<?php
require_once __DIR__ . '/../sesi.php';
if (!isset($_SESSION['admin'])) {
    header("Location: /login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <style>
        body{font-family:Arial;background:#f4f4f4;margin:0}
        .header{
            background:#ff3d6d;
            color:white;
            padding:20px;
        }
        .menu{
            padding:20px;
        }
        a{
            display:inline-block;
            margin-right:15px;
            text-decoration:none;
            color:#ff3d6d;
            font-weight:bold;
        }   
    </style>
</head>
<body>

<div class="header">
    <h2>🍩 Dashboard Admin My Donat</h2>
    Login sebagai: <b><?= $_SESSION['admin'] ?></b>
</div>

<div class="menu">
    <a href="produk.php">Kelola Produk</a>
    <a href="../logout.php">Logout</a>
</div>

</body>
</html>
