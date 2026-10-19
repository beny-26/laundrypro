<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . "/../config/koneksi.php";


// ==================================================
// CEK LOGIN
// ==================================================

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../login.php");
    exit;
}


// ==================================================
// CEK ROLE
// ==================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../index.php");
    exit;
}


$user_id = (int) $_SESSION['id'];


// ==================================================
// AMBIL DATA PELANGGAN
// ==================================================

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.nama_pelanggan,
        p.no_hp,
        p.email,
        p.jenis_layanan,
        p.berat_kg,
        p.tarif_per_kg,
        p.durasi_jam,
        p.status
    FROM pelanggan p
    WHERE p.user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query pelanggan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pelanggan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$pelanggan) {
    die("
        <div style='
            font-family: Arial;
            padding: 50px;
            text-align: center;
        '>
            <h2>Data Pelanggan Tidak Ditemukan</h2>

            <p>
                Akun Anda sudah login, tetapi belum memiliki
                data pelanggan.
            </p>

            <a href='../logout.php'>
                Logout
            </a>
        </div>
    ");
}

$pelanggan_id = (int) $pelanggan['id'];


// ==================================================
// FUNGSI FORMAT
// ==================================================

function formatRupiah($angka)
{
    return "Rp " . number_format(
        (float) $angka,
        0,
        ",",
        "."
    );
}


function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return "-";
    }

    return date(
        "d-m-Y H:i",
        strtotime($tanggal)
    );
}


function statusTagihan($status)
{
    if ($status === 'belum_bayar') {

        return '
            <span class="status status-belum">
                <i class="bi bi-wallet2 me-1"></i>
                Belum Bayar
            </span>
        ';

    }

    if ($status === 'menunggu_verifikasi') {

        return '
            <span class="status status-menunggu">
                <i class="bi bi-hourglass-split me-1"></i>
                Menunggu Verifikasi
            </span>
        ';

    }

    if ($status === 'lunas') {

        return '
            <span class="status status-lunas">
                <i class="bi bi-check-circle me-1"></i>
                Lunas
            </span>
        ';

    }

    if ($status === 'ditolak') {

        return '
            <span class="status status-ditolak">
                <i class="bi bi-x-circle me-1"></i>
                Ditolak
            </span>
        ';
    }

    return '
        <span class="status status-default">
            ' . htmlspecialchars($status) . '
        </span>
    ';
}


// ==================================================
// STATISTIK TAGIHAN
// ==================================================

$sql = "
    SELECT
        COUNT(*) AS jumlah,
        COALESCE(SUM(total), 0) AS nominal
    FROM tagihan
    WHERE pelanggan_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

$total_tagihan = (int) ($data['jumlah'] ?? 0);
$total_nominal_tagihan = (float) ($data['nominal'] ?? 0);

mysqli_stmt_close($stmt);


// ==================================================
// TAGIHAN BELUM BAYAR
// ==================================================

$sql = "
    SELECT
        COUNT(*) AS jumlah,
        COALESCE(SUM(total), 0) AS nominal
    FROM tagihan
    WHERE pelanggan_id = ?
    AND status = 'belum_bayar'
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

$belum_bayar = (int) ($data['jumlah'] ?? 0);
$nominal_belum_bayar = (float) ($data['nominal'] ?? 0);

mysqli_stmt_close($stmt);


// ==================================================
// MENUNGGU VERIFIKASI
// ==================================================

$sql = "
    SELECT
        COUNT(*) AS jumlah,
        COALESCE(SUM(total), 0) AS nominal
    FROM tagihan
    WHERE pelanggan_id = ?
    AND status = 'menunggu_verifikasi'
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

$menunggu = (int) ($data['jumlah'] ?? 0);
$nominal_menunggu = (float) ($data['nominal'] ?? 0);

mysqli_stmt_close($stmt);


// ==================================================
// TAGIHAN LUNAS
// ==================================================

$sql = "
    SELECT
        COUNT(*) AS jumlah,
        COALESCE(SUM(total), 0) AS nominal
    FROM tagihan
    WHERE pelanggan_id = ?
    AND status = 'lunas'
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

$lunas = (int) ($data['jumlah'] ?? 0);
$nominal_lunas = (float) ($data['nominal'] ?? 0);

mysqli_stmt_close($stmt);


// ==================================================
// TAGIHAN DITOLAK
// ==================================================

