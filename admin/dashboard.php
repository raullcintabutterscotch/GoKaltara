<?php

declare(strict_types=1);

require_once "../api/_auth.php";
require_once "../config/image_optimizer.php";
require_once "../config/profile_images.php";

requireAdminPage($koneksi);

$id_user = (int) ($_SESSION["id_user"] ?? 0);
$nama_admin = $_SESSION["nama_lengkap"] ?? "Administrator";

$stmt_user = $koneksi->prepare("
    SELECT
        id_user,
        username,
        nama_lengkap,
        foto_profil,
        level
    FROM user
    WHERE id_user = ?
    LIMIT 1
");

if (!$stmt_user) {
    header("Location: ../login.php");
    exit;
}

$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();

$result_user = $stmt_user->get_result();
$data_user = $result_user->fetch_assoc();

$stmt_user->close();

if (!$data_user) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: ../login.php");
    exit;
}

$nama_admin = trim((string) ($data_user["nama_lengkap"] ?? "Administrator"));

if ($nama_admin === "") {
    $nama_admin = "Administrator";
}

$foto_profil = trim((string) ($data_user["foto_profil"] ?? ""));

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM kuliner
");

$total_kuliner = 0;

if ($query) {
    $row = $query->fetch_assoc();
    $total_kuliner = (int) ($row["total"] ?? 0);
}

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM kategori
");

$total_kategori = 0;

if ($query) {
    $row = $query->fetch_assoc();
    $total_kategori = (int) ($row["total"] ?? 0);
}

