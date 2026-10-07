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

$q = trim($_GET['q'] ?? '');
$daerah = trim($_GET['daerah'] ?? '');
$kategori_id = (int) ($_GET['kategori'] ?? 0);

$query_kategori = $koneksi->query("
    SELECT
        id_kategori,
        nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

$query_daerah = $koneksi->query("
    SELECT DISTINCT
        asal_daerah
    FROM kuliner
    WHERE asal_daerah IS NOT NULL
    AND asal_daerah != ''
    ORDER BY asal_daerah ASC
");

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "
        (
            k.nama_kuliner LIKE ?
            OR k.asal_daerah LIKE ?
            OR k.deskripsi LIKE ?
            OR k.bahan_utama LIKE ?
        )
    ";

    $search = "%" . $q . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "ssss";
}

if ($daerah !== '') {
    $where[] = "k.asal_daerah = ?";
    $params[] = $daerah;
    $types .= "s";
}

if ($kategori_id > 0) {
    $where[] = "k.id_kategori = ?";
    $params[] = $kategori_id;
    $types .= "i";
}

$sql = "
    SELECT
        k.id_kuliner,
        k.nama_kuliner,
        k.id_kategori,
        k.asal_daerah,
        k.deskripsi,
        k.bahan_utama,
        k.foto,
        c.nama_kategori
    FROM kuliner AS k
    LEFT JOIN kategori AS c
        ON k.id_kategori = c.id_kategori
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY k.id_kuliner DESC";

$query_hasil = false;

if (!empty($params)) {
    $stmt = $koneksi->prepare($sql);

    if ($stmt) {
        $bind = [];
        $bind[] = $types;

        foreach ($params as $key => $value) {
            $bind[] = &$params[$key];
        }

        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind
        );

        if ($stmt->execute()) {
            $query_hasil = $stmt->get_result();
        }

        $stmt->close();
    }
} else {
    $query_hasil = $koneksi->query($sql);
}

$total_hasil = $query_hasil
    ? $query_hasil->num_rows
    : 0;

