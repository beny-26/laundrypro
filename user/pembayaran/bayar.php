<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . "/../../config/koneksi.php";


// ======================================================
// CEK LOGIN
// ======================================================

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../login.php");
    exit;
}


// ======================================================
// CEK ROLE
// ======================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../../index.php");
    exit;
}


// ======================================================
// AMBIL USER
// ======================================================

$user_id = (int) $_SESSION['id'];


// ======================================================
// AMBIL ID TAGIHAN
// ======================================================

$tagihan_id = (int) ($_GET['tagihan_id'] ?? 0);

if ($tagihan_id <= 0) {
    header("Location: ../tagihan/index.php");
    exit;
}


// ======================================================
// CARI DATA PELANGGAN
// ======================================================

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


// ======================================================
// AMBIL DATA TAGIHAN
// ======================================================

$sqlTagihan = "
    SELECT
        id,
        kode_tagihan,
        pelanggan_id,
        jenis_layanan,
        berat_kg,
        tarif_per_kg,
        total,
        jatuh_tempo,
        status
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


if (!$tagihan) {
    die("Tagihan tidak ditemukan atau bukan milik akun Anda.");
}


// ======================================================
// CEK STATUS TAGIHAN
// ======================================================

if (
    $tagihan['status'] !== 'belum_bayar' &&
    $tagihan['status'] !== 'ditolak'
) {
    header(
        "Location: ../tagihan/detail.php?id=" . $tagihan_id
    );
    exit;
}


// ======================================================
// PROSES PEMBAYARAN
// ======================================================

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $metode = trim($_POST['metode'] ?? '');

    // Jumlah selalu diambil dari database
    $jumlah = (float) $tagihan['total'];


    // ==================================================
    // VALIDASI METODE
    // ==================================================

    $metodeValid = [
        'Transfer Bank',
        'QRIS',
        'Tunai'
    ];

    if (!in_array($metode, $metodeValid, true)) {

        $error = "Silakan pilih metode pembayaran.";

    } else {

        $bukti_bayar = null;


        // ==================================================
        // UPLOAD BUKTI BAYAR
        // ==================================================

        if (
            isset($_FILES['bukti_bayar']) &&
            $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['bukti_bayar']['error']
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "Bukti pembayaran gagal diupload.";

            } else {

                $fileName =
                    $_FILES['bukti_bayar']['name'];

                $fileTmp =
                    $_FILES['bukti_bayar']['tmp_name'];

                $fileSize =
                    $_FILES['bukti_bayar']['size'];

                $extension = strtolower(
                    pathinfo(
                        $fileName,
                        PATHINFO_EXTENSION
                    )
                );


                $allowedExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


                if (
                    !in_array(
                        $extension,
                        $allowedExtensions,
                        true
                    )
                ) {

                    $error =
                        "Format bukti pembayaran harus JPG, JPEG, PNG, atau WEBP.";

                } elseif (
                    $fileSize > 5 * 1024 * 1024
                ) {

                    $error =
                        "Ukuran bukti pembayaran maksimal 5 MB.";

                } else {

                    $uploadDir =
                        __DIR__ .
                        "/../../uploads/bukti_bayar/";


                    if (!is_dir($uploadDir)) {

                        mkdir(
                            $uploadDir,
                            0755,
                            true
                        );
                    }


                    $newFileName =
                        "BUKTI-" .
                        $tagihan_id .
                        "-" .
                        time() .
                        "-" .
                        bin2hex(
                            random_bytes(4)
                        ) .
                        "." .
                        $extension;


                    $uploadPath =
                        $uploadDir .
                        $newFileName;


                    if (
                        move_uploaded_file(
                            $fileTmp,
                            $uploadPath
                        )
                    ) {

                        $bukti_bayar =
                            "uploads/bukti_bayar/" .
                            $newFileName;

                    } else {

                        $error =
                            "Bukti pembayaran gagal disimpan.";
                    }
                }
            }
        }


        // ==================================================
        // BUKTI WAJIB TRANSFER / QRIS
        // ==================================================

        if (
            $error === "" &&
            (
                $metode === 'Transfer Bank' ||
                $metode === 'QRIS'
            ) &&
            $bukti_bayar === null
        ) {

            $error =
                "Bukti pembayaran wajib diupload untuk metode Transfer Bank atau QRIS.";
        }


        // ==================================================
        // SIMPAN PEMBAYARAN
        // ==================================================

        if ($error === "") {

            $kodePembayaran =
                "PAY-" .
                date("YmdHis") .
                "-" .
                random_int(100, 999);


            $statusPembayaran = "menunggu";


            $sqlBayar = "
                INSERT INTO pembayaran
                (
                    kode_pembayaran,
                    tagihan_id,
                    pelanggan_id,
                    jumlah,
                    metode,
                    bukti_bayar,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";


            $stmtBayar =
                mysqli_prepare(
                    $conn,
                    $sqlBayar
                );


            if (!$stmtBayar) {

                $error =
                    "Query pembayaran gagal: " .
                    mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $stmtBayar,
                    "siidsss",
                    $kodePembayaran,
                    $tagihan_id,
                    $pelanggan_id,
                    $jumlah,
                    $metode,
                    $bukti_bayar,
                    $statusPembayaran
                );


                if (
                    mysqli_stmt_execute(
                        $stmtBayar
                    )
                ) {

                    // ==========================================
                    // UPDATE STATUS TAGIHAN
                    // ==========================================

                    $statusTagihan =
                        "menunggu_verifikasi";


                    $sqlUpdate = "
                        UPDATE tagihan
                        SET status = ?
                        WHERE id = ?
                          AND pelanggan_id = ?
                    ";


                    $stmtUpdate =
                        mysqli_prepare(
                            $conn,
                            $sqlUpdate
                        );


                    if ($stmtUpdate) {

                        mysqli_stmt_bind_param(
                            $stmtUpdate,
                            "sii",
                            $statusTagihan,
                            $tagihan_id,
                            $pelanggan_id
                        );


                        mysqli_stmt_execute(
                            $stmtUpdate
                        );


                        mysqli_stmt_close(
                            $stmtUpdate
                        );
                    }


                    mysqli_stmt_close(
                        $stmtBayar
                    );


                    header(
                        "Location: riwayat.php?success=1"
                    );

                    exit;

                } else {

                    $error =
                        "Pembayaran gagal disimpan: " .
                        mysqli_error($conn);

                    mysqli_stmt_close(
                        $stmtBayar
                    );
                }
            }
        }
    }
}


