<?php

session_start();

require_once __DIR__ . "/../config/koneksi.php";


// ==========================================
// CEK LOGIN
// ==========================================

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

    header("Location: ../login.php");

    exit;
}


// ==========================================
// CEK ROLE
// ==========================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {

    header("Location: ../user/dashboard.php");

    exit;
}


// ==========================================
// DATA ADMIN
// ==========================================

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';


// ==========================================
// STATISTIK DASHBOARD
// ==========================================


// Total pelanggan aktif

$qPelanggan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pelanggan
     WHERE status = 'aktif'"
);

$totalPelanggan = 0;

if ($qPelanggan) {

    $data = mysqli_fetch_assoc($qPelanggan);

    $totalPelanggan = (int) $data['total'];
}


// Total tagihan

$qTagihan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan"
);

$totalTagihan = 0;

if ($qTagihan) {

    $data = mysqli_fetch_assoc($qTagihan);

    $totalTagihan = (int) $data['total'];
}


// Tagihan belum bayar

$qBelumBayar = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan
     WHERE status = 'belum_bayar'"
);

$belumBayar = 0;

if ($qBelumBayar) {

    $data = mysqli_fetch_assoc($qBelumBayar);

    $belumBayar = (int) $data['total'];
}


// Pembayaran menunggu

$qMenunggu = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pembayaran
     WHERE status = 'menunggu'"
);

$menungguVerifikasi = 0;

if ($qMenunggu) {

    $data = mysqli_fetch_assoc($qMenunggu);

    $menungguVerifikasi = (int) $data['total'];
}


// Total pendapatan lunas

$qPendapatan = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM tagihan
     WHERE status = 'lunas'"
);

$totalPendapatan = 0;

if ($qPendapatan) {

    $data = mysqli_fetch_assoc($qPendapatan);

    $totalPendapatan = (float) $data['total'];
}


// ==========================================
// TAGIHAN TERBARU
// ==========================================

$qTerbaru = mysqli_query(
    $conn,
    "SELECT
        t.id,
        t.kode_tagihan,
        t.jenis_layanan,
        t.berat_kg,
        t.total,
        t.jatuh_tempo,
        t.status,
        p.nama_pelanggan
     FROM tagihan t
     LEFT JOIN pelanggan p
        ON t.pelanggan_id = p.id
     ORDER BY t.id DESC
     LIMIT 5"
);


// ==========================================
// FORMAT RUPIAH
// ==========================================

function rupiah($angka)
{
    return 'Rp ' . number_format(
        (float) $angka,
        0,
        ',',
        '.'
    );
}


// ==========================================
// STATUS BADGE
// ==========================================