function fotoKuliner($foto, $thumbnail = false)
{
    return gokaltara_image_url(
        (string) ($foto ?? ""),
        "assets/images/",
        (bool) $thumbnail
    );
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

    <meta
        name="description"
        content="Cari kuliner khas Kalimantan Utara berdasarkan nama, daerah, kategori, dan bahan utama."
    >

    <title>Pencarian | GoKaltara Kuliner</title>

    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/pencarian.css?v=5">
    <?php if ($is_login): ?>
        <link rel="stylesheet" href="assets/css/notifikasi.css?v=61">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/footer.css?v=2">

    <link rel="stylesheet" href="assets/css/lenis.css?v=2" media="(min-width: 992px)">

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
                class="nav-item-public"
            >
                Tentang
            </a>

            <a
                href="pencarian.php"
                class="nav-item-public active"
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

<main class="search-main">

    <section class="search-page-section">

        <div class="container">

            <div class="search-heading">

                <span>
                    Eksplorasi
                </span>

                <h1>
                    Cari Kuliner Kaltara
                </h1>

                <p>
                    Ketik nama kuliner, daerah, bahan utama,
                    atau gunakan filter kategori dan daerah.
                </p>

            </div>

            <div class="search-main-form">

                <div class="search-input-wrap">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        id="realtimeSearch"
                        value="<?= htmlspecialchars(
                            $q,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Ketik untuk mencari kuliner..."
                        autocomplete="off"
                    >

                </div>


            </div>

            <form
                action="pencarian.php"
                method="GET"
                class="search-filter-row"
            >

                <select
                    id="filterKategori"
                    name="kategori"
                    class="search-select"
                >

                    <option value="">
                        Semua Kategori
                    </option>

                    <?php if ($query_kategori): ?>

                        <?php while ($kategori = $query_kategori->fetch_assoc()): ?>

                            <option
                                value="<?= (int) $kategori['id_kategori'] ?>"
                                <?= $kategori_id === (int) $kategori['id_kategori']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $kategori['nama_kategori'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

                <select
                    id="filterDaerah"
                    name="daerah"
                    class="search-select"
                >

                    <option value="">
                        Semua Daerah
                    </option>

                    <?php if ($query_daerah): ?>

                        <?php while ($item = $query_daerah->fetch_assoc()): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $item['asal_daerah'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $daerah === $item['asal_daerah']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $item['asal_daerah'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>
            </form>

        </div>

    </section>

    <section class="search-result-section">

        <div class="container">

            <div class="search-result-top">

                <div>

                    <span>
                        HASIL PENCARIAN
                    </span>

                    <h2 id="resultTitle">

                        <?php if ($q !== ''): ?>

                            Hasil untuk
                            “<?= htmlspecialchars(
                                $q,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>”

                        <?php else: ?>

                            Semua Kuliner

                        <?php endif; ?>

                    </h2>

                </div>

                <div class="result-count">

                    <strong id="resultCount">
                        <?= $total_hasil ?>
                    </strong>

                    kuliner

                </div>

            </div>

            <div
                class="row g-4"
                id="searchResults"
            >

                <?php if ($query_hasil && $query_hasil->num_rows > 0): ?>

                    <?php while ($data = $query_hasil->fetch_assoc()): ?>

                        <?php

                        $foto = fotoKuliner(
                            $data['foto'],
                            true
                        );

                        $deskripsi = strip_tags(
                            $data['deskripsi'] ?? ''
                        );

                        $deskripsi = mb_strimwidth(
                            $deskripsi,
                            0,
                            95,
                            '...'
                        );

                        $nama_data = strtolower(
                            $data['nama_kuliner'] ?? ''
                        );

                        $daerah_data = strtolower(
                            $data['asal_daerah'] ?? ''
                        );

                        $deskripsi_data = strtolower(
                            ($data['deskripsi'] ?? '') .
                            ' ' .
                            ($data['bahan_utama'] ?? '')
                        );

                        ?>

                        <div
                            class="col-6 col-sm-6 col-lg-4 search-card-item"
                            data-name="<?= htmlspecialchars(
                                $nama_data,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-region="<?= htmlspecialchars(
                                $daerah_data,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-category="<?= (int) $data['id_kategori'] ?>"
                            data-description="<?= htmlspecialchars(
                                $deskripsi_data,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <article class="food-card">

                                <a
                                    href="detail.php?id=<?= (int) $data['id_kuliner'] ?>"
                                    class="food-image-wrap"
                                >

                                    <img
                                        src="<?= htmlspecialchars(
                                            $foto,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $data['nama_kuliner'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                     loading="lazy" decoding="async">

                                    <span class="food-category">

                                        <?= htmlspecialchars(
                                            $data['nama_kategori'] ?? 'Kuliner',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </a>

                                <div class="food-card-body">

                                    <div class="food-location">

                                        <i class="bi bi-geo-alt"></i>

                                        <?= htmlspecialchars(
                                            $data['asal_daerah'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $data['nama_kuliner'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </h3>

                                    <p>

                                        <?= htmlspecialchars(
                                            $deskripsi,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>

                                    <a
                                        href="detail.php?id=<?= (int) $data['id_kuliner'] ?>"
                                        class="detail-link"
                                    >

                                        Lihat Detail

                                        <i class="bi bi-arrow-up-right"></i>

                                    </a>

                                </div>

                            </article>

                        </div>

                    <?php endwhile; ?>

                <?php endif; ?>

            </div>

            <div
                id="noRealtimeResult"
                class="empty-state"
                style="display: none;"
            >

                <i class="bi bi-search"></i>

                <h3>
                    Kuliner Tidak Ditemukan
                </h3>

                <p>
                    Tidak ada kuliner yang sesuai dengan pencarian kamu.
                </p>

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

        <span>
            Beranda
        </span>

    </a>

    <a
        href="katalog.php"
        class="mobile-public-link"
    >

        <i class="bi bi-fork-knife"></i>

        <span>
            Katalog
        </span>

    </a>

    <a
        href="pencarian.php"
        class="mobile-public-link active"
    >

        <i class="bi bi-search"></i>

        <span>
            Cari
        </span>

    </a>

    <a
        href="tentang.php"
        class="mobile-public-link"
    >

        <i class="bi bi-info-circle-fill"></i>

        <span>
            Tentang
        </span>

    </a>

    <?php if ($is_login): ?>

        <?php if ($level_user === 'admin'): ?>

            <a
                href="admin/dashboard.php"
                class="mobile-public-link"
            >

                <i class="bi bi-person-fill"></i>

                <span>
                    Admin
                </span>

            </a>

        <?php else: ?>

            <a
                href="profil-user.php"
                class="mobile-public-link"
            >

                <i class="bi bi-person-fill"></i>

                <span>
                    Profil
                </span>

            </a>

        <?php endif; ?>

    <?php else: ?>

        <a
            href="login.php"
            class="mobile-public-link"
        >

            <i class="bi bi-person-fill"></i>

            <span>
                Login
            </span>

        </a>

    <?php endif; ?>
</nav>

<script src="assets/js/pencarian.js?v=3" defer></script>
<?php if ($is_login): ?>
    <script src="assets/js/notifikasi.js?v=60" defer></script>
<?php endif; ?>
    <script src="assets/js/lenis.js?v=1" defer></script>

</body>
</html>
