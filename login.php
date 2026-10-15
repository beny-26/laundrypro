<?php

session_start();


// Jika sudah login
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($_SESSION['role'] === 'pelanggan') {
        header("Location: pelanggan/dashboard.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - LaundryPro</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;

            padding: 20px;
        }

        .login-card {
            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .logo {
            text-align: center;

            margin-bottom: 30px;
        }

        .logo h1 {
            color: #2563eb;

            font-size: 30px;

            margin-bottom: 8px;
        }

        .logo p {
            color: #64748b;

            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;

            margin-bottom: 8px;

            color: #334155;

            font-size: 14px;

            font-weight: bold;
        }

        .form-group input {
            width: 100%;

            padding: 13px 15px;

            border: 1px solid #cbd5e1;

            border-radius: 8px;

            font-size: 14px;

            outline: none;
        }

        .form-group input:focus {
            border-color: #2563eb;
        }

        .btn-login {
            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #2563eb;

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;
        }

        .btn-login:hover {
            background: #1d4ed8;
        }

        .footer {
            text-align: center;

            margin-top: 20px;

            color: #94a3b8;

            font-size: 13px;
        }

    </style>

<link rel="stylesheet" href="/laundrypro/assets/css/redesign.css?v=2">
</head>

<body>

<div class="login-container">

    <div class="login-card">

    <?php if (isset($_GET['error'])): ?>

    <div style="
        background:#fee2e2;
        color:#b91c1c;
        padding:12px;
        border-radius:8px;
        margin-bottom:20px;
        font-size:14px;
    ">

        <?= htmlspecialchars($_GET['error']); ?>

    </div>

<?php endif; ?>

        <!-- LOGO -->

        <div class="logo">

            <h1>LaundryPro</h1>

            <p>
                Sistem Manajemen Laundry
            </p>

        </div>


        <!-- FORM LOGIN -->

        <form action="proses_login.php"
              method="POST">

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Masukkan email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-login">

                Login

            </button>

        </form>


        <div class="footer">

            © 2026 LaundryPro

        </div>

    </div>

</div>

</body>

</html>