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
    header("Location: ../../user/dashboard.php");
    exit;
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';

// ==========================================
// FILTER
// ==========================================

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

// ==========================================
// STATISTIK
// ==========================================

// Total tagihan
$qTotal = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan"
);

$totalTagihan = 0;

if ($qTotal) {
    $data = mysqli_fetch_assoc($qTotal);
    $totalTagihan = (int)($data['total'] ?? 0);
}


// Belum bayar
$qBelum = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan
     WHERE status = 'belum_bayar'"
);

$totalBelumBayar = 0;

if ($qBelum) {
    $data = mysqli_fetch_assoc($qBelum);
    $totalBelumBayar = (int)($data['total'] ?? 0);
}


// Menunggu verifikasi
$qMenunggu = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan
     WHERE status = 'menunggu_verifikasi'"
);

$totalMenunggu = 0;

if ($qMenunggu) {
    $data = mysqli_fetch_assoc($qMenunggu);
    $totalMenunggu = (int)($data['total'] ?? 0);
}


// Lunas
$qLunas = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tagihan
     WHERE status = 'lunas'"
);

$totalLunas = 0;

if ($qLunas) {
    $data = mysqli_fetch_assoc($qLunas);
    $totalLunas = (int)($data['total'] ?? 0);
}


// Total pendapatan
$qPendapatan = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM tagihan
     WHERE status = 'lunas'"
);

$totalPendapatan = 0;

if ($qPendapatan) {
    $data = mysqli_fetch_assoc($qPendapatan);
    $totalPendapatan = (float)($data['total'] ?? 0);
}


// ==========================================
// QUERY TAGIHAN
// ==========================================

$sql = "
    SELECT
        t.id,
        t.kode_tagihan,
        t.pelanggan_id,
        t.periode_mulai,
        t.periode_selesai,
        t.jenis_layanan,
        t.berat_kg,
        t.tarif_per_kg,
        t.total,
        t.jatuh_tempo,
        t.status,
        t.created_at,
        p.nama_pelanggan,
        p.email
    FROM tagihan t
    INNER JOIN pelanggan p
        ON p.id = t.pelanggan_id
    WHERE 1=1
";

$params = [];
$types = "";

// ==========================================
// SEARCH
// ==========================================

if ($search !== '') {

    $sql .= "
        AND (
            t.kode_tagihan LIKE ?
            OR p.nama_pelanggan LIKE ?
            OR p.email LIKE ?
            OR t.jenis_layanan LIKE ?
        )
    ";

    $searchLike = "%" . $search . "%";

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;

    $types .= "ssss";
}

// ==========================================
// FILTER STATUS
// ==========================================

if ($statusFilter !== '') {

    $sql .= " AND t.status = ? ";

    $params[] = $statusFilter;

    $types .= "s";
}

$sql .= "
    ORDER BY t.id DESC
";

// ==========================================
// PREPARE
// ==========================================

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query tagihan gagal: " . mysqli_error($conn));
}

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// ==========================================
// FUNGSI RUPIAH
// ==========================================

function rupiahTagihan($angka)
{
    return 'Rp ' . number_format(
        (float)$angka,
        0,
        ',',
        '.'
    );
}


// ==========================================
// FUNGSI STATUS
// ==========================================

