<?php

session_start();

require_once __DIR__ . "/../../config/koneksi.php";


// =====================================================
// CEK LOGIN
// =====================================================

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../login.php");
    exit;
}


// =====================================================
// CEK ROLE ADMIN
// =====================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}


// =====================================================
// AMBIL ID PEMBAYARAN
// =====================================================

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=ID pembayaran tidak valid");
    exit;
}


// =====================================================
// AMBIL DATA PEMBAYARAN
// =====================================================

$sql = "
    SELECT
        pembayaran.id,
        pembayaran.kode_pembayaran,
        pembayaran.tagihan_id,
        pembayaran.pelanggan_id,
        pembayaran.jumlah,
        pembayaran.metode,
        pembayaran.bukti_bayar,
        pembayaran.status,
        pembayaran.tanggal_bayar,
        pembayaran.diverifikasi_oleh,
        pembayaran.diverifikasi_at,
        pembayaran.catatan,

        tagihan.kode_tagihan,
        tagihan.total AS total_tagihan,
        tagihan.jenis_layanan,
        tagihan.berat_kg,
        tagihan.status AS status_tagihan,

        pelanggan.nama_pelanggan,
        pelanggan.email,
        pelanggan.no_hp

    FROM pembayaran

    INNER JOIN tagihan
        ON pembayaran.tagihan_id = tagihan.id

    INNER JOIN pelanggan
        ON pembayaran.pelanggan_id = pelanggan.id

    WHERE pembayaran.id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$data) {
    header("Location: index.php?error=Data pembayaran tidak ditemukan");
    exit;
}


// =====================================================
// PROSES VERIFIKASI
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    // -------------------------------------------------
    // HANYA PEMBAYARAN MENUNGGU YANG DAPAT DIPROSES
    // -------------------------------------------------

    if ($data['status'] !== 'menunggu') {

        header(
            "Location: verifikasi.php?id=" .
            $id .
            "&error=" .
            urlencode("Pembayaran sudah diproses sebelumnya.")
        );

        exit;
    }


    // -------------------------------------------------
    // VALIDASI AKSI
    // -------------------------------------------------

    if (!in_array($aksi, ['terima', 'tolak'], true)) {

        header(
            "Location: verifikasi.php?id=" .
            $id .
            "&error=" .
            urlencode("Aksi verifikasi tidak valid.")
        );

        exit;
    }


    // =================================================
    // TERIMA PEMBAYARAN
    // =================================================

    if ($aksi === 'terima') {

        // Jumlah pembayaran harus mencukupi
        if ((float) $data['jumlah'] < (float) $data['total_tagihan']) {

            header(
                "Location: verifikasi.php?id=" .
                $id .
                "&error=" .
                urlencode("Jumlah pembayaran belum mencukupi total tagihan.")
            );

            exit;
        }


        // ---------------------------------------------
        // CEK STATUS TAGIHAN
        // ---------------------------------------------

        if (
            $data['status_tagihan'] !== 'belum_bayar' &&
            $data['status_tagihan'] !== 'menunggu_verifikasi'
        ) {

            header(
                "Location: verifikasi.php?id=" .
                $id .
                "&error=" .
                urlencode("Status tagihan tidak dapat diverifikasi.")
            );

            exit;
        }


        mysqli_begin_transaction($conn);

        try {

            $adminId = (int) $_SESSION['id'];
            $tagihanId = (int) $data['tagihan_id'];


            // -----------------------------------------
            // UPDATE PEMBAYARAN
            // -----------------------------------------

            $sqlBayar = "
                UPDATE pembayaran

                SET
                    status = 'terverifikasi',
                    diverifikasi_oleh = ?,
                    diverifikasi_at = NOW(),
                    catatan = ?

                WHERE id = ?
                AND status = 'menunggu'
            ";

            $stmtBayar = mysqli_prepare($conn, $sqlBayar);

            if (!$stmtBayar) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmtBayar,
                "isi",
                $adminId,
                $catatan,
                $id
            );

            if (!mysqli_stmt_execute($stmtBayar)) {
                throw new Exception(mysqli_stmt_error($stmtBayar));
            }

            // Pastikan benar-benar ada baris yang diperbarui
            if (mysqli_stmt_affected_rows($stmtBayar) !== 1) {
                throw new Exception(
                    "Pembayaran gagal diperbarui atau sudah diproses."
                );
            }

            mysqli_stmt_close($stmtBayar);


            // -----------------------------------------
            // UPDATE TAGIHAN
            // -----------------------------------------

            $sqlTagihan = "
                UPDATE tagihan

                SET status = 'lunas'

                WHERE id = ?
                AND status IN ('belum_bayar', 'menunggu_verifikasi')
            ";

            $stmtTagihan = mysqli_prepare($conn, $sqlTagihan);

            if (!$stmtTagihan) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmtTagihan,
                "i",
                $tagihanId
            );

            if (!mysqli_stmt_execute($stmtTagihan)) {
                throw new Exception(mysqli_stmt_error($stmtTagihan));
            }

            if (mysqli_stmt_affected_rows($stmtTagihan) !== 1) {
                throw new Exception(
                    "Status tagihan gagal diperbarui."
                );
            }

            mysqli_stmt_close($stmtTagihan);


            // -----------------------------------------
            // COMMIT
            // -----------------------------------------

            mysqli_commit($conn);


            header(
                "Location: index.php?success=" .
                urlencode("Pembayaran berhasil diverifikasi dan tagihan menjadi lunas.")
            );

            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            header(
                "Location: verifikasi.php?id=" .
                $id .
                "&error=" .
                urlencode($e->getMessage())
            );

            exit;
        }
    }


    // =================================================
    // TOLAK PEMBAYARAN
    // =================================================

    if ($aksi === 'tolak') {

        // Catatan wajib jika pembayaran ditolak
        if ($catatan === '') {

            header(
                "Location: verifikasi.php?id=" .
                $id .
                "&error=" .
                urlencode("Catatan penolakan wajib diisi.")
            );

            exit;
        }


        mysqli_begin_transaction($conn);

        try {

            $adminId = (int) $_SESSION['id'];
            $tagihanId = (int) $data['tagihan_id'];


            // -----------------------------------------
            // UPDATE PEMBAYARAN
            // -----------------------------------------

            $sqlBayar = "
                UPDATE pembayaran

                SET
                    status = 'ditolak',
                    diverifikasi_oleh = ?,
                    diverifikasi_at = NOW(),
                    catatan = ?

                WHERE id = ?
                AND status = 'menunggu'
            ";

            $stmtBayar = mysqli_prepare($conn, $sqlBayar);

            if (!$stmtBayar) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmtBayar,
                "isi",
                $adminId,
                $catatan,
                $id
            );

            if (!mysqli_stmt_execute($stmtBayar)) {
                throw new Exception(mysqli_stmt_error($stmtBayar));
            }

            if (mysqli_stmt_affected_rows($stmtBayar) !== 1) {
                throw new Exception(
                    "Pembayaran gagal diperbarui atau sudah diproses."
                );
            }

            mysqli_stmt_close($stmtBayar);


            // -----------------------------------------
            // UPDATE TAGIHAN
            // -----------------------------------------

            $sqlTagihan = "
                UPDATE tagihan

                SET status = 'ditolak'

                WHERE id = ?
                AND status IN ('belum_bayar', 'menunggu_verifikasi')
            ";

            $stmtTagihan = mysqli_prepare($conn, $sqlTagihan);

            if (!$stmtTagihan) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmtTagihan,
                "i",
                $tagihanId
            );

            if (!mysqli_stmt_execute($stmtTagihan)) {
                throw new Exception(mysqli_stmt_error($stmtTagihan));
            }

            if (mysqli_stmt_affected_rows($stmtTagihan) !== 1) {
                throw new Exception(
                    "Status tagihan gagal diperbarui."
                );
            }

            mysqli_stmt_close($stmtTagihan);


            // -----------------------------------------
            // COMMIT
            // -----------------------------------------

            mysqli_commit($conn);


            header(
                "Location: index.php?success=" .
                urlencode("Pembayaran berhasil ditolak.")
            );

            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            header(
                "Location: verifikasi.php?id=" .
                $id .
                "&error=" .
                urlencode($e->getMessage())
            );

            exit;
        }
    }
}


