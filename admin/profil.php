<?php

declare(strict_types=1);

require_once "../api/_auth.php";
require_once "../config/profile_images.php";

$id_user = requireAdminPage($koneksi);

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

$user = $stmt_user
    ->get_result()
    ->fetch_assoc();

$stmt_user->close();

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

function profileJson(array $data): never
{
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store");

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama_lengkap = trim(
        (string) ($_POST["nama_lengkap"] ?? "")
    );

    $username = trim(
        (string) ($_POST["username"] ?? "")
    );

    $level = "admin";

    if ($nama_lengkap === "") {
        profileJson([
            "success" => false,
            "message" => "Nama lengkap wajib diisi."
        ]);
    }

    if (mb_strlen($nama_lengkap) < 3) {
        profileJson([
            "success" => false,
            "message" => "Nama lengkap minimal 3 karakter."
        ]);
    }

    if ($username === "") {
        profileJson([
            "success" => false,
            "message" => "Username wajib diisi."
        ]);
    }

    if (mb_strlen($username) < 3) {
        profileJson([
            "success" => false,
            "message" => "Username minimal 3 karakter."
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
        profileJson([
            "success" => false,
            "message" => "Gagal memeriksa username."
        ]);
    }

    $stmt_check->bind_param(
        "si",
        $username,
        $id_user
    );

    $stmt_check->execute();

    $duplicate = $stmt_check
        ->get_result()
        ->fetch_assoc();

    $stmt_check->close();

    if ($duplicate) {
        profileJson([
            "success" => false,
            "message" => "Username sudah digunakan."
        ]);
    }

    $foto_lama = trim((string) ($user["foto_profil"] ?? ""));
    $foto_baru = $foto_lama;
    $foto_upload_baru = false;

    if (
        isset($_FILES["foto_profil"]) &&
        (int) ($_FILES["foto_profil"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        $processed = gokaltara_save_profile_upload_direct($_FILES["foto_profil"], (int) $id_user);

        if (!$processed["success"]) {
            profileJson([
                "success" => false,
                "message" => $processed["message"] ?? "Foto profil gagal diproses."
            ]);
        }

        $foto_baru = (string) ($processed["filename"] ?? "");
        $foto_upload_baru = $foto_baru !== "";
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
        profileJson([
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

        profileJson([
            "success" => false,
            "message" => "Profil gagal disimpan."
        ]);
    }

    $stmt_update->close();

    if ($foto_upload_baru && $foto_lama !== "") {
        gokaltara_delete_profile_image_direct($foto_lama);
    }

    $_SESSION["login"] = true;
    $_SESSION["id_user"] = $id_user;
    $_SESSION["username"] = $username;
    $_SESSION["nama_lengkap"] = $nama_lengkap;
    $_SESSION["level"] = $level;
    $_SESSION["foto_profil"] = $foto_baru;

    profileJson([
        "success" => true,
        "message" => "Profil berhasil diperbarui.",
        "nama_lengkap" => $nama_lengkap,
        "username" => $username,
        "level" => $level,
        "foto_profil" => $foto_baru,
        "foto_profil_url" =>
            ($foto_baru !== "" ? gokaltara_profile_image_url_direct($foto_baru, false) : ""),
        "foto_profil_thumbnail_url" =>
            ($foto_baru !== "" ? gokaltara_profile_image_url_direct($foto_baru, true) : "")
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

$foto_profil = trim(
    (string) ($user["foto_profil"] ?? "")
);

$foto_profil_url =
    gokaltara_profile_image_url_direct(
        $foto_profil,
        false
    );

$initial_admin = strtoupper(
    mb_substr(
        trim(
            (string) $user["nama_lengkap"]
        ) ?: "A",
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
        name="description"
        content="Kelola profil administrator GoKaltara Kuliner."
    >

    <title>Profil Admin | GoKaltara Kuliner</title>

    <link
        rel="icon"
        href="../assets/images/logo.svg"
    >

    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >

    <link
        rel="preconnect"
        href="https://cdnjs.cloudflare.com"
        crossorigin
    >

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

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css?v=31"
    >

    <link
        rel="stylesheet"
        href="../assets/css/profil.css?v=62"
    >

    <link
        rel="stylesheet"
        href="../assets/css/lenis.css?v=2" media="(min-width: 992px)"
    >

</head>

<body class="admin-profile-page">

<div class="admin-layout">

    <aside class="sidebar">

        <div>

            <div class="brand">

                <a
                    href="dashboard.php"
                    class="brand-title"
                >

                    <img
                        src="../assets/images/logo.svg"
                        alt="GoKaltara Kuliner"
                        width="58"
                        height="58"
                    >

                    <div>
                        GoKaltara
                        <br>
                        Kuliner
                    </div>

                </a>

                <div class="brand-subtitle">
                    Admin Panel
                </div>

            </div>

            <nav class="sidebar-menu">

                <a
                    href="dashboard.php"
                    class="sidebar-link"
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
                class="admin-profile profile-active"
            >

                <?php if ($foto_profil_url !== ""): ?>

                    <img
                        src="<?= htmlspecialchars(
                            $foto_profil_url,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Foto Profil"
                        class="avatar avatar-image"
                        width="46"
                        height="46"
                    >

                <?php else: ?>

                    <div class="avatar">
                        <?= htmlspecialchars(
                            $initial_admin
                        ) ?>
                    </div>

                <?php endif; ?>

                <div class="flex-grow-1 min-w-0">

                    <div class="admin-name">
                        <?= $nama_admin ?>
                    </div>

                    <div class="admin-role">
                        Administrator
                    </div>

                </div>

                <i class="bi bi-chevron-right"></i>

            </a>

            <a
                href="../logout.php"
                class="sidebar-logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="container-fluid px-0">

            <div class="topbar">

                <div>

                    <p class="eyebrow">
                        AKUN
                    </p>

                    <h1 class="dashboard-title">
                        Profil Admin
                    </h1>

                    <p class="dashboard-subtitle">
                        Kelola foto dan informasi akun administrator.
                    </p>

                </div>

                <a
                    href="dashboard.php"
                    class="website-button desktop-back-button"
                >

                    <i class="bi bi-arrow-left me-2"></i>

                    Kembali ke Dashboard

                </a>

            </div>

            <div id="alertBox"></div>

            <section class="profile-layout">

                <section class="dashboard-card profile-photo-card">

                    <div class="profile-card-header">

                        <h2 class="card-title-custom">
                            Foto Profil
                        </h2>

                        <p class="card-subtitle-custom">
                            Atur posisi foto sebelum menyimpannya.
                        </p>

                    </div>

                    <div class="profile-photo-content">

                        <div
                            class="main-preview"
                            id="mainPreview"
                        >

                            <?php if ($foto_profil_url !== ""): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $foto_profil_url,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    id="mainPreviewImage"
                                    alt="Foto Profil"
                                    width="180"
                                    height="180"
                                >

                            <?php else: ?>

                                <span id="mainPreviewInitial">
                                    <?= htmlspecialchars(
                                        $initial_admin
                                    ) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div
                            class="profile-name"
                            id="liveName"
                        >
                            <?= $nama_admin ?>
                        </div>

                        <div
                            class="profile-username"
                            id="liveUsername"
                        >
                            @<?= $username_admin ?>
                        </div>

                        <div
                            class="profile-role"
                            id="liveRole"
                        >
                            Administrator
                        </div>

                        <label
                            for="fotoInput"
                            class="profile-upload-button"
                        >

                            <i class="bi bi-image"></i>

                            <span>
                                Pilih Foto Baru
                            </span>

                        </label>

                        <input
                            type="file"
                            id="fotoInput"
                            accept="image/jpeg,image/png,image/webp"
                            class="d-none"
                        >

                        <div class="profile-upload-help">
                            JPG, PNG, WEBP · Maksimal 3 MB
                        </div>

                        <div
                            class="selected-file"
                            id="selectedFile"
                        >
                            Belum memilih foto baru
                        </div>

                    </div>

                </section>

                <section class="dashboard-card profile-info-card">

                    <div class="profile-card-header">

                        <h2 class="card-title-custom">
                            Informasi Akun
                        </h2>

                        <p class="card-subtitle-custom">
                            Perubahan dapat dilihat langsung pada preview.
                        </p>

                    </div>

                    <div class="profile-form">

                        <div class="profile-form-group">

                            <label
                                for="namaLengkap"
                                class="profile-form-label"
                            >
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

                            <label
                                for="username"
                                class="profile-form-label"
                            >
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

                            <label
                                for="level"
                                class="profile-form-label"
                            >
                                Role
                            </label>

                            <input
                                type="hidden"
                                id="level"
                                value="admin"
                            >

                            <div class="profile-role-static">
                                Administrator
                            </div>

                        </div>

                        <div class="profile-button-row">

                            <a
                                href="dashboard.php"
                                class="profile-cancel-button"
                            >
                                Batal
                            </a>

                            <button
                                type="button"
                                id="saveButton"
                                class="profile-save-button"
                            >

                                <i class="bi bi-floppy2-fill"></i>

                                Simpan Perubahan

                            </button>

                            <a
                                href="../logout.php"
                                class="profile-logout-button profile-mobile-logout"
                            >
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </a>

                        </div>

                    </div>

                </section>

            </section>

        </div>

    </main>

</div>

<div
    class="modal fade"
    id="cropModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content profile-modal">

            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Atur Foto Profil
                    </h5>

                    <div class="modal-subtitle">
                        Geser dan zoom untuk mengatur posisi foto.
                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>

            </div>

            <div class="modal-body">

                <div class="crop-container">

                    <img
                        id="cropImage"
                        src=""
                        alt="Atur foto profil"
                    >

                </div>

                <div class="crop-actions">

                    <button
                        type="button"
                        class="crop-btn"
                        id="zoomOut"
                        aria-label="Zoom Out"
                    >
                        <i class="bi bi-zoom-out"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="zoomIn"
                        aria-label="Zoom In"
                    >
                        <i class="bi bi-zoom-in"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="moveLeft"
                        aria-label="Geser kiri"
                    >
                        <i class="bi bi-arrow-left"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="moveRight"
                        aria-label="Geser kanan"
                    >
                        <i class="bi bi-arrow-right"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="moveUp"
                        aria-label="Geser atas"
                    >
                        <i class="bi bi-arrow-up"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="moveDown"
                        aria-label="Geser bawah"
                    >
                        <i class="bi bi-arrow-down"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="resetCrop"
                        aria-label="Reset"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="profile-cancel-button"
                    data-bs-dismiss="modal"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="profile-save-button"
                    id="useCrop"
                >
                    Gunakan Foto
                </button>

            </div>

        </div>

    </div>

</div>

<nav class="admin-mobile-bottom-nav" aria-label="Navigasi admin">
    <a href="dashboard.php" class="admin-mobile-nav-link">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <a href="kuliner.php" class="admin-mobile-nav-link">
        <i class="bi bi-fork-knife"></i>
        <span>Kuliner</span>
    </a>

    <a href="tambah_kuliner.php" class="admin-mobile-nav-add" aria-label="Tambah kuliner">
        <span><i class="bi bi-plus-lg"></i></span>
    </a>

    <a href="kategori.php" class="admin-mobile-nav-link">
        <i class="bi bi-tags-fill"></i>
        <span>Kategori</span>
    </a>

    <a href="profil.php" class="admin-mobile-nav-link active">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
</nav>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    defer
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"
    defer
></script>

<script
    src="../assets/js/profil.js?v=62"
    defer
></script>

<script
    src="../assets/js/lenis.js?v=3"
    defer
></script>

</body>

</html>