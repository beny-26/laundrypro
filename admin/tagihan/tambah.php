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
// VARIABEL
// ==========================================
$error = "";

$jenis_layanan = [
    "Reguler" => [
        "tarif" => 3000,
        "durasi" => 72
    ],
    "Express 1 Hari" => [
        "tarif" => 10000,
        "durasi" => 24
    ],
    "Express 5 Jam" => [
        "tarif" => 15000,
        "durasi" => 5
    ]
];

// ==========================================
// PROSES SIMPAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pelanggan_id = (int) ($_POST['pelanggan_id'] ?? 0);
    $jenis        = trim($_POST['jenis_layanan'] ?? '');
    $berat        = (float) ($_POST['berat_kg'] ?? 0);

    // Validasi
    if ($pelanggan_id <= 0) {
        $error = "Pelanggan harus dipilih.";
    } elseif (!isset($jenis_layanan[$jenis])) {
        $error = "Jenis layanan tidak valid.";
    } elseif ($berat <= 0) {
        $error = "Berat laundry harus lebih dari 0 kg.";
    } else {

        $tarif  = $jenis_layanan[$jenis]['tarif'];
        $durasi = $jenis_layanan[$jenis]['durasi'];

        $periode_mulai = date('Y-m-d H:i:s');
        $periode_selesai = date(
            'Y-m-d H:i:s',
            strtotime("+{$durasi} hours")
        );

        $jatuh_tempo = $periode_selesai;

        $total = $berat * $tarif;

        // Generate kode tagihan
        $kode_tagihan = "TAG-" . date("YmdHis") . "-" . rand(100, 999);

        mysqli_begin_transaction($conn);

        try {

            $sql = "INSERT INTO tagihan
                    (
                        kode_tagihan,
                        pelanggan_id,
                        periode_mulai,
                        periode_selesai,
                        jenis_layanan,
                        berat_kg,
                        tarif_per_kg,
                        total,
                        jatuh_tempo,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'belum_bayar')";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                throw new Exception("Query gagal: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "sisssddds",
                $kode_tagihan,
                $pelanggan_id,
                $periode_mulai,
                $periode_selesai,
                $jenis,
                $berat,
                $tarif,
                $total,
                $jatuh_tempo
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    "Gagal menyimpan tagihan: " .
                    mysqli_stmt_error($stmt)
                );
            }

            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            header("Location: index.php?success=Tagihan berhasil dibuat");
            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// ==========================================
// AMBIL DATA PELANGGAN AKTIF
// ==========================================
$queryPelanggan = mysqli_query(
    $conn,
    "SELECT id, nama_pelanggan, no_hp, jenis_layanan, berat_kg
     FROM pelanggan
     WHERE status = 'aktif'
     ORDER BY nama_pelanggan ASC"
);

