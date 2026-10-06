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
$id_kategori = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_kategori) {
    header("Location: kategori.php");
    exit;
}

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

$stmt = $koneksi->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE id_kategori = ? LIMIT 1");
$stmt->bind_param("i", $id_kategori);
$stmt->execute();
$data_kategori = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data_kategori) {
    $_SESSION["kategori_message"] = "Kategori tidak ditemukan.";
    $_SESSION["kategori_message_type"] = "danger";
    header("Location: kategori.php");
    exit;
}

$nama_admin = $data_user["nama_lengkap"];
$foto_profil = $data_user["foto_profil"] ?? "";
$error = "";
$nama_kategori = $data_kategori["nama_kategori"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_kategori = trim($_POST["nama_kategori"] ?? "");

    if ($nama_kategori === "") {
        $error = "Nama kategori wajib diisi.";
    } else {
        $stmt = $koneksi->prepare("
            SELECT id_kategori
            FROM kategori
            WHERE LOWER(TRIM(nama_kategori)) = LOWER(TRIM(?))
            AND id_kategori != ?
            LIMIT 1
        ");
        $stmt->bind_param("si", $nama_kategori, $id_kategori);
        $stmt->execute();
        $cek = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cek) {
            $error = "Kategori tersebut sudah ada.";
        } else {
            $stmt = $koneksi->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
            $stmt->bind_param("si", $nama_kategori, $id_kategori);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION["kategori_message"] = "Kategori berhasil diperbarui.";
                $_SESSION["kategori_message_type"] = "success";
                header("Location: kategori.php");
                exit;
            }

            $error = "Kategori gagal diperbarui.";
            $stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori | Kuliner Kaltara</title>
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
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=5">
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
                        <h1 class="dashboard-title">Edit Kategori</h1>
                        <p class="dashboard-subtitle">Perbarui nama kategori yang tersimpan.</p>
                    </div>
                    <a href="kategori.php" class="website-button">
                        <i class="bi bi-arrow-left me-2"></i>
                        Kembali
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12 col-xl-8">
                        <div class="dashboard-card">
                            <div class="kategori-form-header">
                                <div class="kategori-form-icon">
                                    <i class="bi bi-pencil-square"></i>
                                </div>
                                <div>
                                    <h5 class="card-title-custom">Edit Kategori</h5>
                                    <p class="card-subtitle-custom">Ubah nama kategori sesuai kebutuhan.</p>
                                </div>
                            </div>

                            <?php if ($error !== ""): ?>
                                <div class="alert alert-danger kategori-alert kategori-form-alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="kategori-form">
                                <div class="kategori-field">
                                    <label for="nama_kategori">Nama Kategori</label>
                                    <input
                                        type="text"
                                        id="nama_kategori"
                                        name="nama_kategori"
                                        value="<?= htmlspecialchars($nama_kategori) ?>"
                                        maxlength="100"
                                        required
                                    >
                                </div>

                                <div class="kategori-form-actions">
                                    <a href="kategori.php" class="kategori-secondary-button">Batal</a>
                                    <button type="submit" class="kategori-primary-button">
                                        <i class="bi bi-check-lg me-2"></i>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
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
            <span><i class="bi bi-plus-lg"></i></span>
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

    <script src="../assets/js/profile-live.js?v=1" defer></script>
    <script src="../assets/js/dashboard.js" defer></script>
    <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js" defer></script>
    <script src="../assets/js/lenis.js?v=1" defer></script>

</body>
</html>
