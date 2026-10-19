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
// AMBIL ID TAGIHAN
// ==================================================

$tagihan_id = (int) ($_GET['id'] ?? 0);

if ($tagihan_id <= 0) {
    header("Location: index.php");
    exit;
}


// ==================================================
// AMBIL DATA PELANGGAN
// ==================================================

$sqlPelanggan = "
    SELECT
        id,
        user_id,
        nama_pelanggan,
        no_hp,
        email
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
// AMBIL DATA TAGIHAN
// ==================================================

$sqlTagihan = "
    SELECT
        id,
        kode_tagihan,
        pelanggan_id,
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
    WHERE id = ?
      AND pelanggan_id = ?
    LIMIT 1
";

$stmtTagihan = mysqli_prepare($conn, $sqlTagihan);

if (!$stmtTagihan) {
    die("Query tagihan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtTagihan,
    "ii",
    $tagihan_id,
    $pelanggan_id
);

mysqli_stmt_execute($stmtTagihan);

$resultTagihan = mysqli_stmt_get_result($stmtTagihan);

$tagihan = mysqli_fetch_assoc($resultTagihan);

mysqli_stmt_close($stmtTagihan);


// ==================================================
// CEK TAGIHAN
// ==================================================

if (!$tagihan) {
    die("Tagihan tidak ditemukan atau bukan milik akun Anda.");
}


// ==================================================
// FUNGSI FORMAT
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
// STATUS
// ==================================================

$status = $tagihan['status'];

$statusText = [
    'belum_bayar' => 'Belum Bayar',
    'menunggu_verifikasi' => 'Menunggu Verifikasi',
    'lunas' => 'Lunas',
    'ditolak' => 'Ditolak'
];

$statusClass = [
    'belum_bayar' => 'bg-warning text-dark',
    'menunggu_verifikasi' => 'bg-info text-dark',
    'lunas' => 'bg-success',
    'ditolak' => 'bg-danger'
];

$statusLabel = $statusText[$status] ?? ucfirst($status);

$statusBadge = $statusClass[$status] ?? 'bg-secondary';

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
        Detail Tagihan - LaundryPro
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
            background: white;

            height: 75px;

            padding: 0 30px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid #e5e7eb;
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
           CARD
        ================================================== */

        .card {
            border: none;

            border-radius: 14px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.05);
        }


        /* ==================================================
           INVOICE HEADER
        ================================================== */

        .invoice-header {

            background: #142b5f;

            color: white;

            border-radius:
                14px 14px 0 0;

            padding: 25px;
        }


        .invoice-code {
            font-size: 22px;
            font-weight: bold;
        }


        /* ==================================================
           TOTAL
        ================================================== */

        .total-box {

            background: #f0f7ff;

            border-radius: 12px;

            padding: 20px;
        }


        .total-label {

            color: #64748b;

            font-size: 14px;
        }


        .total-value {

            font-size: 30px;

            font-weight: bold;

            color: #2563eb;
        }


        /* ==================================================
           DETAIL
        ================================================== */

        .detail-row {

            padding: 14px 0;

            border-bottom:
                1px solid #eee;
        }


        .detail-label {

            color: #64748b;

            margin-bottom: 3px;
        }


        .detail-value {

            font-weight: 600;

            color: #142b5f;
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

                Detail Tagihan

            </h5>


            <small class="text-muted">

                Informasi lengkap tagihan laundry

            </small>

        </div>


        <div class="user-area">

            <div>

                <strong>

                    <?= htmlspecialchars(
                        $_SESSION['nama'] ?? $pelanggan['nama_pelanggan']
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
             KEMBALI
        ================================================== -->

        <div class="mb-3">

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>

        </div>


        <!-- ==================================================
             INVOICE
        ================================================== -->

        <div class="card">


            <!-- INVOICE HEADER -->

            <div class="invoice-header">

                <div class="d-flex justify-content-between align-items-center">


                    <div>

                        <div class="invoice-code">

                            <?= htmlspecialchars(
                                $tagihan['kode_tagihan']
                            ); ?>

                        </div>


                        <small>

                            Detail tagihan laundry

                        </small>

                    </div>


                    <div>

                        <span
                            class="badge <?= $statusBadge ?> fs-6"
                        >

                            <?= htmlspecialchars(
                                $statusLabel
                            ); ?>

                        </span>

                    </div>


                </div>

            </div>


            <!-- CARD BODY -->

            <div class="card-body p-4">


                <!-- ==================================================
                     TOTAL
                ================================================== -->

                <div class="total-box mb-4">

                    <div class="total-label">

                        Total Tagihan

                    </div>


                    <div class="total-value">

                        <?= rupiah(
                            $tagihan['total']
                        ); ?>

                    </div>

                </div>


                <div class="row">


                    <!-- ==================================================
                         INFORMASI TAGIHAN
                    ================================================== -->

                    <div class="col-md-6">

                        <h5 class="mb-3">

                            <i class="bi bi-receipt me-2"></i>

                            Informasi Tagihan

                        </h5>


                        <div class="detail-row">

                            <div class="detail-label">

                                Kode Tagihan

                            </div>


                            <div class="detail-value">

                                <?= htmlspecialchars(
                                    $tagihan['kode_tagihan']
                                ); ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Jenis Layanan

                            </div>


                            <div class="detail-value">

                                <?= htmlspecialchars(
                                    $tagihan['jenis_layanan']
                                ); ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Berat Laundry

                            </div>


                            <div class="detail-value">

                                <?= number_format(
                                    (float) $tagihan['berat_kg'],
                                    2,
                                    ",",
                                    "."
                                ); ?>

                                kg

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Tarif per Kg

                            </div>


                            <div class="detail-value">

                                <?= rupiah(
                                    $tagihan['tarif_per_kg']
                                ); ?>

                            </div>

                        </div>

                    </div>


                    <!-- ==================================================
                         WAKTU LAUNDRY
                    ================================================== -->

                    <div class="col-md-6">

                        <h5 class="mb-3">

                            <i class="bi bi-calendar-event me-2"></i>

                            Waktu Laundry

                        </h5>


                        <div class="detail-row">

                            <div class="detail-label">

                                Mulai Laundry

                            </div>


                            <div class="detail-value">

                                <?= tanggalIndonesia(
                                    $tagihan['periode_mulai']
                                ); ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Estimasi Selesai

                            </div>


                            <div class="detail-value">

                                <?= tanggalIndonesia(
                                    $tagihan['periode_selesai']
                                ); ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Jatuh Tempo

                            </div>


                            <div class="detail-value">

                                <?= tanggalIndonesia(
                                    $tagihan['jatuh_tempo']
                                ); ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">

                                Dibuat

                            </div>


                            <div class="detail-value">

                                <?= tanggalIndonesia(
                                    $tagihan['created_at']
                                ); ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     DATA PELANGGAN
                ================================================== -->

                <hr class="my-4">


                <h5 class="mb-3">

                    <i class="bi bi-person me-2"></i>

                    Data Pelanggan

                </h5>


                <div class="row">


                    <div class="col-md-4">

                        <div class="detail-row">

                            <div class="detail-label">

                                Nama

                            </div>


                            <div class="detail-value">

                                <?= htmlspecialchars(
                                    $pelanggan['nama_pelanggan']
                                ); ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-row">

                            <div class="detail-label">

                                Email

                            </div>


                            <div class="detail-value">

                                <?= htmlspecialchars(
                                    $pelanggan['email'] ?? '-'
                                ); ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-row">

                            <div class="detail-label">

                                No. HP

                            </div>


                            <div class="detail-value">

                                <?= htmlspecialchars(
                                    $pelanggan['no_hp'] ?? '-'
                                ); ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     TOMBOL PEMBAYARAN
                ================================================== -->

                <div class="mt-4 d-flex gap-2">


                    <?php if (
                        $status === 'belum_bayar'
                        || $status === 'ditolak'
                    ): ?>


                        <a
                            href="../pembayaran/bayar.php?tagihan_id=<?= (int) $tagihan['id']; ?>"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-credit-card me-1"></i>

                            Bayar Sekarang

                        </a>


                    <?php elseif (
                        $status === 'menunggu_verifikasi'
                    ): ?>


                        <button
                            class="btn btn-info text-dark"
                            disabled
                        >

                            <i class="bi bi-hourglass-split me-1"></i>

                            Menunggu Verifikasi

                        </button>


                    <?php elseif (
                        $status === 'lunas'
                    ): ?>


                        <button
                            class="btn btn-success"
                            disabled
                        >

                            <i class="bi bi-check-circle me-1"></i>

                            Pembayaran Lunas

                        </button>


                    <?php endif; ?>


                    <a
                        href="index.php"
                        class="btn btn-outline-secondary"
                    >

                        <i class="bi bi-arrow-left me-1"></i>

                        Kembali ke Tagihan

                    </a>


                </div>


            </div>

        </div>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>