if (!$queryPelanggan) {
    die("Gagal mengambil data pelanggan: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Buat Tagihan - LaundryPro</title>

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

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,.05);
        }

        .form-label {
            font-weight: 600;
        }

        .preview-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }

        .total-preview {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
        }

        .btn-primary {
            background: #2563eb;
            border: none;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>

<?php require_once __DIR__ . "/../sidebar.php"; ?>

<div class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="d-flex justify-content-between align-items-center">

            <div>
                <h4 class="fw-bold mb-1">
                    <i class="bi bi-receipt"></i>
                    Buat Tagihan
                </h4>

                <small class="text-muted">
                    Membuat tagihan laundry pelanggan
                </small>
            </div>

            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>

        </div>
    </div>

    <!-- ERROR -->
    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <!-- FORM -->
    <div class="form-card">

        <form method="POST">

            <div class="row g-4">

                <!-- PELANGGAN -->
                <div class="col-md-6">

                    <label class="form-label">
                        Pelanggan
                    </label>

                    <select
                        name="pelanggan_id"
                        id="pelanggan_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Pilih Pelanggan --
                        </option>

                        <?php while ($p = mysqli_fetch_assoc($queryPelanggan)): ?>

                            <option
                                value="<?= $p['id'] ?>"
                                data-layanan="<?= htmlspecialchars($p['jenis_layanan']) ?>"
                                data-berat="<?= $p['berat_kg'] ?>"
                                <?= (
                                    isset($_POST['pelanggan_id']) &&
                                    $_POST['pelanggan_id'] == $p['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($p['nama_pelanggan']) ?>

                                <?php if (!empty($p['no_hp'])): ?>
                                    - <?= htmlspecialchars($p['no_hp']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- JENIS LAYANAN -->
                <div class="col-md-6">

                    <label class="form-label">
                        Jenis Layanan
                    </label>

                    <select
                        name="jenis_layanan"
                        id="jenis_layanan"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Pilih Layanan --
                        </option>

                        <?php foreach ($jenis_layanan as $nama => $data): ?>

                            <option
                                value="<?= htmlspecialchars($nama) ?>"
                                data-tarif="<?= $data['tarif'] ?>"
                                data-durasi="<?= $data['durasi'] ?>"
                                <?= (
                                    isset($_POST['jenis_layanan']) &&
                                    $_POST['jenis_layanan'] === $nama
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($nama) ?>
                                - Rp <?= number_format($data['tarif'], 0, ',', '.') ?>/kg

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- BERAT -->
                <div class="col-md-6">

                    <label class="form-label">
                        Berat Laundry (kg)
                    </label>

                    <div class="input-group">

                        <input
                            type="number"
                            name="berat_kg"
                            id="berat_kg"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            placeholder="Contoh: 4.5"
                            value="<?= htmlspecialchars($_POST['berat_kg'] ?? '') ?>"
                            required
                        >

                        <span class="input-group-text">
                            kg
                        </span>

                    </div>

                </div>


                <!-- PREVIEW -->
                <div class="col-md-6">

                    <label class="form-label">
                        Total Tagihan
                    </label>

                    <div class="preview-box">

                        <div class="text-muted mb-1">
                            Estimasi total
                        </div>

                        <div
                            id="totalPreview"
                            class="total-preview"
                        >
                            Rp 0
                        </div>

                        <small
                            id="durasiPreview"
                            class="text-muted"
                        >
                            Durasi layanan: -
                        </small>

                    </div>

                </div>


                <!-- INFO -->
                <div class="col-12">

                    <div class="alert alert-info mb-0">

                        <i class="bi bi-info-circle-fill"></i>

                        <strong>Informasi:</strong>

                        Tagihan akan otomatis dibuat dengan status
                        <strong>Belum Bayar</strong>.

                        Jatuh tempo mengikuti durasi layanan yang dipilih.

                    </div>

                </div>


                <!-- BUTTON -->
                <div class="col-12">

                    <hr>

                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary px-4"
                        >
                            <i class="bi bi-save"></i>
                            Buat Tagihan
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


<script>

const pelanggan = document.getElementById('pelanggan_id');
const layanan = document.getElementById('jenis_layanan');
const berat = document.getElementById('berat_kg');

const totalPreview = document.getElementById('totalPreview');
const durasiPreview = document.getElementById('durasiPreview');


// ==========================================
// FORMAT RUPIAH
// ==========================================
function formatRupiah(angka) {

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);

}


// ==========================================
// HITUNG TOTAL
// ==========================================
function hitungTotal() {

    const option = layanan.options[layanan.selectedIndex];

    if (!option || !option.dataset.tarif) {

        totalPreview.innerText = "Rp 0";
        durasiPreview.innerText = "Durasi layanan: -";

        return;
    }

    const tarif = parseFloat(option.dataset.tarif);
    const durasi = option.dataset.durasi;
    const beratValue = parseFloat(berat.value) || 0;

    const total = tarif * beratValue;

    totalPreview.innerText = formatRupiah(total);

    durasiPreview.innerText =
        "Durasi layanan: " + durasi + " jam";
}


// ==========================================
// AUTO ISI DATA PELANGGAN
// ==========================================
pelanggan.addEventListener('change', function () {

    const option = this.options[this.selectedIndex];

    if (option && option.dataset.layanan) {

        const layananPelanggan = option.dataset.layanan;
        const beratPelanggan = option.dataset.berat;

        layanan.value = layananPelanggan;

        if (
            berat.value === "" &&
            parseFloat(beratPelanggan) > 0
        ) {
            berat.value = beratPelanggan;
        }

        hitungTotal();
    }

});


// ==========================================
// EVENT
// ==========================================
layanan.addEventListener('change', hitungTotal);
berat.addEventListener('input', hitungTotal);


// ==========================================
// HITUNG SAAT HALAMAN DIBUKA
// ==========================================
hitungTotal();

</script>

</body>
</html>