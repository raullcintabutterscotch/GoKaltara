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
$_SESSION["username"] = (string) $user["username"];
$_SESSION["nama_lengkap"] = (string) $user["nama_lengkap"];
$_SESSION["foto_profil"] = (string) ($user["foto_profil"] ?? "");
$_SESSION["level"] = "admin";

function profile_json(array $payload): never
{
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function admin_profile_image_url(string $filename): string
{
    $filename = trim($filename);

    if ($filename === "") {
        return "";
    }

    return gokaltara_profile_image_url($filename, false);
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

    $stmt_cek = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        AND id_user != ?
        LIMIT 1
    ");

    if (!$stmt_cek) {
        profile_json([
            "success" => false,
            "message" => "Tidak dapat memeriksa username."
        ]);
    }

    $stmt_cek->bind_param("si", $username, $id_user);
    $stmt_cek->execute();

    $duplicate = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if ($duplicate) {
        profile_json([
            "success" => false,
            "message" => "Username sudah digunakan."
        ]);
    }

    $foto_lama = trim((string) ($user["foto_profil"] ?? ""));
    $foto_baru = $foto_lama;
    $foto_upload_baru = false;

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

        $foto_upload_baru = true;
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
        if ($foto_upload_baru) {
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

        if ($foto_upload_baru) {
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
        $foto_upload_baru &&
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
    $_SESSION["foto_profil"] = $foto_baru;
    $_SESSION["level"] = $level;

    $foto_url = "";

    if ($foto_baru !== "") {
        $foto_url = admin_profile_image_url($foto_baru);

        $foto_url .= (
            str_contains($foto_url, "?") ? "&" : "?"
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
$foto_profil_url = admin_profile_image_url($foto_profil);
$assets_base_url = rtrim(gokaltara_app_base_path(), "/") . "/assets";
$profile_css_version = (string) (@filemtime(__DIR__ . "/../assets/css/profil.css") ?: 1);
$profile_js_version = (string) (@filemtime(__DIR__ . "/../assets/js/profil.js") ?: 1);

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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

    <link rel="stylesheet" href="<?= htmlspecialchars($assets_base_url . "/css/profil.css?v=" . $profile_css_version, ENT_QUOTES, "UTF-8") ?>">
    <link rel="stylesheet" href="../assets/css/notifikasi.css?v=61">
    <link rel="stylesheet" href="../assets/css/lenis.css?v=1">
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <a href="dashboard.php" class="brand-title">
            <img
                src="../assets/images/logo.svg"
                alt="GoKaltara Kuliner"
                loading="eager"
                decoding="async"
                width="58"
                height="58"
            >
            <div>
                GoKaltara
                <strong>Kuliner</strong>
            </div>
        </a>

        <div class="brand-subtitle">ADMIN PANEL</div>
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

        <a href="profil.php" class="sidebar-link active">
            <i class="bi bi-person-fill"></i>
            <span>Profil</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="profil.php" class="admin-profile">
            <?php if ($foto_profil_url !== ""): ?>
                <img
                    src="<?= htmlspecialchars($foto_profil_url, ENT_QUOTES, "UTF-8") ?>"
                    alt="Foto Profil"
                    class="avatar avatar-image"
                    id="sidebarProfileImage"
                    loading="eager"
                    decoding="async"
                    width="42"
                    height="42"
                >
            <?php else: ?>
                <div class="avatar" id="sidebarProfileInitial">
                    <?= htmlspecialchars($initial_admin, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <div class="user-profile-text">
                <div class="admin-name" id="sidebarProfileName">
                    <?= $nama_admin ?>
                </div>
                <div class="admin-role">
                    <?= $level_admin === "admin" ? "Administrator" : "Pengguna" ?>
                </div>
            </div>

            <i class="bi bi-chevron-right profile-arrow"></i>
        </a>

        <a href="../logout.php" class="sidebar-logout">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="topbar">
        <div>
            <p class="eyebrow">AKUN</p>

            <h1 class="page-title">Profil Admin</h1>

            <p class="page-subtitle">
                Kelola foto dan informasi akun administrator.
            </p>
        </div>

        <a href="dashboard.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <div id="alertBox"></div>

    <section class="profile-layout">
        <div class="profile-photo-card">
            <div class="profile-card-header">
                <h2 class="card-title-custom">Foto Profil</h2>
                <p class="card-subtitle-custom">
                    Atur foto sebelum menyimpan perubahan.
                </p>
            </div>

            <div class="profile-photo-content">
                <div class="main-preview" id="mainPreview">
                    <?php if ($foto_profil_url !== ""): ?>
                        <img
                            src="<?= htmlspecialchars($foto_profil_url, ENT_QUOTES, "UTF-8") ?>"
                            id="previewFoto"
                            alt="Foto Profil"
                            loading="lazy"
                            decoding="async"
                        >
                    <?php else: ?>
                        <i class="bi bi-person-fill"></i>
                    <?php endif; ?>
                </div>

                <div class="profile-name" id="liveName">
                    <?= $nama_admin ?>
                </div>

                <div class="profile-username" id="liveUsername">
                    @<?= $username_admin ?>
                </div>

                <label for="fotoInput" class="profile-upload-button">
                    <i class="bi bi-image"></i>
                    Pilih Foto Baru
                </label>

                <input
                    type="file"
                    id="fotoInput"
                    accept=".jpg,.jpeg,.png,.webp"
                    hidden
                >

                <div class="profile-upload-help">
                    JPG, PNG, WEBP · Maksimal 3 MB
                </div>

                <div class="selected-file" id="selectedFile">
                    Belum memilih foto baru
                </div>
            </div>
        </div>

        <div class="profile-info-card">
            <div class="profile-card-header">
                <h2 class="card-title-custom">Informasi Akun</h2>
                <p class="card-subtitle-custom">
                    Perubahan akun tampil langsung pada preview.
                </p>
            </div>

            <div class="profile-form">
                <div class="profile-form-group">
                    <label for="namaLengkap" class="profile-form-label">
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        id="namaLengkap"
                        class="profile-form-control"
                        value="<?= $nama_admin ?>"
                        maxlength="100"
                        autocomplete="name"
                    >
                </div>

                <div class="profile-form-group">
                    <label for="username" class="profile-form-label">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        class="profile-form-control"
                        value="<?= $username_admin ?>"
                        minlength="3"
                        maxlength="50"
                        autocomplete="username"
                    >
                </div>

                <div class="profile-form-group">
                    <label for="level" class="profile-form-label">
                        Role
                    </label>

                    <select id="level" class="profile-form-control">
                        <option value="admin" <?= $level_admin === "admin" ? "selected" : "" ?>>Administrator</option>
                        <option value="user" <?= $level_admin === "user" ? "selected" : "" ?>>Pengguna</option>
                    </select>
                </div>

                <div class="profile-button-row">
                    <a href="dashboard.php" class="profile-cancel-button">
                        Batal
                    </a>

                    <button
                        type="button"
                        id="saveButton"
                        class="profile-save-button"
                    >
                        <i class="bi bi-floppy2-fill me-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content profile-modal">
            <div class="modal-header profile-modal-header">
                <div>
                    <h5 class="modal-title">Atur Foto Profil</h5>
                    <div class="modal-subtitle">
                        Geser dan zoom foto sesuai posisi yang kamu inginkan.
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    aria-label="Tutup"
                    data-bs-dismiss="modal"
                ></button>
            </div>

            <div class="modal-body">
                <div class="crop-container">
                    <img id="cropImage" alt="Atur Foto Profil" loading="lazy" decoding="async">
                </div>

                <div class="crop-actions">
                    <button type="button" id="zoomOut" class="crop-btn" title="Zoom Out">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <button type="button" id="zoomIn" class="crop-btn" title="Zoom In">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button type="button" id="moveLeft" class="crop-btn" title="Geser Kiri">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <button type="button" id="moveRight" class="crop-btn" title="Geser Kanan">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="button" id="moveUp" class="crop-btn" title="Geser Atas">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" id="moveDown" class="crop-btn" title="Geser Bawah">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" id="resetCrop" class="crop-btn" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <div class="modal-footer profile-modal-footer">
                <button type="button" class="profile-cancel-button" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="button" id="useCrop" class="profile-save-button">
                    Gunakan Foto
                </button>
            </div>
        </div>
    </div>
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

    <a href="tambah_kuliner.php" class="mobile-nav-link">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>
<script src="<?= htmlspecialchars($assets_base_url . "/js/profil.js?v=" . $profile_js_version, ENT_QUOTES, "UTF-8") ?>" defer></script>
</body>
</html>
