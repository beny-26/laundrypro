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

$error = '';
$nama = '';
$email = '';
$no_hp = '';
$jenis_layanan = 'Reguler';
$berat_kg = '';
$status = 'aktif';

// ==========================================
// PROSES SIMPAN
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $jenis_layanan = $_POST['jenis_layanan'] ?? 'Reguler';
    $berat_kg = trim($_POST['berat_kg'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    // --------------------------------------
    // VALIDASI
    // --------------------------------------

    if ($nama === '') {
        $error = 'Nama pelanggan wajib diisi.';
    } elseif ($email === '') {
        $error = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password === '') {
        $error = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array(
        $jenis_layanan,
        ['Reguler', 'Express 1 Hari', 'Express 5 Jam'],
        true
    )) {
        $error = 'Jenis layanan tidak valid.';
    } elseif (!in_array(
        $status,
        ['aktif', 'nonaktif'],
        true
    )) {
        $error = 'Status pelanggan tidak valid.';
    }

    // --------------------------------------
    // TARIF DAN DURASI
    // --------------------------------------

    if ($error === '') {

        switch ($jenis_layanan) {

            case 'Express 1 Hari':
                $tarif_per_kg = 10000;
                $durasi_jam = 24;
                break;

            case 'Express 5 Jam':
                $tarif_per_kg = 15000;
                $durasi_jam = 5;
                break;

            default:
                $tarif_per_kg = 3000;
                $durasi_jam = 72;
                break;
        }

        $berat_kg = (float) $berat_kg;

        if ($berat_kg < 0) {
            $error = 'Berat tidak boleh bernilai negatif.';
        }
    }

    // --------------------------------------
    // CEK EMAIL
    // --------------------------------------

    if ($error === '') {

        $cekEmail = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$cekEmail) {
            $error = 'Query pengecekan email gagal.';
        } else {

            mysqli_stmt_bind_param(
                $cekEmail,
                "s",
                $email
            );

            mysqli_stmt_execute($cekEmail);

            $hasilEmail = mysqli_stmt_get_result($cekEmail);

            if (mysqli_num_rows($hasilEmail) > 0) {
                $error = 'Email sudah digunakan.';
            }

            mysqli_stmt_close($cekEmail);
        }
    }

    // --------------------------------------
    // SIMPAN DATA
    // --------------------------------------

    if ($error === '') {

        mysqli_begin_transaction($conn);

        try {

            // Password hash
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // ----------------------------------
            // INSERT USERS
            // ----------------------------------

            $stmtUser = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (
                    nama,
                    email,
                    no_hp,
                    password,
                    role,
                    status
                )
                VALUES
                (?, ?, ?, ?, 'pelanggan', ?)"
            );

            if (!$stmtUser) {
                throw new Exception(
                    "Query users gagal: " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtUser,
                "sssss",
                $nama,
                $email,
                $no_hp,
                $passwordHash,
                $status
            );

            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception(
                    "Gagal menyimpan akun pelanggan: " .
                    mysqli_stmt_error($stmtUser)
                );
            }

            $user_id = mysqli_insert_id($conn);

            mysqli_stmt_close($stmtUser);

            // ----------------------------------
            // INSERT PELANGGAN
            // ----------------------------------

            $stmtPelanggan = mysqli_prepare(
                $conn,
                "INSERT INTO pelanggan
                (
                    user_id,
                    nama_pelanggan,
                    no_hp,
                    email,
                    jenis_layanan,
                    berat_kg,
                    tarif_per_kg,
                    durasi_jam,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmtPelanggan) {
                throw new Exception(
                    "Query pelanggan gagal: " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtPelanggan,
                "issssddis",
                $user_id,
                $nama,
                $no_hp,
                $email,
                $jenis_layanan,
                $berat_kg,
                $tarif_per_kg,
                $durasi_jam,
                $status
            );

            if (!mysqli_stmt_execute($stmtPelanggan)) {
                throw new Exception(
                    "Gagal menyimpan data pelanggan: " .
                    mysqli_stmt_error($stmtPelanggan)
                );
            }

            mysqli_stmt_close($stmtPelanggan);

            // ----------------------------------
            // COMMIT
            // ----------------------------------

            mysqli_commit($conn);

            header(
                "Location: index.php?success=" .
                urlencode("Pelanggan berhasil ditambahkan.")
            );

            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = $e->getMessage();
        }
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
        Tambah Pelanggan - LaundryPro
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

        .form-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .form-header {
            padding: 22px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-header h5 {
            margin: 0;
            font-weight: bold;
        }

        .form-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .required {
            color: #dc3545;
        }

        .info-box {
            background: #eef5ff;
            border: 1px solid #cfe0ff;
            color: #2457a6;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
        }

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
     SIDEBAR
================================================== -->

<?php require_once __DIR__ . "/../sidebar.php"; ?>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <h1 class="page-title">
            Tambah Pelanggan
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

        <div class="mb-4">

            <a
                href="index.php"
                class="btn btn-outline-secondary btn-sm mb-3"
            >

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>

            <h4 class="fw-bold mb-1">
                Tambah Pelanggan
            </h4>

            <p class="text-muted mb-0">
                Tambahkan akun dan data pelanggan baru.
            </p>

        </div>


        <!-- ERROR -->

        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle me-2"></i>

                <?= htmlspecialchars($error); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="form-card">

            <div class="form-header">

                <h5>

                    <i class="bi bi-person-plus me-2"></i>

                    Data Pelanggan

                </h5>

            </div>


            <div class="form-body">

                <div class="info-box mb-4">

                    <i class="bi bi-info-circle me-2"></i>

                    Password digunakan pelanggan untuk login
                    ke sistem LaundryPro.

                </div>


                <form
                    method="POST"
                    action=""
                >

                    <div class="row g-4">


                        <!-- NAMA -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Nama Pelanggan
                                <span class="required">*</span>

                            </label>

                            <input
                                type="text"
                                name="nama"
                                class="form-control"
                                value="<?= htmlspecialchars($nama); ?>"
                                placeholder="Masukkan nama pelanggan"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Email
                                <span class="required">*</span>

                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email); ?>"
                                placeholder="contoh@email.com"
                                required
                            >

                        </div>


                        <!-- NO HP -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Nomor HP

                            </label>

                            <input
                                type="text"
                                name="no_hp"
                                class="form-control"
                                value="<?= htmlspecialchars($no_hp); ?>"
                                placeholder="08xxxxxxxxxx"
                            >

                        </div>


                        <!-- PASSWORD -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Password
                                <span class="required">*</span>

                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Minimal 6 karakter"
                                required
                            >

                        </div>


                        <!-- LAYANAN -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Jenis Layanan
                                <span class="required">*</span>

                            </label>

                            <select
                                name="jenis_layanan"
                                id="jenis_layanan"
                                class="form-select"
                                required
                            >

                                <option
                                    value="Reguler"
                                    <?= $jenis_layanan === 'Reguler'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Reguler - Rp3.000/kg - 72 Jam
                                </option>

                                <option
                                    value="Express 1 Hari"
                                    <?= $jenis_layanan === 'Express 1 Hari'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Express 1 Hari - Rp10.000/kg - 24 Jam
                                </option>

                                <option
                                    value="Express 5 Jam"
                                    <?= $jenis_layanan === 'Express 5 Jam'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Express 5 Jam - Rp15.000/kg - 5 Jam
                                </option>

                            </select>

                        </div>


                        <!-- BERAT -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Berat Cucian (Kg)

                            </label>

                            <input
                                type="number"
                                name="berat_kg"
                                class="form-control"
                                value="<?= htmlspecialchars($berat_kg); ?>"
                                min="0"
                                step="0.01"
                                placeholder="Contoh: 4.5"
                            >

                        </div>


                        <!-- STATUS -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Status

                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="aktif"
                                    <?= $status === 'aktif'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Aktif
                                </option>

                                <option
                                    value="nonaktif"
                                    <?= $status === 'nonaktif'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Nonaktif
                                </option>

                            </select>

                        </div>


                        <!-- INFORMASI TARIF -->

                        <div class="col-md-6">

                            <label class="form-label">

                                Informasi Tarif

                            </label>

                            <div
                                class="form-control bg-light"
                                id="infoTarif"
                            >
                                Reguler: Rp3.000/kg
                            </div>

                        </div>


                    </div>


                    <!-- BUTTON -->

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">

                        <a
                            href="index.php"
                            class="btn btn-outline-secondary"
                        >

                            Batal

                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-save me-1"></i>

                            Simpan Pelanggan

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

    const layanan =
        document.getElementById('jenis_layanan');

    const infoTarif =
        document.getElementById('infoTarif');


    function updateTarif() {

        if (layanan.value === 'Express 1 Hari') {

            infoTarif.innerHTML =
                'Express 1 Hari: Rp10.000/kg - 24 Jam';

        }
        else if (layanan.value === 'Express 5 Jam') {

            infoTarif.innerHTML =
                'Express 5 Jam: Rp15.000/kg - 5 Jam';

        }
        else {

            infoTarif.innerHTML =
                'Reguler: Rp3.000/kg - 72 Jam';
        }
    }


    layanan.addEventListener(
        'change',
        updateTarif
    );


    updateTarif();

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>