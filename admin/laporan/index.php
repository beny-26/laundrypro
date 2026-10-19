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
// FILTER TANGGAL
// ==========================================
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

// Validasi format tanggal sederhana
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_awal)) {
    $tgl_awal = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir)) {
    $tgl_akhir = date('Y-m-d');
}

// ==========================================
// QUERY LAPORAN
// Hanya tagihan LUNAS
// ==========================================
$sql = "
    SELECT
        t.id,
        t.kode_tagihan,
        t.periode_mulai,
        t.periode_selesai,
        t.jenis_layanan,
        t.berat_kg,
        t.tarif_per_kg,
        t.total,
        t.jatuh_tempo,
        t.status,
        p.nama_pelanggan
    FROM tagihan t
    INNER JOIN pelanggan p
        ON t.pelanggan_id = p.id
    WHERE DATE(t.periode_mulai) BETWEEN ? AND ?
      AND t.status = 'lunas'
    ORDER BY t.periode_mulai DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query laporan gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $tgl_awal,
    $tgl_akhir
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// ==========================================
// STATISTIK LAPORAN
// ==========================================
$sqlStat = "
    SELECT
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(berat_kg), 0) AS total_berat,
        COALESCE(SUM(total), 0) AS total_pendapatan
    FROM tagihan
    WHERE DATE(periode_mulai) BETWEEN ? AND ?
      AND status = 'lunas'
";

$stmtStat = mysqli_prepare($conn, $sqlStat);

if (!$stmtStat) {
    die("Query statistik gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtStat,
    "ss",
    $tgl_awal,
    $tgl_akhir
);

mysqli_stmt_execute($stmtStat);

$resultStat = mysqli_stmt_get_result($stmtStat);
$stat = mysqli_fetch_assoc($resultStat);

$jumlahTransaksi = (int) ($stat['jumlah_transaksi'] ?? 0);
$totalBerat = (float) ($stat['total_berat'] ?? 0);
$totalPendapatan = (float) ($stat['total_pendapatan'] ?? 0);

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

    <title>Laporan - LaundryPro</title>

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
            font-size: 23px;
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
            background: #f8fafc;
        }

        .table td {
            vertical-align: middle;
        }

        .badge-lunas {
            background: #d1e7dd;
            color: #0f5132;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6b7280;
        }

        .report-header {
            display: none;
        }

        @media print {

            body {
                background: white;
            }

            .lp-sidebar,
            .topbar,
            .filter-card,
            .no-print {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
                padding: 0;
            }

            .content-card,
            .stat-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .report-header {
                display: block;
                text-align: center;
                margin-bottom: 25px;
            }

            .report-header h2 {
                margin-bottom: 5px;
            }

            .table {
                font-size: 12px;
            }

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

    <div class="topbar no-print">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h4 class="fw-bold mb-1">

                    <i class="bi bi-bar-chart"></i>

                    Laporan

                </h4>

                <small class="text-muted">

                    Laporan transaksi dan pendapatan LaundryPro

                </small>

            </div>

            <button
                type="button"
                class="btn btn-primary"
                onclick="window.print()"
            >

                <i class="bi bi-printer"></i>

                Cetak Laporan

            </button>

        </div>

    </div>


    <!-- ==========================================
         HEADER CETAK
         ========================================== -->

    <div class="report-header">

        <h2>
            LAUNDRYPRO
        </h2>

        <h4>
            LAPORAN TRANSAKSI
        </h4>

        <p>

            Periode:
            <?= date('d-m-Y', strtotime($tgl_awal)) ?>

            s/d

            <?= date('d-m-Y', strtotime($tgl_akhir)) ?>

        </p>

    </div>


    <!-- ==========================================
         FILTER TANGGAL
         ========================================== -->

    <div class="content-card filter-card no-print mb-4">

        <div class="card-header-custom">

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Tanggal Awal
                        </label>

                        <input
                            type="date"
                            name="tgl_awal"
                            class="form-control"
                            value="<?= htmlspecialchars($tgl_awal) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Tanggal Akhir
                        </label>

                        <input
                            type="date"
                            name="tgl_akhir"
                            class="form-control"
                            value="<?= htmlspecialchars($tgl_akhir) ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-search"></i>

                                Tampilkan

                            </button>

                            <a
                                href="index.php"
                                class="btn btn-outline-secondary"
                            >

                                <i class="bi bi-arrow-clockwise"></i>

                                Bulan Ini

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- ==========================================
         STATISTIK
         ========================================== -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-receipt"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Transaksi Lunas
                        </div>

                        <div class="stat-number">
                            <?= $jumlahTransaksi ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-box-seam"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Total Berat
                        </div>

                        <div class="stat-number">

                            <?= number_format(
                                $totalBerat,
                                2,
                                ',',
                                '.'
                            ) ?>

                            kg

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon">

                        <i class="bi bi-cash-stack"></i>

                    </div>

                    <div>

                        <div class="text-muted">
                            Total Pendapatan
                        </div>

                        <div class="stat-number">

                            <?= rupiah($totalPendapatan) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================
         TABEL LAPORAN
         ========================================== -->

    <div class="content-card">

        <div class="card-header-custom">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="bi bi-table"></i>

                        Detail Laporan

                    </h5>

                    <small class="text-muted">

                        <?= date(
                            'd-m-Y',
                            strtotime($tgl_awal)
                        ) ?>

                        sampai

                        <?= date(
                            'd-m-Y',
                            strtotime($tgl_akhir)
                        ) ?>

                    </small>

                </div>

                <span class="badge badge-lunas">

                    Transaksi Lunas

                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Kode Tagihan</th>

                        <th>Pelanggan</th>

                        <th>Layanan</th>

                        <th>Berat</th>

                        <th>Tarif/kg</th>

                        <th>Total</th>

                        <th>Tanggal</th>

                        <th>Status</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?= $no++ ?>
                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['kode_tagihan']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_pelanggan']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row['jenis_layanan']
                                ) ?>

                            </td>


                            <td>

                                <?= number_format(
                                    $row['berat_kg'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                                kg

                            </td>


                            <td>

                                <?= rupiah(
                                    $row['tarif_per_kg']
                                ) ?>

                            </td>


                            <td>

                                <strong>

                                    <?= rupiah(
                                        $row['total']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= date(
                                    'd-m-Y',
                                    strtotime(
                                        $row['periode_mulai']
                                    )
                                ) ?>

                            </td>


                            <td>

                                <span class="badge badge-lunas">

                                    Lunas

                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9">

                            <div class="empty-state">

                                <i
                                    class="bi bi-bar-chart"
                                    style="font-size:50px;"
                                ></i>

                                <h5 class="mt-3">

                                    Tidak ada transaksi

                                </h5>

                                <p class="mb-0">

                                    Tidak ditemukan transaksi lunas
                                    pada periode yang dipilih.

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

</body>
</html>