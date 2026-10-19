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

mysqli_stmt_close($stmtPelanggan);


if (!$pelanggan) {
    die("Data pelanggan tidak ditemukan.");
}


$pelanggan_id = (int) $pelanggan['id'];


// ==================================================
// AMBIL RIWAYAT PEMBAYARAN
// ==================================================

$sqlPembayaran = "
    SELECT
        pb.id,
        pb.kode_pembayaran,
        pb.jumlah,
        pb.metode,
        pb.bukti_bayar,
        pb.status,
        pb.tanggal_bayar,
        pb.diverifikasi_at,
        pb.catatan,

        t.id AS tagihan_id,
        t.kode_tagihan,
        t.jenis_layanan,
        t.berat_kg,
        t.total AS total_tagihan

    FROM pembayaran pb

    INNER JOIN tagihan t
        ON t.id = pb.tagihan_id

    WHERE pb.pelanggan_id = ?

    ORDER BY pb.tanggal_bayar DESC
";


$stmtPembayaran = mysqli_prepare(
    $conn,
    $sqlPembayaran
);

if (!$stmtPembayaran) {
    die("Query pembayaran gagal: " . mysqli_error($conn));
}


mysqli_stmt_bind_param(
    $stmtPembayaran,
    "i",
    $pelanggan_id
);


mysqli_stmt_execute($stmtPembayaran);


$resultPembayaran =
    mysqli_stmt_get_result(
        $stmtPembayaran
    );


$dataPembayaran = [];


while (
    $row = mysqli_fetch_assoc(
        $resultPembayaran
    )
) {

    $dataPembayaran[] = $row;
}


mysqli_stmt_close(
    $stmtPembayaran
);


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
    if (empty($tanggal)) {
        return "-";
    }

    return date(
        "d-m-Y H:i",
        strtotime($tanggal)
    );
}


// ==================================================
// STATUS BADGE
// ==================================================

function statusBadge($status)
{
    if ($status === 'menunggu') {

        return '
            <span class="badge bg-warning text-dark">
                <i class="bi bi-hourglass-split me-1"></i>
                Menunggu
            </span>
        ';

    } elseif ($status === 'terverifikasi') {

        return '
            <span class="badge bg-success">
                <i class="bi bi-check-circle me-1"></i>
                Terverifikasi
            </span>
        ';

    } elseif ($status === 'ditolak') {

        return '
            <span class="badge bg-danger">
                <i class="bi bi-x-circle me-1"></i>
                Ditolak
            </span>
        ';

    }

    return '
        <span class="badge bg-secondary">
            ' . htmlspecialchars($status) . '
        </span>
    ';
}


// ==================================================
// HITUNG STATISTIK
// ==================================================

$totalPembayaran =
    count($dataPembayaran);

$menunggu = 0;

$terverifikasi = 0;

$ditolak = 0;

$totalDibayar = 0;


