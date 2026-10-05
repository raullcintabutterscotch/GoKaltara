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
            FROM users
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
                "INSERT INTO users
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

    <link
        rel="stylesheet"
        href="./assets/css/login.css"
    >

</head>

<body>

<div class="form-container">

    <p class="title">
        Create account
    </p>

    <?php if ($pesan !== ""): ?>

        <div class="alert-error">
            <?= htmlspecialchars($pesan) ?>
        </div>

    <?php endif; ?>

    <?php if ($berhasil !== ""): ?>

        <div class="alert-success">
            <?= htmlspecialchars($berhasil) ?>
        </div>

    <?php endif; ?>

    <form
        class="form"
        method="POST"
        action=""
    >

        <input
            type="text"
            name="nama_lengkap"
            class="input"
            placeholder="Nama lengkap"
            required
        >

        <input
            type="text"
            name="username"
            class="input"
            placeholder="Username"
            required
        >

        <input
            type="password"
            name="password"
            class="input"
            placeholder="Password"
            required
        >

        <input
            type="password"
            name="konfirmasi_password"
            class="input"
            placeholder="Konfirmasi password"
            required
        >

        <button
            type="submit"
            class="form-btn"
        >
            Sign Up
        </button>

    </form>

    <p class="sign-up-label">

        Sudah punya akun?

        <a
            href="./login.php"
            class="sign-up-link"
        >
            Login
        </a>

    </p>

    <a
        href="./index.php"
        class="back-login"
    >
        Kembali ke halaman utama
    </a>

</div>

</body>

</html>