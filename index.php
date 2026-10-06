<?php
session_start();
require_once "config/koneksi.php";
require_once "config/image_optimizer.php";

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

function e($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function fotoKuliner($foto, $thumbnail = false)
{
    return gokaltara_image_url(
        (string) ($foto ?? ""),
        "assets/images/",
        (bool) $thumbnail
    );
}

function renderFoodCards($koneksi, $kategori_id = 0)
{
    if ($kategori_id > 0) {
        $stmt = $koneksi->prepare("
            SELECT
                k.id_kuliner,
                k.nama_kuliner,
                k.id_kategori,
                k.asal_daerah,
                k.foto,
                k.deskripsi,
                c.nama_kategori
            FROM kuliner AS k
            LEFT JOIN kategori AS c
                ON k.id_kategori = c.id_kategori
            WHERE k.id_kategori = ?
            ORDER BY k.id_kuliner DESC
            LIMIT 6
        ");

        if (!$stmt) {
            return;
        }

        $stmt->bind_param(
            "i",
            $kategori_id
        );

        $stmt->execute();

        $result = $stmt->get_result();
    } else {
        $result = $koneksi->query("
            SELECT
                k.id_kuliner,
                k.nama_kuliner,
                k.id_kategori,
                k.asal_daerah,
                k.foto,
                k.deskripsi,
                c.nama_kategori
            FROM kuliner AS k
            LEFT JOIN kategori AS c
                ON k.id_kategori = c.id_kategori
            ORDER BY k.id_kuliner DESC
            LIMIT 6
        ");
    }

    if (!$result || $result->num_rows === 0) {
        echo '
            <div class="col-12">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Belum ada kuliner</h3>
                    <p>
                        Belum ada kuliner pada kategori yang dipilih.
                    </p>
                </div>
            </div>
        ';

        if (isset($stmt)) {
            $stmt->close();
        }

        return;
    }

    while ($data = $result->fetch_assoc()) {
        $foto = fotoKuliner(
            $data['foto'],
            true
        );

        $deskripsi = strip_tags(
            $data['deskripsi'] ?? ''
        );

        if (mb_strlen($deskripsi) > 85) {
            $deskripsi = mb_strimwidth(
                $deskripsi,
                0,
                85,
                '...'
            );
        }

        echo '
            <div class="col-6 col-sm-6 col-lg-4 food-card-col">
                <article class="food-card">
                    <a
                        href="detail.php?id=' . (int) $data['id_kuliner'] . '"
                        class="food-image-wrap"
                    >
                        <img
                            src="' . e($foto) . '"
                            alt="' . e($data['nama_kuliner']) . '"
                            loading="lazy"
                            decoding="async">

                        <span class="food-category">
                            ' . e($data['nama_kategori'] ?: 'Kuliner') . '
                        </span>
                    </a>

                    <div class="food-card-body">

                        <div class="food-location">
                            <i class="bi bi-geo-alt"></i>

                            ' . e(
                                $data['asal_daerah'] ?: 'Kalimantan Utara'
                            ) . '
                        </div>

                        <h3>
                            ' . e(
                                $data['nama_kuliner']
                            ) . '
                        </h3>

                        <p>
                            ' . e(
                                $deskripsi
                            ) . '
                        </p>

                        <a
                            href="detail.php?id=' . (int) $data['id_kuliner'] . '"
                            class="detail-link"
                        >
                            Lihat Detail
                            <i class="bi bi-arrow-up-right"></i>
                        </a>

                    </div>
                </article>
            </div>
        ';
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}

if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'kuliner'
) {
    $kategori_ajax = max(
        0,
        (int) ($_GET['kategori'] ?? 0)
    );

    renderFoodCards(
        $koneksi,
        $kategori_ajax
    );

    exit;
}