function badgeStatusTagihan($status)
{
    switch ($status) {

        case 'lunas':
            return '<span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Lunas
                    </span>';

        case 'menunggu_verifikasi':
            return '<span class="badge bg-warning text-dark">
                        <i class="bi bi-clock me-1"></i>
                        Menunggu Verifikasi
                    </span>';

        case 'ditolak':
            return '<span class="badge bg-danger">
                        <i class="bi bi-x-circle me-1"></i>
                        Ditolak
                    </span>';

        default:
            return '<span class="badge bg-secondary">
                        <i class="bi bi-hourglass me-1"></i>
                        Belum Bayar
                    </span>';
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
        Tagihan - LaundryPro
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
            font-family: Arial, Helvetica, sans-serif;
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
            padding: 0 15px 30px;
        }

        .brand i {
            margin-right: 8px;
        }

        .menu-title {
            font-size: 12px;
            color: #aebbd6;
            padding: 10px 15px;
            text-transform: uppercase;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #dbe5f5;
            padding: 13px 15px;
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
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
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

        /* ==========================================
           STAT CARD
        ========================================== */

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            height: 100%;
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

        .orange {
            background: #fff3df;
            color: #f59e0b;
        }

        .purple {
            background: #f0e9ff;
            color: #7c3aed;
        }

        .green {
            background: #e7f8ef;
            color: #198754;
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 25px;
            font-weight: bold;
        }

        /* ==========================================
           CARD
        ========================================== */

        .content-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header-custom {
            padding: 20px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .card-header-custom h5 {
            margin: 0;
            font-weight: bold;
        }

        /* ==========================================
           SEARCH
        ========================================== */

        .search-box {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-box input {
            min-width: 260px;
        }

        /* ==========================================
           TABLE
        ========================================== */

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8fafc;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .kode {
            font-size: 12px;
            color: #6b7280;
        }

        .layanan {
            background: #e8f0ff;
            color: #2563eb;
            padding: 5px 9px;
            border-radius: 15px;
            font-size: 12px;
            white-space: nowrap;
        }

        /* ==========================================
           RESPONSIVE
        ========================================== */

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

            .content {
                padding: 15px;
            }

            .topbar {
                padding: 0 15px;
            }

        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>

<!-- ==================================================
     SIDEBAR UTAMA
================================================== -->

<?php require_once __DIR__ . "/../sidebar.php"; ?>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <h1 class="page-title">
            Tagihan
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

        <!-- ==========================================
             JUDUL
        =========================================== -->

        <div class="mb-4">

            <h4 class="fw-bold mb-1">
                Data Tagihan
            </h4>

            <p class="text-muted mb-0">
                Kelola tagihan pelanggan LaundryPro.
            </p>

        </div>


        <!-- ==========================================
             PESAN SUCCESS
        =========================================== -->

        <?php if (isset($_GET['success'])): ?>

            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-check-circle me-2"></i>

                <?= htmlspecialchars($_GET['success']); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- ==========================================
             STATISTIK
        =========================================== -->

        <div class="row g-4 mb-4">


            <!-- TOTAL -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon blue">

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

                    <div class="stat-icon orange">

                        <i class="bi bi-hourglass"></i>

                    </div>

                    <div class="stat-title">
                        Belum Bayar
                    </div>

                    <div class="stat-value">
                        <?= $totalBelumBayar; ?>
                    </div>

                </div>

            </div>


            <!-- MENUNGGU -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-clock-history"></i>

                    </div>

                    <div class="stat-title">
                        Menunggu Verifikasi
                    </div>

                    <div class="stat-value">
                        <?= $totalMenunggu; ?>
                    </div>

                </div>

            </div>


            <!-- LUNAS -->

            <div class="col-xl-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon green">

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

        </div>


        <!-- ==========================================
             PENDAPATAN
        =========================================== -->

        <div class="content-card mb-4">

            <div class="p-4">

                <div class="text-muted mb-1">
                    Total Pendapatan Tagihan Lunas
                </div>

                <div class="fs-3 fw-bold text-success">

                    <?= rupiahTagihan($totalPendapatan); ?>

                </div>

            </div>

        </div>


        <!-- ==========================================
             DATA TAGIHAN
        =========================================== -->

        <div class="content-card">

            <!-- HEADER -->

            <div class="card-header-custom">

                <h5>

                    <i class="bi bi-receipt me-2"></i>

                    Daftar Tagihan

                </h5>

                <a
                    href="tambah.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-plus-circle me-1"></i>

                    Buat Tagihan

                </a>

            </div>


            <!-- FILTER -->

            <div class="p-3 border-bottom">

                <form
                    method="GET"
                    class="search-box"
                >

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari kode atau nama pelanggan..."
                        value="<?= htmlspecialchars($search); ?>"
                    >


                    <select
                        name="status"
                        class="form-select"
                        style="max-width: 220px;"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="belum_bayar"
                            <?= $statusFilter === 'belum_bayar'
                                ? 'selected'
                                : ''; ?>
                        >
                            Belum Bayar
                        </option>

                        <option
                            value="menunggu_verifikasi"
                            <?= $statusFilter === 'menunggu_verifikasi'
                                ? 'selected'
                                : ''; ?>
                        >
                            Menunggu Verifikasi
                        </option>

                        <option
                            value="lunas"
                            <?= $statusFilter === 'lunas'
                                ? 'selected'
                                : ''; ?>
                        >
                            Lunas
                        </option>

                        <option
                            value="ditolak"
                            <?= $statusFilter === 'ditolak'
                                ? 'selected'
                                : ''; ?>
                        >
                            Ditolak
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-search"></i>

                        Cari

                    </button>


                    <?php if (
                        $search !== '' ||
                        $statusFilter !== ''
                    ): ?>

                        <a
                            href="index.php"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <!-- TABLE -->

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Tagihan</th>

                            <th>Pelanggan</th>

                            <th>Layanan</th>

                            <th>Berat</th>

                            <th>Total</th>

                            <th>Jatuh Tempo</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        $result &&
                        mysqli_num_rows($result) > 0
                    ): ?>

                        <?php

                        $no = 1;

                        while (
                            $row =
                            mysqli_fetch_assoc($result)
                        ):

                        ?>

                            <tr>

                                <!-- NO -->

                                <td>
                                    <?= $no++; ?>
                                </td>


                                <!-- TAGIHAN -->

                                <td>

                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $row['kode_tagihan']
                                        ); ?>

                                    </div>

                                    <div class="kode">

                                        Dibuat:
                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $row['created_at']
                                            )
                                        ); ?>

                                    </div>

                                </td>


                                <!-- PELANGGAN -->

                                <td>

                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $row['nama_pelanggan']
                                        ); ?>

                                    </div>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $row['email'] ?? '-'
                                        ); ?>

                                    </small>

                                </td>


                                <!-- LAYANAN -->

                                <td>

                                    <span class="layanan">

                                        <?= htmlspecialchars(
                                            $row['jenis_layanan']
                                        ); ?>

                                    </span>

                                </td>


                                <!-- BERAT -->

                                <td>

                                    <?= number_format(
                                        (float)$row['berat_kg'],
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                    kg

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong>

                                        <?= rupiahTagihan(
                                            $row['total']
                                        ); ?>

                                    </strong>

                                </td>


                                <!-- JATUH TEMPO -->

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $row['jatuh_tempo']
                                        )
                                    ); ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?= badgeStatusTagihan(
                                        $row['status']
                                    ); ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-receipt fs-1 text-muted"
                                ></i>

                                <div class="mt-2 text-muted">

                                    Belum ada data tagihan.

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