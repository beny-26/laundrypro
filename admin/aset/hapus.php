<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";

// ==========================================
// CEK LOGIN
// ==========================================
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../login.php");
    exit;
}

// ==========================================
// CEK ROLE
// ==========================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// ==========================================
// CEK ID
// ==========================================
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=" . urlencode("ID aset tidak valid"));
    exit;
}

// ==========================================
// CEK DATA ASET
// ==========================================
$sql = "SELECT id, kode_aset, nama_aset
        FROM aset_tetap
        WHERE id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$aset = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$aset) {
    header("Location: index.php?error=" . urlencode("Data aset tidak ditemukan"));
    exit;
}

// ==========================================
// HAPUS DATA
// ==========================================
$sqlDelete = "DELETE FROM aset_tetap WHERE id = ?";

$stmtDelete = mysqli_prepare($conn, $sqlDelete);

if (!$stmtDelete) {
    header(
        "Location: index.php?error=" .
        urlencode("Gagal menyiapkan proses penghapusan")
    );
    exit;
}

mysqli_stmt_bind_param($stmtDelete, "i", $id);

if (mysqli_stmt_execute($stmtDelete)) {

    mysqli_stmt_close($stmtDelete);

    header(
        "Location: index.php?success=" .
        urlencode(
            "Aset " . $aset['kode_aset'] .
            " - " . $aset['nama_aset'] .
            " berhasil dihapus"
        )
    );

    exit;

} else {

    $error = mysqli_stmt_error($stmtDelete);

    mysqli_stmt_close($stmtDelete);

    header(
        "Location: index.php?error=" .
        urlencode("Aset gagal dihapus: " . $error)
    );

    exit;
}
?>