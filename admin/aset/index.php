<?php

session_start();

require_once __DIR__ . "/../../config/koneksi.php";


// ==========================================
// CEK LOGIN
// ==========================================

if (
    !isset($_SESSION['login']) ||
    $_SESSION['login'] !== true
) {
    header("Location: ../../login.php");
    exit;
}


// ==========================================
// CEK ROLE
// ==========================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../index.php");
    exit;
}


// ==========================================
// FILTER PENCARIAN
// ==========================================

$search = trim(
    $_GET['search'] ?? ''
);

$where = "";

$params = [];

$types = "";


if ($search !== '') {

    $where = "
        WHERE
            kode_aset LIKE ?
            OR nama_aset LIKE ?
            OR kategori LIKE ?
    ";

    $keyword = "%" . $search . "%";

    $params = [
        $keyword,
        $keyword,
        $keyword
    ];

    $types = "sss";
}


// ==========================================
// DATA ASET
// ==========================================

$sql = "
    SELECT
        id,
        kode_aset,
        nama_aset,
        kategori,
        tanggal_perolehan,
        nilai_perolehan,
        kondisi,
        status,
        keterangan
    FROM aset_tetap
    $where
    ORDER BY id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    die(
        "Query aset gagal: "
        . mysqli_error($conn)
    );
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
// STATISTIK
// ==========================================

// ------------------------------------------
// TOTAL ASET
// ------------------------------------------

$qTotal = mysqli_query(
    $conn,
    "
        SELECT
            COUNT(*) AS total
        FROM aset_tetap
    "
);


if (!$qTotal) {

    die(
        "Query total aset gagal: "
        . mysqli_error($conn)
    );
}


$rowTotal = mysqli_fetch_assoc($qTotal);


$totalAset = (int) (
    $rowTotal['total'] ?? 0
);


// ------------------------------------------
// TOTAL NILAI ASET
// ------------------------------------------

$qNilai = mysqli_query(
    $conn,
    "
        SELECT
            COALESCE(
                SUM(nilai_perolehan),
                0
            ) AS total
        FROM aset_tetap
    "
);


if (!$qNilai) {

    die(
        "Query nilai aset gagal: "
        . mysqli_error($conn)
    );
}


$rowNilai = mysqli_fetch_assoc($qNilai);


$totalNilai = (float) (
    $rowNilai['total'] ?? 0
);


// ------------------------------------------
// ASET AKTIF
// ------------------------------------------

$qAktif = mysqli_query(
    $conn,
    "
        SELECT
            COUNT(*) AS total
        FROM aset_tetap
        WHERE status = 'Aktif'
    "
);


if (!$qAktif) {

    die(
        "Query aset aktif gagal: "
        . mysqli_error($conn)
    );
}


$rowAktif = mysqli_fetch_assoc($qAktif);


$totalAktif = (int) (
    $rowAktif['total'] ?? 0
);


// ------------------------------------------
// ASET PERAWATAN
// ------------------------------------------

$qPerawatan = mysqli_query(
    $conn,
    "
        SELECT
            COUNT(*) AS total
        FROM aset_tetap
        WHERE status = 'Perawatan'
    "
);


if (!$qPerawatan) {

    die(
        "Query aset perawatan gagal: "
        . mysqli_error($conn)
    );
}


$rowPerawatan = mysqli_fetch_assoc(
    $qPerawatan
);


