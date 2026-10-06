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

$id_user =
    (int) ($_SESSION["id_user"] ?? 0);

$nama_user =
    $_SESSION["nama_lengkap"] ??
    $_SESSION["username"] ??
    "Pengguna";

$level_user =
    $_SESSION["level"] ??
    "user";

if (
    !$is_login ||
    $id_user <= 0
) {
    header("Location: login.php");
    exit;
}

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

function getUnreadNotification($koneksi, $id_user)
{
    $stmt =
        $koneksi->prepare("
            SELECT COUNT(*) AS total
            FROM notifikasi
            WHERE id_user = ?
            AND dibaca = 0
        ");

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param(
        "i",
        $id_user
    );

    $stmt->execute();

    $row =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    return (int) (
        $row["total"] ?? 0
    );
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["ajax"])
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $action =
        $_POST["action"] ?? "";

    if (
        $action === "hapus_favorit"
    ) {

        $id_kuliner =
            (int) (
                $_POST["id_kuliner"] ?? 0
            );

        if (
            $id_kuliner <= 0
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Data kuliner tidak valid."
            ]);

            exit;
        }

        $stmt =
            $koneksi->prepare("
                DELETE FROM favorit
                WHERE id_user = ?
                AND id_kuliner = ?
            ");

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Query favorit gagal dibuat."
            ]);

            exit;
        }

        $stmt->bind_param(
            "ii",
            $id_user,
            $id_kuliner
        );

        $stmt->execute();

        $deleted =
            $stmt->affected_rows > 0;

        $stmt->close();

        echo json_encode([
            "success" => $deleted,
            "message" =>
                $deleted
                    ? "Kuliner dihapus dari favorit."
                    : "Kuliner tidak ditemukan di favorit.",
            "id_kuliner" =>
                $id_kuliner
        ]);

        exit;
    }

    echo json_encode([
        "success" => false,
        "message" =>
            "Permintaan tidak valid."
    ]);

    exit;
}

$unread_count =
    getUnreadNotification(
        $koneksi,
        $id_user
    );

