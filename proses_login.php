<?php

session_start();

require_once __DIR__ . "/config/koneksi.php";


// ==========================================
// CEK REQUEST
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}


// ==========================================
// AMBIL DATA LOGIN
// ==========================================

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// ==========================================
// VALIDASI
// ==========================================

if ($email === '' || $password === '') {
    header("Location: login.php?error=Email dan password harus diisi");
    exit;
}


// ==========================================
// CARI USER
// ==========================================

$sql = "
    SELECT
        id,
        nama,
        email,
        no_hp,
        password,
        role,
        status
    FROM users
    WHERE email = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query login gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $email
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);


// ==========================================
// USER TIDAK DITEMUKAN
// ==========================================

if (!$user) {
    header("Location: login.php?error=Email atau password salah");
    exit;
}


// ==========================================
// CEK STATUS
// ==========================================

if ($user['status'] !== 'aktif') {
    header("Location: login.php?error=Akun tidak aktif");
    exit;
}


// ==========================================
// CEK PASSWORD
// ==========================================

if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=Email atau password salah");
    exit;
}


// ==========================================
// BUAT SESSION
// ==========================================

session_regenerate_id(true);

$_SESSION['login'] = true;

$_SESSION['id'] = (int) $user['id'];

$_SESSION['nama'] = $user['nama'];

$_SESSION['email'] = $user['email'];

$_SESSION['role'] = $user['role'];


// ==========================================
// REDIRECT ADMIN
// ==========================================

if ($user['role'] === 'admin') {

    header("Location: admin/dashboard.php");

    exit;
}


// ==========================================
// REDIRECT PELANGGAN
// ==========================================

if ($user['role'] === 'pelanggan') {

    header("Location: user/dashboard.php");

    exit;
}


// ==========================================
// ROLE TIDAK VALID
// ==========================================

session_unset();
session_destroy();

header("Location: login.php?error=Role akun tidak valid");

exit;

?>