// =====================================================
// FORMAT DATA
// =====================================================

$jumlah = (float) $data['jumlah'];
$totalTagihan = (float) $data['total_tagihan'];

$buktiBayar = trim(
    (string) $data['bukti_bayar']
);


// =====================================================
// HEADER SIDEBAR
// =====================================================

require_once __DIR__ . "/../sidebar.php";

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verifikasi Pembayaran - LaundryPro</title>

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
            margin: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .page-title {
            font-size: 25px;
            font-weight: 700;
            color: #142b5f;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        .card-box {
            background: #fff;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
            padding: 25px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .proof-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .proof-image {
            width: 100%;
            max-width: 500px;
            max-height: 600px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .no-proof {
            padding: 60px 20px;
            color: #9ca3af;
        }

        .amount {
            font-size: 25px;
            font-weight: 700;
            color: #2563eb;
        }

        .btn {
            border-radius: 9px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .status-terverifikasi {
            background: #dcfce7;
            color: #166534;
        }

        .status-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>


<div class="main-content">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <div class="page-title">
                Verifikasi Pembayaran
            </div>

            <div class="page-subtitle">
                Periksa bukti pembayaran sebelum melakukan verifikasi.
            </div>

        </div>

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>

    </div>


    <!-- ALERT ERROR -->

    <?php if (isset($_GET['error'])): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle me-1"></i>

            <?= htmlspecialchars($_GET['error']) ?>

        </div>

    <?php endif; ?>


    <div class="row g-4">


        <!-- INFORMASI PEMBAYARAN -->

        <div class="col-lg-5">

            <div class="card-box">

                <h5 class="fw-bold mb-4">

                    <i class="bi bi-credit-card me-2"></i>

                    Informasi Pembayaran

                </h5>


                <div class="mb-3">

                    <div class="info-label">
                        Kode Pembayaran
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($data['kode_pembayaran']) ?>
                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Kode Tagihan
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($data['kode_tagihan']) ?>
                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Pelanggan
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($data['nama_pelanggan']) ?>
                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Email
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($data['email'] ?? '-') ?>
                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        No. HP
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($data['no_hp'] ?? '-') ?>
                    </div>

                </div>


                <div class="row">

                    <div class="col-6 mb-3">

                        <div class="info-label">
                            Layanan
                        </div>

                        <div class="info-value">
                            <?= htmlspecialchars($data['jenis_layanan']) ?>
                        </div>

                    </div>


                    <div class="col-6 mb-3">

                        <div class="info-label">
                            Berat
                        </div>

                        <div class="info-value">

                            <?= number_format(
                                (float) $data['berat_kg'],
                                2,
                                ',',
                                '.'
                            ) ?>

                            kg

                        </div>

                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Total Tagihan
                    </div>

                    <div class="amount">

                        Rp <?= number_format(
                            $totalTagihan,
                            0,
                            ',',
                            '.'
                        ) ?>

                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Jumlah Dibayar
                    </div>

                    <div class="info-value text-success fs-5">

                        Rp <?= number_format(
                            $jumlah,
                            0,
                            ',',
                            '.'
                        ) ?>

                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Metode Pembayaran
                    </div>

                    <div class="info-value">

                        <?= htmlspecialchars(
                            $data['metode']
                        ) ?>

                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Status Pembayaran
                    </div>

                    <?php

                    $statusClass = match ($data['status']) {
                        'menunggu' => 'status-menunggu',
                        'terverifikasi' => 'status-terverifikasi',
                        'ditolak' => 'status-ditolak',
                        default => ''
                    };

                    ?>

                    <span class="status-badge <?= $statusClass ?>">

                        <?= htmlspecialchars(
                            ucfirst($data['status'])
                        ) ?>

                    </span>

                </div>


                <div class="mb-4">

                    <div class="info-label">
                        Tanggal Pembayaran
                    </div>

                    <div class="info-value">

                        <?= date(
                            'd/m/Y H:i',
                            strtotime($data['tanggal_bayar'])
                        ) ?>

                    </div>

                </div>


                <!-- FORM VERIFIKASI -->

                <?php if ($data['status'] === 'menunggu'): ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Catatan
                            </label>

                            <textarea
                                name="catatan"
                                class="form-control"
                                rows="3"
                                placeholder="Isi catatan jika diperlukan..."
                            ></textarea>

                        </div>


                        <div class="d-grid gap-2">

                            <button
                                type="submit"
                                name="aksi"
                                value="terima"
                                class="btn btn-success"
                                onclick="return confirm('Apakah Anda yakin ingin menerima pembayaran ini?')"
                            >

                                <i class="bi bi-check-circle me-1"></i>

                                Terima Pembayaran

                            </button>


                            <button
                                type="submit"
                                name="aksi"
                                value="tolak"
                                class="btn btn-danger"
                                onclick="return confirm('Apakah Anda yakin ingin menolak pembayaran ini?')"
                            >

                                <i class="bi bi-x-circle me-1"></i>

                                Tolak Pembayaran

                            </button>

                        </div>

                    </form>

                <?php else: ?>

                    <div class="alert alert-info">

                        <i class="bi bi-info-circle me-1"></i>

                        Pembayaran ini sudah diproses dengan status:

                        <strong>
                            <?= htmlspecialchars($data['status']) ?>
                        </strong>

                    </div>


                    <?php if (!empty($data['catatan'])): ?>

                        <div class="alert alert-secondary">

                            <strong>Catatan:</strong><br>

                            <?= nl2br(
                                htmlspecialchars($data['catatan'])
                            ) ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- BUKTI PEMBAYARAN -->

        <div class="col-lg-7">

            <div class="card-box">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h5 class="fw-bold mb-0">

                        <i class="bi bi-image me-2"></i>

                        Bukti Pembayaran

                    </h5>


                    <?php if (!empty($buktiBayar)): ?>

                        <a
                            href="../../<?= htmlspecialchars($buktiBayar) ?>"
                            target="_blank"
                            class="btn btn-sm btn-outline-primary"
                        >

                            <i class="bi bi-box-arrow-up-right"></i>

                            Buka Penuh

                        </a>

                    <?php endif; ?>

                </div>


                <div class="proof-box">

                    <?php if (!empty($buktiBayar)): ?>

                        <a
                            href="../../<?= htmlspecialchars($buktiBayar) ?>"
                            target="_blank"
                        >

                            <img
                                src="../../<?= htmlspecialchars($buktiBayar) ?>"
                                class="proof-image"
                                alt="Bukti pembayaran"
                            >

                        </a>

                    <?php else: ?>

                        <div class="no-proof">

                            <i class="bi bi-image fs-1"></i>

                            <p class="mt-3 mb-0">
                                Tidak ada bukti pembayaran.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>