<?php

session_start();
require_once "config/koneksi.php";
require_once "config/image_optimizer.php";

$is_login = isset($_SESSION["login"]) &&
    (
        $_SESSION["login"] === true ||
        $_SESSION["login"] === 1 ||
        $_SESSION["login"] === "1"
    );

$nama_user =
    $_SESSION["nama_lengkap"] ??
    $_SESSION["username"] ??
    "Pengguna";

$level_user =
    $_SESSION["level"] ??
    "user";

$kategori_id =
    (int) ($_GET["kategori"] ?? 0);

$daerah =
    trim($_GET["daerah"] ?? "");

$q =
    trim($_GET["q"] ?? "");

$page =
    max(
        1,
        (int) ($_GET["page"] ?? 1)
    );

$per_page =
    8;

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
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


function renderCatalogCards($query_hasil): void
{
    if (!$query_hasil || $query_hasil->num_rows === 0) {
        echo '
            <div class="catalog-empty">
                <div class="catalog-empty-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h2>Kuliner Tidak Ditemukan</h2>
                <p>Tidak ada kuliner yang sesuai dengan pencarian atau filter kamu.</p>
                <a href="katalog.php">Tampilkan Semua Kuliner</a>
            </div>
        ';
        return;
    }

    echo '<div class="catalog-grid">';

    while ($data = $query_hasil->fetch_assoc()) {

        $foto = fotoKuliner($data["foto"], true);

        $rating = (float) ($data["average_rating"] ?? 0);
        $total_rating = (int) ($data["total_rating"] ?? 0);

        $deskripsi = strip_tags($data["deskripsi"] ?? "");

        if (mb_strlen($deskripsi) > 95) {
            $deskripsi = mb_strimwidth($deskripsi, 0, 95, "...");
        }

        $nama_kuliner = e($data["nama_kuliner"] ?? "Kuliner");
        $nama_kategori = e($data["nama_kategori"] ?? "Kuliner");
        $asal_daerah = e($data["asal_daerah"] ?? "-");
        $deskripsi_html = e($deskripsi);
        $detail_url = "detail.php?id=" . (int) $data["id_kuliner"];
        $foto_html = e($foto);
        $rating_value = number_format($rating, 1);

        echo '
            <article class="food-card">
                <a href="' . $detail_url . '" class="food-image-wrap">
                    <img
                        src="' . $foto_html . '"
                        alt="' . $nama_kuliner . '"
                        loading="lazy"
                        decoding="async"
                    >
                    <span class="food-category">
                        ' . $nama_kategori . '
                    </span>
                </a>

                <div class="food-card-body">
                    <div class="food-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>' . $asal_daerah . '</span>
                    </div>

                    <h3>' . $nama_kuliner . '</h3>

                    <p>' . $deskripsi_html . '</p>

                    <div class="food-rating">
                        <span class="rating-stars">';

        for ($i = 1; $i <= 5; $i++) {
            echo '<i class="bi ' . ($i <= round($rating) ? 'bi-star-fill' : 'bi-star') . '"></i>';
        }

        echo '
                        </span>
                        <strong>' . $rating_value . '</strong>
                        <small>(' . $total_rating . ')</small>
                    </div>

                    <a href="' . $detail_url . '" class="detail-link">
                        Lihat Detail
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </article>
        ';
    }

    echo '</div>';
}

