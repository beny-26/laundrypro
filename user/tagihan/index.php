<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . "/../../config/koneksi.php";


// ==================================================
// CEK LOGIN
// ==================================================

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../login.php");
    exit;
}


// ==================================================
// CEK ROLE
// ==================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../../index.php");
    exit;
}


// ==================================================
// USER LOGIN
// ==================================================

$user_id = (int) $_SESSION['id'];


// ==================================================
// AMBIL DATA PELANGGAN
// ==================================================

$sqlPelanggan = "
    SELECT
        id,
        nama_pelanggan,
        email,
        no_hp
    FROM pelanggan
    WHERE user_id = ?
    LIMIT 1
";

$stmtPelanggan = mysqli_prepare($conn, $sqlPelanggan);

if (!$stmtPelanggan) {
    die("Query pelanggan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtPelanggan,
    "i",
    $user_id
);

mysqli_stmt_execute($stmtPelanggan);

$resultPelanggan = mysqli_stmt_get_result($stmtPelanggan);

$pelanggan = mysqli_fetch_assoc($resultPelanggan);

if (!$pelanggan) {
    die("
        <div style='font-family:Arial;padding:50px;text-align:center'>
            <h2>Data pelanggan tidak ditemukan</h2>
            <p>Akun Anda belum terhubung dengan data pelanggan.</p>
            <a href='../../logout.php'>Logout</a>
        </div>
    ");
}

$pelanggan_id = (int) $pelanggan['id'];


// ==================================================
// AMBIL TAGIHAN
// ==================================================

$sqlTagihan = "
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
        status,
        created_at
    FROM tagihan
    WHERE pelanggan_id = ?
    ORDER BY created_at DESC
";

$stmtTagihan = mysqli_prepare($conn, $sqlTagihan);

if (!$stmtTagihan) {
    die("Query tagihan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtTagihan,
    "i",
    $pelanggan_id
);

mysqli_stmt_execute($stmtTagihan);

$resultTagihan = mysqli_stmt_get_result($stmtTagihan);


// ==================================================
// STATISTIK
// ==================================================

$totalTagihan = 0;
$totalNominal = 0;
$totalBelumBayar = 0;
$totalLunas = 0;

$tagihanData = [];

while ($row = mysqli_fetch_assoc($resultTagihan)) {

    $tagihanData[] = $row;

    $totalTagihan++;

    $totalNominal += (float) $row['total'];

    if (
        $row['status'] === 'belum_bayar' ||
        $row['status'] === 'ditolak'
    ) {
        $totalBelumBayar++;
    }

    if ($row['status'] === 'lunas') {
        $totalLunas++;
    }
}


// ==================================================
// FORMAT RUPIAH
// ==================================================

function rupiahTagihan($angka)
{
    return "Rp " . number_format(
        (float) $angka,
        0,
        ",",
        "."
    );
}


// ==================================================
// FORMAT TANGGAL
// ==================================================

function tanggalTagihan($tanggal)
{
    if (empty($tanggal)) {
        return "-";
    }

    return date(
        "d-m-Y H:i",
        strtotime($tanggal)
    );
}


// ==================================================
// STATUS
// ==================================================

function badgeStatus($status)
{
    switch ($status) {

        case 'belum_bayar':
            return '
                <span class="status status-belum">
                    <i class="bi bi-clock"></i>
                    Belum Bayar
                </span>
            ';

        case 'menunggu_verifikasi':
            return '
                <span class="status status-menunggu">
                    <i class="bi bi-hourglass-split"></i>
                    Menunggu Verifikasi
                </span>
            ';

        case 'lunas':
            return '
                <span class="status status-lunas">
                    <i class="bi bi-check-circle"></i>
                    Lunas
                </span>
            ';

        case 'ditolak':
            return '
                <span class="status status-ditolak">
                    <i class="bi bi-x-circle"></i>
                    Ditolak
                </span>
            ';

        default:
            return '
                <span class="status status-belum">
                    Tidak Diketahui
                </span>
            ';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Tagihan Saya - LaundryPro</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

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


        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #142b5f;
            color: white;
            padding: 25px 15px;
            z-index: 1000;
        }

        .logo {
            text-align: center;
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .menu-title {
            color: #aebbd3;
            font-size: 11px;
            text-transform: uppercase;
            margin: 20px 10px 10px;
            letter-spacing: .5px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: .2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
        }

        .sidebar i {
            margin-right: 10px;
        }


        /* =========================================
           MAIN
        ========================================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* =========================================
           TOPBAR
        ========================================= */

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
        }


        /* =========================================
           CONTENT
        ========================================= */

        .content {
            padding: 30px;
        }


        /* =========================================
           HEADER
        ========================================= */

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
        }

        .page-header h3 {
            margin: 0 0 8px;
            color: #142b5f;
            font-weight: bold;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }


        /* =========================================
           STAT CARD
        ========================================= */

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
            height: 100%;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .icon-blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .icon-yellow {
            background: #fef3c7;
            color: #d97706;
        }

        .icon-green {
            background: #dcfce7;
            color: #16a34a;
        }

        .icon-purple {
            background: #ede9fe;
            color: #7c3aed;
        }

        .stat-title {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .stat-value {
            color: #142b5f;
            font-size: 22px;
            font-weight: bold;
        }


        /* =========================================
           TABLE
        ========================================= */

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
            margin-top: 25px;
        }

        .table-title {
            color: #142b5f;
            font-weight: bold;
            margin-bottom: 20px;
        }

        table th {
            background: #f8fafc !important;
            color: #374151;
            font-size: 13px;
            white-space: nowrap;
        }

        table td {
            font-size: 13px;
            vertical-align: middle;
        }


        /* =========================================
           STATUS
        ========================================= */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
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


        /* =========================================
           BUTTON
        ========================================= */

        .btn-detail {
            background: #2563eb;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 3px;
        }

        .btn-detail:hover {
            background: #1d4ed8;
            color: white;
        }

        .btn-bayar {
            background: #198754;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }

        .btn-bayar:hover {
            background: #157347;
            color: white;
        }


        /* =========================================
           EMPTY
        ========================================= */

        .empty-box {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-box i {
            font-size: 45px;
            display: block;
            margin-bottom: 15px;
        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 768px) {

            .sidebar {
                width: 70px;
                padding: 20px 8px;
            }

            .logo {
                font-size: 0;
            }

            .logo::after {
                content: "LP";
                font-size: 20px;
            }

            .sidebar a {
                text-align: center;
                padding: 12px 5px;
            }

            .sidebar a span {
                display: none;
            }

            .sidebar i {
                margin: 0;
            }

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
                font-size: 16px;
            }

            .user-area strong,
            .user-area small {
                display: none;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<div class="sidebar">

    <div class="logo">
        LaundryPro
    </div>


    <div class="menu-title">
        Menu
    </div>


    <a href="../dashboard.php">

        <i class="bi bi-speedometer2"></i>

        <span>Dashboard</span>

    </a>


    <a
        href="index.php"
        class="active"
    >

        <i class="bi bi-receipt"></i>

        <span>Tagihan Saya</span>

    </a>


    <a href="../pembayaran/riwayat.php">

        <i class="bi bi-credit-card"></i>

        <span>Riwayat Pembayaran</span>

    </a>


    <div class="menu-title">
        Akun
    </div>


    <a
        href="../../logout.php"
        onclick="return confirm('Apakah Anda yakin ingin logout?')"
    >

        <i class="bi bi-box-arrow-right"></i>

        <span>Logout</span>

    </a>

</div>



<!-- =========================================
     MAIN
========================================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h4>
            Tagihan Saya
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



    <!-- CONTENT -->

    <div class="content">


        <!-- HEADER -->

        <div class="page-header">

            <h3>

                <i class="bi bi-receipt"></i>

                Tagihan Saya

            </h3>

            <p>
                Berikut adalah daftar tagihan laundry
                yang terhubung dengan akun Anda.
            </p>

        </div>



        <!-- STATISTIK -->

        <div class="row g-4">


            <!-- TOTAL -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon icon-blue">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div class="stat-title">
                        Total Tagihan
                    </div>

                    <div class="stat-value">
                        <?= $totalTagihan; ?>
                    </div>

                </div>

            </div>


            <!-- BELUM BAYAR -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon icon-yellow">
                        <i class="bi bi-credit-card"></i>
                    </div>

                    <div class="stat-title">
                        Belum Dibayar
                    </div>

                    <div class="stat-value">
                        <?= $totalBelumBayar; ?>
                    </div>

                </div>

            </div>


            <!-- LUNAS -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon icon-green">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div class="stat-title">
                        Tagihan Lunas
                    </div>

                    <div class="stat-value">
                        <?= $totalLunas; ?>
                    </div>

                </div>

            </div>


            <!-- NOMINAL -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon icon-purple">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="stat-title">
                        Total Nominal
                    </div>

                    <div class="stat-value"
                         style="font-size:18px">

                        <?= rupiahTagihan($totalNominal); ?>

                    </div>

                </div>

            </div>

        </div>



        <!-- TABLE -->

        <div class="table-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="table-title mb-0">

                    Daftar Tagihan

                </h5>

                <span class="text-muted small">

                    <?= $totalTagihan; ?> tagihan

                </span>

            </div>


            <div class="table-responsive">

                <table class="table table-hover align-middle">


                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Kode Tagihan</th>

                            <th>Layanan</th>

                            <th>Berat</th>

                            <th>Tarif/Kg</th>

                            <th>Total</th>

                            <th>Jatuh Tempo</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (count($tagihanData) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($tagihanData as $tagihan): ?>

                            <tr>


                                <!-- NO -->

                                <td>
                                    <?= $no++; ?>
                                </td>


                                <!-- KODE -->

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $tagihan['kode_tagihan']
                                        ); ?>
                                    </strong>

                                </td>


                                <!-- LAYANAN -->

                                <td>

                                    <span class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $tagihan['jenis_layanan']
                                        ); ?>

                                    </span>

                                </td>


                                <!-- BERAT -->

                                <td>

                                    <?= number_format(
                                        $tagihan['berat_kg'],
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                    Kg

                                </td>


                                <!-- TARIF -->

                                <td>

                                    <?= rupiahTagihan(
                                        $tagihan['tarif_per_kg']
                                    ); ?>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong class="text-primary">

                                        <?= rupiahTagihan(
                                            $tagihan['total']
                                        ); ?>

                                    </strong>

                                </td>


                                <!-- JATUH TEMPO -->

                                <td>

                                    <?= tanggalTagihan(
                                        $tagihan['jatuh_tempo']
                                    ); ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?= badgeStatus(
                                        $tagihan['status']
                                    ); ?>

                                </td>


                                <!-- AKSI -->

                                <td>

                                    <div class="d-flex flex-column gap-1">


                                        <!-- DETAIL -->

                                        <a
                                            href="detail.php?id=<?= (int) $tagihan['id']; ?>"
                                            class="btn-detail"
                                        >

                                            <i class="bi bi-eye"></i>

                                            Detail

                                        </a>


                                        <!-- BAYAR -->

                                        <?php if (
                                            $tagihan['status'] === 'belum_bayar'
                                            ||
                                            $tagihan['status'] === 'ditolak'
                                        ): ?>

                                            <a
                                                href="../pembayaran/bayar.php?tagihan_id=<?= (int) $tagihan['id']; ?>"
                                                class="btn-bayar"
                                            >

                                                <i class="bi bi-credit-card"></i>

                                                Bayar

                                            </a>

                                        <?php endif; ?>


                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                class="p-0"
                            >

                                <div class="empty-box">

                                    <i class="bi bi-receipt"></i>

                                    <strong>
                                        Belum ada tagihan
                                    </strong>

                                    <div class="mt-2">

                                        Saat ini belum terdapat
                                        tagihan yang terhubung
                                        dengan akun Anda.

                                    </div>

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