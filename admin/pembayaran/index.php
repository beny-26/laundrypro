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
// FILTER
// ==========================================
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = "";

if (
    $status !== '' &&
    in_array($status, ['menunggu', 'terverifikasi', 'ditolak'])
) {
    $where[] = "p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search !== '') {

    $where[] = "(
        p.kode_pembayaran LIKE ?
        OR t.kode_tagihan LIKE ?
        OR pl.nama_pelanggan LIKE ?
    )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= "sss";
}

$whereSql = "";

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

// ==========================================
// QUERY DATA PEMBAYARAN
// ==========================================
$sql = "
    SELECT
        p.id,
        p.kode_pembayaran,
        p.jumlah,
        p.metode,
        p.bukti_bayar,
        p.status,
        p.tanggal_bayar,
        p.catatan,

        t.kode_tagihan,
        t.total AS total_tagihan,

        pl.nama_pelanggan

    FROM pembayaran p

    INNER JOIN tagihan t
        ON p.tagihan_id = t.id

    INNER JOIN pelanggan pl
        ON p.pelanggan_id = pl.id

    $whereSql

    ORDER BY p.tanggal_bayar DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query pembayaran gagal: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// ==========================================
// STATISTIK PEMBAYARAN
// ==========================================

// Total seluruh pembayaran
$qTotal = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pembayaran
    "
);

if (!$qTotal) {
    die("Query total pembayaran gagal: " . mysqli_error($conn));
}

$rowTotal = mysqli_fetch_assoc($qTotal);

$totalPembayaran = (int) ($rowTotal['total'] ?? 0);


// Total menunggu
$qMenunggu = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status = 'menunggu'
    "
);

if (!$qMenunggu) {
    die("Query pembayaran menunggu gagal: " . mysqli_error($conn));
}

$rowMenunggu = mysqli_fetch_assoc($qMenunggu);

$totalMenunggu = (int) ($rowMenunggu['total'] ?? 0);


// Total terverifikasi
$qVerified = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status = 'terverifikasi'
    "
);

if (!$qVerified) {
    die("Query pembayaran terverifikasi gagal: " . mysqli_error($conn));
}

$rowVerified = mysqli_fetch_assoc($qVerified);

$totalVerified = (int) ($rowVerified['total'] ?? 0);


// Total ditolak
$qDitolak = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status = 'ditolak'
    "
);

if (!$qDitolak) {
    die("Query pembayaran ditolak gagal: " . mysqli_error($conn));
}

$rowDitolak = mysqli_fetch_assoc($qDitolak);

$totalDitolak = (int) ($rowDitolak['total'] ?? 0);