$query = $koneksi->query("
    SELECT COUNT(DISTINCT asal_daerah) AS total
    FROM kuliner
    WHERE asal_daerah IS NOT NULL
    AND TRIM(asal_daerah) != ''
");

$total_daerah = 0;

if ($query) {
    $row = $query->fetch_assoc();
    $total_daerah = (int) ($row["total"] ?? 0);
}

$query_terbaru = $koneksi->query("
    SELECT
        k.id_kuliner,
        k.nama_kuliner,
        k.asal_daerah,
        k.foto,
        c.nama_kategori
    FROM kuliner AS k
    LEFT JOIN kategori AS c
        ON k.id_kategori = c.id_kategori
    ORDER BY k.id_kuliner DESC
    LIMIT 5
");

$total_kuliner_terbaru = 0;

if ($query_terbaru) {
    $total_kuliner_terbaru = $query_terbaru->num_rows;
}

$chart_labels = [];
$chart_values = [];

$query_chart = $koneksi->query("
    SELECT
        asal_daerah,
        COUNT(*) AS total
    FROM kuliner
    WHERE asal_daerah IS NOT NULL
    AND TRIM(asal_daerah) != ''
    GROUP BY asal_daerah
    ORDER BY total DESC, asal_daerah ASC
");

if ($query_chart) {
    while ($row = $query_chart->fetch_assoc()) {
        $label = trim((string) ($row["asal_daerah"] ?? ""));

        $label = preg_replace(
            '/,\s*Provinsi Kalimantan Utara.*$/i',
            '',
            $label
        );

        $label = preg_replace(
            '/,\s*Kalimantan Utara.*$/i',
            '',
            $label
        );

        $label = preg_replace(
            '/^(Kabupaten|Kota)\s+/i',
            '',
            $label
        );

        $label = trim($label);

        $chart_labels[] = $label;
        $chart_values[] = (int) ($row["total"] ?? 0);
    }
}

$initial_admin =
    strtoupper(
        substr(
            $nama_admin,
            0,
            1
        )
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
        name="theme-color"
        content="#123d32"
    >

    <title>Dashboard Admin | Kuliner Kaltara</title>

    <link
        rel="icon"
        type="image/svg+xml"
        href="../assets/images/logo.svg"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

        <link rel="stylesheet" href="../assets/css/dashboard.css?v=6">

    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>

        <link rel="stylesheet" href="../assets/css/lenis.css?v=2" media="(min-width: 992px)">

</head>

<body>

    <div>

        <aside class="sidebar">

            <div>

                <div class="brand">

                    <div class="brand-title">

                        <img
                            src="../assets/images/logo.svg"
                            alt="Logo GoKaltara Kuliner" loading="eager" decoding="async" width="58" height="58">

                        <div>
                            GoKaltara
                            <br>
                            Kuliner
                        </div>

                    </div>

                    <div class="brand-subtitle">
                        Admin Panel
                    </div>

                </div>

                <nav class="sidebar-menu">

                    <a
                        href="dashboard.php"
                        class="sidebar-link active"
                    >

                        <i class="bi bi-grid-1x2-fill"></i>

                        <span>
                            Dashboard
                        </span>

                    </a>

                    <a
                        href="kuliner.php"
                        class="sidebar-link"
                    >

                        <i class="bi bi-fork-knife"></i>

                        <span>
                            Data Kuliner
                        </span>

                    </a>

                    <a
                        href="kategori.php"
                        class="sidebar-link"
                    >

                        <i class="bi bi-tags-fill"></i>

                        <span>
                            Kategori
                        </span>

                    </a>

                    <a
                        href="tambah_kuliner.php"
                        class="sidebar-link"
                    >

                        <i class="bi bi-plus-circle-fill"></i>

                        <span>
                            Tambah Kuliner
                        </span>

                    </a>

                </nav>

            </div>

            <div class="sidebar-bottom">

                <a
                    href="profil.php"
                    class="admin-profile"
                >

                    <?php if ($foto_profil !== ""): ?>

                        <img
                            src="<?= htmlspecialchars(gokaltara_profile_image_url_direct($foto_profil, true), ENT_QUOTES, "UTF-8") ?>"
                            alt="Foto Profil"
                            class="avatar avatar-image"
                         loading="eager" decoding="async">

                    <?php else: ?>

                        <div class="avatar">

                            <?= htmlspecialchars(
                                $initial_admin,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    <?php endif; ?>

                    <div class="flex-grow-1 min-w-0">

                        <div class="admin-name">

                            <?= htmlspecialchars(
                                $nama_admin,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                        <div class="admin-role">
                            Administrator
                        </div>

                    </div>

                    <i class="bi bi-chevron-right text-white-50"></i>

                </a>

                <a
                    href="../logout.php"
                    class="sidebar-logout"
                >

                    <i class="bi bi-box-arrow-right me-2"></i>

                    Logout

                </a>

            </div>

        </aside>

        <main class="main-content">

            <div class="mobile-header">

                <div class="mobile-header-left">

                    <a
                        href="../index.php"
                        class="mobile-brand"
                    >

                        <div class="mobile-brand-logo">

                            <img
                                src="../assets/images/logo.svg"
                                alt="GoKaltara Kuliner" loading="eager" decoding="async" width="58" height="58">

                        </div>

                        <div class="mobile-brand-info">

                            <div class="mobile-brand-title">
                                GoKaltara Kuliner
                            </div>

                            <div class="mobile-brand-subtitle">
                                Admin Panel
                            </div>

                        </div>

                    </a>

                </div>

                <a
                    href="profil.php"
                    class="mobile-header-profile"
                    title="Profil"
                >

                    <?php if ($foto_profil !== ""): ?>

                        <img
                            src="<?= htmlspecialchars(gokaltara_profile_image_url_direct($foto_profil, true), ENT_QUOTES, "UTF-8") ?>"
                            alt="Foto Profil"
                         loading="lazy" decoding="async">

                    <?php else: ?>

                        <?= htmlspecialchars(
                            $initial_admin,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    <?php endif; ?>

                </a>

            </div>

            <a href="../index.php" class="mobile-page-action">
                <i class="bi bi-arrow-left"></i>
                Kembali ke Beranda
            </a>

            <div class="container-fluid px-0">

                <div class="topbar">

                    <div>

                        <p class="eyebrow">
                            Administrator
                        </p>

                        <h1 class="dashboard-title">
                            Dashboard
                        </h1>

                        <p class="dashboard-subtitle">
                            Kelola dan pantau data Kuliner Kaltara.
                        </p>

                    </div>

                    <div class="topbar-actions">
                        <a
                            href="../index.php"
                            class="website-button"
                        >
                            <i class="bi bi-globe2 me-2"></i>
                            Kembali ke Beranda
                        </a>

                        <a
                            href="profil.php"
                            class="profile-top-button"
                        >
                            <i class="bi bi-person-circle"></i>
                            Profil
                        </a>
                    </div>

                </div>

                <div class="row g-3 mb-4">

                    <div class="col-6 col-xl-3">

                        <div class="stat-card">

                            <div class="p-4">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <div class="stat-label">
                                            Total Kuliner
                                        </div>

                                        <div class="stat-number">
                                            <?= $total_kuliner ?>
                                        </div>

                                        <div class="stat-description">
                                            Data kuliner tersimpan
                                        </div>

                                    </div>

                                    <div class="stat-icon">

                                        <i class="bi bi-fork-knife"></i>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-6 col-xl-3">

                        <div class="stat-card">

                            <div class="p-4">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <div class="stat-label">
                                            Total Kategori
                                        </div>

                                        <div class="stat-number">
                                            <?= $total_kategori ?>
                                        </div>

                                        <div class="stat-description">
                                            Kategori kuliner
                                        </div>

                                    </div>

                                    <div class="stat-icon">

                                        <i class="bi bi-tags"></i>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-6 col-xl-3">

                        <div class="stat-card">

                            <div class="p-4">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <div class="stat-label">
                                            Total Daerah
                                        </div>

                                        <div class="stat-number">
                                            <?= $total_daerah ?>
                                        </div>

                                        <div class="stat-description">
                                            Daerah asal kuliner
                                        </div>

                                    </div>

                                    <div class="stat-icon">

                                        <i class="bi bi-geo-alt"></i>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-6 col-xl-3">

                        <div class="stat-card">

                            <div class="p-4">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <div class="stat-label">
                                            Kuliner Terbaru
                                        </div>

                                        <div class="stat-number">
                                            <?= $total_kuliner_terbaru ?>
                                        </div>

                                        <div class="stat-description">
                                            Lima data terakhir
                                        </div>

                                    </div>

                                    <div class="stat-icon">

                                        <i class="bi bi-clock-history"></i>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="dashboard-card mb-4">

                    <div class="p-4">

                        <div class="d-flex align-items-start justify-content-between gap-3">

                            <div>

                                <h2 class="card-title-custom">
                                    Statistik Kuliner per Daerah
                                </h2>

                                <p class="card-subtitle-custom">
                                    Jumlah kuliner berdasarkan daerah asal.
                                </p>

                            </div>

                            <div class="stat-icon">

                                <i class="bi bi-bar-chart-fill"></i>

                            </div>

                        </div>

                    </div>

                    <div class="region-chart-wrapper">

                        <canvas id="regionChart"></canvas>

                    </div>

                </div>

                <div class="row g-3">

                    <div class="col-12">

                        <div class="dashboard-card">

                            <div class="p-4">

                                <div class="d-flex align-items-start justify-content-between gap-3">

                                    <div>

                                        <h5 class="card-title-custom">
                                            Data Kuliner Terbaru
                                        </h5>

                                        <p class="card-subtitle-custom">
                                            Lima data kuliner terakhir yang ditambahkan.
                                        </p>

                                    </div>

                                    <a
                                        href="kuliner.php"
                                        class="btn btn-sm btn-outline-success rounded-3"
                                    >
                                        Lihat Semua
                                    </a>

                                </div>

                            </div>

                            <div class="table-responsive">

                                <table class="table mb-0 align-middle latest-table">

                                    <thead>

                                        <tr>

                                            <th class="ps-4">
                                                Kuliner
                                            </th>

                                            <th>
                                                Daerah
                                            </th>

                                            <th>
                                                Kategori
                                            </th>

                                            <th class="text-end pe-4">
                                                Aksi
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php if ($query_terbaru && $query_terbaru->num_rows > 0): ?>

                                            <?php while ($data = $query_terbaru->fetch_assoc()): ?>

                                                <?php
                                                $foto_nama = trim(
                                                    (string) ($data["foto"] ?? "")
                                                );

                                                $foto_kuliner = gokaltara_image_url(
                                                    $foto_nama,
                                                    "../assets/images/",
                                                    true
                                                );

                                                $foto_kuliner_exists = $foto_nama !== "";
                                                ?>

                                                <tr>

                                                    <td class="ps-4">

                                                        <div class="food-wrapper">

                                                            <?php if (
                                                                $foto_kuliner_exists
                                                            ): ?>

                                                                <div class="food-image">

                                                                    <img
                                                                        src="<?= htmlspecialchars(
                                                                            $foto_kuliner,
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>"
                                                                        alt="<?= htmlspecialchars(
                                                                            $data["nama_kuliner"],
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>"
                                                                    loading="lazy" decoding="async">

                                                                </div>

                                                            <?php else: ?>

                                                                <div class="food-placeholder">

                                                                    <i class="bi bi-image"></i>

                                                                </div>

                                                            <?php endif; ?>

                                                            <div class="food-content">

                                                                <div class="food-name">

                                                                    <?= htmlspecialchars(
                                                                        $data["nama_kuliner"],
                                                                        ENT_QUOTES,
                                                                        "UTF-8"
                                                                    ) ?>

                                                                </div>

                                                                <div class="food-id">

                                                                    ID #
                                                                    <?= htmlspecialchars(
                                                                        $data["id_kuliner"],
                                                                        ENT_QUOTES,
                                                                        "UTF-8"
                                                                    ) ?>

                                                                </div>

                                                                <div class="mobile-food-meta">

                                                                    <span class="mobile-area">

                                                                        <?= htmlspecialchars(
                                                                            $data["asal_daerah"] ?: "-",
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>

                                                                    </span>

                                                                    <span class="mobile-dot">
                                                                        •
                                                                    </span>

                                                                    <span class="category-badge">

                                                                        <?= htmlspecialchars(
                                                                            $data["nama_kategori"] ?: "Tanpa kategori",
                                                                            ENT_QUOTES,
                                                                            "UTF-8"
                                                                        ) ?>

                                                                    </span>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </td>

                                                    <td>

                                                        <?= htmlspecialchars(
                                                            $data["asal_daerah"] ?: "-",
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>

                                                    </td>

                                                    <td>

                                                        <span class="category-badge">

                                                            <?= htmlspecialchars(
                                                                $data["nama_kategori"] ?: "Tanpa kategori",
                                                                ENT_QUOTES,
                                                                "UTF-8"
                                                            ) ?>

                                                        </span>

                                                    </td>

                                                    <td class="text-end pe-4">

                                                        <a
                                                            href="edit_kuliner.php?id=<?= urlencode(
                                                                (string) $data["id_kuliner"]
                                                            ) ?>"
                                                            class="edit-button"
                                                            title="Edit Kuliner"
                                                        >

                                                            <i class="bi bi-pencil"></i>

                                                        </a>

                                                    </td>

                                                </tr>

                                            <?php endwhile; ?>

                                        <?php else: ?>

                                            <tr>

                                                <td
                                                    colspan="4"
                                                    class="text-center py-5"
                                                >

                                                    <div class="text-muted">

                                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>

                                                        Belum ada data kuliner.

                                                    </div>

                                                </td>

                                            </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

    
<nav class="mobile-bottom-nav" aria-label="Navigasi admin">
    <a href="dashboard.php" class="mobile-nav-link active">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <a href="kuliner.php" class="mobile-nav-link">
        <i class="bi bi-fork-knife"></i>
        <span>Kuliner</span>
    </a>

    <a href="tambah_kuliner.php" class="mobile-nav-add" aria-label="Tambah data">
        <span><i class="bi bi-plus-lg"></i></span>
    </a>

    <a href="kategori.php" class="mobile-nav-link">
        <i class="bi bi-tags-fill"></i>
        <span>Kategori</span>
    </a>

    <a href="profil.php" class="mobile-nav-link">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
</nav>

    <script>
        document.addEventListener("DOMContentLoaded", function () {


        const regionLabels =
            <?= json_encode(
                $chart_labels,
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const regionValues =
            <?= json_encode(
                $chart_values,
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const regionCanvas =
            document.getElementById("regionChart");

        if (
            regionCanvas &&
            regionLabels.length > 0 &&
            regionValues.length > 0 &&
            typeof Chart !== "undefined"
        ) {

            const regionColors = [
                "#123D32",
                "#1D6250",
                "#C59A4A",
                "#C65B09",
                "#4E8170",
                "#8A6A3D",
                "#6B8E7E",
                "#A47C48"
            ];

            new Chart(
                regionCanvas,
                {
                    type: "bar",

                    data: {
                        labels: regionLabels,

                        datasets: [
                            {
                                label: "Jumlah Kuliner",
                                data: regionValues,

                                backgroundColor:
                                    regionValues.map(
                                        function (_, index) {
                                            return regionColors[
                                                index % regionColors.length
                                            ];
                                        }
                                    ),

                                borderWidth: 0,
                                borderRadius: 8,
                                borderSkipped: false,
                                barPercentage: 0.65,
                                categoryPercentage: 0.7
                            }
                        ]
                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        animation: {
                            duration: 500
                        },

                        plugins: {

                            legend: {
                                display: false
                            },

                            tooltip: {

                                displayColors: false,

                                backgroundColor: "#123D32",

                                titleColor: "#FFFFFF",

                                bodyColor: "#FFFFFF",

                                padding: 10,

                                cornerRadius: 8,

                                callbacks: {

                                    label: function (context) {

                                        return (
                                            " " +
                                            context.parsed.y +
                                            " kuliner"
                                        );

                                    }

                                }

                            }

                        },

                        scales: {

                            x: {

                                grid: {
                                    display: false
                                },

                                border: {
                                    display: false
                                },

                                ticks: {

                                    color: "#5F6B65",

                                    font: {
                                        size: 9
                                    },

                                    maxRotation: 35,

                                    minRotation: 0,

                                    autoSkip: false

                                }

                            },

                            y: {

                                beginAtZero: true,

                                border: {
                                    display: false
                                },

                                grid: {
                                    color: "#E5EBE8"
                                },

                                ticks: {

                                    color: "#7D8883",

                                    precision: 0,

                                    stepSize: 1,

                                    font: {
                                        size: 9
                                    }

                                }

                            }

                        }

                    }

                }
            );

        }

    
        });
</script>

    <script src="../assets/js/profile-live.js?v=1" defer></script>
    <script src="../assets/js/lenis.js?v=3" defer></script>

</body>

</html>
