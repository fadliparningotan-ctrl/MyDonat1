<?php
include 'db.php';

if (isset($_POST['daftar'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    mysqli_query($conn,"INSERT INTO admin (nama, username, password, email) VALUES ('$nama','$username','$password','$email')");
    header("Location: login.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Admin</title>
</head>
<body>

<h2>Daftar Admin My Donat</h2>

<form method="POST">
    <input type="text" name="nama" placeholder="Nama Lengkap" required><br><br>
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button name="daftar">Daftar</button>
</form>

</body>
</html>
