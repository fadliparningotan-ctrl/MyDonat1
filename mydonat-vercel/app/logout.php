<?php
require_once __DIR__ . '/sesi.php';
session_destroy();
header("Location: login.php");
exit;
?>