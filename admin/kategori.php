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
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = $koneksi->prepare("
        SELECT
            k.id_kategori,
            k.nama_kategori,
            COUNT(ku.id_kuliner) AS total_kuliner
        FROM kategori AS k
        LEFT JOIN kuliner AS ku
            ON ku.id_kategori = k.id_kategori
        WHERE k.nama_kategori LIKE ?
        GROUP BY k.id_kategori, k.nama_kategori
        ORDER BY k.id_kategori DESC
    ");

    $keyword = "%{$search}%";
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $result_kategori = $stmt->get_result();
} else {
    $result_kategori = $koneksi->query("
        SELECT
            k.id_kategori,
            k.nama_kategori,
            COUNT(ku.id_kuliner) AS total_kuliner
        FROM kategori AS k
        LEFT JOIN kuliner AS ku
            ON ku.id_kategori = k.id_kategori
        GROUP BY k.id_kategori, k.nama_kategori
        ORDER BY k.id_kategori DESC
    ");
}

$query_total_kategori = $koneksi->query("SELECT COUNT(*) AS total FROM kategori");
$total_kategori = $query_total_kategori
    ? (int) $query_total_kategori->fetch_assoc()["total"]
    : 0;

$query_total_kuliner = $koneksi->query("SELECT COUNT(*) AS total FROM kuliner");
$total_kuliner = $query_total_kuliner
    ? (int) $query_total_kuliner->fetch_assoc()["total"]
    : 0;

$query_total_daerah = $koneksi->query("
    SELECT COUNT(DISTINCT asal_daerah) AS total
    FROM kuliner
    WHERE asal_daerah IS NOT NULL
    AND TRIM(asal_daerah) != ''
");
$total_daerah = $query_total_daerah
    ? (int) $query_total_daerah->fetch_assoc()["total"]
    : 0;

$message = $_SESSION["kategori_message"] ?? "";
$message_type = $_SESSION["kategori_message_type"] ?? "success";
unset($_SESSION["kategori_message"], $_SESSION["kategori_message_type"]);

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori | Kuliner Kaltara</title>
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
    <link rel="stylesheet" href="../assets/css/kategori.css">
        <link rel="stylesheet" href="../assets/css/lenis.css?v=1">

</head>
<body>
    <div>
        <aside class="sidebar">
            <div>
                <div class="brand">
                    <div class="brand-title">
                        <img src="../assets/images/logo.svg" alt="Logo Kuliner Kaltara" loading="eager" decoding="async" width="58" height="58">
                        <div>
                            GoKaltara
                            <br>
                            Kuliner
                        </div>
                    </div>
                    <div class="brand-subtitle">Admin Panel</div>
                </div>

                <nav class="sidebar-menu">
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="kuliner.php" class="sidebar-link">
                        <i class="bi bi-fork-knife"></i>
                        <span>Data Kuliner</span>
                    </a>
                    <a href="kategori.php" class="sidebar-link active">
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
                        <img
                            src="<?= htmlspecialchars(gokaltara_profile_image_url($foto_profil, true)) ?>"
                            alt="Foto Profil"
                            class="avatar avatar-image"
                         loading="eager" decoding="async">
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
                        <h1 class="dashboard-title">Kategori</h1>
                        <p class="dashboard-subtitle">Kelola kategori yang digunakan pada data Kuliner Kaltara.</p>
                    </div>
                    <a href="tambah_kategori.php" class="website-button kategori-add-top">
                        <i class="bi bi-plus-lg me-2"></i>
                        Tambah Kategori
                    </a>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-<?= htmlspecialchars($message_type) ?> kategori-alert" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <div class="kategori-card-header">
                        <div>
                            <h5 class="card-title-custom">Data Kategori</h5>
                            <p class="card-subtitle-custom">Kelola nama kategori dan jumlah kuliner di setiap kategori.</p>
                        </div>
                    </div>

                    <div class="kategori-toolbar">
                        <form method="get" class="kategori-search-form">
                            <div class="kategori-search">
                                <i class="bi bi-search"></i>
                                <input
                                    type="search"
                                    name="search"
                                    value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Cari kategori..."
                                >
                            </div>
                            <button type="submit" class="kategori-search-button">Cari</button>
                            <?php if ($search !== ""): ?>
                                <a href="kategori.php" class="kategori-reset-button">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-responsive kategori-table-wrap">
                        <table class="table mb-0 align-middle kategori-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kategori</th>
                                    <th>Jumlah Kuliner</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_kategori && $result_kategori->num_rows > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($data = $result_kategori->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="kategori-number"><?= $no++ ?></span>
                                            </td>
                                            <td>
                                                <div class="kategori-name-wrap">
                                                    <div class="kategori-icon">
                                                        <i class="bi bi-tag-fill"></i>
                                                    </div>
                                                    <div>
                                                        <div class="kategori-name">
                                                            <?= htmlspecialchars($data["nama_kategori"]) ?>
                                                        </div>
                                                        <div class="kategori-id">
                                                            ID #<?= htmlspecialchars($data["id_kategori"]) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="kategori-count">
                                                    <?= (int) $data["total_kuliner"] ?> kuliner
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="kategori-actions">
                                                    <a
                                                        href="edit_kategori.php?id=<?= urlencode($data["id_kategori"]) ?>"
                                                        class="edit-button"
                                                        title="Edit Kategori"
                                                    >
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a
                                                        href="hapus_kategori.php?id=<?= urlencode($data["id_kategori"]) ?>"
                                                        class="delete-button"
                                                        title="Hapus Kategori"
                                                        onclick="return confirm('Hapus kategori ini? Jika masih digunakan oleh data kuliner, kategori tidak akan dihapus.')"
                                                    >
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                <?= $search !== "" ? "Kategori tidak ditemukan." : "Belum ada kategori." ?>
                                            </div>
                                        </td>
                                    </tr>
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
        <a href="kuliner.php" class="mobile-nav-link">
            <i class="bi bi-fork-knife"></i>
            <span>Kuliner</span>
        </a>
        <a href="tambah_kategori.php" class="mobile-nav-add kategori-mobile-add" aria-label="Tambah data">
            <span>
                <i class="bi bi-plus-lg"></i>
            </span>
        </a>
        <a href="kategori.php" class="mobile-nav-link active">
            <i class="bi bi-tags-fill"></i>
            <span>Kategori</span>
        </a>
        <a href="profil.php" class="mobile-nav-link">
            <i class="bi bi-person-circle"></i>
            <span>Profil</span>
        </a>
</nav>

    <script src="../assets/js/dashboard.js" defer></script>
    <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js" defer></script>
    <script src="../assets/js/lenis.js?v=1" defer></script>

</body>
</html>