function renderCatalogPagination(
    int $page,
    int $total_pages,
    string $q,
    int $kategori_id,
    string $daerah
): void {
    if ($total_pages <= 1) {
        return;
    }

    $pagination_params = [];

    if ($q !== "") {
        $pagination_params["q"] = $q;
    }

    if ($kategori_id > 0) {
        $pagination_params["kategori"] = $kategori_id;
    }

    if ($daerah !== "") {
        $pagination_params["daerah"] = $daerah;
    }

    echo '<nav class="catalog-pagination" aria-label="Navigasi halaman katalog">';

    if ($page > 1) {
        $pagination_params["page"] = $page - 1;
        $url = "katalog.php?" . http_build_query($pagination_params);
        echo '<a href="' . e($url) . '" class="pagination-arrow" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>';
    }

    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);

    if ($start_page > 1) {
        $pagination_params["page"] = 1;
        $url = "katalog.php?" . http_build_query($pagination_params);
        echo '<a href="' . e($url) . '" class="pagination-number">1</a>';

        if ($start_page > 2) {
            echo '<span class="pagination-dots">...</span>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $pagination_params["page"] = $i;
        $url = "katalog.php?" . http_build_query($pagination_params);
        $active = $page === $i ? " active" : "";
        echo '<a href="' . e($url) . '" class="pagination-number' . $active . '">' . $i . '</a>';
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            echo '<span class="pagination-dots">...</span>';
        }

        $pagination_params["page"] = $total_pages;
        $url = "katalog.php?" . http_build_query($pagination_params);
        echo '<a href="' . e($url) . '" class="pagination-number">' . $total_pages . '</a>';
    }

    if ($page < $total_pages) {
        $pagination_params["page"] = $page + 1;
        $url = "katalog.php?" . http_build_query($pagination_params);
        echo '<a href="' . e($url) . '" class="pagination-arrow" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>';
    }

    echo '</nav>';
}

