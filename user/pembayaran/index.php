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
// CEK ROLE ADMIN
// ==================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// ==================================================
// AMBIL DATA PEMBAYARAN
// ==================================================

$sql = "
    SELECT
        pb.id,
        pb.kode_pembayaran,
        pb.tagihan_id,
        pb.pelanggan_id,
        pb.jumlah,
        pb.metode,
        pb.bukti_bayar,
        pb.status,
        pb.tanggal_bayar,

        t.kode_tagihan,
        t.jenis_layanan,
        t.total AS total_tagihan,

        p.nama_pelanggan,
        p.no_hp,
        p.email

    FROM pembayaran pb

    INNER JOIN tagihan t
        ON t.id = pb.tagihan_id

    INNER JOIN pelanggan p
        ON p.id = pb.pelanggan_id

    ORDER BY pb.tanggal_bayar DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query pembayaran gagal: " . mysqli_error($conn));
}

// ==================================================
// FORMAT RUPIAH
// ==================================================

function rupiah($angka)
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

function tanggalIndonesia($tanggal)
{
    if (!$tanggal) {
        return "-";
    }

    return date(
        "d-m-Y H:i",
        strtotime($tanggal)
    );
}

// ==================================================
// HITUNG JUMLAH STATUS
// ==================================================

$totalPembayaran = 0;
$menunggu = 0;
$terverifikasi = 0;
$ditolak = 0;

$dataPembayaran = [];

