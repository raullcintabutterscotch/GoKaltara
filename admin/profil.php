<?php

session_start();

require_once "../config/koneksi.php";

$id_user = (int) ($_SESSION['id_user'] ?? 0);
$username_session = trim($_SESSION['username'] ?? '');

if ($id_user <= 0 && $username_session !== '') {

    $stmt_session = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        LIMIT 1
    ");

    $stmt_session->bind_param(
        "s",
        $username_session
    );

    $stmt_session->execute();

    $result_session = $stmt_session->get_result();
    $data_session = $result_session->fetch_assoc();

    if ($data_session) {

        $id_user = (int) $data_session['id_user'];

        $_SESSION['id_user'] = $id_user;
    }

    $stmt_session->close();
}

if ($id_user <= 0) {

    header("Location: ../login.php");

    exit;
}

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

$stmt_user->bind_param(
    "i",
    $id_user
);

$stmt_user->execute();

$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

$stmt_user->close();

if (!$user) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");

    exit;
}

if ($user['level'] !== 'admin') {

    header("Location: ../profil-user.php");

    exit;
}

$_SESSION['login'] = true;
$_SESSION['id_user'] = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['level'] = $user['level'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $nama_lengkap = trim(
        $_POST['nama_lengkap'] ?? ''
    );

    $username = trim(
        $_POST['username'] ?? ''
    );

    $level = trim(
        $_POST['level'] ?? 'admin'
    );

    if ($nama_lengkap === '') {

        echo json_encode([
            'success' => false,
            'message' => 'Nama lengkap wajib diisi.'
        ]);

        exit;
    }

    if (mb_strlen($nama_lengkap) < 3) {

        echo json_encode([
            'success' => false,
            'message' => 'Nama lengkap minimal 3 karakter.'
        ]);

        exit;
    }

    if ($username === '') {

        echo json_encode([
            'success' => false,
            'message' => 'Username wajib diisi.'
        ]);

        exit;
    }

    if (mb_strlen($username) < 3) {

        echo json_encode([
            'success' => false,
            'message' => 'Username minimal 3 karakter.'
        ]);

        exit;
    }

    if (!in_array(
        $level,
        ['admin', 'user'],
        true
    )) {

        echo json_encode([
            'success' => false,
            'message' => 'Role tidak valid.'
        ]);

        exit;
    }

    $stmt_cek = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        AND id_user != ?
        LIMIT 1
    ");

    $stmt_cek->bind_param(
        "si",
        $username,
        $id_user
    );

    $stmt_cek->execute();

    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {

        $stmt_cek->close();

        echo json_encode([
            'success' => false,
            'message' => 'Username sudah digunakan.'
        ]);

        exit;
    }

    $stmt_cek->close();

    $foto_lama = $user['foto_profil'] ?? '';
    $foto_baru = $foto_lama;
    $foto_upload_baru = false;

    $folder = dirname(__DIR__) .
        "/assets/images/profil/";

    if (!is_dir($folder)) {

        if (!mkdir(
            $folder,
            0755,
            true
        )) {

            echo json_encode([
                'success' => false,
                'message' => 'Folder foto profil tidak dapat dibuat.'
            ]);

            exit;
        }
    }

    if (
        isset($_FILES['foto_profil']) &&
        $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['foto_profil']['error'] !==
            UPLOAD_ERR_OK
        ) {

            echo json_encode([
                'success' => false,
                'message' => 'Foto profil gagal diupload.'
            ]);

            exit;
        }

        $tmp_name = $_FILES['foto_profil']['tmp_name'];

        $file_size = (int) $_FILES['foto_profil']['size'];

        if ($file_size > 3 * 1024 * 1024) {

            echo json_encode([
                'success' => false,
                'message' => 'Ukuran foto maksimal 3 MB.'
            ]);

            exit;
        }

        $image_info = @getimagesize(
            $tmp_name
        );

        if (!$image_info) {

            echo json_encode([
                'success' => false,
                'message' => 'File yang dipilih bukan gambar.'
            ]);

            exit;
        }

        $mime = $image_info['mime'] ?? '';

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!in_array(
            $mime,
            $allowed,
            true
        )) {

            echo json_encode([
                'success' => false,
                'message' => 'Format foto harus JPG, PNG, atau WEBP.'
            ]);

            exit;
        }

        $foto_baru =
            'profil_' .
            $id_user .
            '_' .
            time() .
            '_' .
            bin2hex(
                random_bytes(4)
            ) .
            '.jpg';

        $tujuan = $folder . $foto_baru;

        if (!move_uploaded_file(
            $tmp_name,
            $tujuan
        )) {

            echo json_encode([
                'success' => false,
                'message' => 'Foto profil gagal disimpan.'
            ]);

            exit;
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

    $stmt_update->bind_param(
        "ssssi",
        $nama_lengkap,
        $username,
        $level,
        $foto_baru,
        $id_user
    );

    if (!$stmt_update->execute()) {

        if (
            $foto_upload_baru &&
            !empty($foto_baru)
        ) {

            $file_gagal =
                $folder .
                basename($foto_baru);

            if (file_exists($file_gagal)) {

                unlink($file_gagal);
            }
        }

        $error = $stmt_update->error;

        $stmt_update->close();

        echo json_encode([
            'success' => false,
            'message' =>
                'Profil gagal diperbarui: ' .
                $error
        ]);

        exit;
    }

    $stmt_update->close();

    if (
        $foto_upload_baru &&
        !empty($foto_lama) &&
        $foto_lama !== $foto_baru
    ) {

        $file_lama =
            $folder .
            basename($foto_lama);

        if (file_exists($file_lama)) {

            unlink($file_lama);
        }
    }

    $_SESSION['login'] = true;
    $_SESSION['id_user'] = $id_user;
    $_SESSION['username'] = $username;
    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['level'] = $level;
    $_SESSION['foto_profil'] = $foto_baru;

    echo json_encode([
        'success' => true,
        'message' => 'Profil berhasil diperbarui.',
        'nama_lengkap' => $nama_lengkap,
        'username' => $username,
        'level' => $level,
        'foto_profil' => $foto_baru
    ]);

    exit;
}