$stmt =
    $koneksi->prepare("
        SELECT
            f.id_favorit,
            f.id_user,
            f.id_kuliner,
            f.created_at,

            k.nama_kuliner,
            k.asal_daerah,
            k.foto,
            k.deskripsi,
            k.id_kategori,

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

        FROM favorit AS f

        INNER JOIN kuliner AS k
            ON f.id_kuliner =
            k.id_kuliner

        LEFT JOIN kategori AS c
            ON k.id_kategori =
            c.id_kategori

        WHERE
            f.id_user = ?

        ORDER BY
            f.id_favorit DESC
    ");

if (!$stmt) {

    die(
        "Query favorit gagal: " .
        $koneksi->error
    );
}

$stmt->bind_param(
    "i",
    $id_user
);

$stmt->execute();

$result =
    $stmt->get_result();

$favorit_list =
    [];

while (
    $row =
    $result->fetch_assoc()
) {

    $favorit_list[] =
        $row;
}

$stmt->close();

$total_favorit =
    count(
        $favorit_list
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
        content="Daftar kuliner favorit kamu di GoKaltara Kuliner."
    >

    <title>
        Favorit | GoKaltara Kuliner
    </title>

    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/favorit.css?v=10"
    >

    <link
        rel="stylesheet"
        href="assets/css/notifikasi.css?v=60"
    >

    <link rel="stylesheet" href="assets/css/performance.css?v=1">

</head>

<body>

<nav class="public-navbar">

    <div class="nav-container">

        <a
            href="index.php"
            class="brand-public"
        >

            <span class="brand-logo">

                <img
                    src="assets/images/logo.svg"
                    alt="Logo GoKaltara Kuliner" loading="eager" decoding="async">

            </span>

            <span class="brand-name">

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
                class="nav-item-public"
            >
                Pencarian
            </a>

            <a
                href="notifikasi.php"
                class="notification-nav"
            >

                <i
                    class="bi bi-bell"
                ></i>

                <?php if (
                    $unread_count > 0
                ): ?>

                    <span class="notification-badge">
                        <?= $unread_count > 99
                            ? "99+"
                            : $unread_count ?>
                    </span>

                <?php endif; ?>

            </a>

            <a
                href="favorit.php"
                class="favorite-nav active"
            >

                <i
                    class="bi bi-heart-fill"
                ></i>

            </a>

            <?php if (
                $level_user ===
                "admin"
            ): ?>

                <a
                    href="admin/dashboard.php"
                    class="btn-admin"
                >

                    <i
                        class="bi bi-person"
                    ></i>

                    <span>

                        <?= e(
                            $nama_user
                        ) ?>

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

                    <i
                        class="bi bi-person"
                    ></i>

                    <span>

                        <?= e(
                            $nama_user
                        ) ?>

                    </span>

                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

<main class="favorite-main">

    <section class="favorite-page">

        <div class="favorite-container">

            <div class="favorite-header">

                <div>

                    <span
                        class="favorite-eyebrow"
                    >
                        KOLEKSI SAYA
                    </span>

                    <h1>
                        Favorit
                    </h1>

                    <p>
                        Kuliner yang kamu simpan untuk dilihat kembali.
                    </p>

                </div>

                <div
                    class="favorite-total-box"
                >

                    <span>
                        Total
                    </span>

                    <strong
                        id="favoriteTotal"
                    >
                        <?= $total_favorit ?>
                    </strong>

                    <small>
                        kuliner
                    </small>

                </div>

            </div>

            <?php if (
                !empty(
                    $favorit_list
                )
            ): ?>

                <div
                    class="favorite-grid"
                    id="favoriteGrid"
                >

                    <?php foreach (
                        $favorit_list as $item
                    ): ?>

                        <?php

                        $foto =
                            fotoKuliner(
                                $item[
                                    "foto"
                                ] ?? "",
                                true
                            );

                        $rating =
                            (float) (
                                $item[
                                    "average_rating"
                                ] ?? 0
                            );

                        $total_rating =
                            (int) (
                                $item[
                                    "total_rating"
                                ] ?? 0
                            );

                        $deskripsi =
                            strip_tags(
                                $item[
                                    "deskripsi"
                                ] ?? ""
                            );

                        if (
                            mb_strlen(
                                $deskripsi
                            ) > 100
                        ) {

                            $deskripsi =
                                mb_strimwidth(
                                    $deskripsi,
                                    0,
                                    100,
                                    "..."
                                );
                        }

                        ?>

                        <article
                            class="favorite-card"
                            data-kuliner-id="<?= (int) $item["id_kuliner"] ?>"
                        >

                            <a
                                href="detail.php?id=<?= (int) $item["id_kuliner"] ?>"
                                class="favorite-image-wrap"
                            >

                                <img
                                    src="<?= e(
                                        $foto
                                    ) ?>"
                                    alt="<?= e(
                                        $item[
                                            "nama_kuliner"
                                        ]
                                    ) ?>"
                                 loading="lazy" decoding="async">

                                <span
                                    class="favorite-category"
                                >

                                    <?= e(
                                        $item[
                                            "nama_kategori"
                                        ] ??
                                        "Kuliner"
                                    ) ?>

                                </span>

                            </a>

                            <div
                                class="favorite-card-body"
                            >

                                <div
                                    class="favorite-location"
                                >

                                    <i
                                        class="bi bi-geo-alt-fill"
                                    ></i>

                                    <span>

                                        <?= e(
                                            $item[
                                                "asal_daerah"
                                            ] ??
                                            "-"
                                        ) ?>

                                    </span>

                                </div>

                                <h2>

                                    <?= e(
                                        $item[
                                            "nama_kuliner"
                                        ]
                                    ) ?>

                                </h2>

                                <p>

                                    <?= e(
                                        $deskripsi
                                    ) ?>

                                </p>

                                <div
                                    class="favorite-rating"
                                >

                                    <span>

                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>

                                            <i
                                                class="bi <?= $i <= round($rating)
                                                    ? "bi-star-fill"
                                                    : "bi-star" ?>"
                                            ></i>

                                        <?php endfor; ?>

                                    </span>

                                    <strong>

                                        <?= number_format(
                                            $rating,
                                            1
                                        ) ?>

                                    </strong>

                                    <small>

                                        (<?= $total_rating ?>)

                                    </small>

                                </div>

                                <div
                                    class="favorite-actions"
                                >

                                    <a
                                        href="detail.php?id=<?= (int) $item["id_kuliner"] ?>"
                                        class="favorite-detail"
                                    >

                                        Lihat Detail

                                        <i
                                            class="bi bi-arrow-up-right"
                                        ></i>

                                    </a>

                                    <button
                                        type="button"
                                        class="favorite-remove"
                                        data-id="<?= (int) $item["id_kuliner"] ?>"
                                    >

                                        <i
                                            class="bi bi-heart-fill"
                                        ></i>

                                        Hapus

                                    </button>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div
                    class="favorite-empty"
                    id="favoriteEmpty"
                >

                    <div
                        class="favorite-empty-icon"
                    >

                        <i
                            class="bi bi-heart"
                        ></i>

                    </div>

                    <h2>
                        Belum Ada Favorit
                    </h2>

                    <p>
                        Simpan kuliner yang kamu sukai agar mudah ditemukan kembali.
                    </p>

                    <a
                        href="katalog.php"
                    >
                        Jelajahi Katalog
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<footer class="public-footer">

    <div class="footer-container">

        <div class="footer-top">

            <div class="footer-brand-block">

                <a
                    href="index.php"
                    class="footer-brand-link"
                >

                    <span class="footer-logo">
                        <img
                            src="assets/images/logo.svg"
                            alt="GoKaltara Kuliner" loading="eager" decoding="async">
                    </span>

                    <span class="footer-brand-text">
                        GoKaltara
                        <strong>Kuliner</strong>
                    </span>

                </a>

                <p class="footer-description">
                    Katalog kuliner khas Kalimantan Utara.
                </p>

            </div>

            <nav class="footer-nav" aria-label="Navigasi website">

                <span class="footer-nav-title">
                    Jelajahi
                </span>

                <a href="index.php">
                    Beranda
                </a>

                <a href="katalog.php">
                    Katalog
                </a>

                <a href="pencarian.php">
                    Pencarian
                </a>

                <a href="tentang.php">
                    Tentang
                </a>

            </nav>

            <nav class="footer-nav" aria-label="Navigasi akun">

                <span class="footer-nav-title">
                    Akun
                </span>

                <a href="favorit.php">
                    Favorit
                </a>

                <a href="notifikasi.php">
                    Notifikasi
                </a>

                <a href="profil-user.php">
                    Profil
                </a>

            </nav>

        </div>

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

        <i
            class="bi bi-house-fill"
        ></i>

        <span>
            Beranda
        </span>

    </a>

    <a
        href="katalog.php"
        class="mobile-public-link"
    >

        <i
            class="bi bi-fork-knife"
        ></i>

        <span>
            Katalog
        </span>

    </a>

    <a
        href="pencarian.php"
        class="mobile-public-link"
    >

        <i
            class="bi bi-search"
        ></i>

        <span>
            Cari
        </span>

    </a>

    <a
        href="favorit.php"
        class="mobile-public-link active"
    >

        <i
            class="bi bi-heart-fill"
        ></i>

        <span>
            Favorit
        </span>

    </a>

    <a
        href="profil-user.php"
        class="mobile-public-link"
    >

        <i
            class="bi bi-person-fill"
        ></i>

        <span>
            Profil
        </span>

    </a>

</nav>

<script
    src="assets/js/favorit.js?v=10"
></script>

    <script
        src="assets/js/notifikasi.js?v=60"
    ></script>

    <script src="assets/js/performance.js?v=1" defer></script>

</body>
</html>