$sql = "
    SELECT COUNT(*) AS jumlah
    FROM tagihan
    WHERE pelanggan_id = ?
    AND status = 'ditolak'
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

$ditolak = (int) ($data['jumlah'] ?? 0);

mysqli_stmt_close($stmt);


// ==================================================
// TAGIHAN TERBARU
// ==================================================

$sql = "
    SELECT
        id,
        kode_tagihan,
        jenis_layanan,
        berat_kg,
        tarif_per_kg,
        total,
        periode_mulai,
        periode_selesai,
        jatuh_tempo,
        status
    FROM tagihan
    WHERE pelanggan_id = ?
    ORDER BY id DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query tagihan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$resultTagihan = mysqli_stmt_get_result($stmt);


// ==================================================
// PEMBAYARAN TERBARU
// ==================================================

$sql = "
    SELECT
        pb.id,
        pb.kode_pembayaran,
        pb.jumlah,
        pb.metode,
        pb.status,
        pb.tanggal_bayar,
        pb.bukti_bayar,
        t.kode_tagihan
    FROM pembayaran pb
    INNER JOIN tagihan t
        ON t.id = pb.tagihan_id
    WHERE pb.pelanggan_id = ?
    ORDER BY pb.tanggal_bayar DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query pembayaran gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmt);