// ======================================================
// FORMAT RUPIAH
// ======================================================

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
        Bayar Tagihan - LaundryPro
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

            align-items: center;

            justify-content: space-between;
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
           CARD
        ================================================== */

        .card {

            border: none;

            border-radius: 14px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.05);
        }


        /* ==================================================
           PAYMENT TOTAL
        ================================================== */

        .payment-total {

            background: #142b5f;

            color: white;

            border-radius: 12px;

            padding: 25px;
        }


        .payment-total .label {

            font-size: 14px;

            opacity: .8;
        }


        .payment-total .amount {

            font-size: 32px;

            font-weight: bold;
        }


        /* ==================================================
           METHOD
        ================================================== */

        .method-card {

            border: 2px solid #e5e7eb;

            border-radius: 12px;

            padding: 15px;

            cursor: pointer;

            transition: .2s;
        }


        .method-card:hover {

            border-color: #2563eb;

            background: #f8fbff;
        }


        .method-card input {

            margin-right: 10px;
        }


        /* ==================================================
           INVOICE INFO
        ================================================== */

        .invoice-info {

            background: #f8fafc;

            border-radius: 10px;

            padding: 18px;
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


            .topbar small {

                display: none;
            }


            .topbar h5 {

                font-size: 17px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>


<body>


<?php

// ======================================================
// SIDEBAR PELANGGAN
// ======================================================

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

                Pembayaran Tagihan

            </h5>


            <small class="text-muted">

                Lakukan pembayaran tagihan laundry Anda

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


        <!-- KEMBALI -->

        <div class="mb-3">

            <a
                href="../tagihan/detail.php?id=<?= (int) $tagihan['id']; ?>"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>

        </div>


        <div class="row g-4">


            <!-- ==================================================
                 INFORMASI TAGIHAN
            ================================================== -->

            <div class="col-lg-5">

                <div class="card">

                    <div class="card-body p-4">

                        <h5 class="mb-4">

                            <i class="bi bi-receipt me-2"></i>

                            Detail Tagihan

                        </h5>


                        <div class="invoice-info">


                            <div class="mb-3">

                                <small class="text-muted">

                                    Kode Tagihan

                                </small>


                                <div class="fw-bold">

                                    <?= htmlspecialchars(
                                        $tagihan['kode_tagihan']
                                    ); ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <small class="text-muted">

                                    Layanan

                                </small>


                                <div class="fw-bold">

                                    <?= htmlspecialchars(
                                        $tagihan['jenis_layanan']
                                    ); ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <small class="text-muted">

                                    Berat

                                </small>


                                <div class="fw-bold">

                                    <?= number_format(
                                        (float) $tagihan['berat_kg'],
                                        2,
                                        ",",
                                        "."
                                    ); ?>

                                    kg

                                </div>

                            </div>


                            <div class="mb-3">

                                <small class="text-muted">

                                    Tarif

                                </small>


                                <div class="fw-bold">

                                    <?= rupiah(
                                        $tagihan['tarif_per_kg']
                                    ); ?>

                                    / kg

                                </div>

                            </div>


                            <div>

                                <small class="text-muted">

                                    Jatuh Tempo

                                </small>


                                <div class="fw-bold">

                                    <?= tanggalIndonesia(
                                        $tagihan['jatuh_tempo']
                                    ); ?>

                                </div>

                            </div>

                        </div>


                        <div class="payment-total mt-4">

                            <div class="label">

                                Total yang harus dibayar

                            </div>


                            <div class="amount">

                                <?= rupiah(
                                    $tagihan['total']
                                ); ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 FORM PEMBAYARAN
            ================================================== -->

            <div class="col-lg-7">

                <div class="card">

                    <div class="card-body p-4">


                        <h5 class="mb-4">

                            <i class="bi bi-credit-card me-2"></i>

                            Form Pembayaran

                        </h5>


                        <?php if ($error !== ""): ?>

                            <div class="alert alert-danger">

                                <i class="bi bi-exclamation-triangle me-2"></i>

                                <?= htmlspecialchars($error); ?>

                            </div>

                        <?php endif; ?>


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                        >


                            <!-- JUMLAH -->

                            <div class="mb-4">

                                <label class="form-label fw-bold">

                                    Jumlah Pembayaran

                                </label>


                                <input
                                    type="text"
                                    class="form-control form-control-lg"
                                    value="<?= rupiah($tagihan['total']); ?>"
                                    readonly
                                >


                                <div class="form-text">

                                    Jumlah pembayaran mengikuti total tagihan.

                                </div>

                            </div>


                            <!-- METODE -->

                            <div class="mb-4">

                                <label class="form-label fw-bold">

                                    Metode Pembayaran

                                </label>


                                <label class="method-card d-block mb-2">

                                    <input
                                        type="radio"
                                        name="metode"
                                        value="Transfer Bank"
                                        required
                                    >

                                    <i class="bi bi-bank me-2"></i>

                                    Transfer Bank

                                </label>


                                <label class="method-card d-block mb-2">

                                    <input
                                        type="radio"
                                        name="metode"
                                        value="QRIS"
                                    >

                                    <i class="bi bi-qr-code me-2"></i>

                                    QRIS

                                </label>


                                <label class="method-card d-block">

                                    <input
                                        type="radio"
                                        name="metode"
                                        value="Tunai"
                                    >

                                    <i class="bi bi-cash-stack me-2"></i>

                                    Tunai

                                </label>

                            </div>


                            <!-- BUKTI PEMBAYARAN -->

                            <div class="mb-4">

                                <label
                                    for="bukti_bayar"
                                    class="form-label fw-bold"
                                >

                                    Bukti Pembayaran

                                </label>


                                <input
                                    type="file"
                                    name="bukti_bayar"
                                    id="bukti_bayar"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >


                                <div class="form-text">

                                    Untuk Transfer Bank dan QRIS,
                                    bukti pembayaran wajib diupload.

                                    Maksimal 5 MB.

                                </div>

                            </div>


                            <!-- PERINGATAN -->

                            <div class="alert alert-warning">

                                <i class="bi bi-info-circle me-2"></i>

                                Setelah pembayaran dikirim,
                                status tagihan akan berubah menjadi
                                <strong>Menunggu Verifikasi</strong>.

                                Admin akan memeriksa pembayaran Anda.

                            </div>


                            <!-- BUTTON -->

                            <div class="d-flex gap-2">

                                <a
                                    href="../tagihan/detail.php?id=<?= (int) $tagihan['id']; ?>"
                                    class="btn btn-outline-secondary"
                                >

                                    Batal

                                </a>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >

                                    <i class="bi bi-send me-1"></i>

                                    Kirim Pembayaran

                                </button>

                            </div>


                        </form>


                    </div>

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