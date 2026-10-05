<?php

session_start();
require_once "./config/koneksi.php";


if (isset($_SESSION["login"]) && $_SESSION["login"] === true) {

    if ($_SESSION["level"] === "admin") {
        header("Location: ./admin/dashboard.php");
        exit;
    }

    if ($_SESSION["level"] === "user") {
        header("Location: ./index.php");
        exit;
    }
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    if ($username === "" || $password === "") {

        $pesan = "Username dan password wajib diisi.";
    } else {

        $stmt = $koneksi->prepare(
            "SELECT
                id_user,
                username,
                password,
                nama_lengkap,
                level
            FROM user
            WHERE username = ?
            LIMIT 1"
        );

        if (!$stmt) {

            $pesan = "Terjadi kesalahan pada sistem database.";
        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                if (password_verify($password, $user["password"])) {


                    session_regenerate_id(true);

                    $_SESSION["login"] = true;
                    $_SESSION["id_user"] = $user["id_user"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["nama_lengkap"] = $user["nama_lengkap"];
                    $_SESSION["level"] = $user["level"];



                    if ($user["level"] === "admin") {

                        header("Location: ./admin/dashboard.php");
                        exit;
                    } elseif ($user["level"] === "user") {

                        header("Location: ./index.php");
                        exit;
                    } else {

                        session_unset();
                        session_destroy();

                        $pesan = "Level akun tidak dikenali.";
                    }
                } else {

                    $pesan = "Username atau password salah.";
                }
            } else {

                $pesan = "Username atau password salah.";
            }

            $stmt->close();
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
        content="width=device-width, initial-scale=1.0">

    <title>Login | Kuliner Kaltara</title>
    
    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >

    <link
        rel="stylesheet"
        href="./assets/css/login.css">

</head>

<body>

    <div class="form-container">

        <p class="title">
            Welcome back
        </p>

        <?php if ($pesan !== ""): ?>

            <div class="alert-error">
                <?= htmlspecialchars($pesan) ?>
            </div>

        <?php endif; ?>

        <form
            class="form"
            method="POST"
            action="">

            <input
                type="text"
                name="username"
                class="input"
                placeholder="Username"
                autocomplete="username"
                required>

            <input
                type="password"
                name="password"
                class="input"
                placeholder="Password"
                autocomplete="current-password"
                required>

            <p class="page-link">

                <a
                    href="./lupa_password.php"
                    class="page-link-label">
                    Forgot Password?
                </a>

            </p>

            <button
                type="submit"
                class="form-btn">
                Log in
            </button>

        </form>

        <p class="sign-up-label">

            Don't have an account?

            <a
                href="./signup.php"
                class="sign-up-link">
                Sign up
            </a>

        </p>

        <div class="buttons-container">

        <a
        href="./index.php"
        class="back-login"
    >
        Kembali ke halaman utama
    </a>
        </div>

    </div>

</body>

</html>