<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Jika belum login, redirect ke halaman login
header("Location: login.php");
exit;
?>