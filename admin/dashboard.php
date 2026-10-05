<?php

session_start();

require_once "../config/koneksi.php";

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true ||
    !isset($_SESSION["level"]) ||
    $_SESSION["level"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION["id_user"] ?? 0;
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

$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();

$result_user = $stmt_user->get_result();
$data_user = $result_user->fetch_assoc();

$stmt_user->close();

if (!$data_user) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

$nama_admin = $data_user["nama_lengkap"];
$foto_profil = $data_user["foto_profil"] ?? "";

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM kuliner
");

$total_kuliner = 0;

if ($query) {
    $total_kuliner = (int) $query->fetch_assoc()["total"];
}

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM kategori
");

$total_kategori = 0;

if ($query) {
    $total_kategori = (int) $query->fetch_assoc()["total"];
}

$query = $koneksi->query("
    SELECT COUNT(DISTINCT asal_daerah) AS total
    FROM kuliner
    WHERE asal_daerah IS NOT NULL
    AND TRIM(asal_daerah) != ''
");

$total_daerah = 0;

if ($query) {
    $total_daerah = (int) $query->fetch_assoc()["total"];
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

        $label = trim($row["asal_daerah"]);

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
        $chart_values[] = (int) $row["total"];
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

    <title>Dashboard Admin | Kuliner Kaltara</title>
    
    <link
        rel="icon"
        href="../assets/images/logo.svg"
        sizes="48x48"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <div>

        <aside class="sidebar">

            <div>

                <div class="brand">

                    <div class="brand-title">

                        <img
                            src="../assets/images/logo.svg"
                            alt="Logo Kuliner Kaltara"
                        >

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

                    <?php if (!empty($foto_profil)): ?>

                        <img
                            src="../assets/images/profil/<?= htmlspecialchars($foto_profil) ?>"
                            alt="Foto Profil"
                            class="avatar avatar-image"
                        >

                    <?php else: ?>

                        <div class="avatar">

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr($nama_admin, 0, 1)
                                )
                            ) ?>

                        </div>

                    <?php endif; ?>

                    <div class="flex-grow-1 min-w-0">

                        <div class="admin-name">

                            <?= htmlspecialchars($nama_admin) ?>

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

                    <a
                        href="../index.php"
                        class="website-button"
                    >

                        <i class="bi bi-globe2 me-2"></i>

                        Lihat Website

                    </a>

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

                    <div
                        style="
                            position: relative;
                            width: 100%;
                            height: 340px;
                            padding: 0 22px 22px;
                        "
                    >

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

                                        <?php if (
                                            $query_terbaru &&
                                            $query_terbaru->num_rows > 0
                                        ): ?>

                                            <?php while (
                                                $data =
                                                $query_terbaru->fetch_assoc()
                                            ): ?>

                                                <?php

                                                $foto_kuliner =
                                                    "../assets/images/" .
                                                    ($data["foto"] ?? "");

                                                ?>

                                                <tr>

                                                    <td class="ps-4">

                                                        <div class="food-wrapper">

                                                            <?php if (
                                                                !empty($data["foto"]) &&
                                                                file_exists($foto_kuliner)
                                                            ): ?>

                                                                <div class="food-image">

                                                                    <img
                                                                        src="<?= htmlspecialchars(
                                                                            $foto_kuliner
                                                                        ) ?>"
                                                                        alt="<?= htmlspecialchars(
                                                                            $data["nama_kuliner"]
                                                                        ) ?>"
                                                                    >

                                                                </div>

                                                            <?php else: ?>

                                                                <div class="food-placeholder">

                                                                    <i class="bi bi-image"></i>

                                                                </div>

                                                            <?php endif; ?>

                                                            <div class="food-content">

                                                                <div class="food-name">

                                                                    <?= htmlspecialchars(
                                                                        $data["nama_kuliner"]
                                                                    ) ?>

                                                                </div>

                                                                <div class="food-id">

                                                                    ID #<?= htmlspecialchars(
                                                                        $data["id_kuliner"]
                                                                    ) ?>

                                                                </div>

                                                                <div class="mobile-food-meta">

                                                                    <span class="mobile-area">

                                                                        <?= htmlspecialchars(
                                                                            $data["asal_daerah"] ?: "-"
                                                                        ) ?>

                                                                    </span>

                                                                    <span class="mobile-dot">
                                                                        •
                                                                    </span>

                                                                    <span class="category-badge">

                                                                        <?= htmlspecialchars(
                                                                            $data["nama_kategori"] ?: "Tanpa kategori"
                                                                        ) ?>

                                                                    </span>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </td>

                                                    <td>

                                                        <?= htmlspecialchars(
                                                            $data["asal_daerah"] ?: "-"
                                                        ) ?>

                                                    </td>

                                                    <td>

                                                        <span class="category-badge">

                                                            <?= htmlspecialchars(
                                                                $data["nama_kategori"] ?: "Tanpa kategori"
                                                            ) ?>

                                                        </span>

                                                    </td>

                                                    <td class="text-end pe-4">

                                                        <a
                                                            href="edit_kuliner.php?id=<?= urlencode(
                                                                $data["id_kuliner"]
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

    <nav class="mobile-bottom-nav">

        <a
            href="dashboard.php"
            class="mobile-nav-link active"
        >

            <i class="bi bi-grid-1x2-fill"></i>

            <span>
                Dashboard
            </span>

        </a>

        <a
            href="kuliner.php"
            class="mobile-nav-link"
        >

            <i class="bi bi-fork-knife"></i>

            <span>
                Kuliner
            </span>

        </a>

        <a
            href="tambah_kuliner.php"
            class="mobile-nav-add"
        >

            <span>

                <i class="bi bi-plus-lg"></i>

            </span>

        </a>

        <a
            href="kategori.php"
            class="mobile-nav-link"
        >

            <i class="bi bi-tags-fill"></i>

            <span>
                Kategori
            </span>

        </a>

        <a
            href="profil.php"
            class="mobile-nav-link"
        >

            <i class="bi bi-person-circle"></i>

            <span>
                Profil
            </span>

        </a>

    </nav>

    <script>

        const regionLabels = <?= json_encode(
            $chart_labels,
            JSON_UNESCAPED_UNICODE
        ) ?>;

        const regionValues = <?= json_encode(
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
                                                index %
                                                regionColors.length
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

    </script>

    <script src="../assets/js/dashboard.js"></script>

</body>

</html>