$query_kategori = $koneksi->query("
    SELECT
        id_kategori,
        nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");
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
        content="GoKaltara Kuliner, katalog kuliner khas Kalimantan Utara."
    >

    <title>
        GoKaltara Kuliner
    </title>

    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >

    <link
        rel="preload"
        as="image"
        href="assets/images/carousel/Kepiting-Soka.webp"
        fetchpriority="high"
    >

    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/index.css?v=12">

<?php if ($is_login): ?>

    <link rel="stylesheet" href="assets/css/notifikasi.css?v=61">

<?php endif; ?>

</head>

<body>

<a class="skip-link" href="#main-content">Lewati ke konten utama</a>

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
                class="nav-item-public active"
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
                class="nav-item-public"
            >
                Tentang
            </a>

            <a
                href="pencarian.php"
                class="nav-item-public"
            >
                Pencarian
            </a>
            
            <a
                    href="notifikasi.php"
                    class="notification-nav"
                    aria-label="Notifikasi"
                >

                    <i class="bi bi-bell"></i>

                

                    <span class="notification-badge" hidden>0</span>

                </a>

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

<main id="main-content">

<section class="hero-section">

    <div class="container">

        <div
            id="heroCarousel"
            class="carousel slide hero-carousel"
            data-bs-ride="carousel"
            data-bs-interval="4500"
        >

            <div class="carousel-indicators">

                <button
                    type="button"
                    data-bs-target="#heroCarousel"
                    data-bs-slide-to="0"
                    class="active"
                    aria-current="true"
                    aria-label="Slide 1"
                ></button>

                <button
                    type="button"
                    data-bs-target="#heroCarousel"
                    data-bs-slide-to="1"
                    aria-label="Slide 2"
                ></button>

                <button
                    type="button"
                    data-bs-target="#heroCarousel"
                    data-bs-slide-to="2"
                    aria-label="Slide 3"
                ></button>

            </div>

            <div class="carousel-inner">

                <div class="carousel-item active">

                    <div
                        class="hero-slide hero-slide-1"
                        style="background-image: url('assets/images/carousel/Kepiting-Soka.webp');"
                    >

                        <div class="hero-overlay"></div>

                        <div class="hero-content">

                            <span class="hero-kicker">
                                Jelajah Rasa Kalimantan Utara
                            </span>

                            <h1>
                                Kuliner Kaltara
                                <br>
                                Dalam Satu Tempat
                            </h1>

                            <p>
                                Temukan makanan, minuman, kue tradisional,
                                dan camilan khas dari berbagai wilayah
                                Kalimantan Utara.
                            </p>

                            <a
                                href="katalog.php"
                                class="hero-button"
                            >
                                Jelajahi Katalog
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </div>

                <div class="carousel-item">

                    <div
                        class="hero-slide hero-slide-2"
                        data-bg="assets/images/carousel/nasi-subut.webp"
                    >

                        <div class="hero-overlay"></div>

                        <div class="hero-content">

                            <span class="hero-kicker">
                                Dari Bulungan hingga Nunukan
                            </span>

                            <h1>
                                Kenali
                                <br>
                                Kekayaan Rasa Kaltara
                            </h1>

                            <p>
                                Kenali asal daerah dan cerita di balik
                                kuliner khas Kalimantan Utara.
                            </p>

                            <a
                                href="pencarian.php"
                                class="hero-button"
                            >
                                Cari Kuliner
                                <i class="bi bi-search"></i>
                            </a>

                        </div>

                    </div>

                </div>

                <div class="carousel-item">

                    <div
                        class="hero-slide hero-slide-3"
                        data-bg="assets/images/carousel/kue-lapis.webp"
                    >

                        <div class="hero-overlay"></div>

                        <div class="hero-content">

                            <span class="hero-kicker">
                                Warisan Kuliner Daerah
                            </span>

                            <h1>
                                Rasa,
                                <br>
                                Cerita, dan Budaya
                            </h1>

                            <p>
                                Satu katalog untuk mengenalkan keberagaman
                                kuliner Kalimantan Utara.
                            </p>

                            <a
                                href="tentang.php"
                                class="hero-button"
                            >
                                Tentang Aplikasi
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

            <button
                class="carousel-control-prev"
                type="button"
                aria-label="Slide sebelumnya"
                data-bs-target="#heroCarousel"
                data-bs-slide="prev"
            >

                <span
                    class="carousel-control-prev-icon"
                    aria-hidden="true"
                ></span>

            </button>

            <button
                class="carousel-control-next"
                type="button"
                aria-label="Slide berikutnya"
                data-bs-target="#heroCarousel"
                data-bs-slide="next"
            >

                <span
                    class="carousel-control-next-icon"
                    aria-hidden="true"
                ></span>

            </button>

        </div>

    </div>

</section>

<section class="intro-section">

    <div class="container">

        <div class="section-heading">

            <span>
                Eksplorasi
            </span>

            <h2>
                Temukan Kuliner Khas Kaltara
            </h2>

            <p>
                Jelajahi berbagai hidangan khas Kalimantan Utara
                berdasarkan kategori dan daerah asalnya.
            </p>

        </div>

        <div class="category-scroll">

            <button
                type="button"
                class="category-chip active"
                data-kategori="0"
            >
                Semua
            </button>

            <?php if ($query_kategori): ?>

                <?php while ($kategori = $query_kategori->fetch_assoc()): ?>

                    <button
                        type="button"
                        class="category-chip"
                        data-kategori="<?= (int) $kategori['id_kategori'] ?>"
                    >
                        <?= e($kategori['nama_kategori']) ?>
                    </button>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>

    </div>

</section>

<section class="featured-section">

    <div class="container">

        <div class="section-heading section-heading-row">

            <div>

                <span>
                    Pilihan Terbaru
                </span>

                <h2 id="featuredTitle">
                    Kuliner Unggulan
                </h2>

            </div>

            <a
                href="katalog.php"
                class="section-link"
            >
                Lihat Semua
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>

        <div
            id="featuredGrid"
            class="row g-4"
            aria-live="polite"
        >

            <?php renderFoodCards($koneksi, 0); ?>

        </div>

    </div>

</section>

<section class="about-strip">

    <div class="container">

        <div class="about-strip-inner">

            <div>

                <span>
                    Tentang GoKaltara Kuliner
                </span>

                <h2>
                    Mengenalkan rasa dan cerita
                    Kalimantan Utara.
                </h2>

                <p>
                    Sebuah katalog digital untuk membantu masyarakat
                    mengenal keberagaman kuliner khas Kalimantan Utara.
                </p>

            </div>

            <a
                href="tentang.php"
                class="outline-button"
            >
                Selengkapnya
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
        class="mobile-public-link active"
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
        class="mobile-public-link"
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
                <span>Profil</span>
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
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
 defer></script>

<script
    src="assets/js/index.js?v=10"
 defer></script>

<?php if ($is_login): ?>

    <script
        src="assets/js/notifikasi.js?v=60"
        defer
    ></script>

<?php endif; ?>
</body>
</html>