// ==========================================
// FORMAT RUPIAH
// ==========================================
function rupiah($angka)
{
    return "Rp " . number_format(
        (float) $angka,
        0,
        ',',
        '.'
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
            background: #f4f7fb;
            font-family: Arial, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 22px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
            height: 100%;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: #e8f0ff;
            color: #2563eb;
        }

        .stat-number {
            font-size: 25px;
            font-weight: 700;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .table th {
            white-space: nowrap;
            background: #f8fafc;
        }

        .table td {
            vertical-align: middle;
        }

        .badge-menunggu {
            background: #fff3cd;
            color: #856404;
        }

        .badge-verifikasi {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-ditolak {
            background: #f8d7da;
            color: #842029;
        }

        .proof-image {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .table-responsive {
                font-size: 13px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>

<?php require_once __DIR__ . "/../sidebar.php"; ?>


<div class="main-content">


    <!-- ==========================================
         TOPBAR
         ========================================== -->

    <div class="topbar">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h4 class="fw-bold mb-1">

                    <i class="bi bi-credit-card"></i>

                    Pembayaran

                </h4>

                <small class="text-muted">

                    Kelola dan verifikasi pembayaran pelanggan

                </small>

            </div>

        </div>

    </div>


    <!-- ==========================================
         ALERT SUCCESS
         ========================================== -->

    <?php if (isset($_GET['success'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle-fill"></i>

            <?= htmlspecialchars($_GET['success']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         ALERT ERROR
         ========================================== -->

    <?php if (isset($_GET['error'])): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= htmlspecialchars($_GET['error']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         STATISTIK
         ========================================== -->

    <div class="row g-4 mb-4">


        <!-- TOTAL PEMBAYARAN -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-credit-card"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Total Pembayaran
                        </div>

                        <div class="stat-number">

                            <?= $totalPembayaran ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- MENUNGGU -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-hourglass-split"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Menunggu
                        </div>

                        <div class="stat-number">

                            <?= $totalMenunggu ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- TERVERIFIKASI -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Terverifikasi
                        </div>

                        <div class="stat-number">

                            <?= $totalVerified ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- DITOLAK -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-x-circle"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Ditolak
                        </div>

                        <div class="stat-number">

                            <?= $totalDitolak ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================
         FILTER
         ========================================== -->

    <div class="content-card mb-4">

        <div class="card-header-custom">

            <form method="GET">

                <div class="row g-3 align-items-end">


                    <!-- SEARCH -->

                    <div class="col-md-5">

                        <label class="form-label fw-semibold">

                            Cari Pembayaran

                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Kode pembayaran, tagihan, atau pelanggan..."
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">

                            Status

                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">
                                Semua Status
                            </option>

                            <option
                                value="menunggu"
                                <?= $status === 'menunggu' ? 'selected' : '' ?>
                            >
                                Menunggu
                            </option>

                            <option
                                value="terverifikasi"
                                <?= $status === 'terverifikasi' ? 'selected' : '' ?>
                            >
                                Terverifikasi
                            </option>

                            <option
                                value="ditolak"
                                <?= $status === 'ditolak' ? 'selected' : '' ?>
                            >
                                Ditolak
                            </option>

                        </select>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-md-4">

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-search"></i>

                                Cari

                            </button>

                            <a
                                href="index.php"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-clockwise"></i>

                                Reset

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- ==========================================
         DATA PEMBAYARAN
         ========================================== -->

    <div class="content-card">

        <div class="card-header-custom">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-list-ul"></i>

                Data Pembayaran

            </h5>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Kode Pembayaran</th>

                        <th>Pelanggan</th>

                        <th>Kode Tagihan</th>

                        <th>Jumlah</th>

                        <th>Metode</th>

                        <th>Bukti</th>

                        <th>Tanggal</th>

                        <th>Status</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>


                            <!-- NO -->

                            <td>

                                <?= $no++ ?>

                            </td>


                            <!-- KODE PEMBAYARAN -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['kode_pembayaran']
                                    ) ?>

                                </strong>

                            </td>


                            <!-- PELANGGAN -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_pelanggan']
                                ) ?>

                            </td>


                            <!-- KODE TAGIHAN -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['kode_tagihan']
                                ) ?>

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

                                <span class="badge text-bg-light">

                                    <?= htmlspecialchars(
                                        $row['metode']
                                    ) ?>

                                </span>

                            </td>


                            <!-- BUKTI -->

                            <td>

                                <?php if (!empty($row['bukti_bayar'])): ?>

                                    <a
                                        href="../../<?= htmlspecialchars(
                                            $row['bukti_bayar']
                                        ) ?>"
                                        target="_blank"
                                    >

                                        <img
                                            src="../../<?= htmlspecialchars(
                                                $row['bukti_bayar']
                                            ) ?>"
                                            class="proof-image"
                                            alt="Bukti pembayaran"
                                        >

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Tidak ada

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= date(
                                    'd-m-Y H:i',
                                    strtotime(
                                        $row['tanggal_bayar']
                                    )
                                ) ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ($row['status'] === 'menunggu'): ?>

                                    <span class="badge badge-menunggu">

                                        Menunggu

                                    </span>

                                <?php elseif ($row['status'] === 'terverifikasi'): ?>

                                    <span class="badge badge-verifikasi">

                                        Terverifikasi

                                    </span>

                                <?php elseif ($row['status'] === 'ditolak'): ?>

                                    <span class="badge badge-ditolak">

                                        Ditolak

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <?= htmlspecialchars(
                                            $row['status']
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <?php if ($row['status'] === 'menunggu'): ?>

                                    <a
                                        href="verifikasi.php?id=<?= (int) $row['id'] ?>"
                                        class="btn btn-sm btn-primary"
                                    >

                                        <i class="bi bi-check2-square"></i>

                                        Verifikasi

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Selesai

                                    </span>

                                <?php endif; ?>

                            </td>


                        </tr>

                    <?php endwhile; ?>


                <?php else: ?>

                    <tr>

                        <td colspan="10">

                            <div class="empty-state">

                                <i
                                    class="bi bi-credit-card"
                                    style="font-size: 50px;"
                                ></i>

                                <h5 class="mt-3">

                                    Belum ada pembayaran

                                </h5>

                                <p class="mb-0">

                                    Data pembayaran pelanggan akan muncul di sini.

                                </p>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>