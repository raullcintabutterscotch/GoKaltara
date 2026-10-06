<?php

declare(strict_types=1);

require_once "config/koneksi.php";

if (($_SESSION["login"] ?? false) === true) {
    if (($_SESSION["level"] ?? "") === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }

    header("Location: index.php");
    exit;
}

$pesan = "";
$berhasil = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_lengkap = trim((string) ($_POST["nama_lengkap"] ?? ""));
    $username = trim((string) ($_POST["username"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $konfirmasi_password = (string) ($_POST["konfirmasi_password"] ?? "");

    if ($nama_lengkap === "" || $username === "" || $password === "" || $konfirmasi_password === "") {
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
        $cek = $koneksi->prepare("
            SELECT id_user
            FROM user
            WHERE username = ?
            LIMIT 1
        ");

        if (!$cek) {
            $pesan = "Database tidak dapat memproses pendaftaran.";
        } else {
            $cek->bind_param("s", $username);
            $cek->execute();
            $hasil = $cek->get_result();

            if ($hasil && $hasil->num_rows > 0) {
                $pesan = "Username sudah digunakan.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $level = "user";

                $stmt = $koneksi->prepare("
                    INSERT INTO user
                        (username, password, nama_lengkap, level)
                    VALUES (?, ?, ?, ?)
                ");

                if (!$stmt) {
                    $pesan = "Gagal membuat akun.";
                } else {
                    $stmt->bind_param("ssss", $username, $password_hash, $nama_lengkap, $level);

                    if ($stmt->execute()) {
                        $berhasil = "Akun berhasil dibuat. Silakan login.";
                    } else {
                        $pesan = "Gagal membuat akun.";
                    }

                    $stmt->close();
                }
            }

            $cek->close();
        }
    }
}

?>
<!doctype html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Buat akun GoKaltara Kuliner untuk menyimpan favorit dan berinteraksi dengan katalog kuliner Kalimantan Utara."
    >

    <title>Daftar | GoKaltara Kuliner</title>

    <link rel="icon" type="image/svg+xml" href="assets/images/logo.svg">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="assets/css/login.css?v=8">

</head>

<body class="login-body">

<a class="login-skip-link" href="#signup-main">Lewati ke formulir pendaftaran</a>

<div class="login-page" id="signup-main">

    <section class="login-visual">

        <div>

            <p class="eyebrow">
                GoKaltara Kuliner
            </p>

            <h1>
                Kenali rasa khas
                Kalimantan Utara.
            </h1>

            <p class="mb-0 text-white-50">
                Buat akun untuk menyimpan kuliner favorit
                dan ikut berinteraksi di dalam katalog.
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
                    <strong>GoKaltara</strong>
                    <small>Kuliner</small>
                </span>

            </div>

            <p class="section-kicker mb-2">
                Daftar
            </p>

            <h2>
                Buat akun
            </h2>

            <p class="login-copy mb-4">
                Daftarkan akun untuk menggunakan fitur
                favorit, rating, dan komentar.
            </p>

            <?php if ($pesan !== ""): ?>
                <div class="alert-soft mb-3" role="alert" aria-live="polite">
                    <?= htmlspecialchars($pesan, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <?php if ($berhasil !== ""): ?>
                <div class="alert-success-soft mb-3" role="status" aria-live="polite">
                    <?= htmlspecialchars($berhasil, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form
                action="signup.php"
                method="POST"
                autocomplete="on"
            >

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="nama_lengkap">Nama Lengkap</label>
                    <input
                        class="form-control"
                        id="nama_lengkap"
                        name="nama_lengkap"
                        type="text"
                        autocomplete="name"
                        value="<?= htmlspecialchars($_POST["nama_lengkap"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="username">Username</label>
                    <input
                        class="form-control"
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        value="<?= htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <input
                        class="form-control"
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="konfirmasi_password">Konfirmasi Password</label>
                    <input
                        class="form-control"
                        id="konfirmasi_password"
                        name="konfirmasi_password"
                        type="password"
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

            <div class="d-flex justify-content-between mt-4 pt-3 border-top small">
                <a href="login.php">
                    Sudah punya akun? Masuk
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