function statusBadge($status)
{
    switch ($status) {

        case 'lunas':

            return '<span class="badge bg-success">
                        Lunas
                    </span>';

        case 'belum_bayar':

            return '<span class="badge bg-warning text-dark">
                        Belum Bayar
                    </span>';

        case 'menunggu_verifikasi':

            return '<span class="badge bg-info text-dark">
                        Menunggu Verifikasi
                    </span>';

        case 'ditolak':

            return '<span class="badge bg-danger">
                        Ditolak
                    </span>';

        default:

            return '<span class="badge bg-secondary">'
                . htmlspecialchars($status) .
                '</span>';
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

    <title>
        Dashboard Admin - LaundryPro
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICON -->

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

            background: #f4f7fb;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #1f2937;
        }


        /* ==========================================
           SIDEBAR
        ========================================== */

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


        .brand {

            font-size: 23px;

            font-weight: bold;

            padding:
                0
                15px
                30px;
        }


        .brand i {

            margin-right: 8px;
        }


        .menu-title {

            font-size: 12px;

            color: #aebbd6;

            padding:
                10px
                15px;

            text-transform: uppercase;
        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 12px;

            text-decoration: none;

            color: #dbe5f5;

            padding:
                13px
                15px;

            border-radius: 9px;

            margin-bottom: 5px;

            transition: .2s;
        }


        .sidebar a:hover {

            background: #1e4088;

            color: white;
        }


        .sidebar a.active {

            background: #2563eb;

            color: white;
        }


        .sidebar .logout {

            position: absolute;

            left: 15px;

            right: 15px;

            bottom: 20px;

            background: #dc3545;

            color: white;
        }


        .sidebar .logout:hover {

            background: #bb2d3b;
        }


        /* ==========================================
           MAIN
        ========================================== */

        .main {

            margin-left: 250px;

            min-height: 100vh;
        }


        /* ==========================================
           TOPBAR
        ========================================== */

        .topbar {

            height: 75px;

            background: white;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0
                30px;
        }


        .page-title {

            font-size: 23px;

            font-weight: bold;

            margin: 0;
        }


        .admin-info {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .admin-icon {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        /* ==========================================
           CONTENT
        ========================================== */

        .content {

            padding: 30px;
        }


        .welcome {

            margin-bottom: 25px;
        }


        .welcome h4 {

            font-weight: bold;

            margin-bottom: 5px;
        }


        .welcome p {

            color: #6b7280;

            margin: 0;
        }


        /* ==========================================
           STAT CARD
        ========================================== */

        .stat-card {

            background: white;

            border-radius: 14px;

            padding: 22px;

            border:
                1px solid #e5e7eb;

            height: 100%;

            transition: .2s;
        }


        .stat-card:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.06);
        }


        .stat-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            margin-bottom: 15px;
        }


        .blue {

            background: #e8f0ff;

            color: #2563eb;
        }


        .green {

            background: #e7f8ef;

            color: #198754;
        }


        .orange {

            background: #fff3df;

            color: #f59e0b;
        }


        .red {

            background: #ffe8e8;

            color: #dc3545;
        }


        .purple {

            background: #f0e9ff;

            color: #7c3aed;
        }


        .stat-title {

            color: #6b7280;

            font-size: 14px;

            margin-bottom: 5px;
        }


        .stat-value {

            font-size: 26px;

            font-weight: bold;
        }


        /* ==========================================
           SECTION
        ========================================== */

        .section-card {

            background: white;

            border-radius: 14px;

            border:
                1px solid #e5e7eb;

            overflow: hidden;
        }


        .section-header {

            padding:
                20px
                22px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .section-header h5 {

            margin: 0;

            font-weight: bold;
        }


        /* ==========================================
           TABLE
        ========================================== */

        .table {

            margin-bottom: 0;
        }


        .table th {

            font-size: 13px;

            color: #6b7280;

            font-weight: 600;

            background: #f8fafc;

            white-space: nowrap;
        }


        .table td {

            vertical-align: middle;

            font-size: 14px;
        }


        /* ==========================================
           QUICK MENU
        ========================================== */

        .quick-menu {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;

            padding: 20px;
        }


        .quick-menu a {

            text-decoration: none;

            color: #1f2937;

            background: #f8fafc;

            padding: 15px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            gap: 10px;

            transition: .2s;
        }


        .quick-menu a:hover {

            background: #e8f0ff;

            color: #2563eb;
        }


        .quick-menu i {

            font-size: 20px;
        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 992px) {

            .sidebar {

                width: 210px;
            }


            .main {

                margin-left: 210px;
            }

        }


        @media (max-width: 768px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .sidebar .logout {

                position: static;

                margin-top: 15px;
            }


            .main {

                margin-left: 0;
            }


            .topbar {

                padding:
                    0
                    15px;
            }


            .content {

                padding: 15px;
            }


            .quick-menu {

                grid-template-columns: 1fr;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>


<body>


<!-- =====================================================
     SIDEBAR
====================================================== -->

<?php require_once __DIR__ . "/sidebar.php"; ?>


<!-- =====================================================
     MAIN
====================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h1 class="page-title">
            Dashboard
        </h1>


        <div class="admin-info">

            <div class="admin-icon">

                <i class="bi bi-person-fill"></i>

            </div>


            <div>

                <div class="fw-bold">

                    <?= htmlspecialchars($namaAdmin); ?>

                </div>


                <small class="text-muted">

                    Administrator

                </small>

            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h4>

                Selamat datang,
                <?= htmlspecialchars($namaAdmin); ?>
                👋

            </h4>


            <p>

                Kelola pelanggan, tagihan,
                pembayaran, aset tetap,
                dan laporan LaundryPro.

            </p>

        </div>


        <!-- ==========================================
             STATISTIK
        =========================================== -->

        <div class="row g-4 mb-4">


            <!-- PELANGGAN -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-people-fill"></i>

                    </div>


                    <div class="stat-title">

                        Pelanggan Aktif

                    </div>


                    <div class="stat-value">

                        <?= $totalPelanggan; ?>

                    </div>

                </div>

            </div>


            <!-- TAGIHAN -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-receipt-cutoff"></i>

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

                    <div class="stat-icon orange">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <div class="stat-title">

                        Belum Bayar

                    </div>


                    <div class="stat-value">

                        <?= $belumBayar; ?>

                    </div>

                </div>

            </div>


            <!-- VERIFIKASI -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon red">

                        <i class="bi bi-hourglass-split"></i>

                    </div>


                    <div class="stat-title">

                        Menunggu Verifikasi

                    </div>


                    <div class="stat-value">

                        <?= $menungguVerifikasi; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             PENDAPATAN
        =========================================== -->

        <div class="row g-4 mb-4">


            <div class="col-lg-8">

                <div class="stat-card">

                    <div class="d-flex align-items-center gap-3">


                        <div class="stat-icon green mb-0">

                            <i class="bi bi-cash-stack"></i>

                        </div>


                        <div>

                            <div class="stat-title">

                                Total Pendapatan Lunas

                            </div>


                            <div class="stat-value">

                                <?= rupiah($totalPendapatan); ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- AKSI CEPAT -->

            <div class="col-lg-4">

                <div class="section-card h-100">


                    <div class="section-header">

                        <h5>

                            Aksi Cepat

                        </h5>

                    </div>


                    <div class="quick-menu">


                        <a href="pelanggan/tambah.php">

                            <i class="bi bi-person-plus"></i>

                            <span>
                                Tambah Pelanggan
                            </span>

                        </a>


                        <a href="tagihan/tambah.php">

                            <i class="bi bi-file-earmark-plus"></i>

                            <span>
                                Buat Tagihan
                            </span>

                        </a>


                        <a href="aset/tambah.php">

                            <i class="bi bi-building-add"></i>

                            <span>
                                Tambah Aset
                            </span>

                        </a>


                        <a href="pembayaran/index.php">

                            <i class="bi bi-credit-card"></i>

                            <span>
                                Pembayaran
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             TAGIHAN TERBARU
        =========================================== -->

        <div class="section-card">


            <div class="section-header">

                <h5>

                    <i class="bi bi-receipt me-2"></i>

                    Tagihan Terbaru

                </h5>


                <a
                    href="tagihan/index.php"
                    class="btn btn-sm btn-primary"
                >

                    Lihat Semua

                </a>

            </div>


            <div class="table-responsive">

                <table class="table table-hover">


                    <thead>

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                Kode Tagihan
                            </th>

                            <th>
                                Pelanggan
                            </th>

                            <th>
                                Layanan
                            </th>

                            <th>
                                Berat
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Jatuh Tempo
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        $qTerbaru &&
                        mysqli_num_rows($qTerbaru) > 0
                    ): ?>


                        <?php

                        $no = 1;

                        while (
                            $row =
                            mysqli_fetch_assoc($qTerbaru)
                        ):

                        ?>


                            <tr>


                                <td>

                                    <?= $no++; ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row['kode_tagihan']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $row['nama_pelanggan']
                                        ?? '-'
                                    ); ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $row['jenis_layanan']
                                    ); ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)
                                        $row['berat_kg'],
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                    kg

                                </td>


                                <td>

                                    <strong>

                                        <?= rupiah(
                                            $row['total']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= date(
                                        'd-m-Y H:i',
                                        strtotime(
                                            $row['jatuh_tempo']
                                        )
                                    ); ?>

                                </td>


                                <td>

                                    <?= statusBadge(
                                        $row['status']
                                    ); ?>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-4 text-muted"
                            >

                                <i
                                    class="bi bi-inbox fs-3 d-block mb-2"
                                ></i>

                                Belum ada data tagihan.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- BOOTSTRAP JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>