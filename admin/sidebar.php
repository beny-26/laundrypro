<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];

function activeMenu($keyword, $currentPath)
{
    return strpos($currentPath, $keyword) !== false ? 'active' : '';
}
?>

<style>
    /* ==========================================
       SIDEBAR LAUNDRYPRO
       ========================================== */

    .lp-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        background: #142b5f;
        color: white;
        z-index: 1000;
        overflow-y: auto;
        box-shadow: 3px 0 15px rgba(0,0,0,.08);
    }

    .lp-brand {
        height: 75px;
        display: flex;
        align-items: center;
        padding: 0 22px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .lp-brand-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 20px;
    }

    .lp-brand-text {
        font-size: 20px;
        font-weight: 700;
    }

    .lp-menu-title {
        padding: 25px 22px 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,.5);
    }

    .lp-menu {
        padding: 0 12px;
    }

    .lp-menu a {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 12px 14px;
        margin-bottom: 5px;
        color: rgba(255,255,255,.75);
        text-decoration: none;
        border-radius: 9px;
        font-size: 14px;
        transition: all .2s ease;
    }

    .lp-menu a i {
        width: 20px;
        text-align: center;
        font-size: 17px;
    }

    .lp-menu a:hover {
        background: rgba(255,255,255,.1);
        color: white;
    }

    .lp-menu a.active {
        background: #2563eb;
        color: white;
        box-shadow: 0 4px 10px rgba(37,99,235,.25);
    }

    .lp-menu-divider {
        height: 1px;
        background: rgba(255,255,255,.1);
        margin: 18px 12px;
    }

    .lp-logout {
        color: #fecaca !important;
    }

    .lp-logout:hover {
        background: rgba(220,38,38,.15) !important;
        color: #fff !important;
    }

    /* Scrollbar */
    .lp-sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .lp-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .lp-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,.2);
        border-radius: 10px;
    }

    /* Mobile */
    @media (max-width: 768px) {

        .lp-sidebar {
            width: 220px;
        }

        .lp-brand-text {
            font-size: 18px;
        }
    }
</style>


<!-- ==========================================
     SIDEBAR
     ========================================== -->

<aside class="lp-sidebar">

    <!-- BRAND -->
    <div class="lp-brand">

        <div class="lp-brand-icon">
            <i class="bi bi-droplet-fill"></i>
        </div>

        <div class="lp-brand-text">
            LaundryPro
        </div>

    </div>


    <!-- MENU TITLE -->
    <div class="lp-menu-title">
        Menu Utama
    </div>


    <!-- MENU -->
    <div class="lp-menu">

        <!-- DASHBOARD -->
        <a
            href="/laundrypro/admin/dashboard.php"
            class="<?= activeMenu('/admin/dashboard.php', $currentPath) ?>"
        >
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>


        <!-- PELANGGAN -->
        <a
            href="/laundrypro/admin/pelanggan/index.php"
            class="<?= activeMenu('/admin/pelanggan/', $currentPath) ?>"
        >
            <i class="bi bi-people"></i>
            <span>Pelanggan</span>
        </a>


        <!-- TAGIHAN -->
        <a
            href="/laundrypro/admin/tagihan/index.php"
            class="<?= activeMenu('/admin/tagihan/', $currentPath) ?>"
        >
            <i class="bi bi-receipt"></i>
            <span>Tagihan</span>
        </a>


        <!-- PEMBAYARAN -->
        <a
            href="/laundrypro/admin/pembayaran/index.php"
            class="<?= activeMenu('/admin/pembayaran/', $currentPath) ?>"
        >
            <i class="bi bi-credit-card"></i>
            <span>Pembayaran</span>
        </a>


        <!-- ASET TETAP -->
        <a
            href="/laundrypro/admin/aset/index.php"
            class="<?= activeMenu('/admin/aset/', $currentPath) ?>"
        >
            <i class="bi bi-building"></i>
            <span>Aset Tetap</span>
        </a>


        <!-- LAPORAN -->
        <a
            href="/laundrypro/admin/laporan/index.php"
            class="<?= activeMenu('/admin/laporan/', $currentPath) ?>"
        >
            <i class="bi bi-bar-chart"></i>
            <span>Laporan</span>
        </a>


        <div class="lp-menu-divider"></div>


        <!-- LOGOUT -->
        <a
            href="/laundrypro/logout.php"
            class="lp-logout"
            onclick="return confirm('Apakah Anda yakin ingin logout?')"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>