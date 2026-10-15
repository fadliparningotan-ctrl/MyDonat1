<?php
require_once __DIR__ . '/sesi.php';
include 'db.php';

// Login Admin
if (isset($_POST['login'])) {
    $u = mysqli_real_escape_string($conn, $_POST['username']);
    $p = md5($_POST['password']);

    $q = mysqli_query($conn, "SELECT * FROM admin WHERE username='$u' AND password='$p'");
    $data = mysqli_fetch_assoc($q);

    if ($data) {
        $_SESSION['admin_status'] = true;
        $_SESSION['admin_username'] = $data['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau Password Admin salah!";
    }
}

// Register Admin Baru
if (isset($_POST['register'])) {
    $u = mysqli_real_escape_string($conn, $_POST['reg_username']);
    $p = md5($_POST['reg_password']);

    $cek = mysqli_query($conn, "SELECT * FROM admin WHERE username='$u'");
    if (mysqli_num_rows($cek) > 0) {
        $error_reg = "Username sudah digunakan!";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO admin (username, password) VALUES ('$u', '$p')");
        if ($insert) {
            $success_reg = "Akun Admin berhasil dibuat! Silakan login.";
        } else {
            $error_reg = "Gagal membuat akun admin!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin | My Donat</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            margin: 0; 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            background: linear-gradient(135deg, #FFB6C1 0%, #EC407A 50%, #C2185B 100%);
            font-family: 'Segoe UI', Arial, sans-serif; 
            position: relative;
            overflow-x: hidden;
        }
        
        /* 🍩 BACKGROUND DONAT DECORATIVE */
        .donat-bg {
            position: fixed;
            font-size: 140px;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
        }
        .donat-1 { top: 5%; left: 5%; animation-delay: 0s; }
        .donat-2 { top: 15%; right: 8%; animation-delay: 1s; font-size: 100px; }
        .donat-3 { bottom: 10%; left: 10%; animation-delay: 2s; font-size: 120px; }
        .donat-4 { bottom: 8%; right: 5%; animation-delay: 1.5s; font-size: 150px; }
        .donat-5 { top: 45%; left: 3%; animation-delay: 0.5s; font-size: 90px; }
        .donat-6 { top: 50%; right: 3%; animation-delay: 2.5s; font-size: 90px; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        .box { 
            background: white; 
            padding: 40px 35px; 
            width: 360px; 
            border-radius: 25px; 
            box-shadow: 0 15px 40px rgba(194,24,91,0.4); 
            text-align: center;
            position: relative;
            z-index: 1;
            border-top: 6px solid #EC407A;
        }
        
        .box h2 {
            color: #C2185B;
            margin: 0 0 5px 0;
            font-size: 26px;
        }
        
        .box p {
            color: #EC407A;
            margin: 0 0 25px 0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .donat-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 10px;
            animation: spin 3s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            75% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }
        
        input { 
            width: 100%; 
            padding: 14px 16px; 
            margin-top: 12px; 
            box-sizing: border-box; 
            border-radius: 12px; 
            border: 2px solid #F8BBD0;
            background: #FFF5F8;
            font-size: 14px;
            transition: 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #EC407A;
            background: white;
            box-shadow: 0 0 0 3px rgba(236,64,122,0.15);
        }
        
        button { 
            width: 100%; 
            padding: 14px; 
            margin-top: 18px; 
            box-sizing: border-box; 
            border-radius: 12px; 
            border: none; 
            background: linear-gradient(135deg, #EC407A 0%, #F06292 100%);
            color: white; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px;
            letter-spacing: 0.5px;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(236,64,122,0.4);
        }
        
        button:hover { 
            background: linear-gradient(135deg, #C2185B 0%, #EC407A 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(236,64,122,0.5);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error { 
            color: #D32F2F; 
            margin-top: 10px; 
            font-size: 13px; 
            background: #FFEBEE;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #D32F2F;
        }
        
        .success { 
            color: #2E7D32; 
            margin-top: 10px; 
            font-size: 13px;
            background: #E8F5E9;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #2E7D32;
        }
        
        .register-link { 
            display: block; 
            margin-top: 20px; 
            font-size: 14px; 
            color: #EC407A; 
            text-decoration: none; 
            font-weight: bold; 
            cursor: pointer;
            transition: 0.2s;
        }
        
        .register-link:hover {
            color: #C2185B;
            text-decoration: underline;
        }
        
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 10; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(194,24,91,0.5); 
            justify-content: center; 
            align-items: center; 
            backdrop-filter: blur(4px);
        }
        
        .modal-content { 
            background-color: white; 
            padding: 30px 25px; 
            border-radius: 20px; 
            width: 340px; 
            text-align: center; 
            position: relative;
            box-shadow: 0 15px 40px rgba(194,24,91,0.4);
            border-top: 6px solid #EC407A;
        }
        
        .modal-content h3 {
            color: #C2185B;
            margin: 0 0 15px 0;
        }
        
        .close-btn { 
            position: absolute; 
            right: 18px; 
            top: 12px; 
            font-size: 24px; 
            cursor: pointer; 
            color: #EC407A;
            font-weight: bold;
        }
        
        .close-btn:hover {
            color: #C2185B;
        }
    </style>
</head>
<body>

<!-- 🍩 BACKGROUND DECORATIVE DONUTS -->
<div class="donat-bg donat-1">🍩</div>
<div class="donat-bg donat-2">🍩</div>
<div class="donat-bg donat-3">🍩</div>
<div class="donat-bg donat-4">🍩</div>
<div class="donat-bg donat-5">🍩</div>
<div class="donat-bg donat-6">🍩</div>

<div class="box">
    <span class="donat-icon">🍩</span>
    <h2>Admin My Donat</h2>
    <p>Silakan Login Kasir / Admin</p>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if(isset($success_reg)) echo "<div class='success'>$success_reg</div>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="👤 Username Admin" required>
        <input type="password" name="password" placeholder="🔒 Password" required>
        <button name="login">Login Admin</button>
    </form>

    <a class="register-link" onclick="openModal()">+ Buat Akun Admin Baru</a>
</div>

<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>🍩 Daftar Akun Admin Baru</h3>
        
        <?php if(isset($error_reg)) echo "<div class='error'>$error_reg</div>"; ?>

        <form method="POST">
            <input type="text" name="reg_username" placeholder="👤 Username Baru" required>
            <input type="password" name="reg_password" placeholder="🔒 Password Baru" required>
            <button name="register" style="background: linear-gradient(135deg, #66BB6A 0%, #43A047 100%); box-shadow: 0 4px 12px rgba(67,160,71,0.4);">Daftar Admin</button>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('registerModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('registerModal').style.display = 'none'; }
    <?php if (isset($error_reg)) : ?> openModal(); <?php endif; ?>
</script>

</body>
</html>