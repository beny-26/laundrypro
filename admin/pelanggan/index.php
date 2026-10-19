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

// ==========================================
// DATA ADMIN
// ==========================================

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';

// ==========================================
// SEARCH
// ==========================================

$search = trim($_GET['search'] ?? '');

$statusFilter = $_GET['status'] ?? '';

// ==========================================
// STATISTIK
// ==========================================

// Total pelanggan
$qTotal = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM pelanggan"
);

$totalPelanggan = 0;

if ($qTotal) {
    $data = mysqli_fetch_assoc($qTotal);
    $totalPelanggan = (int) ($data['total'] ?? 0);
}


// Pelanggan aktif
$qAktif = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pelanggan
     WHERE status = 'aktif'"
);

$pelangganAktif = 0;

if ($qAktif) {
    $data = mysqli_fetch_assoc($qAktif);
    $pelangganAktif = (int) ($data['total'] ?? 0);
}


// Pelanggan nonaktif
$qNonaktif = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pelanggan
     WHERE status = 'nonaktif'"
);

$pelangganNonaktif = 0;

if ($qNonaktif) {
    $data = mysqli_fetch_assoc($qNonaktif);
    $pelangganNonaktif = (int) ($data['total'] ?? 0);
}


// ==========================================
// QUERY DATA PELANGGAN
// ==========================================

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
        p.status,
        p.created_at
    FROM pelanggan p
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
            p.nama_pelanggan LIKE ?
            OR p.email LIKE ?
            OR p.no_hp LIKE ?
            OR p.jenis_layanan LIKE ?
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

    $sql .= " AND p.status = ? ";

    $params[] = $statusFilter;

    $types .= "s";
}


// ==========================================
// ORDER
// ==========================================

$sql .= " ORDER BY p.id DESC";


// ==========================================
// PREPARE
// ==========================================

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query pelanggan gagal: " . mysqli_error($conn));
}


// ==========================================
// BIND PARAMETER
// ==========================================

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

function rupiahPelanggan($angka)
{
    return 'Rp ' . number_format(
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

    <title>
        Pelanggan - LaundryPro
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


        /* ==========================================
           STAT CARD
        ========================================== */

        .stat-card {

            background: white;

            border:
                1px solid #e5e7eb;

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


        .green {

            background: #e7f8ef;

            color: #198754;
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
           CONTENT CARD
        ========================================== */

        .content-card {

            background: white;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            overflow: hidden;
        }


        .card-header-custom {

            padding:
                20px
                22px;

            border-bottom:
                1px solid #e5e7eb;

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

            min-width: 250px;
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


        /* ==========================================
           BADGE
        ========================================== */

        .badge-layanan {

            background: #e8f0ff;

            color: #2563eb;

            padding:
                6px
                10px;

            border-radius: 20px;

            font-size: 12px;
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

                padding:
                    0
                    15px;
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

            Pelanggan

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

                Data Pelanggan

            </h4>


            <p class="text-muted mb-0">

                Kelola data pelanggan LaundryPro.

            </p>

        </div>


        <!-- ==========================================
             STATISTIK
        =========================================== -->

        <div class="row g-4 mb-4">


            <!-- TOTAL -->

            <div class="col-xl-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-people-fill"></i>

                    </div>


                    <div class="stat-title">

                        Total Pelanggan

                    </div>


                    <div class="stat-value">

                        <?= $totalPelanggan; ?>

                    </div>

                </div>

            </div>


            <!-- AKTIF -->

            <div class="col-xl-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon green">

                        <i class="bi bi-person-check-fill"></i>

                    </div>


                    <div class="stat-title">

                        Pelanggan Aktif

                    </div>


                    <div class="stat-value">

                        <?= $pelangganAktif; ?>

                    </div>

                </div>

            </div>


            <!-- NONAKTIF -->

            <div class="col-xl-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon red">

                        <i class="bi bi-person-x-fill"></i>

                    </div>


                    <div class="stat-title">

                        Pelanggan Nonaktif

                    </div>


                    <div class="stat-value">

                        <?= $pelangganNonaktif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             DATA PELANGGAN
        =========================================== -->

        <div class="content-card">


            <!-- HEADER -->

            <div class="card-header-custom">


                <div>

                    <h5>

                        <i class="bi bi-people me-2"></i>

                        Daftar Pelanggan

                    </h5>

                </div>


                <a
                    href="tambah.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-person-plus me-1"></i>

                    Tambah Pelanggan

                </a>

            </div>


            <!-- ======================================
                 FILTER
            ======================================= -->

            <div class="p-3 border-bottom">

                <form
                    method="GET"
                    class="search-box"
                >


                    <!-- SEARCH -->

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari nama, email, no. HP..."
                        value="<?= htmlspecialchars($search); ?>"
                    >


                    <!-- STATUS -->

                    <select
                        name="status"
                        class="form-select"
                        style="max-width: 180px;"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="aktif"
                            <?= $statusFilter === 'aktif' ? 'selected' : ''; ?>
                        >
                            Aktif
                        </option>

                        <option
                            value="nonaktif"
                            <?= $statusFilter === 'nonaktif' ? 'selected' : ''; ?>
                        >
                            Nonaktif
                        </option>

                    </select>


                    <!-- BUTTON -->

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


            <!-- ======================================
                 TABLE
            ======================================= -->

            <div class="table-responsive">

                <table class="table table-hover">


                    <thead>

                        <tr>

                            <th width="60">
                                No
                            </th>

                            <th>
                                Pelanggan
                            </th>

                            <th>
                                No. HP
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Layanan
                            </th>

                            <th>
                                Berat
                            </th>

                            <th>
                                Tarif
                            </th>

                            <th>
                                Durasi
                            </th>

                            <th>
                                Status
                            </th>

                            <th width="130">
                                Aksi
                            </th>

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


                                <!-- PELANGGAN -->

                                <td>

                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $row['nama_pelanggan']
                                        ); ?>

                                    </div>


                                    <small class="text-muted">

                                        ID:
                                        <?= $row['id']; ?>

                                    </small>

                                </td>


                                <!-- NO HP -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['no_hp'] ?? '-'
                                    ); ?>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['email'] ?? '-'
                                    ); ?>

                                </td>


                                <!-- LAYANAN -->

                                <td>

                                    <span class="badge-layanan">

                                        <?= htmlspecialchars(
                                            $row['jenis_layanan']
                                        ); ?>

                                    </span>

                                </td>


                                <!-- BERAT -->

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


                                <!-- TARIF -->

                                <td>

                                    <?= rupiahPelanggan(
                                        $row['tarif_per_kg']
                                    ); ?>

                                    /kg

                                </td>


                                <!-- DURASI -->

                                <td>

                                    <?= (int)
                                        $row['durasi_jam']; ?>

                                    jam

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $row['status'] === 'aktif'
                                    ): ?>

                                        <span class="badge bg-success">

                                            Aktif

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Nonaktif

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- AKSI -->

                                <td>

                                    <div class="d-flex gap-1">


                                        <a
                                            href="edit.php?id=<?= $row['id']; ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Edit"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="10"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-people fs-1 text-muted"
                                ></i>


                                <div class="mt-2 text-muted">

                                    Tidak ada data pelanggan.

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


<!-- BOOTSTRAP JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>