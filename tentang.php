<?php
session_start();
require_once "config/koneksi.php";

$is_login = isset($_SESSION['login']) &&
    (
        $_SESSION['login'] === true ||
        $_SESSION['login'] === 1 ||
        $_SESSION['login'] === '1'
    );

$nama_user = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengguna';
$level_user = $_SESSION['level'] ?? 'user';

$nama_tampilan = htmlspecialchars(
    $nama_user,
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Tentang GoKaltara Kuliner dan tujuan pengembangan katalog kuliner Kalimantan Utara."
    >

    <title>
        Tentang | GoKaltara Kuliner
    </title>

    <link
        rel="icon"
        href="assets/images/logo.svg"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/tentang.css?v=4">

    <link rel="stylesheet" href="assets/css/notifikasi.css?v=61">
    <link rel="stylesheet" href="assets/css/footer.css?v=2">
    <noscript>
        <link rel="stylesheet" href="assets/css/footer.css?v=2">
    </noscript>
    <noscript>
        </noscript>

    <link rel="stylesheet" href="assets/css/lenis.css?v=1">

</head>

<body>

<nav class="public-navbar">

    <div class="container">

        <a
            href="index.php"
            class="brand-public"
        >

            <span class="brand-logo">

                <img
                    src="assets/images/logo.svg"
                    alt="Logo GoKaltara Kuliner" loading="eager" decoding="async" width="58" height="58">

            </span>

            <span>
                GoKaltara
                <strong>Kuliner</strong>
            </span>

        </a>

        <div class="desktop-menu">

            <a
                href="index.php"
                class="nav-item-public"
            >
                Beranda
            </a>

            <a
                href="katalog.php"
                class="nav-item-public"
            >
                Katalog
            </a>

            <a
                href="tentang.php"
                class="nav-item-public active"
            >
                Tentang
            </a>

            <a
                href="pencarian.php"
                class="nav-item-public"
            >
                Pencarian
            </a>

            <?php if ($is_login): ?>

                <a
                    href="notifikasi.php"
                    class="notification-nav"
                 aria-label="Notifikasi">
                    <i class="bi bi-bell"></i>
                

                    <span class="notification-badge" hidden>0</span>

                </a>

            <?php endif; ?>

            <?php if ($is_login): ?>

                <?php if ($level_user === 'admin'): ?>

                    <a
                        href="admin/dashboard.php"
                        class="btn-admin"
                    >

                        <i class="bi bi-person"></i>

                        <span>

                            <?= $nama_tampilan ?>

                            <small>
                                Admin
                            </small>

                        </span>

                    </a>

                <?php else: ?>

                    <a
                        href="profil-user.php"
                        class="btn-admin"
                    >

                        <i class="bi bi-person"></i>

                        <span>
                            <?= $nama_tampilan ?>
                        </span>

                    </a>

                <?php endif; ?>

            <?php else: ?>

                <a
                    href="login.php"
                    class="btn-admin"
                >

                    <i class="bi bi-person"></i>

                    <span>
                        Login
                    </span>

                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

<main>

    <section class="about-hero">

        <div class="container">

            <div class="about-hero-content">

                <span>
                    Tentang GoKaltara Kuliner
                </span>

                <h1>
                    Mengenal Kaltara
                    <br>
                    Lewat Rasa
                </h1>

                <p>
                    GoKaltara Kuliner adalah katalog digital yang
                    dirancang untuk memperkenalkan keberagaman kuliner
                    khas Kalimantan Utara berdasarkan daerah asal,
                    kategori, bahan utama, penyajian, serta cerita
                    dan nilai budaya di baliknya.
                </p>

            </div>

        </div>

    </section>

    <section class="about-content-section">

        <div class="container">

            <div class="about-grid">

                <div class="about-card">

                    <span class="about-card-number">
                        01
                    </span>

                    <h2>
                        Tentang Website
                    </h2>

                    <p>
                        GoKaltara Kuliner menjadi tempat untuk
                        menemukan informasi berbagai makanan,
                        minuman, kue tradisional, dan camilan
                        dari Kalimantan Utara dalam satu katalog.
                    </p>

                </div>

                <div class="about-card">

                    <span class="about-card-number">
                        02
                    </span>

                    <h2>
                        Tujuan
                    </h2>

                    <p>
                        Website ini dibuat untuk membantu pengguna
                        mengenal kuliner daerah dengan informasi
                        yang lebih terstruktur dan mudah dicari.
                    </p>

                </div>

                <div class="about-card">

                    <span class="about-card-number">
                        03
                    </span>

                    <h2>
                        Nilai Budaya
                    </h2>

                    <p>
                        Kuliner tidak hanya tentang makanan,
                        tetapi juga berkaitan dengan kebiasaan,
                        bahan lokal, sejarah, dan identitas
                        masyarakat di setiap daerah.
                    </p>

                </div>

            </div>

        </div>

    </section>

    <section class="about-feature-section">

        <div class="container">

            <div class="section-heading">

                <span>
                    Fitur
                </span>

                <h2>
                    Apa yang Bisa Dilakukan?
                </h2>

                <p>
                    Beberapa fitur utama yang tersedia dalam
                    GoKaltara Kuliner.
                </p>

            </div>

            <div class="about-feature-grid">

                <div class="about-feature">

                    <div class="about-feature-icon">
                        <i class="bi bi-grid"></i>
                    </div>

                    <h3>
                        Katalog Kuliner
                    </h3>

                    <p>
                        Menampilkan koleksi kuliner dalam bentuk
                        kartu dengan foto, nama, kategori,
                        dan daerah asal.
                    </p>

                </div>

                <div class="about-feature">

                    <div class="about-feature-icon">
                        <i class="bi bi-search"></i>
                    </div>

                    <h3>
                        Pencarian
                    </h3>

                    <p>
                        Memudahkan pengguna menemukan kuliner
                        berdasarkan nama, daerah, dan kategori.
                    </p>

                </div>

                <div class="about-feature">

                    <div class="about-feature-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>

                    <h3>
                        Detail Kuliner
                    </h3>

                    <p>
                        Menampilkan informasi lebih lengkap
                        mengenai bahan utama, penyajian,
                        sejarah, dan deskripsi.
                    </p>

                </div>

            </div>

        </div>

    </section>

    <section class="about-cta-section">

        <div class="container">

            <div class="about-cta">

                <div>

                    <span>
                        Mulai Menjelajah
                    </span>

                    <h2>
                        Temukan cerita di balik
                        setiap kuliner.
                    </h2>

                    <p>
                        Jelajahi katalog dan kenali lebih dekat
                        kuliner khas Kalimantan Utara.
                    </p>

                </div>

                <a
                    href="katalog.php"
                    class="hero-button"
                >

                    Buka Katalog

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>

        </div>

    </section>

</main>

<footer class="public-footer">
        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; 2026 GoKaltara | Muhammad Raul Zia Parsa | All Rights Reserved.
            </p>
        </div>
    </div>
</footer>

<nav class="mobile-public-nav">

    <a
        href="index.php"
        class="mobile-public-link"
    >
        <i class="bi bi-house-fill"></i>
        <span>Beranda</span>
    </a>

    <a
        href="katalog.php"
        class="mobile-public-link"
    >
        <i class="bi bi-fork-knife"></i>
        <span>Katalog</span>
    </a>

    <a
        href="pencarian.php"
        class="mobile-public-link"
    >
        <i class="bi bi-search"></i>
        <span>Cari</span>
    </a>

    <a
        href="tentang.php"
        class="mobile-public-link active"
    >
        <i class="bi bi-info-circle-fill"></i>
        <span>Tentang</span>
    </a>

    <?php if ($is_login): ?>

        <?php if ($level_user === 'admin'): ?>

            <a
                href="admin/dashboard.php"
                class="mobile-public-link"
            >
                <i class="bi bi-person-fill"></i>
                <span>Admin</span>
            </a>

        <?php else: ?>

            <a
                href="profil-user.php"
                class="mobile-public-link"
            >
                <i class="bi bi-person-fill"></i>
                <span>Profile</span>
            </a>

        <?php endif; ?>

    <?php else: ?>

        <a
            href="login.php"
            class="mobile-public-link"
        >
            <i class="bi bi-person-fill"></i>
            <span>Login</span>
        </a>

    <?php endif; ?>
</nav>

    <script
        src="assets/js/notifikasi.js?v=60"
     defer></script>
    <script src="assets/js/lenis.js?v=1" defer></script>

</body>
</html>