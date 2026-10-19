<?php

$currentPath = $_SERVER['PHP_SELF'];

function activeUserMenu($keyword, $currentPath)
{
    return strpos($currentPath, $keyword) !== false ? 'active' : '';
}

?>

<style>

    /* =========================================
       SIDEBAR PELANGGAN
    ========================================= */

    .lp-user-sidebar {
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


    /* =========================================
       BRAND
    ========================================= */

    .lp-user-brand {
        height: 75px;
        display: flex;
        align-items: center;
        padding: 0 22px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .lp-user-brand-icon {
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

    .lp-user-brand-text {
        font-size: 20px;
        font-weight: 700;
    }


    /* =========================================
       MENU TITLE
    ========================================= */

    .lp-user-menu-title {
        padding: 25px 22px 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,.5);
    }


    /* =========================================
       MENU
    ========================================= */

    .lp-user-menu {
        padding: 0 12px;
    }

    .lp-user-menu a {
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

    .lp-user-menu a i {
        width: 20px;
        text-align: center;
        font-size: 17px;
    }

    .lp-user-menu a:hover {
        background: rgba(255,255,255,.1);
        color: white;
    }

    .lp-user-menu a.active {
        background: #2563eb;
        color: white;
        box-shadow: 0 4px 10px rgba(37,99,235,.25);
    }


    /* =========================================
       DIVIDER
    ========================================= */

    .lp-user-divider {
        height: 1px;
        background: rgba(255,255,255,.1);
        margin: 18px 12px;
    }


    /* =========================================
       LOGOUT
    ========================================= */

    .lp-user-logout {
        color: #fecaca !important;
    }

    .lp-user-logout:hover {
        background: rgba(220,38,38,.15) !important;
        color: #fff !important;
    }


    /* =========================================
       SCROLLBAR
    ========================================= */

    .lp-user-sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .lp-user-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .lp-user-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,.2);
        border-radius: 10px;
    }


    /* =========================================
       MOBILE
    ========================================= */

    @media (max-width: 768px) {

        .lp-user-sidebar {
            width: 70px;
        }

        .lp-user-brand {
            padding: 0;
            justify-content: center;
        }

        .lp-user-brand-icon {
            margin: 0;
        }

        .lp-user-brand-text {
            display: none;
        }

        .lp-user-menu-title {
            display: none;
        }

        .lp-user-menu {
            padding: 12px 8px;
        }

        .lp-user-menu a {
            justify-content: center;
            padding: 12px 5px;
        }

        .lp-user-menu a span {
            display: none;
        }

        .lp-user-menu a i {
            margin: 0;
        }

    }

</style>


<aside class="lp-user-sidebar">


    <!-- =====================================
         BRAND
    ====================================== -->

    <div class="lp-user-brand">

        <div class="lp-user-brand-icon">

            <i class="bi bi-droplet-fill"></i>

        </div>

        <div class="lp-user-brand-text">
            LaundryPro
        </div>

    </div>


    <!-- =====================================
         MENU UTAMA
    ====================================== -->

    <div class="lp-user-menu-title">
        Menu Utama
    </div>


    <div class="lp-user-menu">


        <!-- DASHBOARD -->

        <a
            href="/laundrypro/user/dashboard.php"
            class="<?= activeUserMenu('/user/dashboard.php', $currentPath) ?>"
        >

            <i class="bi bi-speedometer2"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- TAGIHAN -->

        <a
            href="/laundrypro/user/tagihan/index.php"
            class="<?= activeUserMenu('/user/tagihan/', $currentPath) ?>"
        >

            <i class="bi bi-receipt"></i>

            <span>
                Tagihan Saya
            </span>

        </a>


        <!-- PEMBAYARAN -->

        <a
            href="/laundrypro/user/pembayaran/riwayat.php"
            class="<?= activeUserMenu('/user/pembayaran/', $currentPath) ?>"
        >

            <i class="bi bi-credit-card"></i>

            <span>
                Riwayat Pembayaran
            </span>

        </a>


        <!-- DIVIDER -->

        <div class="lp-user-divider"></div>


        <!-- LOGOUT -->

        <a
            href="/laundrypro/logout.php"
            class="lp-user-logout"
            onclick="return confirm('Apakah Anda yakin ingin logout?')"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>


    </div>

</aside>