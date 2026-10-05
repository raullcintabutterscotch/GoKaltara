<?php
session_start();
require_once "config/koneksi.php";

$id_user = (int) ($_SESSION['id_user'] ?? 0);
$username_session = trim($_SESSION['username'] ?? '');

if ($id_user <= 0 && $username_session !== '') {
    $stmt_session = $koneksi->prepare("
        SELECT id_user
        FROM user
        WHERE username = ?
        LIMIT 1
    ");

    $stmt_session->bind_param("s", $username_session);
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
    header("Location: login.php");
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

$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();

$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

$stmt_user->close();

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

if ($user['level'] === 'admin') {
    header("Location: admin/profil.php");
    exit;
}

$_SESSION['login'] = true;
$_SESSION['id_user'] = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['level'] = $user['level'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header("Content-Type: application/json; charset=UTF-8");

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');

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

    if (
        isset($_FILES['foto_profil']) &&
        $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
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

        $image_info = @getimagesize($tmp_name);

        if (!$image_info) {
            echo json_encode([
                'success' => false,
                'message' => 'File yang dipilih bukan gambar.'
            ]);
            exit;
        }

        $mime = $image_info['mime'] ?? '';

        if ($mime !== 'image/jpeg') {
            echo json_encode([
                'success' => false,
                'message' => 'Foto hasil crop harus berformat JPG.'
            ]);
            exit;
        }

        $folder = __DIR__ . "/assets/images/profil/";

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $foto_baru =
            "profil_" .
            $id_user .
            "_" .
            time() .
            "_" .
            bin2hex(random_bytes(4)) .
            ".jpg";

        $tujuan = $folder . $foto_baru;

        if (!move_uploaded_file($tmp_name, $tujuan)) {
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
            foto_profil = ?
        WHERE id_user = ?
    ");

    $stmt_update->bind_param(
        "sssi",
        $nama_lengkap,
        $username,
        $foto_baru,
        $id_user
    );

    if (!$stmt_update->execute()) {

        if ($foto_upload_baru && !empty($foto_baru)) {
            $file_gagal =
                __DIR__ .
                "/assets/images/profil/" .
                basename($foto_baru);

            if (file_exists($file_gagal)) {
                unlink($file_gagal);
            }
        }

        echo json_encode([
            'success' => false,
            'message' => 'Profil gagal diperbarui.'
        ]);

        exit;
    }

    $stmt_update->close();

    if (
        $foto_upload_baru &&
        !empty($foto_lama)
    ) {

        $file_lama =
            __DIR__ .
            "/assets/images/profil/" .
            basename($foto_lama);

        if (file_exists($file_lama)) {
            unlink($file_lama);
        }
    }

    $_SESSION['login'] = true;
    $_SESSION['id_user'] = $id_user;
    $_SESSION['username'] = $username;
    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['level'] = $user['level'];

    echo json_encode([
        'success' => true,
        'message' => 'Profil berhasil diperbarui.',
        'nama_lengkap' => $nama_lengkap,
        'username' => $username,
        'foto_profil' => $foto_baru
    ]);

    exit;
}

$nama_user = htmlspecialchars(
    $user['nama_lengkap'],
    ENT_QUOTES,
    'UTF-8'
);

$username_user = htmlspecialchars(
    $user['username'],
    ENT_QUOTES,
    'UTF-8'
);

if (!empty($user['foto_profil'])) {

    $foto_profil =
        "assets/images/profil/" .
        rawurlencode(
            basename($user['foto_profil'])
        );

} else {

    $foto_profil = "";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Profil User | GoKaltara Kuliner</title>

    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="assets/css/profil-user.css?v=5">

    <link rel="stylesheet" href="assets/css/notifikasi.css?v=60">

</head>

<body>

<aside class="sidebar">

    <div class="brand">

        <a
            href="index.php"
            class="brand-title">

            <img
                src="assets/images/logo.svg"
                alt="GoKaltara Kuliner">

            <div>
                GoKaltara
                <strong>Kuliner</strong>
            </div>

        </a>

        <div class="brand-subtitle">
            KATALOG KULINER KALTARA
        </div>

    </div>

    <nav class="sidebar-menu">

        <a href="index.php" class="sidebar-link">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Beranda</span>
        </a>

        <a href="katalog.php" class="sidebar-link">
        <i class="bi bi-fork-knife"></i>
            <span>Katalog</span>
        </a>

        <a href="tentang.php" class="sidebar-link">
            <i class="bi bi-info-circle-fill"></i>
            <span>Tentang</span>
        </a>

        <a href="pencarian.php" class="sidebar-link">
            <i class="bi bi-search"></i>
            <span>Pencarian</span>
        </a>

        <a
            href="profil-user.php"
            class="sidebar-link active">

            <i class="bi bi-person-fill"></i>

            <span>
                Profil
            </span>

        </a>

    </nav>

    <div class="sidebar-bottom">

        <a
            href="profil-user.php"
            class="admin-profile">

            <?php if ($foto_profil !== ''): ?>

                <img
                    src="<?= htmlspecialchars(
                        $foto_profil,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt="Foto Profil"
                    class="avatar avatar-image">

            <?php else: ?>

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $user['nama_lengkap'],
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

            <?php endif; ?>

            <div class="user-profile-text">

                <div class="admin-name">
                    <?= $nama_user ?>
                </div>

                <div class="admin-role">
                    Pengguna
                </div>

            </div>

            <i class="bi bi-chevron-right profile-arrow"></i>

        </a>

        <a
            href="logout.php"
            class="sidebar-logout">

            <i class="bi bi-box-arrow-right"></i>

            Logout

        </a>

    </div>

</aside>

<main class="main-content">

    <div class="topbar">

        <div>

            <p class="eyebrow">
                AKUN
            </p>

            <h1 class="page-title">
                Profil User
            </h1>

            <p class="page-subtitle">
                Kelola foto dan informasi akun kamu.
            </p>

        </div>

        <div class="topbar-actions">

            <a
                href="notifikasi.php"
                class="notification-nav"
                aria-label="Notifikasi"
            >
                <i class="bi bi-bell"></i>
                <span
                    class="notification-badge"
                    hidden
                >0</span>
            </a>

            <a
                href="index.php"
                class="back-button">

                <i class="bi bi-arrow-left"></i>

                Kembali ke Website

            </a>

        </div>

    </div>

    <div id="alertBox"></div>

    <section class="profile-layout">

        <div class="profile-photo-card">

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
                    id="mainPreview">

                    <?php if ($foto_profil !== ''): ?>

                        <img
                            src="<?= htmlspecialchars(
                                $foto_profil,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            id="previewFoto"
                            alt="Foto Profil">

                    <?php else: ?>

                        <i class="bi bi-person-fill"></i>

                    <?php endif; ?>

                </div>

                <div
                    class="profile-name"
                    id="liveName">

                    <?= $nama_user ?>

                </div>

                <div
                    class="profile-username"
                    id="liveUsername">

                    @<?= $username_user ?>

                </div>

                <label
                    for="fotoInput"
                    class="profile-upload-button">

                    <i class="bi bi-image"></i>

                    Pilih Foto Baru

                </label>

                <input
                    type="file"
                    id="fotoInput"
                    accept=".jpg,.jpeg,.png,.webp"
                    hidden>

                <div class="profile-upload-help">
                    JPG, PNG, WEBP · Maksimal 3 MB
                </div>

                <div
                    class="selected-file"
                    id="selectedFile">

                    Belum memilih foto baru

                </div>

            </div>

        </div>

        <div class="profile-info-card">

            <div class="profile-card-header">

                <h2 class="card-title-custom">
                    Informasi Akun
                </h2>

                <p class="card-subtitle-custom">
                    Perubahan akun tampil langsung pada preview.
                </p>

            </div>

            <div class="profile-form">

                <div class="profile-form-group">

                    <label
                        for="namaLengkap"
                        class="profile-form-label">

                        Nama Lengkap

                    </label>

                    <input
                        type="text"
                        id="namaLengkap"
                        class="profile-form-control"
                        value="<?= $nama_user ?>"
                        maxlength="100"
                        autocomplete="name">

                </div>

                <div class="profile-form-group">

                    <label
                        for="username"
                        class="profile-form-label">

                        Username

                    </label>

                    <input
                        type="text"
                        id="username"
                        class="profile-form-control"
                        value="<?= $username_user ?>"
                        minlength="3"
                        maxlength="50"
                        autocomplete="username">

                </div>

                <div class="profile-button-row">

                    <a
                        href="index.php"
                        class="profile-cancel-button">

                        Batal

                    </a>

                    <button
                        type="button"
                        id="saveButton"
                        class="profile-save-button">

                        <i class="bi bi-floppy2-fill me-2"></i>

                        Simpan Perubahan

                    </button>

                </div>

            </div>

        </div>

    </section>

</main>

<div
    class="modal fade"
    id="cropModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content profile-modal">

            <div class="modal-header profile-modal-header">

                <div>

                    <h5 class="modal-title">
                        Atur Foto Profil
                    </h5>

                    <div class="modal-subtitle">
                        Geser dan zoom foto sesuai posisi yang kamu inginkan.
                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="crop-container">

                    <img
                        id="cropImage"
                        alt="Atur Foto Profil">

                </div>

                <div class="crop-actions">

                    <button
                        type="button"
                        id="zoomOut"
                        class="crop-btn"
                        title="Zoom Out">

                        <i class="bi bi-zoom-out"></i>

                    </button>

                    <button
                        type="button"
                        id="zoomIn"
                        class="crop-btn"
                        title="Zoom In">

                        <i class="bi bi-zoom-in"></i>

                    </button>

                    <button
                        type="button"
                        id="moveLeft"
                        class="crop-btn"
                        title="Geser Kiri">

                        <i class="bi bi-arrow-left"></i>

                    </button>

                    <button
                        type="button"
                        id="moveRight"
                        class="crop-btn"
                        title="Geser Kanan">

                        <i class="bi bi-arrow-right"></i>

                    </button>

                    <button
                        type="button"
                        id="moveUp"
                        class="crop-btn"
                        title="Geser Atas">

                        <i class="bi bi-arrow-up"></i>

                    </button>

                    <button
                        type="button"
                        id="moveDown"
                        class="crop-btn"
                        title="Geser Bawah">

                        <i class="bi bi-arrow-down"></i>

                    </button>

                    <button
                        type="button"
                        id="resetCrop"
                        class="crop-btn"
                        title="Reset">

                        <i class="bi bi-arrow-counterclockwise"></i>

                    </button>

                </div>

            </div>

            <div class="modal-footer profile-modal-footer">

                <button
                    type="button"
                    class="profile-cancel-button"
                    data-bs-dismiss="modal">

                    Batal

                </button>

                <button
                    type="button"
                    id="useCrop"
                    class="profile-save-button">

                    Gunakan Foto

                </button>

            </div>

        </div>

    </div>

</div>

<nav class="mobile-bottom-nav">

    <a
        href="index.php"
        class="mobile-nav-link">

        <i class="bi bi-house-fill"></i>
        <span>Beranda</span>

    </a>

    <a
        href="katalog.php"
        class="mobile-nav-link">

        <i class="bi bi-fork-knife"></i>
        <span>Katalog</span>

    </a>

    <a
        href="pencarian.php"
        class="mobile-nav-link">

        <i class="bi bi-search"></i>
        <span>Cari</span>

    </a>

    <a
        href="tentang.php"
        class="mobile-nav-link">

        <i class="bi bi-info-circle-fill"></i>
        <span>Tentang</span>

    </a>

    <a
        href="profil-user.php"
        class="mobile-nav-link active">

        <i class="bi bi-person-fill"></i>
        <span>Profil</span>

    </a>

</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

<script src="assets/js/profil-user.js?v=5"></script>

    <script
        src="assets/js/notifikasi.js?v=60"
    ></script>

</body>
</html>