foreach (
    $dataPembayaran as $row
) {

    if (
        $row['status'] === 'menunggu'
    ) {

        $menunggu++;
    }


    if (
        $row['status'] === 'terverifikasi'
    ) {

        $terverifikasi++;

        $totalDibayar +=
            (float) $row['jumlah'];
    }


    if (
        $row['status'] === 'ditolak'
    ) {

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

    <title>
        Riwayat Pembayaran - LaundryPro
    </title>


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

            height: 75px;

            background: white;

            padding: 0 30px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .topbar h5 {

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

            border-radius: 50%;

            background: #2563eb;

            color: white;

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
           STAT CARD
        ================================================== */

        .stat-card {

            background: white;

            border-radius: 14px;

            padding: 20px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.05);

            height: 100%;
        }


        .stat-number {

            font-size: 27px;

            font-weight: bold;
        }


        /* ==================================================
           TABLE
        ================================================== */

        .table-card {

            background: white;

            border-radius: 14px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.05);

            overflow: hidden;
        }


        .table-header {

            padding: 20px;

            border-bottom:
                1px solid #eee;
        }


        .table th {

            white-space: nowrap;

            font-size: 13px;
        }


        .table td {

            vertical-align: middle;

            font-size: 14px;
        }


        .payment-code {

            color: #2563eb;

            font-weight: bold;
        }


        /* ==================================================
           CUSTOMER
        ================================================== */

        .customer-box {

            background: white;

            border-radius: 14px;

            padding: 20px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.05);
        }


        /* ==================================================
           PROOF IMAGE
        ================================================== */

        .proof-image {

            width: 100%;

            max-width: 500px;

            max-height: 550px;

            object-fit: contain;

            border-radius: 10px;

            border: 1px solid #ddd;
        }


        /* ==================================================
           MODAL
        ================================================== */

        .modal-content {

            border: none;

            border-radius: 15px;
        }


        .modal-header {

            border-bottom:
                1px solid #eee;
        }


        .payment-detail {

            background: #f8fafc;

            border-radius: 10px;

            padding: 15px;
        }


        .detail-label {

            font-size: 12px;

            color: #6b7280;
        }


        .detail-value {

            font-weight: 600;
        }


        /* ==================================================
           MOBILE
        ================================================== */

        @media (max-width: 768px) {

            .main {

                margin-left: 70px;
            }


            .content {

                padding: 15px;
            }


            .topbar {

                padding: 0 15px;
            }


            .topbar h5 {

                font-size: 17px;
            }


            .topbar small {

                display: none;
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

require_once __DIR__ . "/../sidebar.php";

?>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">


    <!-- ==================================================
         TOPBAR
    ================================================== -->

    <div class="topbar">

        <div>

            <h5 class="mb-0">

                Riwayat Pembayaran

            </h5>


            <small class="text-muted">

                Riwayat pembayaran Anda

            </small>

        </div>


        <div class="user-area">

            <div>

                <strong>

                    <?= htmlspecialchars(
                        $_SESSION['nama']
                        ?? $pelanggan['nama_pelanggan']
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


        <div class="mb-4">

            <h3 class="fw-bold">

                Riwayat Pembayaran

            </h3>


            <p class="text-muted mb-0">

                Lihat seluruh pembayaran
                yang pernah Anda lakukan.

            </p>

        </div>


        <!-- ==================================================
             INFORMASI PELANGGAN
        ================================================== -->

        <div class="customer-box mb-4">

            <div class="row align-items-center">


                <div class="col-md-8">

                    <h5 class="mb-1">

                        <i class="bi bi-person-circle me-2"></i>

                        <?= htmlspecialchars(
                            $pelanggan['nama_pelanggan']
                        ); ?>

                    </h5>


                    <div class="text-muted">

                        <?= htmlspecialchars(
                            $pelanggan['email'] ?? '-'
                        ); ?>

                        &nbsp; • &nbsp;

                        <?= htmlspecialchars(
                            $pelanggan['no_hp'] ?? '-'
                        ); ?>

                    </div>

                </div>


                <div
                    class="col-md-4 text-md-end mt-3 mt-md-0"
                >

                    <a
                        href="../tagihan/index.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-receipt me-1"></i>

                        Lihat Tagihan

                    </a>

                </div>

            </div>

        </div>


        <!-- ==================================================
             STATISTIK
        ================================================== -->

        <div class="row g-3 mb-4">


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="text-muted small">

                        Total Pembayaran

                    </div>


                    <div class="stat-number">

                        <?= $totalPembayaran ?>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="text-muted small">

                        Menunggu

                    </div>


                    <div class="stat-number text-warning">

                        <?= $menunggu ?>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="text-muted small">

                        Terverifikasi

                    </div>


                    <div class="stat-number text-success">

                        <?= $terverifikasi ?>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="text-muted small">

                        Total Dibayar

                    </div>


                    <div class="stat-number">

                        <?= rupiah($totalDibayar) ?>

                    </div>

                </div>

            </div>


        </div>


        <!-- ==================================================
             TABEL PEMBAYARAN
        ================================================== -->

        <div class="table-card">


            <div class="table-header">

                <h5 class="mb-1">

                    Daftar Pembayaran

                </h5>


                <small class="text-muted">

                    Semua pembayaran yang terkait
                    dengan akun Anda.

                </small>

            </div>


            <div class="table-responsive">

                <table class="table table-hover mb-0">


                    <thead class="table-light">

                        <tr>

                            <th>No</th>

                            <th>Kode Pembayaran</th>

                            <th>Kode Tagihan</th>

                            <th>Layanan</th>

                            <th>Jumlah</th>

                            <th>Metode</th>

                            <th>Tanggal</th>

                            <th>Status</th>

                            <th>Bukti</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($dataPembayaran) > 0
                    ): ?>


                        <?php $no = 1; ?>


                        <?php foreach (
                            $dataPembayaran as $row
                        ): ?>


                            <tr>


                                <td>

                                    <?= $no++ ?>

                                </td>


                                <td>

                                    <span class="payment-code">

                                        <?= htmlspecialchars(
                                            $row['kode_pembayaran']
                                        ); ?>

                                    </span>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $row['kode_tagihan']
                                    ); ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $row['jenis_layanan']
                                    ); ?>


                                    <br>


                                    <small class="text-muted">

                                        <?= number_format(
                                            (float) $row['berat_kg'],
                                            2,
                                            ",",
                                            "."
                                        ); ?>

                                        kg

                                    </small>

                                </td>


                                <td>

                                    <strong>

                                        <?= rupiah(
                                            $row['jumlah']
                                        ); ?>

                                    </strong>

                                </td>


                                <td>


                                    <?php if (
                                        $row['metode']
                                        === 'QRIS'
                                    ): ?>


                                        <span class="badge bg-dark">

                                            <i class="bi bi-qr-code me-1"></i>

                                            QRIS

                                        </span>


                                    <?php elseif (
                                        $row['metode']
                                        === 'Transfer Bank'
                                    ): ?>


                                        <span class="badge bg-primary">

                                            <i class="bi bi-bank me-1"></i>

                                            Transfer Bank

                                        </span>


                                    <?php else: ?>


                                        <span class="badge bg-success">

                                            <i class="bi bi-cash me-1"></i>

                                            Tunai

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <td>

                                    <?= tanggalIndonesia(
                                        $row['tanggal_bayar']
                                    ); ?>

                                </td>


                                <td>

                                    <?= statusBadge(
                                        $row['status']
                                    ); ?>

                                </td>


                                <!-- BUKTI -->

                                <td>


                                    <?php if (
                                        !empty(
                                            $row['bukti_bayar']
                                        )
                                    ): ?>


                                        <a
                                            href="../../<?= htmlspecialchars(
                                                $row['bukti_bayar']
                                            ); ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-primary"
                                        >

                                            <i class="bi bi-image me-1"></i>

                                            Lihat

                                        </a>


                                    <?php else: ?>


                                        <span class="text-muted small">

                                            Tidak ada

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- AKSI -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detailModal<?= (int) $row['id']; ?>"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Detail

                                    </button>

                                </td>


                            </tr>


                            <!-- ==================================================
                                 MODAL DETAIL
                            ================================================== -->

                            <div
                                class="modal fade"
                                id="detailModal<?= (int) $row['id']; ?>"
                                tabindex="-1"
                            >

                                <div
                                    class="modal-dialog modal-lg modal-dialog-centered"
                                >

                                    <div class="modal-content">


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-credit-card me-2"></i>

                                                Detail Pembayaran

                                            </h5>


                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <div class="modal-body">


                                            <div class="row g-3">


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Kode Pembayaran

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= htmlspecialchars(
                                                                $row['kode_pembayaran']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Kode Tagihan

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= htmlspecialchars(
                                                                $row['kode_tagihan']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Jumlah Pembayaran

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= rupiah(
                                                                $row['jumlah']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Total Tagihan

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= rupiah(
                                                                $row['total_tagihan']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Metode Pembayaran

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= htmlspecialchars(
                                                                $row['metode']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-md-6">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Tanggal Pembayaran

                                                        </div>


                                                        <div class="detail-value">

                                                            <?= tanggalIndonesia(
                                                                $row['tanggal_bayar']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <div class="col-12">

                                                    <div class="payment-detail">

                                                        <div class="detail-label">

                                                            Status

                                                        </div>


                                                        <div class="mt-1">

                                                            <?= statusBadge(
                                                                $row['status']
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>


                                                <?php if (
                                                    !empty(
                                                        $row['catatan']
                                                    )
                                                ): ?>


                                                    <div class="col-12">

                                                        <div class="alert alert-secondary mb-0">

                                                            <strong>

                                                                Catatan Admin:

                                                            </strong>


                                                            <br>


                                                            <?= nl2br(
                                                                htmlspecialchars(
                                                                    $row['catatan']
                                                                )
                                                            ); ?>

                                                        </div>

                                                    </div>


                                                <?php endif; ?>


                                                <?php if (
                                                    !empty(
                                                        $row['bukti_bayar']
                                                    )
                                                ): ?>


                                                    <div class="col-12">

                                                        <hr>


                                                        <h6 class="fw-bold mb-3">

                                                            <i class="bi bi-image me-2"></i>

                                                            Bukti Pembayaran

                                                        </h6>


                                                        <div class="text-center">


                                                            <a
                                                                href="../../<?= htmlspecialchars(
                                                                    $row['bukti_bayar']
                                                                ); ?>"
                                                                target="_blank"
                                                            >


                                                                <img
                                                                    src="../../<?= htmlspecialchars(
                                                                        $row['bukti_bayar']
                                                                    ); ?>"
                                                                    class="proof-image"
                                                                    alt="Bukti pembayaran"
                                                                >


                                                            </a>

                                                        </div>

                                                    </div>


                                                <?php endif; ?>


                                            </div>

                                        </div>


                                        <div class="modal-footer">


                                            <a
                                                href="../tagihan/detail.php?id=<?= (int) $row['tagihan_id']; ?>"
                                                class="btn btn-primary"
                                            >

                                                <i class="bi bi-receipt me-1"></i>

                                                Lihat Tagihan

                                            </a>


                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal"
                                            >

                                                Tutup

                                            </button>


                                        </div>


                                    </div>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="10"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-credit-card fs-1 text-muted"
                                ></i>


                                <div class="mt-2 text-muted">

                                    Belum ada riwayat pembayaran.

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