$totalPerawatan = (int) (
    $rowPerawatan['total'] ?? 0
);


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

    <title>
        Aset Tetap - LaundryPro
    </title>


    <!-- ==========================================
         BOOTSTRAP
    =========================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- ==========================================
         BOOTSTRAP ICONS
    =========================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f4f7fb;

            font-family: Arial, sans-serif;

        }


        /* ==========================================
           MAIN CONTENT
        =========================================== */

        .main-content {

            margin-left: 250px;

            padding: 30px;

        }


        /* ==========================================
           TOPBAR
        =========================================== */

        .topbar {

            background: white;

            padding: 20px 25px;

            border-radius: 14px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.05);

        }


        /* ==========================================
           STAT CARD
        =========================================== */

        .stat-card {

            background: white;

            border-radius: 15px;

            padding: 22px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.05);

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


        /* ==========================================
           CONTENT CARD
        =========================================== */

        .content-card {

            background: white;

            border-radius: 16px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.05);

            overflow: hidden;

        }


        .card-header-custom {

            padding: 20px 25px;

            border-bottom:
                1px solid #eee;

        }


        /* ==========================================
           TABLE
        =========================================== */

        .table th {

            white-space: nowrap;

            background: #f8fafc;

        }


        .table td {

            vertical-align: middle;

        }


        /* ==========================================
           BADGE KONDISI
        =========================================== */

        .badge-baik {

            background: #d1e7dd;

            color: #0f5132;

        }


        .badge-ringan {

            background: #fff3cd;

            color: #856404;

        }


        .badge-berat {

            background: #f8d7da;

            color: #842029;

        }


        /* ==========================================
           BADGE STATUS
        =========================================== */

        .badge-aktif {

            background: #d1e7dd;

            color: #0f5132;

        }


        .badge-perawatan {

            background: #fff3cd;

            color: #856404;

        }


        .badge-tidak {

            background: #e2e3e5;

            color: #41464b;

        }


        /* ==========================================
           EMPTY STATE
        =========================================== */

        .empty-state {

            padding: 60px 20px;

            text-align: center;

            color: #6b7280;

        }


        /* ==========================================
           MOBILE
        =========================================== */

        @media (max-width: 768px) {

            .main-content {

                margin-left: 70px;

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


<?php

// ==========================================
// SIDEBAR ADMIN
// ==========================================

require_once __DIR__ . "/../sidebar.php";

?>


<div class="main-content">


    <!-- ==========================================
         TOPBAR
    =========================================== -->

    <div class="topbar">

        <div
            class="d-flex justify-content-between align-items-center"
        >


            <div>

                <h4 class="fw-bold mb-1">

                    <i class="bi bi-building"></i>

                    Aset Tetap

                </h4>


                <small class="text-muted">

                    Kelola data aset tetap LaundryPro

                </small>

            </div>


            <a
                href="tambah.php"
                class="btn btn-primary"
            >

                <i class="bi bi-plus-circle"></i>

                Tambah Aset

            </a>

        </div>

    </div>


    <!-- ==========================================
         ALERT SUCCESS
    =========================================== -->

    <?php if (
        isset($_GET['success'])
    ): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
        >

            <i class="bi bi-check-circle-fill"></i>

            <?= htmlspecialchars(
                $_GET['success']
            ) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         ALERT ERROR
    =========================================== -->

    <?php if (
        isset($_GET['error'])
    ): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
        >

            <i
                class="bi bi-exclamation-triangle-fill"
            ></i>

            <?= htmlspecialchars(
                $_GET['error']
            ) ?>


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


        <!-- TOTAL ASET -->

        <div class="col-md-3">

            <div class="stat-card">

                <div
                    class="d-flex align-items-center gap-3"
                >

                    <div class="stat-icon">

                        <i
                            class="bi bi-building"
                        ></i>

                    </div>


                    <div>

                        <div class="text-muted">

                            Total Aset

                        </div>


                        <div class="stat-number">

                            <?= $totalAset ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- NILAI ASET -->

        <div class="col-md-3">

            <div class="stat-card">

                <div
                    class="d-flex align-items-center gap-3"
                >

                    <div class="stat-icon">

                        <i
                            class="bi bi-cash-stack"
                        ></i>

                    </div>


                    <div>

                        <div class="text-muted">

                            Nilai Aset

                        </div>


                        <div class="fw-bold">

                            <?= rupiah(
                                $totalNilai
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ASET AKTIF -->

        <div class="col-md-3">

            <div class="stat-card">

                <div
                    class="d-flex align-items-center gap-3"
                >

                    <div class="stat-icon">

                        <i
                            class="bi bi-check-circle"
                        ></i>

                    </div>


                    <div>

                        <div class="text-muted">

                            Aset Aktif

                        </div>


                        <div class="stat-number">

                            <?= $totalAktif ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- PERAWATAN -->

        <div class="col-md-3">

            <div class="stat-card">

                <div
                    class="d-flex align-items-center gap-3"
                >

                    <div class="stat-icon">

                        <i
                            class="bi bi-tools"
                        ></i>

                    </div>


                    <div>

                        <div class="text-muted">

                            Perawatan

                        </div>


                        <div class="stat-number">

                            <?= $totalPerawatan ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>


    <!-- ==========================================
         FILTER PENCARIAN
    =========================================== -->

    <div class="content-card mb-4">


        <div class="card-header-custom">


            <form method="GET">


                <div
                    class="row g-3 align-items-end"
                >


                    <div class="col-md-8">

                        <label
                            class="form-label fw-semibold"
                        >

                            Cari Aset

                        </label>


                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Kode aset, nama aset, atau kategori..."
                            value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                        >

                    </div>


                    <div class="col-md-4">

                        <div class="d-flex gap-2">


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i
                                    class="bi bi-search"
                                ></i>

                                Cari

                            </button>


                            <a
                                href="index.php"
                                class="btn btn-outline-secondary"
                            >

                                <i
                                    class="bi bi-arrow-clockwise"
                                ></i>

                                Reset

                            </a>


                        </div>

                    </div>


                </div>

            </form>

        </div>

    </div>


    <!-- ==========================================
         DATA ASET
    =========================================== -->

    <div class="content-card">


        <div class="card-header-custom">


            <h5 class="fw-bold mb-0">

                <i class="bi bi-list-ul"></i>

                Daftar Aset Tetap

            </h5>


        </div>


        <div class="table-responsive">


            <table
                class="table table-hover mb-0"
            >


                <thead>


                    <tr>

                        <th>No</th>

                        <th>Kode Aset</th>

                        <th>Nama Aset</th>

                        <th>Kategori</th>

                        <th>Tanggal Perolehan</th>

                        <th>Nilai Perolehan</th>

                        <th>Kondisi</th>

                        <th>Status</th>

                        <th>Aksi</th>

                    </tr>


                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows($result) > 0
                ): ?>


                    <?php $no = 1; ?>


                    <?php while (
                        $row =
                            mysqli_fetch_assoc($result)
                    ): ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?= $no++ ?>

                            </td>


                            <!-- KODE -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['kode_aset']
                                    ) ?>

                                </strong>

                            </td>


                            <!-- NAMA -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_aset']
                                ) ?>

                            </td>


                            <!-- KATEGORI -->

                            <td>

                                <span
                                    class="badge text-bg-light"
                                >

                                    <?= htmlspecialchars(
                                        $row['kategori']
                                    ) ?>

                                </span>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= date(
                                    'd-m-Y',
                                    strtotime(
                                        $row[
                                            'tanggal_perolehan'
                                        ]
                                    )
                                ) ?>

                            </td>


                            <!-- NILAI -->

                            <td>

                                <strong>

                                    <?= rupiah(
                                        $row[
                                            'nilai_perolehan'
                                        ]
                                    ) ?>

                                </strong>

                            </td>


                            <!-- KONDISI -->

                            <td>


                                <?php if (
                                    $row['kondisi']
                                    === 'Baik'
                                ): ?>


                                    <span
                                        class="badge badge-baik"
                                    >

                                        Baik

                                    </span>


                                <?php elseif (
                                    $row['kondisi']
                                    === 'Rusak Ringan'
                                ): ?>


                                    <span
                                        class="badge badge-ringan"
                                    >

                                        Rusak Ringan

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge badge-berat"
                                    >

                                        Rusak Berat

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- STATUS -->

                            <td>


                                <?php if (
                                    $row['status']
                                    === 'Aktif'
                                ): ?>


                                    <span
                                        class="badge badge-aktif"
                                    >

                                        Aktif

                                    </span>


                                <?php elseif (
                                    $row['status']
                                    === 'Perawatan'
                                ): ?>


                                    <span
                                        class="badge badge-perawatan"
                                    >

                                        Perawatan

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge badge-tidak"
                                    >

                                        Tidak Digunakan

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- AKSI -->

                            <td>


                                <div
                                    class="d-flex gap-1"
                                >


                                    <!-- EDIT -->

                                    <a
                                        href="edit.php?id=<?= (int) $row['id'] ?>"
                                        class="btn btn-sm btn-warning"
                                        title="Edit"
                                    >

                                        <i
                                            class="bi bi-pencil"
                                        ></i>

                                    </a>


                                    <!-- HAPUS -->

                                    <a
                                        href="hapus.php?id=<?= (int) $row['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        title="Hapus"
                                        onclick="return confirm(
                                            'Apakah Anda yakin ingin menghapus aset <?= htmlspecialchars(
                                                $row['nama_aset'],
                                                ENT_QUOTES
                                            ) ?>?'
                                        )"
                                    >

                                        <i
                                            class="bi bi-trash"
                                        ></i>

                                    </a>


                                </div>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <!-- ==========================================
                         EMPTY STATE
                    =========================================== -->

                    <tr>


                        <td colspan="9">


                            <div class="empty-state">


                                <i
                                    class="bi bi-building"
                                    style="font-size: 50px;"
                                ></i>


                                <h5 class="mt-3">

                                    Belum ada data aset

                                </h5>


                                <p class="mb-0">

                                    Silakan tambahkan aset
                                    tetap pertama.

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


<!-- ==========================================
     BOOTSTRAP JS
=========================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>