while ($row = mysqli_fetch_assoc($result)) {

    $dataPembayaran[] = $row;

    $totalPembayaran++;

    if ($row['status'] === 'menunggu') {
        $menunggu++;
    }

    if ($row['status'] === 'terverifikasi') {
        $terverifikasi++;
    }

    if ($row['status'] === 'ditolak') {
        $ditolak++;
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

    <title>Pembayaran - LaundryPro</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #142b5f;
            color: white;
            padding: 25px 15px;
        }

        .sidebar .brand {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: #dbe5ff;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 18px 30px;
            border-bottom: 1px solid #e5e7eb;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            height: 100%;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .table-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .table-card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .table th {
            white-space: nowrap;
            font-size: 13px;
            color: #475569;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .payment-code {
            font-weight: bold;
            color: #2563eb;
        }

        .customer-name {
            font-weight: 600;
        }

        .customer-contact {
            font-size: 12px;
            color: #64748b;
        }

        @media (max-width: 768px) {

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .content {
                padding: 15px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>

<!-- ================================================== -->
<!-- SIDEBAR -->
<!-- ================================================== -->

<div class="sidebar">

    <div class="brand">

        <i class="bi bi-droplet-fill"></i>

        LaundryPro

    </div>

    <a href="../dashboard.php">

        <i class="bi bi-speedometer2 me-2"></i>

        Dashboard

    </a>

    <a href="../pelanggan/index.php">

        <i class="bi bi-people me-2"></i>

        Pelanggan

    </a>

    <a href="../tagihan/index.php">

        <i class="bi bi-receipt me-2"></i>

        Tagihan

    </a>

    <a href="index.php" class="active">

        <i class="bi bi-credit-card me-2"></i>

        Pembayaran

    </a>

    <a href="../laporan/index.php">

        <i class="bi bi-bar-chart me-2"></i>

        Laporan

    </a>

    <a href="../../logout.php">

        <i class="bi bi-box-arrow-right me-2"></i>

        Logout

    </a>

</div>


<!-- ================================================== -->
<!-- MAIN -->
<!-- ================================================== -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h5 class="mb-0">
                Pembayaran
            </h5>

            <small class="text-muted">
                Kelola dan verifikasi pembayaran pelanggan
            </small>

        </div>

        <div>

            <i class="bi bi-person-circle me-2"></i>

            <?= htmlspecialchars($_SESSION['nama']) ?>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">

        <!-- ================================================== -->
        <!-- JUDUL -->
        <!-- ================================================== -->

        <div class="mb-4">

            <h3 class="fw-bold">
                Daftar Pembayaran
            </h3>

            <p class="text-muted mb-0">
                Periksa pembayaran yang dilakukan pelanggan.
            </p>

        </div>


        <!-- ================================================== -->
        <!-- STATISTIK -->
        <!-- ================================================== -->

        <div class="row g-3 mb-4">

            <!-- TOTAL -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="text-muted small">
                                Total Pembayaran
                            </div>

                            <h3 class="fw-bold mb-0">
                                <?= $totalPembayaran ?>
                            </h3>

                        </div>

                        <div class="stat-icon bg-primary-subtle text-primary">

                            <i class="bi bi-credit-card"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- MENUNGGU -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="text-muted small">
                                Menunggu Verifikasi
                            </div>

                            <h3 class="fw-bold mb-0">
                                <?= $menunggu ?>
                            </h3>

                        </div>

                        <div class="stat-icon bg-warning-subtle text-warning">

                            <i class="bi bi-hourglass-split"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TERVERIFIKASI -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="text-muted small">
                                Terverifikasi
                            </div>

                            <h3 class="fw-bold mb-0">
                                <?= $terverifikasi ?>
                            </h3>

                        </div>

                        <div class="stat-icon bg-success-subtle text-success">

                            <i class="bi bi-check-circle"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- DITOLAK -->

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="text-muted small">
                                Ditolak
                            </div>

                            <h3 class="fw-bold mb-0">
                                <?= $ditolak ?>
                            </h3>

                        </div>

                        <div class="stat-icon bg-danger-subtle text-danger">

                            <i class="bi bi-x-circle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================== -->
        <!-- TABEL PEMBAYARAN -->
        <!-- ================================================== -->

        <div class="table-card">

            <div class="table-card-header">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-1">
                            Riwayat Pembayaran
                        </h5>

                        <small class="text-muted">
                            Semua transaksi pembayaran pelanggan
                        </small>

                    </div>

                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>No</th>

                            <th>Kode Pembayaran</th>

                            <th>Pelanggan</th>

                            <th>Kode Tagihan</th>

                            <th>Jumlah</th>

                            <th>Metode</th>

                            <th>Tanggal</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($dataPembayaran) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($dataPembayaran as $row): ?>

                            <tr>

                                <!-- NO -->

                                <td>
                                    <?= $no++ ?>
                                </td>


                                <!-- KODE PEMBAYARAN -->

                                <td>

                                    <div class="payment-code">

                                        <?= htmlspecialchars(
                                            $row['kode_pembayaran']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- PELANGGAN -->

                                <td>

                                    <div class="customer-name">

                                        <?= htmlspecialchars(
                                            $row['nama_pelanggan']
                                        ) ?>

                                    </div>

                                    <div class="customer-contact">

                                        <?= htmlspecialchars(
                                            $row['email'] ?? '-'
                                        ) ?>

                                    </div>

                                </td>


                                <!-- KODE TAGIHAN -->

                                <td>

                                    <span class="badge text-bg-light">

                                        <?= htmlspecialchars(
                                            $row['kode_tagihan']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- JUMLAH -->

                                <td>

                                    <strong>

                                        <?= rupiah(
                                            $row['jumlah']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- METODE -->

                                <td>

                                    <?php if ($row['metode'] === 'Transfer Bank'): ?>

                                        <span class="badge bg-primary">

                                            <i class="bi bi-bank me-1"></i>

                                            Transfer Bank

                                        </span>

                                    <?php elseif ($row['metode'] === 'QRIS'): ?>

                                        <span class="badge bg-dark">

                                            <i class="bi bi-qr-code me-1"></i>

                                            QRIS

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-success">

                                            <i class="bi bi-cash me-1"></i>

                                            Tunai

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- TANGGAL -->

                                <td>

                                    <?= tanggalIndonesia(
                                        $row['tanggal_bayar']
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($row['status'] === 'menunggu'): ?>

                                        <span class="badge bg-warning text-dark">

                                            <i class="bi bi-hourglass-split me-1"></i>

                                            Menunggu

                                        </span>

                                    <?php elseif ($row['status'] === 'terverifikasi'): ?>

                                        <span class="badge bg-success">

                                            <i class="bi bi-check-circle me-1"></i>

                                            Terverifikasi

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">

                                            <i class="bi bi-x-circle me-1"></i>

                                            Ditolak

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- AKSI -->

                                <td>

                                    <a
                                        href="verifikasi.php?id=<?= $row['id'] ?>"
                                        class="btn btn-sm btn-primary"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Detail

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-credit-card fs-1 text-muted"
                                ></i>

                                <div class="mt-2 text-muted">

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