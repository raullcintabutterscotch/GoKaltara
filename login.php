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

<meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Masuk ke GoKaltara Kuliner. Jelajahi katalog kuliner khas Kalimantan Utara."
    >

    <title>Masuk | GoKaltara Kuliner</title>

    <link
        rel="icon"
        type="image/svg+xml"
        href="assets/images/logo.svg"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/login.css?v=3">

<link rel="stylesheet" href="assets/css/lenis.css?v=1">

</head>

<body class="login-body">

<a class="login-skip-link" href="#login-main">Lewati ke formulir masuk</a>

<div class="login-page" id="login-main">

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

                <div class="alert-soft mb-3" role="alert" aria-live="polite">
                    Username atau password salah.
                </div>

            <?php endif; ?>

            <?php if ($error === "db"): ?>

                <div class="alert-soft mb-3" role="alert" aria-live="polite">
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

<script src="assets/js/lenis.js?v=2" defer></script>

</body>
</html>