<?php

session_start();
require_once "../config/koneksi.php";
require_once "../config/image_optimizer.php";

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

$stmt_user = $koneksi->prepare("
    SELECT id_user, username, nama_lengkap, foto_profil, level
    FROM user
    WHERE id_user = ?
    LIMIT 1
");
$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();
$data_user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$data_user) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$nama_admin = $data_user["nama_lengkap"];
$foto_profil = $data_user["foto_profil"] ?? "";
$keyword = trim($_GET["q"] ?? "");

if ($keyword !== "") {
    $search = "%{$keyword}%";
    $stmt = $koneksi->prepare("
        SELECT
            k.id_kuliner,
            k.nama_kuliner,
            k.asal_daerah,
            k.foto,
            c.nama_kategori
        FROM kuliner AS k
        LEFT JOIN kategori AS c ON k.id_kategori = c.id_kategori
        WHERE
            k.nama_kuliner LIKE ? OR
            k.asal_daerah LIKE ? OR
            c.nama_kategori LIKE ?
        ORDER BY k.id_kuliner DESC
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result_kuliner = $stmt->get_result();
} else {
    $result_kuliner = $koneksi->query("
        SELECT
            k.id_kuliner,
            k.nama_kuliner,
            k.asal_daerah,
            k.foto,
            c.nama_kategori
        FROM kuliner AS k
        LEFT JOIN kategori AS c ON k.id_kategori = c.id_kategori
        ORDER BY k.id_kuliner DESC
    ");
}

$total_data = $result_kuliner ? $result_kuliner->num_rows : 0;
$flash_success = $_SESSION["flash_success"] ?? "";
$flash_error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_success"], $_SESSION["flash_error"]);

$total_kategori = 0;
$query = $koneksi->query("SELECT COUNT(*) AS total FROM kategori");
if ($query) {
    $total_kategori = (int) $query->fetch_assoc()["total"];
}

$total_daerah = 0;
$query = $koneksi->query("
    SELECT COUNT(DISTINCT asal_daerah) AS total
    FROM kuliner
    WHERE asal_daerah IS NOT NULL AND TRIM(asal_daerah) != ''
");
if ($query) {
    $total_daerah = (int) $query->fetch_assoc()["total"];
}

$total_kuliner = 0;
$query = $koneksi->query("SELECT COUNT(*) AS total FROM kuliner");
if ($query) {
    $total_kuliner = (int) $query->fetch_assoc()["total"];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kuliner | Kuliner Kaltara</title>
    <link
        rel="icon"
        href="../assets/images/logo.svg"
        sizes="48x48"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=4">
    <link rel="stylesheet" href="../assets/css/kuliner.css">
        <link rel="stylesheet" href="../assets/css/lenis.css?v=1">

</head>
<body>
    <div>
        <aside class="sidebar">
            <div>
                <div class="brand">
                    <div class="brand-title">
                        <img src="../assets/images/logo.svg" alt="Logo Kuliner Kaltara" loading="eager" decoding="async" width="58" height="58">
                        <div>GoKaltara<br>Kuliner</div>
                    </div>
                    <div class="brand-subtitle">Admin Panel</div>
                </div>

                <nav class="sidebar-menu">
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="kuliner.php" class="sidebar-link active">
                        <i class="bi bi-fork-knife"></i>
                        <span>Data Kuliner</span>
                    </a>
                    <a href="kategori.php" class="sidebar-link">
                        <i class="bi bi-tags-fill"></i>
                        <span>Kategori</span>
                    </a>
                    <a href="tambah_kuliner.php" class="sidebar-link">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Kuliner</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-bottom">
                <a href="profil.php" class="admin-profile">
                    <?php if (!empty($foto_profil)): ?>
                        <img src="<?= htmlspecialchars(gokaltara_profile_image_url($foto_profil, true)) ?>" alt="Foto Profil" class="avatar avatar-image" loading="eager" decoding="async">
                    <?php else: ?>
                        <div class="avatar">
                            <?= htmlspecialchars(strtoupper(substr($nama_admin, 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="admin-name"><?= htmlspecialchars($nama_admin) ?></div>
                        <div class="admin-role">Administrator</div>
                    </div>
                    <i class="bi bi-chevron-right text-white-50"></i>
                </a>
                <a href="../logout.php" class="sidebar-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="container-fluid px-0">
                <div class="topbar">
                    <div>
                        <p class="eyebrow">Administrator</p>
                        <h1 class="dashboard-title">Data Kuliner</h1>
                        <p class="dashboard-subtitle">Kelola seluruh data kuliner khas Kalimantan Utara.</p>
                    </div>
                    <a href="tambah_kuliner.php" class="website-button page-action-button">
                        <i class="bi bi-plus-lg me-2"></i>
                        Tambah Kuliner
                    </a>
                </div>

                <?php if ($flash_success !== ""): ?>
                    <div class="alert alert-success page-alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($flash_success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flash_error !== ""): ?>
                    <div class="alert alert-danger page-alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($flash_error) ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <div class="page-card-header">
                        <div>
                            <h5 class="card-title-custom">Daftar Kuliner</h5>
                            <p class="card-subtitle-custom">Kelola, edit, dan hapus data kuliner.</p>
                        </div>
                        <form method="get" class="search-form">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="search" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari kuliner...">
                            </div>
                            <?php if ($keyword !== ""): ?>
                                <a href="kuliner.php" class="search-reset">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0 align-middle kuliner-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Kuliner</th>
                                    <th>Daerah</th>
                                    <th>Kategori</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_kuliner && $result_kuliner->num_rows > 0): ?>
                                    <?php while ($data = $result_kuliner->fetch_assoc()): ?>
                                        <?php
                                        $foto = $data["foto"] ?? "";
                                        $foto_path = gokaltara_image_url($foto, "../assets/images/", true);
                                        $foto_full_path = gokaltara_image_url($foto, "../assets/images/", false);
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="food-wrapper">
                                                    <?php if ($foto !== ""): ?>
                                                        <div class="food-image">
                                                            <img src="<?= htmlspecialchars($foto_path) ?>" alt="<?= htmlspecialchars($data["nama_kuliner"]) ?>" loading="lazy" decoding="async">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="food-placeholder">
                                                            <i class="bi bi-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="food-content">
                                                        <div class="food-name"><?= htmlspecialchars($data["nama_kuliner"]) ?></div>
                                                        <div class="food-id">ID #<?= htmlspecialchars($data["id_kuliner"]) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($data["asal_daerah"] ?: "-") ?></td>
                                            <td>
                                                <span class="category-badge">
                                                    <?= htmlspecialchars($data["nama_kategori"] ?: "Tanpa kategori") ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="table-actions">
                                                    <a href="edit_kuliner.php?id=<?= urlencode($data["id_kuliner"]) ?>" class="edit-button" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="hapus_kuliner.php" method="post" onsubmit="return confirm('Hapus data kuliner ini?');">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($data["id_kuliner"]) ?>">
                                                        <button type="submit" class="delete-button" title="Hapus">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>

                                                <td
                                                    colspan="4"
                                                    class="text-center py-5">

                                                    <div class="text-muted">

                                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>

                                                        Belum ada data kuliner.

                                                    </div>

                                                </td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <nav class="mobile-bottom-nav">
        <a href="dashboard.php" class="mobile-nav-link">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a href="kuliner.php" class="mobile-nav-link active">
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

    <script src="../assets/js/dashboard.js" defer></script>
    <script src="../assets/js/kuliner.js" defer></script>
    <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js" defer></script>
    <script src="../assets/js/lenis.js?v=1" defer></script>

</body>
</html>
