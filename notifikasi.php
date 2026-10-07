<?php
session_start();

require_once "config/koneksi.php";
require_once "config/image_optimizer.php";
require_once "config/profile_images.php";

$is_login = isset($_SESSION['login']) &&
    (
        $_SESSION['login'] === true ||
        $_SESSION['login'] === 1 ||
        $_SESSION['login'] === '1'
    );

if (!$is_login) {

    if (isset($_GET['ajax'])) {
        http_response_code(401);

        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Silakan login terlebih dahulu.'
        ]);

        exit;
    }

    header(
        "Location: login.php"
    );

    exit;
}

date_default_timezone_set(
    "Asia/Makassar"
);

$id_user =
    (int) (
        $_SESSION['id_user'] ??
        0
    );

$nama_user =
    $_SESSION['nama_lengkap'] ??
    $_SESSION['username'] ??
    'Pengguna';

$return_url =
    trim(
        $_GET['from'] ??
        ''
    );

if (
    $return_url === ''
) {

    $return_url =
        $_SERVER['HTTP_REFERER'] ??
        'index.php';
}

function e($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function fotoProfil($foto, $thumbnail = false)
{
    $url = gokaltara_profile_image_url_direct(
        (string) ($foto ?? ""),
        (bool) $thumbnail
    );

    if ($url !== "") {
        return $url;
    }

    if (file_exists("assets/images/profil/default.png")) {
        return "assets/images/profil/default.png";
    }

    return "assets/images/no-image.jpg";
}

function fotoKuliner($foto, $thumbnail = false)
{
    return gokaltara_image_url(
        (string) ($foto ?? ""),
        "assets/images/",
        (bool) $thumbnail
    );
}

function waktuRelatif($tanggal)
{
    try {

        $now =
            new DateTime();

        $date =
            new DateTime(
                $tanggal
            );

        $diff =
            $now->getTimestamp() -
            $date->getTimestamp();

        if (
            $diff < 60
        ) {
            return "baru saja";
        }

        if (
            $diff < 3600
        ) {
            return
                floor(
                    $diff / 60
                ) .
                " menit";
        }

        if (
            $diff < 86400
        ) {
            return
                floor(
                    $diff / 3600
                ) .
                " jam";
        }

        if (
            $diff < 604800
        ) {
            return
                floor(
                    $diff / 86400
                ) .
                " hari";
        }

        if (
            $date->format('Y') ===
            date('Y')
        ) {
            return
                $date->format(
                    'd M'
                );
        }

        return
            $date->format(
                'd M Y'
            );

    } catch (
        Exception $e
    ) {
        return "";
    }
}

function tipeNotifikasi($pesan)
{
    $pesan =
        strtolower(
            trim(
                $pesan ?? ''
            )
        );

    if (
        str_contains(
            $pesan,
            'suka'
        ) ||
        str_contains(
            $pesan,
            'like'
        )
    ) {
        return "like";
    }

    if (
        str_contains(
            $pesan,
            'balas'
        ) ||
        str_contains(
            $pesan,
            'komentar'
        )
    ) {
        return "comment";
    }

    if (
        str_contains(
            $pesan,
            'favorit'
        )
    ) {
        return "favorite";
    }

    return "activity";
}

function iconNotifikasi($tipe)
{
    switch (
        $tipe
    ) {

        case "like":
            return "bi-heart-fill";

        case "comment":
            return "bi-chat-fill";

        case "favorite":
            return "bi-bookmark-fill";

        default:
            return "bi-bell-fill";
    }
}

function ambilNotifikasi(
    $koneksi,
    $id_user,
    $limit = 100
) {
    $limit =
        max(
            1,
            min(
                (int) $limit,
                100
            )
        );

    $sql = "
        SELECT
            n.id_notifikasi,
            n.id_user,
            n.id_pengirim,
            n.id_kuliner,
            n.id_komentar,
            n.pesan,
            n.dibaca,
            n.created_at,

            u.username,
            u.nama_lengkap,
            u.foto_profil,

            k.nama_kuliner,
            k.foto AS foto_kuliner

        FROM notifikasi AS n

        LEFT JOIN user AS u
            ON n.id_pengirim = u.id_user

        LEFT JOIN kuliner AS k
            ON n.id_kuliner = k.id_kuliner

        WHERE n.id_user = ?

        ORDER BY n.created_at DESC

        LIMIT $limit
    ";

    $stmt =
        $koneksi->prepare(
            $sql
        );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param(
        "i",
        $id_user
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $rows = [];

    while (
        $row =
        $result->fetch_assoc()
    ) {
        $rows[] =
            $row;
    }

    $stmt->close();

    return $rows;
}

function renderNotifikasi(
    $notifications
) {
    if (
        empty(
            $notifications
        )
    ) {

        return '
            <div class="notification-empty">

                <div class="notification-empty-icon">
                    <i class="bi bi-bell"></i>
                </div>

                <h3>
                    Belum ada notifikasi
                </h3>

                <p>
                    Aktivitas komentar dan interaksi
                    kamu akan muncul di sini.
                </p>

            </div>
        ';
    }

    $today =
        new DateTime(
            "today"
        );

    $week =
        new DateTime(
            "-7 days"
        );

    $month =
        new DateTime(
            "-30 days"
        );

    $groups = [
        "today" => [],
        "week" => [],
        "month" => []
    ];

    foreach (
        $notifications as $item
    ) {

        try {

            $date =
                new DateTime(
                    $item['created_at']
                );

        } catch (
            Exception $e
        ) {
            continue;
        }

        if (
            $date >= $today
        ) {

            $groups['today'][] =
                $item;

        } elseif (
            $date >= $week
        ) {

            $groups['week'][] =
                $item;

        } elseif (
            $date >= $month
        ) {

            $groups['month'][] =
                $item;
        }
    }

    ob_start();

    foreach (
        $groups as
        $group => $items
    ) {

        if (
            empty($items)
        ) {
            continue;
        }

        if (
            $group ===
            "today"
        ) {

            $title =
                "Hari ini";

        } elseif (
            $group ===
            "week"
        ) {

            $title =
                "7 hari terakhir";

        } else {

            $title =
                "30 hari terakhir";
        }

        echo '
            <section class="notification-group">

                <h3 class="notification-group-title">
                    ' . e($title) . '
                </h3>
        ';

        foreach (
            $items as $item
        ) {

            $tipe =
                tipeNotifikasi(
                    $item['pesan']
                );

            $icon =
                iconNotifikasi(
                    $tipe
                );

            $avatar =
                fotoProfil(
                    $item['foto_profil'],
                    true
                );

            $thumb = "";

            if (
                !empty(
                    $item['foto_kuliner']
                )
            ) {

                $thumb =
                    fotoKuliner(
                        $item['foto_kuliner'],
                        true
                    );
            }

            $unread =
                (
                    (int)
                    $item['dibaca'] ===
                    0
                )
                    ? " unread"
                    : "";

            $href =
                "#";

            if (
                !empty(
                    $item['id_kuliner']
                )
            ) {

                $href =
                    "detail.php?id=" .
                    (int)
                    $item['id_kuliner'];
            }

            echo '
                <a
                    href="' . e($href) . '"
                    class="notification-item' .
                        $unread .
                    '"
                    data-notification-id="' .
                        (int)
                        $item['id_notifikasi'] .
                    '"
                    data-type="' .
                        e($tipe) .
                    '"
                >

                    <div class="notification-avatar-wrap">

                        <img
                            src="' .
                                e($avatar) .
                            '"
                            alt="' .
                                e(
                                    $item['nama_lengkap']
                                    ??
                                    "Pengguna"
                                ) .
                            '"
                            class="notification-avatar">

                        <span
                            class="notification-type-icon ' .
                                e($tipe) .
                            '"
                        >

                            <i
                                class="bi ' .
                                    e($icon) .
                                '"
                            ></i>

                        </span>

                    </div>

                    <div class="notification-content">

                        <div class="notification-message">
                            ' .
                                e(
                                    $item['pesan']
                                ) .
                            '
                        </div>

                        <div class="notification-time">
                            ' .
                                e(
                                    waktuRelatif(
                                        $item['created_at']
                                    )
                                ) .
                            '
                        </div>

                    </div>

                    ' .
                    (
                        $thumb !== ""
                            ? '
                                <div class="notification-thumb-wrap">

                                    <img
                                        src="' .
                                            e($thumb) .
                                        '"
                                        alt="' .
                                            e(
                                                $item['nama_kuliner']
                                                ??
                                                "Kuliner"
                                            ) .
                                        '"
                                        class="notification-thumb">

                                </div>
                                '
                            : '
                                <div class="notification-thumb-placeholder">
                                    <i class="bi bi-bell"></i>
                                </div>
                            '
                    ) .
                    '

                    <span class="notification-unread-dot"></span>

                </a>
            ';
        }

        echo '
            </section>
        ';
    }

    return
        ob_get_clean();
}

if (
    isset($_GET['ajax'])
) {

    $ajax =
        $_GET['ajax'];

    if (
        $ajax ===
        "panel"
    ) {

        header(
            "Content-Type: text/html; charset=UTF-8"
        );

        $notifications =
            ambilNotifikasi(
                $koneksi,
                $id_user,
                50
            );

        echo
            renderNotifikasi(
                $notifications
            );

        exit;
    }

    if (
        $ajax ===
        "count"
    ) {

        header(
            "Content-Type: application/json; charset=UTF-8"
        );

        $stmt =
            $koneksi->prepare("
                SELECT
                    COUNT(*) AS total
                FROM notifikasi
                WHERE id_user = ?
                AND dibaca = 0
            ");

        $total = 0;

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id_user
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $row =
                $result->fetch_assoc();

            $total =
                (int) (
                    $row['total'] ??
                    0
                );

            $stmt->close();
        }

        echo json_encode([
            "success" => true,
            "total" => $total
        ]);

        exit;
    }

    if (
        $ajax ===
        "read"
    ) {

        header(
            "Content-Type: application/json; charset=UTF-8"
        );

        $id_notifikasi =
            (int) (
                $_POST['id_notifikasi'] ??
                0
            );

        if (
            $id_notifikasi >
            0
        ) {

            $stmt =
                $koneksi->prepare("
                    UPDATE notifikasi
                    SET dibaca = 1
                    WHERE id_notifikasi = ?
                    AND id_user = ?
                ");

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $id_notifikasi,
                    $id_user
                );

                $stmt->execute();

                $stmt->close();
            }
        }

        echo json_encode([
            "success" => true
        ]);

        exit;
    }
}

$notifications =
    ambilNotifikasi(
        $koneksi,
        $id_user,
        100
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
        content="Notifikasi GoKaltara Kuliner."
    >

    <title>
        Notifikasi | GoKaltara Kuliner
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

    <link rel="stylesheet" href="assets/css/notifikasi.css?v=61">
    <noscript>
        </noscript>

    <link rel="stylesheet" href="assets/css/lenis.css?v=2" media="(min-width: 992px)">

</head>

<body class="notification-page">

<header class="notification-mobile-header">

    <button
        type="button"
        class="notification-back"
        id="notificationBack"
        data-return-url="<?= e($return_url) ?>"
        aria-label="Kembali"
    >

        <i class="bi bi-chevron-left"></i>

    </button>

    <div class="notification-user-title">
        <?= e($nama_user) ?>
    </div>

    <div class="notification-header-icon">

        <i class="bi bi-bell-fill"></i>

    </div>

</header>

<main class="notification-page-main">

    <div class="notification-page-heading">

        <div>

            <span>
                AKTIVITAS
            </span>

            <h1>
                Notifikasi
            </h1>

        </div>

        <a
            href="<?= e($return_url) ?>"
            class="notification-close"
            id="notificationPageClose"
            data-return-url="<?= e($return_url) ?>"
            aria-label="Tutup"
        >


        </a>

    </div>

    <div class="notification-filters">

        <button
            type="button"
            class="notification-filter active"
            data-filter="all"
        >
            Semua
        </button>

        <button
            type="button"
            class="notification-filter"
            data-filter="like"
        >

            <i class="bi bi-heart"></i>

            Suka

        </button>

        <button
            type="button"
            class="notification-filter"
            data-filter="comment"
        >

            <i class="bi bi-chat"></i>

            Komentar

        </button>

        <button
            type="button"
            class="notification-filter"
            data-filter="favorite"
        >

            <i class="bi bi-bookmark"></i>

            Favorit

        </button>

    </div>

    <div id="notificationList">

        <?= renderNotifikasi($notifications) ?>

    </div>

</main>

<script
    src="assets/js/notifikasi.js?v=50"
 defer></script>
    <script src="assets/js/lenis.js?v=3" defer></script>

</body>

</html>