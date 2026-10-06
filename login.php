<?php

declare(strict_types=1);

require_once "config/koneksi.php";

if (
    ($_SESSION["login"] ?? false) === true
) {

    if (
        ($_SESSION["level"] ?? "")
        === "admin"
    ) {
        header(
            "Location: admin/dashboard.php"
        );
        exit;
    }

    header(
        "Location: index.php"
    );
    exit;
}

$page_title =
    "Masuk | GoKaltara Kuliner";

$error =
    $_GET["error"] ?? "";

?>
<!doctype html>
<html lang="id">

<head>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

</head>

<body>

<div class="login-page">

    <section class="login-visual">

        <div>

            <p class="eyebrow">
                GoKaltara Kuliner
            </p>

            <h1>
                Jelajahi rasa khas
                Kalimantan Utara.
            </h1>

            <p class="mb-0 text-white-50">
                Katalog kuliner, asal daerah,
                dan informasi hidangan dalam satu aplikasi.
            </p>

        </div>

    </section>

    <section class="login-panel">

        <div class="login-box">

            <div class="login-brand">

                <span class="brand-logo">

                    <img
                        src="assets/images/logo.svg"
                        alt="GoKaltara"
                    >

                </span>

                <span>

                    <strong>
                        GoKaltara
                    </strong>

                    <small>
                        Kuliner
                    </small>

                </span>

            </div>

            <p class="section-kicker mb-2">
                Masuk
            </p>

            <h2>
                Selamat datang
            </h2>

            <p class="login-copy mb-4">
                Masuk menggunakan akun
                yang tersimpan di database aplikasi.
            </p>

            <?php if ($error === "1"): ?>

                <div class="alert-soft mb-3">
                    Username atau password salah.
                </div>

            <?php endif; ?>

            <?php if ($error === "db"): ?>

                <div class="alert-soft mb-3">
                    Database belum tersambung.
                </div>

            <?php endif; ?>

            <form
                action="proses_login.php"
                method="POST"
                autocomplete="on"
            >

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                        for="username"
                    >
                        Username
                    </label>

                    <input
                        class="form-control"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                        for="password"
                    >
                        Password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button
                    class="btn btn-brand w-100"
                    type="submit"
                >
                    Masuk
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>

            </form>

            <div class="mt-3 small text-muted text-center">

                <i class="bi bi-shield-check me-1"></i>

                Perangkat ini akan diingat
                selama 30 hari.

            </div>

            <div
                class="d-flex justify-content-between
                    mt-4 pt-3 border-top small"
            >

                <a href="signup.php">
                    Belum punya akun? Daftar
                </a>

                <a href="index.php">
                    Beranda
                </a>

            </div>

        </div>

    </section>

</div>

</body>
</html>