$query_kategori =
    $koneksi->query("
        SELECT
            id_kategori,
            nama_kategori
        FROM kategori
        ORDER BY nama_kategori ASC
    ");

$query_daerah =
    $koneksi->query("
        SELECT DISTINCT
            asal_daerah
        FROM kuliner
        WHERE asal_daerah IS NOT NULL
        AND TRIM(asal_daerah) != ''
        ORDER BY asal_daerah ASC
    ");

$where = [];
$params = [];
$types = "";

if ($kategori_id > 0) {

    $where[] =
        "k.id_kategori = ?";

    $params[] =
        $kategori_id;

    $types .= "i";
}

if ($daerah !== "") {

    $where[] =
        "k.asal_daerah = ?";

    $params[] =
        $daerah;

    $types .= "s";
}

if ($q !== "") {

    $where[] = "
        (
            k.nama_kuliner LIKE ?
            OR k.asal_daerah LIKE ?
            OR k.deskripsi LIKE ?
            OR k.bahan_utama LIKE ?
        )
    ";

    $search =
        "%" . $q . "%";

    $params[] =
        $search;

    $params[] =
        $search;

    $params[] =
        $search;

    $params[] =
        $search;

    $types .=
        "ssss";
}

$where_sql =
    !empty($where)
    ? " WHERE " .
    implode(
        " AND ",
        $where
    )
    : "";

$count_sql = "
    SELECT COUNT(*) AS total
    FROM kuliner AS k
    $where_sql
";

$total_data =
    0;

if (!empty($params)) {

    $stmt_count =
        $koneksi->prepare(
            $count_sql
        );

    if (!$stmt_count) {

        die("Query jumlah data gagal: " .
            $koneksi->error);
    }

    $bind_count =
        [$types];

    foreach (
        $params as $key => $value
    ) {

        $bind_count[] =
            &$params[$key];
    }

    call_user_func_array(
        [
            $stmt_count,
            "bind_param"
        ],
        $bind_count
    );

    $stmt_count->execute();

    $count_result =
        $stmt_count->get_result();

    $count_row =
        $count_result->fetch_assoc();

    $stmt_count->close();

    $total_data =
        (int) (
            $count_row["total"] ?? 0
        );
} else {

    $count_result =
        $koneksi->query(
            $count_sql
        );

    if ($count_result) {

        $count_row =
            $count_result->fetch_assoc();

        $total_data =
            (int) (
                $count_row["total"] ?? 0
            );
    }
}

$total_pages =
    max(
        1,
        (int) ceil(
            $total_data /
                $per_page
        )
    );

if (
    $page >
    $total_pages
) {

    $page =
        $total_pages;
}

$offset =
    ($page - 1) *
    $per_page;

$data_params =
    $params;

$data_types =
    $types . "ii";

$data_params[] =
    $per_page;

$data_params[] =
    $offset;

$sql = "
    SELECT
        k.id_kuliner,
        k.nama_kuliner,
        k.id_kategori,
        k.asal_daerah,
        k.deskripsi,
        k.foto,
        c.nama_kategori,

        (
            SELECT
                AVG(r.nilai)
            FROM rating AS r
            WHERE
                r.id_kuliner =
                k.id_kuliner
        ) AS average_rating,

        (
            SELECT
                COUNT(r2.id_rating)
            FROM rating AS r2
            WHERE
                r2.id_kuliner =
                k.id_kuliner
        ) AS total_rating

    FROM kuliner AS k

    LEFT JOIN kategori AS c
        ON k.id_kategori =
        c.id_kategori

    $where_sql

    ORDER BY
        k.id_kuliner DESC

    LIMIT ?

    OFFSET ?
";

$stmt =
    $koneksi->prepare(
        $sql
    );

if (!$stmt) {

    die("Query katalog gagal: " .
        $koneksi->error);
}

$bind_data =
    [$data_types];

foreach (
    $data_params as $key => $value
) {

    $bind_data[] =
        &$data_params[$key];
}

call_user_func_array(
    [
        $stmt,
        "bind_param"
    ],
    $bind_data
);

$stmt->execute();

$query_hasil =
    $stmt->get_result();

$stmt->close();

if (isset($_GET["ajax"]) && $_GET["ajax"] === "1") {

    ob_start();
    renderCatalogCards($query_hasil);
    $cards_html = ob_get_clean();

    ob_start();
    renderCatalogPagination(
        $page,
        $total_pages,
        $q,
        $kategori_id,
        $daerah
    );
    $pagination_html = ob_get_clean();

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode(
        [
            "success" => true,
            "html" => $cards_html,
            "pagination" => $pagination_html,
            "total" => $total_data,
            "page" => $page,
            "total_pages" => $total_pages
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

$nama_tampilan =
    e($nama_user);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Katalog kuliner khas Kalimantan Utara.">

    <title>
        Katalog | GoKaltara Kuliner
    </title>

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


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/katalog.css?v=4">

    <link rel="stylesheet" href="assets/css/pagination.css?v=40">

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

        <div class="nav-container">

            <a
                href="index.php"
                class="brand-public">

                <span class="brand-logo">

                    <img
                        src="assets/images/logo.svg"
                        alt="Logo GoKaltara Kuliner" loading="eager" decoding="async" width="58" height="58">

                </span>

                <span class="brand-name">

                    GoKaltara
                    <strong>Kuliner</strong>

                </span>

            </a>

            <div class="desktop-menu">

                <a
                    href="index.php"
                    class="nav-item-public">
                    Beranda
                </a>

                <a
                    href="katalog.php"
                    class="nav-item-public active">
                    Katalog
                </a>

                <a
                    href="tentang.php"
                    class="nav-item-public">
                    Tentang
                </a>

                <a
                    href="pencarian.php"
                    class="nav-item-public">
                    Pencarian
                </a>

                <?php if ($is_login): ?>

                    <a
                        href="notifikasi.php"
                        class="notification-nav"
                        aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                    </a>

                    <?php if (
                        $level_user ===
                        "admin"
                    ): ?>

                        <a
                            href="admin/dashboard.php"
                            class="btn-admin">

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
                            class="btn-admin">

                            <i class="bi bi-person"></i>

                            <span>
                                <?= $nama_tampilan ?>
                            </span>

                        </a>

                    <?php endif; ?>

                <?php else: ?>

                    <a
                        href="login.php"
                        class="btn-admin">

                        <i class="bi bi-person"></i>

                        <span>
                            Login
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

    <main class="catalog-main">

        <section class="catalog-hero">

            <div class="catalog-container">

                <span class="catalog-eyebrow">
                    KATALOG
                </span>

                <h1>
                    Jelajahi Kuliner Kaltara
                </h1>

                <p>
                    Temukan beragam kuliner khas Kalimantan Utara.
                </p>

            </div>

        </section>

        <section class="catalog-section">

            <div class="catalog-container">

                <form
                    action="katalog.php"
                    method="GET"
                    class="catalog-filter"
                    id="catalogFilter"
                    role="search">

                    <div class="filter-search">

                        <i
                            class="bi bi-search"></i>

                        <input
                            type="search"
                            name="q"
                            id="catalogRealtimeSearch"
                            value="<?= e($q) ?>"
                            placeholder="Cari nama, daerah, atau bahan utama..."
                            autocomplete="off"
                            aria-label="Cari kuliner berdasarkan nama, daerah, atau bahan utama">

                    </div>

                    <select
                        name="kategori"
                        id="catalogCategoryFilter"
                        class="filter-select"
                        aria-label="Filter kategori">

                        <option value="">
                            Semua Kategori
                        </option>

                        <?php if (
                            $query_kategori
                        ): ?>

                            <?php while (
                                $kategori =
                                $query_kategori
                                ->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?= (int) $kategori["id_kategori"] ?>"
                                    <?= $kategori_id ===
                                        (int) $kategori["id_kategori"]
                                        ? "selected"
                                        : "" ?>>

                                    <?= e(
                                        $kategori["nama_kategori"]
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                    <select
                        name="daerah"
                        id="catalogRegionFilter"
                        class="filter-select"
                        aria-label="Filter daerah">

                        <option value="">
                            Semua Daerah
                        </option>

                        <?php if (
                            $query_daerah
                        ): ?>

                            <?php while (
                                $region =
                                $query_daerah
                                ->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?= e(
                                                $region["asal_daerah"]
                                            ) ?>"
                                    <?= $daerah ===
                                        $region["asal_daerah"]
                                        ? "selected"
                                        : "" ?>>

                                    <?= e(
                                        $region["asal_daerah"]
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </form>

                <div class="catalog-heading">

                    <div>

                        <span>
                            HASIL KATALOG
                        </span>

                        <h2>

                            <span id="catalogCount">
                                <?= $total_data ?>
                            </span>

                            Kuliner

                        </h2>

                    </div>

                    <div
                        class="active-filter-text"
                        id="catalogFilterStatus"
                        <?= $q !== "" || $kategori_id > 0 || $daerah !== "" ? "" : "hidden" ?>
                    >
                        Filter aktif
                    </div>

                </div>

                <div id="catalogResults">

                    <?php
                    renderCatalogCards($query_hasil);
                    ?>

                </div>

                <div id="catalogPagination">

                    <?php
                    renderCatalogPagination(
                        $page,
                        $total_pages,
                        $q,
                        $kategori_id,
                        $daerah
                    );
                    ?>

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
            class="mobile-public-link">

            <i
                class="bi bi-house-fill"></i>

            <span>
                Beranda
            </span>

        </a>

        <a
            href="katalog.php"
            class="mobile-public-link active">

            <i
                class="bi bi-fork-knife"></i>

            <span>
                Katalog
            </span>

        </a>

        <a
            href="pencarian.php"
            class="mobile-public-link">

            <i
                class="bi bi-search"></i>

            <span>
                Cari
            </span>

        </a>

        <a
            href="tentang.php"
            class="mobile-public-link">

            <i
                class="bi bi-info-circle-fill"></i>

            <span>
                Tentang
            </span>

        </a>

        <?php if (
            $is_login
        ): ?>

            <a
                href="profil-user.php"
                class="mobile-public-link">

                <i
                    class="bi bi-person-fill"></i>

                <span>
                    Profil
                </span>

            </a>

        <?php else: ?>

            <a
                href="login.php"
                class="mobile-public-link">

                <i
                    class="bi bi-person-fill"></i>

                <span>
                    Login
                </span>

            </a>

        <?php endif; ?>
</nav>

    <script src="assets/js/katalog.js?v=1" defer></script>
<?php if ($is_login): ?>
    <script src="assets/js/notifikasi.js?v=60" defer></script>
<?php endif; ?>
    <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js" defer></script>
    <script src="assets/js/lenis.js?v=1" defer></script>

</body>

</html>