$resultPembayaran = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard Pelanggan - LaundryPro
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }


        /* ==================================================
           MAIN
        ================================================== */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* ==================================================
           TOPBAR
        ================================================== */

        .topbar {
            background: white;
            height: 75px;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }

        .topbar h4 {
            margin: 0;
            color: #142b5f;
            font-weight: bold;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-icon {
            width: 42px;
            height: 42px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }


        /* ==================================================
           CONTENT
        ================================================== */

        .content {
            padding: 30px;
        }


        /* ==================================================
           WELCOME
        ================================================== */

        .welcome {
            background: linear-gradient(
                135deg,
                #142b5f,
                #2563eb
            );

            color: white;
            border-radius: 15px;
            padding: 28px;
            margin-bottom: 25px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.08);
        }

        .welcome h3 {
            margin: 0 0 8px;
            font-weight: bold;
        }

        .welcome p {
            margin: 0;
            color: #dbeafe;
        }


        /* ==================================================
           STAT
        ================================================== */

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 22px;
            height: 100%;

            box-shadow:
                0 3px 15px rgba(0,0,0,.05);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: #e8f0ff;
            color: #2563eb;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 21px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #142b5f;
        }

        .stat-money {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
        }


        /* ==================================================
           CARD
        ================================================== */

        .card-custom {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.05);
        }

        .card-title {
            color: #142b5f;
            font-weight: bold;
            margin-bottom: 20px;
        }


        /* ==================================================
           PROFILE
        ================================================== */

        .profile-item {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
        }

        .profile-label {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .profile-value {
            font-weight: bold;
            color: #142b5f;
        }


        /* ==================================================
           STATUS
        ================================================== */

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-belum {
            background: #fff3cd;
            color: #856404;
        }

        .status-menunggu {
            background: #cff4fc;
            color: #055160;
        }

        .status-lunas {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-ditolak {
            background: #f8d7da;
            color: #842029;
        }

        .status-default {
            background: #e5e7eb;
            color: #374151;
        }


        /* ==================================================
           BUTTON
        ================================================== */

        .btn-detail {
            background: #2563eb;
            color: white;
            padding: 7px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-detail:hover {
            background: #1d4ed8;
            color: white;
        }


        /* ==================================================
           MOBILE
        ================================================== */

        @media (max-width: 768px) {

            .main {
                margin-left: 70px;
            }

            .topbar {
                padding: 0 15px;
            }

            .content {
                padding: 15px;
            }

            .topbar h4 {
                font-size: 17px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>


<body>


<?php

// ==================================================
// SIDEBAR PELANGGAN
// ==================================================

require_once __DIR__ . "/sidebar.php";

?>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">


    <!-- ==================================================
         TOPBAR
    ================================================== -->

    <div class="topbar">

        <h4>
            Dashboard Pelanggan
        </h4>


        <div class="user-area">

            <div>

                <strong>
                    <?= htmlspecialchars(
                        $pelanggan['nama_pelanggan']
                    ); ?>
                </strong>

                <br>

                <small class="text-muted">
                    Pelanggan
                </small>

            </div>


            <div class="user-icon">

                <i class="bi bi-person"></i>

            </div>

        </div>

    </div>


    <!-- ==================================================
         CONTENT
    ================================================== -->

    <div class="content">


        <!-- ==================================================
             WELCOME
        ================================================== -->

        <div class="welcome">

            <h3>

                Selamat Datang,
                <?= htmlspecialchars(
                    $pelanggan['nama_pelanggan']
                ); ?>

                👋

            </h3>


            <p>

                Pantau tagihan, pembayaran, dan status laundry
                Anda melalui dashboard LaundryPro.

            </p>

        </div>


        <!-- ==================================================
             STATISTIK
        ================================================== -->

        <div class="row g-4">


            <!-- TOTAL TAGIHAN -->

            <div class="col-md-6 col-lg-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-receipt"></i>

                    </div>


                    <div class="stat-number">

                        <?= $total_tagihan; ?>

                    </div>


                    <div class="stat-title">

                        Total Tagihan

                    </div>


                    <div class="stat-money">

                        <?= formatRupiah(
                            $total_nominal_tagihan
                        ); ?>

                    </div>

                </div>

            </div>


            <!-- BELUM BAYAR -->

            <div class="col-md-6 col-lg-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-wallet2"></i>

                    </div>


                    <div class="stat-number">

                        <?= $belum_bayar; ?>

                    </div>


                    <div class="stat-title">

                        Belum Bayar

                    </div>


                    <div class="stat-money">

                        <?= formatRupiah(
                            $nominal_belum_bayar
                        ); ?>

                    </div>

                </div>

            </div>


            <!-- MENUNGGU VERIFIKASI -->

            <div class="col-md-6 col-lg-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-hourglass-split"></i>

                    </div>


                    <div class="stat-number">

                        <?= $menunggu; ?>

                    </div>


                    <div class="stat-title">

                        Menunggu Verifikasi

                    </div>


                    <div class="stat-money">

                        <?= formatRupiah(
                            $nominal_menunggu
                        ); ?>

                    </div>

                </div>

            </div>


            <!-- LUNAS -->

            <div class="col-md-6 col-lg-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-check-circle"></i>

                    </div>


                    <div class="stat-number">

                        <?= $lunas; ?>

                    </div>


                    <div class="stat-title">

                        Tagihan Lunas

                    </div>


                    <div class="stat-money">

                        <?= formatRupiah(
                            $nominal_lunas
                        ); ?>

                    </div>

                </div>

            </div>


        </div>


        <!-- ==================================================
             ALERT TAGIHAN
        ================================================== -->

        <?php if ($belum_bayar > 0): ?>

            <div class="alert alert-warning mt-4">

                <div class="d-flex align-items-center">

                    <i class="bi bi-exclamation-circle fs-4 me-3"></i>

                    <div>

                        <strong>
                            Anda memiliki
                            <?= $belum_bayar; ?>
                            tagihan yang belum dibayar.
                        </strong>

                        <br>

                        <small>

                            Total yang perlu dibayar:
                            <?= formatRupiah(
                                $nominal_belum_bayar
                            ); ?>

                        </small>

                    </div>


                    <a
                        href="tagihan/index.php"
                        class="btn btn-warning ms-auto"
                    >

                        Bayar Sekarang

                    </a>

                </div>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             INFORMASI PELANGGAN
        ================================================== -->

        <div class="card-custom">

            <h5 class="card-title">

                <i class="bi bi-person-vcard me-2"></i>

                Informasi Pelanggan

            </h5>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            Nama
                        </div>

                        <div class="profile-value">

                            <?= htmlspecialchars(
                                $pelanggan['nama_pelanggan']
                            ); ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            Email
                        </div>

                        <div class="profile-value">

                            <?= htmlspecialchars(
                                $pelanggan['email'] ?? '-'
                            ); ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            No. HP
                        </div>

                        <div class="profile-value">

                            <?= htmlspecialchars(
                                $pelanggan['no_hp'] ?? '-'
                            ); ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            Jenis Layanan
                        </div>

                        <div class="profile-value">

                            <?= htmlspecialchars(
                                $pelanggan['jenis_layanan']
                            ); ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            Berat Laundry
                        </div>

                        <div class="profile-value">

                            <?= number_format(
                                $pelanggan['berat_kg'],
                                2,
                                ',',
                                '.'
                            ); ?>

                            Kg

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="profile-item">

                        <div class="profile-label">
                            Tarif / Kg
                        </div>

                        <div class="profile-value">

                            <?= formatRupiah(
                                $pelanggan['tarif_per_kg']
                            ); ?>

                        </div>

                    </div>

                </div>


            </div>

        </div>


        <!-- ==================================================
             TAGIHAN TERBARU
        ================================================== -->

        <div class="card-custom">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="card-title mb-0">

                    <i class="bi bi-receipt me-2"></i>

                    Tagihan Terbaru

                </h5>


                <a
                    href="tagihan/index.php"
                    class="btn btn-primary btn-sm"
                >

                    Lihat Semua

                </a>

            </div>


            <div class="table-responsive">

                <table class="table table-hover align-middle">


                    <thead>

                        <tr>

                            <th>Kode</th>

                            <th>Layanan</th>

                            <th>Berat</th>

                            <th>Total</th>

                            <th>Jatuh Tempo</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (mysqli_num_rows($resultTagihan) > 0): ?>


                        <?php while (
                            $tagihan = mysqli_fetch_assoc(
                                $resultTagihan
                            )
                        ): ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $tagihan['kode_tagihan']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $tagihan['jenis_layanan']
                                    ); ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        $tagihan['berat_kg'],
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                    Kg

                                </td>


                                <td>

                                    <strong>

                                        <?= formatRupiah(
                                            $tagihan['total']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= formatTanggal(
                                        $tagihan['jatuh_tempo']
                                    ); ?>

                                </td>


                                <td>

                                    <?= statusTagihan(
                                        $tagihan['status']
                                    ); ?>

                                </td>


                                <td>

                                    <a
                                        href="tagihan/detail.php?id=<?= (int) $tagihan['id']; ?>"
                                        class="btn-detail"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Detail

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="bi bi-receipt"
                                    style="font-size:35px;"
                                ></i>


                                <div class="mt-2">

                                    Belum ada tagihan.

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


        <!-- ==================================================
             PEMBAYARAN TERBARU
        ================================================== -->

        <div class="card-custom">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="card-title mb-0">

                    <i class="bi bi-credit-card me-2"></i>

                    Pembayaran Terbaru

                </h5>


                <a
                    href="pembayaran/riwayat.php"
                    class="btn btn-outline-primary btn-sm"
                >

                    Lihat Riwayat

                </a>

            </div>


            <div class="table-responsive">

                <table class="table table-hover align-middle">


                    <thead>

                        <tr>

                            <th>Kode Pembayaran</th>

                            <th>Tagihan</th>

                            <th>Jumlah</th>

                            <th>Metode</th>

                            <th>Tanggal</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (mysqli_num_rows($resultPembayaran) > 0): ?>


                        <?php while (
                            $pembayaran = mysqli_fetch_assoc(
                                $resultPembayaran
                            )
                        ): ?>


                            <tr>


                                <td>

                                    <strong class="text-primary">

                                        <?= htmlspecialchars(
                                            $pembayaran['kode_pembayaran']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $pembayaran['kode_tagihan']
                                    ); ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= formatRupiah(
                                            $pembayaran['jumlah']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $pembayaran['metode']
                                    ); ?>

                                </td>


                                <td>

                                    <?= formatTanggal(
                                        $pembayaran['tanggal_bayar']
                                    ); ?>

                                </td>


                                <td>


                                    <?php if (
                                        $pembayaran['status'] === 'menunggu'
                                    ): ?>

                                        <span class="status status-menunggu">

                                            <i class="bi bi-hourglass-split me-1"></i>

                                            Menunggu

                                        </span>


                                    <?php elseif (
                                        $pembayaran['status'] === 'terverifikasi'
                                    ): ?>

                                        <span class="status status-lunas">

                                            <i class="bi bi-check-circle me-1"></i>

                                            Terverifikasi

                                        </span>


                                    <?php elseif (
                                        $pembayaran['status'] === 'ditolak'
                                    ): ?>

                                        <span class="status status-ditolak">

                                            <i class="bi bi-x-circle me-1"></i>

                                            Ditolak

                                        </span>


                                    <?php else: ?>

                                        <span class="status status-default">

                                            <?= htmlspecialchars(
                                                $pembayaran['status']
                                            ); ?>

                                        </span>

                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5 text-muted"
                            >

                                <i
                                    class="bi bi-credit-card"
                                    style="font-size:35px;"
                                ></i>


                                <div class="mt-2">

                                    Belum ada pembayaran.

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>