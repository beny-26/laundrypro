<?php

session_start();

// Jika pengguna sudah login
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {

    // Arahkan berdasarkan role pengguna
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'pelanggan') {
        header("Location: user/dashboard.php");
        exit;
    }
}

// Jika belum login, arahkan ke halaman login
header("Location: login.php");
exit;

?>