$nama_admin = htmlspecialchars(
    $user['nama_lengkap'],
    ENT_QUOTES,
    'UTF-8'
);

$username_admin = htmlspecialchars(
    $user['username'],
    ENT_QUOTES,
    'UTF-8'
);

$level_admin = $user['level'];

$foto_profil = $user['foto_profil'] ?? '';

if (!empty($foto_profil)) {

    $foto_profil_url =
        "../assets/images/profil/" .
        rawurlencode(
            basename($foto_profil)
        );

} else {

    $foto_profil_url = "";
}

$initial_admin = strtoupper(
    mb_substr(
        $user['nama_lengkap'],
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
        href="../assets/css/dashboard.css?v=20"
    >

    <link
        rel="stylesheet"
        href="../assets/css/profil.css?v=20"
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
                        alt="Logo GoKaltara Kuliner"
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

                <?php if ($foto_profil_url !== ''): ?>

                    <img
                        src="<?= htmlspecialchars(
                            $foto_profil_url,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="Foto Profil"
                        class="avatar avatar-image"
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

                <i class="bi bi-chevron-right text-white-50"></i>

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

        <header class="mobile-admin-header">

            <a
                href="dashboard.php"
                class="mobile-admin-brand"
            >

                <img
                    src="../assets/images/logo.svg"
                    alt="GoKaltara Kuliner"
                >

                <span>
                    Profil Admin
                </span>

            </a>

            <a
                href="../logout.php"
                class="mobile-logout-button"
                aria-label="Logout"
                title="Logout"
            >

                <i class="bi bi-box-arrow-right"></i>

            </a>

        </header>

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

                            <?php if ($foto_profil_url !== ''): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $foto_profil_url,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    id="mainPreviewImage"
                                    alt="Foto Profil"
                                >

                            <?php else: ?>

                                <span
                                    id="mainPreviewInitial"
                                >
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
                            Perubahan langsung terlihat pada preview.
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

                            <select
                                id="level"
                                class="profile-form-control"
                            >

                                <option
                                    value="admin"
                                    <?= $level_admin === 'admin'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Administrator
                                </option>

                                <option
                                    value="user"
                                    <?= $level_admin === 'user'
                                        ? 'selected'
                                        : '' ?>
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

            </section>

        </div>

    </main>

</div>

<nav class="mobile-bottom-nav">

    <a
        href="dashboard.php"
        class="mobile-nav-link"
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
        class="mobile-nav-link mobile-nav-main-action"
    >

        <i class="bi bi-plus-lg"></i>

        <span>
            Tambah
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
        class="mobile-nav-link active"
    >

        <i class="bi bi-person-fill"></i>

        <span>
            Profil
        </span>

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
                        title="Zoom Out"
                    >
                        <i class="bi bi-zoom-out"></i>
                    </button>

                    <button
                        type="button"
                        class="crop-btn"
                        id="zoomIn"
                        title="Zoom In"
                    >
                        <i class="bi bi-zoom-in"></i>
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

<script src="../assets/js/profil.js?v=20"></script>

</body>

</html>