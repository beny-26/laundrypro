<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";

// ===============================
// CEK LOGIN
// ===============================
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$error = "";

// ===============================
// PROSES SIMPAN
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kode_aset          = trim($_POST['kode_aset'] ?? '');
    $nama_aset          = trim($_POST['nama_aset'] ?? '');
    $kategori           = trim($_POST['kategori'] ?? '');
    $tanggal_perolehan  = $_POST['tanggal_perolehan'] ?? '';
    $nilai_perolehan    = (float) ($_POST['nilai_perolehan'] ?? 0);
    $kondisi             = $_POST['kondisi'] ?? '';
    $status              = $_POST['status'] ?? '';
    $keterangan          = trim($_POST['keterangan'] ?? '');

    // ===============================
    // VALIDASI
    // ===============================
    if ($kode_aset === '') {
        $error = "Kode aset wajib diisi.";
    } elseif ($nama_aset === '') {
        $error = "Nama aset wajib diisi.";
    } elseif ($kategori === '') {
        $error = "Kategori aset wajib diisi.";
    } elseif ($tanggal_perolehan === '') {
        $error = "Tanggal perolehan wajib diisi.";
    } elseif ($nilai_perolehan <= 0) {
        $error = "Nilai perolehan harus lebih dari 0.";
    } elseif (!in_array($kondisi, ['Baik', 'Rusak Ringan', 'Rusak Berat'])) {
        $error = "Kondisi aset tidak valid.";
    } elseif (!in_array($status, ['Aktif', 'Perawatan', 'Tidak Digunakan'])) {
        $error = "Status aset tidak valid.";
    }

    // ===============================
    // SIMPAN
    // ===============================
    if ($error === '') {

        // Cek kode aset
        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM aset_tetap WHERE kode_aset = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $cek,
            "s",
            $kode_aset
        );

        mysqli_stmt_execute($cek);

        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = "Kode aset sudah digunakan.";

        } else {

            $sql = "
                INSERT INTO aset_tetap (
                    kode_aset,
                    nama_aset,
                    kategori,
                    tanggal_perolehan,
                    nilai_perolehan,
                    kondisi,
                    status,
                    keterangan
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {

                $error = "Query gagal: " . mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssdsss",
                    $kode_aset,
                    $nama_aset,
                    $kategori,
                    $tanggal_perolehan,
                    $nilai_perolehan,
                    $kondisi,
                    $status,
                    $keterangan
                );

                if (mysqli_stmt_execute($stmt)) {

                    header(
                        "Location: index.php?success=" .
                        urlencode("Aset berhasil ditambahkan")
                    );

                    exit;

                } else {

                    $error =
                        "Gagal menyimpan aset: " .
                        mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            }
        }

        mysqli_stmt_close($cek);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Tambah Aset - LaundryPro</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        body {
            background: #f4f7fb;
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

        .brand {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 30px;
            padding-left: 10px;
        }

        .sidebar a {
            display: block;
            color: #dbe5ff;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .sidebar i {
            margin-right: 10px;
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

        .card {
            border: none;
            border-radius: 14px;

            box-shadow:
                0 4px 15px rgba(0,0,0,.05);
        }

        .form-label {
            font-weight: 600;
        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="brand">

        <i class="bi bi-droplet-fill"></i>

        LaundryPro

    </div>


    <a href="../dashboard.php">

        <i class="bi bi-grid"></i>

        Dashboard

    </a>


    <a href="../pelanggan/index.php">

        <i class="bi bi-people"></i>

        Pelanggan

    </a>


    <a href="../tagihan/index.php">

        <i class="bi bi-receipt"></i>

        Tagihan

    </a>


    <a href="../pembayaran/index.php">

        <i class="bi bi-credit-card"></i>

        Pembayaran

    </a>


    <a href="index.php" class="active">

        <i class="bi bi-box-seam"></i>

        Aset Tetap

    </a>


    <a href="../laporan/index.php">

        <i class="bi bi-bar-chart"></i>

        Laporan

    </a>


    <hr>


    <a href="../../logout.php">

        <i class="bi bi-box-arrow-right"></i>

        Logout

    </a>

</div>



<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h5 class="mb-0">
                Tambah Aset
            </h5>

            <small class="text-muted">
                Tambahkan aset tetap baru
            </small>

        </div>


        <div>

            <i class="bi bi-person-circle"></i>

            <?= htmlspecialchars($_SESSION['nama']) ?>

        </div>

    </div>



    <!-- CONTENT -->

    <div class="content">


        <div class="mb-4">

            <a
                href="index.php"
                class="btn btn-outline-secondary">

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>

        </div>



        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-triangle"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <!-- FORM -->

        <div class="card">

            <div class="card-body p-4">

                <h5 class="fw-bold mb-4">

                    <i class="bi bi-box-seam"></i>

                    Form Aset Tetap

                </h5>


                <form method="POST">


                    <div class="row">


                        <!-- KODE -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Kode Aset

                            </label>

                            <input
                                type="text"
                                name="kode_aset"
                                class="form-control"
                                placeholder="Contoh: AST-001"
                                value="<?= htmlspecialchars($_POST['kode_aset'] ?? '') ?>"
                                required>

                            <small class="text-muted">

                                Kode aset harus unik.

                            </small>

                        </div>



                        <!-- NAMA -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Nama Aset

                            </label>

                            <input
                                type="text"
                                name="nama_aset"
                                class="form-control"
                                placeholder="Contoh: Mesin Cuci"
                                value="<?= htmlspecialchars($_POST['nama_aset'] ?? '') ?>"
                                required>

                        </div>



                        <!-- KATEGORI -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Kategori

                            </label>

                            <select
                                name="kategori"
                                class="form-select"
                                required>

                                <option value="">
                                    -- Pilih Kategori --
                                </option>

                                <option
                                    value="Mesin"
                                    <?= (($_POST['kategori'] ?? '') === 'Mesin') ? 'selected' : '' ?>>

                                    Mesin

                                </option>

                                <option
                                    value="Peralatan"
                                    <?= (($_POST['kategori'] ?? '') === 'Peralatan') ? 'selected' : '' ?>>

                                    Peralatan

                                </option>

                                <option
                                    value="Elektronik"
                                    <?= (($_POST['kategori'] ?? '') === 'Elektronik') ? 'selected' : '' ?>>

                                    Elektronik

                                </option>

                                <option
                                    value="Furniture"
                                    <?= (($_POST['kategori'] ?? '') === 'Furniture') ? 'selected' : '' ?>>

                                    Furniture

                                </option>

                                <option
                                    value="Kendaraan"
                                    <?= (($_POST['kategori'] ?? '') === 'Kendaraan') ? 'selected' : '' ?>>

                                    Kendaraan

                                </option>

                                <option
                                    value="Lainnya"
                                    <?= (($_POST['kategori'] ?? '') === 'Lainnya') ? 'selected' : '' ?>>

                                    Lainnya

                                </option>

                            </select>

                        </div>



                        <!-- TANGGAL -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Tanggal Perolehan

                            </label>

                            <input
                                type="date"
                                name="tanggal_perolehan"
                                class="form-control"
                                value="<?= htmlspecialchars($_POST['tanggal_perolehan'] ?? date('Y-m-d')) ?>"
                                required>

                        </div>



                        <!-- NILAI -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Nilai Perolehan

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="nilai_perolehan"
                                    class="form-control"
                                    min="1"
                                    step="1"
                                    placeholder="5000000"
                                    value="<?= htmlspecialchars($_POST['nilai_perolehan'] ?? '') ?>"
                                    required>

                            </div>

                        </div>



                        <!-- KONDISI -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Kondisi

                            </label>

                            <select
                                name="kondisi"
                                class="form-select"
                                required>

                                <option value="">
                                    -- Pilih Kondisi --
                                </option>

                                <option
                                    value="Baik"
                                    <?= (($_POST['kondisi'] ?? '') === 'Baik') ? 'selected' : '' ?>>

                                    Baik

                                </option>

                                <option
                                    value="Rusak Ringan"
                                    <?= (($_POST['kondisi'] ?? '') === 'Rusak Ringan') ? 'selected' : '' ?>>

                                    Rusak Ringan

                                </option>

                                <option
                                    value="Rusak Berat"
                                    <?= (($_POST['kondisi'] ?? '') === 'Rusak Berat') ? 'selected' : '' ?>>

                                    Rusak Berat

                                </option>

                            </select>

                        </div>



                        <!-- STATUS -->

                        <div class="col-md-6 mb-3">

                            <label class="form-label">

                                Status

                            </label>

                            <select
                                name="status"
                                class="form-select"
                                required>

                                <option value="">
                                    -- Pilih Status --
                                </option>

                                <option
                                    value="Aktif"
                                    <?= (($_POST['status'] ?? '') === 'Aktif') ? 'selected' : '' ?>>

                                    Aktif

                                </option>

                                <option
                                    value="Perawatan"
                                    <?= (($_POST['status'] ?? '') === 'Perawatan') ? 'selected' : '' ?>>

                                    Perawatan

                                </option>

                                <option
                                    value="Tidak Digunakan"
                                    <?= (($_POST['status'] ?? '') === 'Tidak Digunakan') ? 'selected' : '' ?>>

                                    Tidak Digunakan

                                </option>

                            </select>

                        </div>



                        <!-- KETERANGAN -->

                        <div class="col-12 mb-4">

                            <label class="form-label">

                                Keterangan

                            </label>

                            <textarea
                                name="keterangan"
                                class="form-control"
                                rows="4"
                                placeholder="Keterangan tambahan mengenai aset..."><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>

                        </div>

                    </div>



                    <!-- BUTTON -->

                    <div class="d-flex gap-2">

                        <a
                            href="index.php"
                            class="btn btn-secondary">

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="bi bi-save"></i>

                            Simpan Aset

                        </button>

                    </div>


                </form>

            </div>

        </div>

    </div>

</div>


</body>

</html>