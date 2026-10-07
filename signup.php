<?php

session_start();
require_once "./config/koneksi.php";

$pesan = "";
$berhasil = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama_lengkap = trim($_POST["nama_lengkap"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi_password = $_POST["konfirmasi_password"] ?? "";

    if (
        $nama_lengkap === "" ||
        $username === "" ||
        $password === "" ||
        $konfirmasi_password === ""
    ) {
        $pesan = "Semua data wajib diisi.";

    } elseif (strlen($nama_lengkap) < 3) {
        $pesan = "Nama lengkap minimal 3 karakter.";

    } elseif (strlen($username) < 4) {
        $pesan = "Username minimal 4 karakter.";

    } elseif (strlen($password) < 6) {
        $pesan = "Password minimal 6 karakter.";

    } elseif ($password !== $konfirmasi_password) {
        $pesan = "Konfirmasi password tidak sama.";

    } else {

        $cek = $koneksi->prepare(
            "SELECT id_user
            FROM user
            WHERE username = ?
            LIMIT 1"
        );

        $cek->bind_param("s", $username);
        $cek->execute();

        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {

            $pesan = "Username sudah digunakan.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $level = "user";

            $stmt = $koneksi->prepare(
                "INSERT INTO user
                (username, password, nama_lengkap, level)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $username,
                $password_hash,
                $nama_lengkap,
                $level
            );

            if ($stmt->execute()) {

                $berhasil = "Akun berhasil dibuat. Silakan login.";

            } else {

                $pesan = "Gagal membuat akun.";
            }

            $stmt->close();
        }

        $cek->close();
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

    <title>Sign Up | Kuliner Kaltara</title>
    
    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >
    <link rel="stylesheet" href="./assets/css/login.css?v=8">

<link rel="stylesheet" href="assets/css/lenis.css?v=1">

</head>

<body class="login-body signup-body">

<a class="login-skip-link" href="#signup-main">Lewati ke formulir pendaftaran</a>

<div class="login-page" id="signup-main">

    <section class="login-visual">

        <div>

            <p class="eyebrow">
                GoKaltara Kuliner
            </p>

            <h1>
                Mulai jelajahi rasa khas
                Kalimantan Utara.
            </h1>

            <p class="mb-0 text-white-50">
                Buat akun untuk menikmati pengalaman
                menjelajahi katalog kuliner GoKaltara.
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
                Daftar
            </p>

            <h2>
                Buat akun
            </h2>

            <p class="login-copy mb-4">
                Isi data berikut untuk membuat akun GoKaltara Kuliner.
            </p>

            <?php if ($pesan !== ""): ?>

                <div class="alert-soft mb-3" role="alert" aria-live="polite">
                    <?= htmlspecialchars($pesan) ?>
                </div>

            <?php endif; ?>

            <?php if ($berhasil !== ""): ?>

                <div
                    class="alert-soft signup-success mb-3"
                    role="alert"
                    aria-live="polite"
                >
                    <?= htmlspecialchars($berhasil) ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action=""
                autocomplete="on"
            >

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                        for="nama_lengkap"
                    >
                        Nama Lengkap
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="nama_lengkap"
                        name="nama_lengkap"
                        placeholder="Nama lengkap"
                        autocomplete="name"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                        for="username"
                    >
                        Username
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Username"
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
                        placeholder="Password"
                        autocomplete="new-password"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label
                        class="form-label fw-semibold"
                        for="konfirmasi_password"
                    >
                        Konfirmasi Password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="konfirmasi_password"
                        name="konfirmasi_password"
                        placeholder="Konfirmasi password"
                        autocomplete="new-password"
                        required
                    >

                </div>

                <button
                    class="btn btn-brand w-100"
                    type="submit"
                >
                    Daftar
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>

            </form>

            <div class="mt-4 pt-3 border-top small text-muted text-center">

                Sudah punya akun?

                <a href="./login.php">
                    Login
                </a>

            </div>

            <div class="mt-3 text-center small">

                <a href="./index.php">
                    <i class="bi bi-arrow-left me-1"></i>
                    Kembali ke halaman utama
                </a>

            </div>

        </div>

    </section>

</div>
<script src="assets/js/lenis.js?v=2" defer></script>

</body>

</html>