<?php
declare(strict_types=1);

require_once "../api/_auth.php";
require_once "../config/image_optimizer.php";

$id_user = requirePageLogin($koneksi);

$stmt_user = $koneksi->prepare("
    SELECT
        id_user,
        username,
        nama_lengkap,
        level,
        foto_profil
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

$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if (($user["level"] ?? "") !== "admin") {
    header("Location: ../profil-user.php");
    exit;
}

$_SESSION["login"] = true;
$_SESSION["id_user"] = (int) $user["id_user"];
$_SESSION["username"] = $user["username"];
$_SESSION["nama_lengkap"] = $user["nama_lengkap"];
$_SESSION["level"] = "admin";

$profile_dir = __DIR__ . "/../assets/images/profil/";

if (!is_dir($profile_dir)) {
    @mkdir($profile_dir, 0755, true);
}

function profile_json(array $payload): never
{
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function delete_profile_file(string $filename, string $profile_dir): void
{
    $filename = basename($filename);

    if ($filename === "") {
        return;
    }

    $path = rtrim($profile_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_lengkap = trim((string) ($_POST["nama_lengkap"] ?? ""));
    $username = trim((string) ($_POST["username"] ?? ""));
    $level = trim((string) ($_POST["level"] ?? "admin"));

    if ($nama_lengkap === "") {
        profile_json([
            "success" => false,
            "message" => "Nama lengkap wajib diisi."
        ]);
    }

    if (mb_strlen($nama_lengkap) < 3) {
        profile_json([
            "success" => false,
            "message" => "Nama lengkap minimal 3 karakter."
        ]);
    }

    if ($username === "") {
        profile_json([
            "success" => false,
            "message" => "Username wajib diisi."
        ]);
    }

    if (mb_strlen($username) < 3) {
        profile_json([
            "success" => false,
            "message" => "Username minimal 3 karakter."
        ]);
    }

    if (!in_array($level, ["admin", "user"], true)) {
        profile_json([
            "success" => false,
            "message" => "Role tidak valid."
        ]);
    }

    $stmt_check = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        AND id_user != ?
        LIMIT 1
    ");

    if (!$stmt_check) {
        profile_json([
            "success" => false,
            "message" => "Tidak dapat memeriksa username."
        ]);
    }

    $stmt_check->bind_param("si", $username, $id_user);
    $stmt_check->execute();

    $duplicate = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($duplicate) {
        profile_json([
            "success" => false,
            "message" => "Username sudah digunakan."
        ]);
    }

    $foto_lama = trim((string) ($user["foto_profil"] ?? ""));
    $foto_baru = $foto_lama;
    $upload_baru = false;

    if (
        isset($_FILES["foto_profil"]) &&
        ($_FILES["foto_profil"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        $processed = gokaltara_process_upload(
            $_FILES["foto_profil"],
            __DIR__ . "/../assets/images/profil",
            "profil_" . $id_user,
            3 * 1024 * 1024,
            600,
            240,
            80,
            78
        );

        if (!$processed["success"]) {
            profile_json([
                "success" => false,
                "message" => $processed["message"] ?? "Foto profil gagal diproses."
            ]);
        }

        $foto_baru = (string) ($processed["filename"] ?? "");

        if ($foto_baru === "") {
            profile_json([
                "success" => false,
                "message" => "Foto profil gagal diproses."
            ]);
        }

        $upload_baru = true;
    }

    $stmt_update = $koneksi->prepare("
        UPDATE user
        SET
            nama_lengkap = ?,
            username = ?,
            level = ?,
            foto_profil = ?
        WHERE id_user = ?
    ");

    if (!$stmt_update) {
        if ($upload_baru) {
            gokaltara_delete_optimized_image(
                $foto_baru,
                __DIR__ . "/../assets/images/profil"
            );
        }

        profile_json([
            "success" => false,
            "message" => "Profil gagal diperbarui."
        ]);
    }

    $stmt_update->bind_param(
        "ssssi",
        $nama_lengkap,
        $username,
        $level,
        $foto_baru,
        $id_user
    );

    if (!$stmt_update->execute()) {
        $stmt_update->close();

        if ($upload_baru) {
            gokaltara_delete_optimized_image(
                $foto_baru,
                __DIR__ . "/../assets/images/profil"
            );
        }

        profile_json([
            "success" => false,
            "message" => "Profil gagal diperbarui."
        ]);
    }

    $stmt_update->close();

    if (
        $upload_baru &&
        $foto_lama !== "" &&
        $foto_lama !== $foto_baru
    ) {
        gokaltara_delete_optimized_image(
            $foto_lama,
            __DIR__ . "/../assets/images/profil"
        );
    }

    $_SESSION["login"] = true;
    $_SESSION["id_user"] = $id_user;
    $_SESSION["username"] = $username;
    $_SESSION["nama_lengkap"] = $nama_lengkap;
    $_SESSION["level"] = $level;
    $_SESSION["foto_profil"] = $foto_baru;

    $foto_url = "";

    if ($foto_baru !== "") {
        $foto_url = gokaltara_profile_image_url(
            $foto_baru,
            false
        );

        $foto_url .= (
            str_contains($foto_url, "?")
                ? "&"
                : "?"
        ) . "v=" . time();
    }

    profile_json([
        "success" => true,
        "message" => "Profil berhasil diperbarui.",
        "nama_lengkap" => $nama_lengkap,
        "username" => $username,
        "level" => $level,
        "foto_profil" => $foto_baru,
        "foto_profil_url" => $foto_url
    ]);
}

$nama_admin = htmlspecialchars(
    (string) $user["nama_lengkap"],
    ENT_QUOTES,
    "UTF-8"
);

$username_admin = htmlspecialchars(
    (string) $user["username"],
    ENT_QUOTES,
    "UTF-8"
);

$level_admin = (string) $user["level"];
$foto_profil = trim((string) ($user["foto_profil"] ?? ""));
$foto_profil_url = "";

if ($foto_profil !== "") {
    $foto_profil_url = gokaltara_profile_image_url(
        $foto_profil,
        false
    );
}

$initial_admin = strtoupper(
    mb_substr(
        trim((string) $user["nama_lengkap"]) ?: "A",
        0,
        1
    )
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#123d32">
    <meta name="description" content="Kelola profil administrator GoKaltara Kuliner.">
    <title>Profil Admin | GoKaltara Kuliner</title>

    <link rel="icon" href="../assets/images/logo.svg" sizes="48x48">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    >

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=5">
    <link rel="stylesheet" href="../assets/css/profil.css?v=50">
    <link rel="stylesheet" href="../assets/css/lenis.css?v=1">

</head>
<body class="admin-profile-page">

<div class="admin-layout">

    <aside class="sidebar">

        <div class="sidebar-top">

            <div class="brand">
                <a href="dashboard.php" class="brand-title">
                    <img
                        src="../assets/images/logo.svg"
                        alt="GoKaltara Kuliner"
                        width="58"
                        height="58"
                        loading="eager"
                        decoding="async"
                    >
                    <div>
                        GoKaltara<br>
                        <strong>Kuliner</strong>
                    </div>
                </a>
                <div class="brand-subtitle">Admin Panel</div>
            </div>

            <nav class="sidebar-menu" aria-label="Menu admin">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
                <a href="kuliner.php" class="sidebar-link">
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

            <a href="profil.php" class="sidebar-profile active">
                <div class="sidebar-avatar-wrap">
                    <?php if ($foto_profil_url !== ""): ?>
                        <img
                            src="<?= htmlspecialchars($foto_profil_url, ENT_QUOTES, "UTF-8") ?>"
                            class="sidebar-avatar-image"
                            alt="Foto Profil"
                            id="sidebarProfileImage"
                            width="44"
                            height="44"
                            loading="eager"
                            decoding="async"
                        >
                    <?php else: ?>
                        <span class="sidebar-avatar-initial" id="sidebarProfileInitial">
                            <?= htmlspecialchars($initial_admin, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="sidebar-profile-text">
                    <div class="sidebar-profile-name" id="sidebarProfileName">
                        <?= $nama_admin ?>
                    </div>
                    <div class="sidebar-profile-role">
                        Administrator
                    </div>
                </div>

                <i class="bi bi-chevron-right sidebar-profile-arrow"></i>
            </a>

            <a href="../logout.php" class="sidebar-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="mobile-admin-header">
            <div>
                <div class="mobile-admin-eyebrow">AKUN</div>
                <div class="mobile-admin-title">Profil Admin</div>
            </div>

            <a
                href="../logout.php"
                class="mobile-logout-button"
                aria-label="Logout"
                title="Logout"
            >
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>

        <div class="page-container">

            <header class="page-header">
                <div>
                    <div class="page-eyebrow">AKUN</div>
                    <h1>Profil Admin</h1>
                    <p>Kelola foto dan informasi akun administrator.</p>
                </div>

                <a href="dashboard.php" class="back-button">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </header>

            <div id="alertBox" aria-live="polite"></div>

            <section class="profile-grid">

                <article class="profile-card photo-card">
                    <div class="card-header-custom">
                        <h2>Foto Profil</h2>
                        <p>Atur posisi foto sebelum menyimpannya.</p>
                    </div>

                    <div class="photo-content">
                        <div class="main-preview" id="mainPreview">
                            <?php if ($foto_profil_url !== ""): ?>
                                <img
                                    src="<?= htmlspecialchars($foto_profil_url, ENT_QUOTES, "UTF-8") ?>"
                                    id="mainPreviewImage"
                                    alt="Foto Profil"
                                    width="220"
                                    height="220"
                                    loading="eager"
                                    decoding="async"
                                >
                            <?php else: ?>
                                <span id="mainPreviewInitial" class="preview-initial">
                                    <?= htmlspecialchars($initial_admin, ENT_QUOTES, "UTF-8") ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="live-name" id="liveName">
                            <?= $nama_admin ?>
                        </div>

                        <div class="live-username" id="liveUsername">
                            @<?= $username_admin ?>
                        </div>

                        <div class="live-role" id="liveRole">
                            <?= $level_admin === "admin" ? "Administrator" : "User" ?>
                        </div>

                        <label for="fotoInput" class="profile-upload-button">
                            <i class="bi bi-image"></i>
                            <span>Pilih Foto Baru</span>
                        </label>

                        <input
                            type="file"
                            id="fotoInput"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <div class="upload-help">
                            JPG, PNG, WEBP · Maksimal 3 MB
                        </div>

                        <div class="selected-file" id="selectedFile">
                            Belum memilih foto baru
                        </div>
                    </div>
                </article>

                <article class="profile-card info-card">
                    <div class="card-header-custom">
                        <h2>Informasi Akun</h2>
                        <p>Perubahan langsung terlihat pada preview.</p>
                    </div>

                    <div class="profile-form">
                        <div class="form-group">
                            <label for="namaLengkap">Nama Lengkap</label>
                            <input
                                type="text"
                                id="namaLengkap"
                                value="<?= $nama_admin ?>"
                                maxlength="100"
                                autocomplete="name"
                            >
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input
                                type="text"
                                id="username"
                                value="<?= $username_admin ?>"
                                minlength="3"
                                maxlength="50"
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label for="level">Role</label>
                            <select id="level">
                                <option value="admin" <?= $level_admin === "admin" ? "selected" : "" ?>>Administrator</option>
                                <option value="user" <?= $level_admin === "user" ? "selected" : "" ?>>User</option>
                            </select>
                        </div>

                        <div class="account-status">
                            <div>
                                <div class="status-title">Status Akun</div>
                                <div class="status-description">Akun aktif dan siap digunakan.</div>
                            </div>
                            <div class="status-active">
                                <span></span>
                                Aktif
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="dashboard.php" class="button-secondary">Batal</a>
                            <button type="button" id="saveButton" class="button-primary">
                                <i class="bi bi-floppy2-fill"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </div>
                </article>

            </section>
        </div>
    </main>
</div>

<nav class="mobile-bottom-nav" aria-label="Navigasi admin mobile">
    <a href="dashboard.php" class="mobile-nav-link">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>
    <a href="kuliner.php" class="mobile-nav-link">
        <i class="bi bi-fork-knife"></i>
        <span>Kuliner</span>
    </a>
    <a href="tambah_kuliner.php" class="mobile-nav-link mobile-nav-main">
        <i class="bi bi-plus-lg"></i>
        <span>Tambah</span>
    </a>
    <a href="kategori.php" class="mobile-nav-link">
        <i class="bi bi-tags-fill"></i>
        <span>Kategori</span>
    </a>
    <a href="profil.php" class="mobile-nav-link active">
        <i class="bi bi-person-fill"></i>
        <span>Profil</span>
    </a>
</nav>

<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content crop-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Atur Foto Profil</h5>
                    <p>Geser dan zoom sesuai posisi yang diinginkan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Atur Foto Profil">
                </div>

                <div class="crop-actions">
                    <button type="button" class="crop-btn" id="zoomOut" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                    <button type="button" class="crop-btn" id="zoomIn" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                    <button type="button" class="crop-btn" id="moveLeft" title="Geser Kiri"><i class="bi bi-arrow-left"></i></button>
                    <button type="button" class="crop-btn" id="moveRight" title="Geser Kanan"><i class="bi bi-arrow-right"></i></button>
                    <button type="button" class="crop-btn" id="moveUp" title="Geser Atas"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="crop-btn" id="moveDown" title="Geser Bawah"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="crop-btn" id="resetCrop" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="button-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="useCrop" class="button-primary">Gunakan Foto</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>
<script src="../assets/js/profil.js?v=51" defer></script>

    <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js" defer></script>
    <script src="../assets/js/lenis.js?v=1" defer></script>

</body>
</html>
