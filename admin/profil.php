<?php

require_once "../api/_auth.php";
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

$stmt = $koneksi->prepare("
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

$stmt->bind_param("i", $id_user);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

$nama_admin = $user["nama_lengkap"];
$username_admin = $user["username"];
$foto_profil = $user["foto_profil"] ?? "";
$level_admin = $user["level"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=UTF-8");

    $nama_lengkap = trim($_POST["nama_lengkap"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $level = trim($_POST["level"] ?? "");

    if ($nama_lengkap === "") {
        echo json_encode([
            "success" => false,
            "message" => "Nama lengkap wajib diisi."
        ]);
        exit;
    }

    if (strlen($nama_lengkap) < 3) {
        echo json_encode([
            "success" => false,
            "message" => "Nama lengkap minimal 3 karakter."
        ]);
        exit;
    }

    if ($username === "") {
        echo json_encode([
            "success" => false,
            "message" => "Username wajib diisi."
        ]);
        exit;
    }

    if (strlen($username) < 4) {
        echo json_encode([
            "success" => false,
            "message" => "Username minimal 4 karakter."
        ]);
        exit;
    }

    if (!in_array($level, ["admin", "user"], true)) {
        echo json_encode([
            "success" => false,
            "message" => "Role tidak valid."
        ]);
        exit;
    }

    $cek_username = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        AND id_user != ?
        LIMIT 1
    ");

    $cek_username->bind_param(
        "si",
        $username,
        $id_user
    );

    $cek_username->execute();

    $hasil_username = $cek_username->get_result();

    if ($hasil_username->num_rows > 0) {
        $cek_username->close();

        echo json_encode([
            "success" => false,
            "message" => "Username sudah digunakan."
        ]);
        exit;
    }

    $cek_username->close();

    $nama_file_baru = $foto_profil;
    $folder = "../assets/images/profil/";
    $ada_foto_baru = false;

    if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
        echo json_encode([
            "success" => false,
            "message" => "Folder foto profil tidak dapat dibuat."
        ]);
        exit;
    }

    if (
        isset($_FILES["foto_profil"]) &&
        $_FILES["foto_profil"]["error"] !== UPLOAD_ERR_NO_FILE
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
            echo json_encode([
                "success" => false,
                "message" => $processed["message"] ?? "Foto gagal diproses."
            ]);
            exit;
        }

        $nama_file_baru = $processed["filename"];
        $ada_foto_baru = true;
    }

    $update = $koneksi->prepare("
        UPDATE user
        SET
            nama_lengkap = ?,
            username = ?,
            level = ?,
            foto_profil = ?
        WHERE id_user = ?
    ");

    $update->bind_param(
        "ssssi",
        $nama_lengkap,
        $username,
        $level,
        $nama_file_baru,
        $id_user
    );

    if (!$update->execute()) {
        if ($ada_foto_baru) {
            gokaltara_delete_optimized_image(
                $nama_file_baru,
                $folder
            );
        }

        $error = $update->error;

        $update->close();

        echo json_encode([
            "success" => false,
            "message" => "Database gagal diperbarui: " . $error
        ]);
        exit;
    }

    $update->close();

    if (
        $ada_foto_baru &&
        !empty($foto_profil) &&
        $foto_profil !== $nama_file_baru &&
        (
            is_file($folder . $foto_profil) ||
            is_file($folder . "thumbs/" . gokaltara_image_stem($foto_profil) . ".webp")
        )
    ) {
        gokaltara_delete_optimized_image(
            $foto_profil,
            $folder
        );
    }

    $_SESSION["nama_lengkap"] = $nama_lengkap;
    $_SESSION["username"] = $username;
    $_SESSION["level"] = $level;
    $_SESSION["foto_profil"] = $nama_file_baru;

    echo json_encode([
        "success" => true,
        "message" => "Profil berhasil diperbarui.",
        "nama_lengkap" => $nama_lengkap,
        "username" => $username,
        "level" => $level,
        "foto_profil" => $nama_file_baru
    ]);

    exit;
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

    <title>Profil Admin | Kuliner Kaltara</title>
    
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

    <link
        rel="preconnect"
        href="https://cdnjs.cloudflare.com"
        crossorigin
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
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/profil.css?v=3"
    >
    <link rel="stylesheet" href="../assets/css/performance.css?v=2">

</head>

<body>

    <div>

        <aside class="sidebar">

            <div>

                <div class="brand">

                    <div class="brand-title">

                        <img
                            src="../assets/images/logo.svg"
                            alt="Logo Kuliner Kaltara" loading="eager" decoding="async">

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
                        class="sidebar-link"
                    >
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>

                    <a
                        href="kuliner.php"
                        class="sidebar-link"
                    >
                        <i class="bi bi-fork-knife"></i>
                        <span>Data Kuliner</span>
                    </a>

                    <a
                        href="kategori.php"
                        class="sidebar-link"
                    >
                        <i class="bi bi-tags-fill"></i>
                        <span>Kategori</span>
                    </a>

                    <a
                        href="tambah_kuliner.php"
                        class="sidebar-link"
                    >
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tambah Kuliner</span>
                    </a>

                </nav>

            </div>

            <div class="sidebar-bottom">

                <a
                    href="profil.php"
                    class="admin-profile profile-active"
                >

                    <?php if (!empty($foto_profil)): ?>

                        <img
                            src="<?= htmlspecialchars(
                                gokaltara_profile_image_url($foto_profil, true),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            alt="Foto Profil"
                            class="avatar avatar-image"
                            loading="eager"
                            decoding="async">

                    <?php else: ?>

                        <div class="avatar">
                            <?= htmlspecialchars(
                                strtoupper(substr($nama_admin, 0, 1))
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
                            Akun
                        </p>

                        <h1 class="dashboard-title">
                            Profil Admin
                        </h1>

                        <p class="dashboard-subtitle">
                            Kelola foto dan informasi akun kamu.
                        </p>

                    </div>

                    <a
                        href="dashboard.php"
                        class="website-button"
                    >
                        <i class="bi bi-arrow-left me-2"></i>
                        Kembali ke Dashboard
                    </a>

                </div>

                <div id="alertBox"></div>

                <div class="profile-layout">

                    <section class="dashboard-card profile-photo-card">

                        <div class="profile-card-header">

                            <h2 class="card-title-custom">
                                Foto Profil
                            </h2>

                            <p class="card-subtitle-custom">
                                Atur foto sebelum menyimpan perubahan.
                            </p>

                        </div>

                        <div class="profile-photo-content">

                            <div
                                class="main-preview"
                                id="mainPreview"
                            >

                                <?php if (!empty($foto_profil)): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            gokaltara_profile_image_url($foto_profil, false),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        id="mainPreviewImage"
                                        alt="Foto Profil"
                                        loading="eager"
                                        decoding="async">

                                <?php else: ?>

                                    <span id="mainPreviewInitial">
                                        <?= htmlspecialchars(
                                            strtoupper(substr($nama_admin, 0, 1))
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div
                                class="profile-name"
                                id="liveName"
                            >
                                <?= htmlspecialchars($nama_admin) ?>
                            </div>

                            <div
                                class="profile-username"
                                id="liveUsername"
                            >
                                @<?= htmlspecialchars($username_admin) ?>
                            </div>

                            <div
                                class="profile-role"
                                id="liveRole"
                            >
                                <?= htmlspecialchars(ucfirst($level_admin)) ?>
                            </div>

                            <label
                                for="fotoInput"
                                class="profile-upload-button"
                            >
                                <i class="bi bi-image me-2"></i>
                                Pilih Foto Baru
                            </label>

                            <input
                                type="file"
                                id="fotoInput"
                                name="foto_profil"
                                class="d-none"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <div class="profile-upload-help">
                                JPG, PNG, WEBP · Maksimal 2 MB
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
                                Perubahan akan tampil langsung pada preview.
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
                                    name="nama_lengkap"
                                    class="profile-form-control"
                                    value="<?= htmlspecialchars($nama_admin) ?>"
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
                                    name="username"
                                    class="profile-form-control"
                                    value="<?= htmlspecialchars($username_admin) ?>"
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

                                <select
                                    id="level"
                                    name="level"
                                    class="profile-form-control"
                                >

                                    <option
                                        value="admin"
                                        <?= $level_admin === "admin" ? "selected" : "" ?>
                                    >
                                        Administrator
                                    </option>

                                    <option
                                        value="user"
                                        <?= $level_admin === "user" ? "selected" : "" ?>
                                    >
                                        User
                                    </option>

                                </select>

                            </div>

                            <div class="account-status">

                                <div>

                                    <div class="status-label">
                                        Status Akun
                                    </div>

                                    <div class="status-caption">
                                        Akun aktif dan siap digunakan.
                                    </div>

                                </div>

                                <div class="status-value">
                                    <i class="bi bi-circle-fill"></i>
                                    Aktif
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
                                    <i class="bi bi-floppy2-fill me-2"></i>
                                    Simpan Perubahan
                                </button>

                            </div>

                        </div>

                    </section>

                </div>

            </div>

        </main>

    </div>

    <nav class="mobile-bottom-nav">

        <a
            href="dashboard.php"
            class="mobile-nav-link"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <a
            href="kuliner.php"
            class="mobile-nav-link"
        >
            <i class="bi bi-fork-knife"></i>
            <span>Kuliner</span>
        </a>

        <a
            href="tambah_kuliner.php"
            class="mobile-nav-add" aria-label="Tambah data">
            <span>
                <i class="bi bi-plus-lg"></i>
            </span>
        </a>

        <a
            href="kategori.php"
            class="mobile-nav-link"
        >
            <i class="bi bi-tags-fill"></i>
            <span>Kategori</span>
        </a>

        <a
            href="profil.php"
            class="mobile-nav-link active"
        >
            <i class="bi bi-person-fill"></i>
            <span>Profil</span>
        </a>

    </nav>

    <div
        class="modal fade"
        id="cropModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content profile-modal">

                <div class="modal-header profile-modal-header">

                    <div>

                        <h5 class="modal-title">
                            Atur Foto Profil
                        </h5>

                        <div class="modal-subtitle">
                            Geser dan zoom foto sesuai kebutuhan.
                        </div>

                    </div>

                    <button type="button" class="btn-close" aria-label="Tutup"
                        data-bs-dismiss="modal"
                    ></button>

                </div>

                <div class="modal-body">

                    <div class="crop-container">

                        <img
                            id="cropImage"
                            src=""
                            alt="Foto yang sedang diedit" loading="lazy" decoding="async">

                    </div>

                    <div class="crop-actions">

                        <button
                            type="button"
                            class="crop-btn"
                            id="zoomOut"
                            title="Zoom Out"
                        >
                            <i class="bi bi-dash-lg"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="zoomIn"
                            title="Zoom In"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="moveLeft"
                            title="Geser Kiri"
                        >
                            <i class="bi bi-arrow-left"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="moveRight"
                            title="Geser Kanan"
                        >
                            <i class="bi bi-arrow-right"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="moveUp"
                            title="Geser Atas"
                        >
                            <i class="bi bi-arrow-up"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="moveDown"
                            title="Geser Bawah"
                        >
                            <i class="bi bi-arrow-down"></i>
                        </button>

                        <button
                            type="button"
                            class="crop-btn"
                            id="resetCrop"
                            title="Reset"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>

                    </div>

                </div>

                <div class="modal-footer profile-modal-footer">

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

    <script src="../assets/js/profil.js"></script>

    <script src="../assets/js/performance.js?v=1" defer></script>

</body>

</html>