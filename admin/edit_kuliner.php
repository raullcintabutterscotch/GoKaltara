<?php

session_start();
require_once "../config/koneksi.php";
require_once "../config/image_optimizer.php";
require_once "../config/profile_images.php";

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
$id_kuliner = (int) ($_GET["id"] ?? $_POST["id_kuliner"] ?? 0);

if ($id_kuliner <= 0) {
    $_SESSION["flash_error"] = "Data kuliner tidak ditemukan.";
    header("Location: kuliner.php");
    exit;
}

$stmt = $koneksi->prepare("
    SELECT id_kuliner, nama_kuliner, asal_daerah, id_kategori, foto
    FROM kuliner
    WHERE id_kuliner = ?
    LIMIT 1
");
$stmt->bind_param("i", $id_kuliner);
$stmt->execute();
$data_kuliner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data_kuliner) {
    $_SESSION["flash_error"] = "Data kuliner tidak ditemukan.";
    header("Location: kuliner.php");
    exit;
}

$nama_kuliner = $data_kuliner["nama_kuliner"];
$asal_daerah = $data_kuliner["asal_daerah"];
$id_kategori = (int) $data_kuliner["id_kategori"];
$foto_lama = $data_kuliner["foto"] ?? "";
$errors = [];

$kategori = $koneksi->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_kuliner = trim($_POST["nama_kuliner"] ?? "");
    $asal_daerah = trim($_POST["asal_daerah"] ?? "");
    $id_kategori = (int) ($_POST["id_kategori"] ?? 0);

    if ($nama_kuliner === "") {
        $errors[] = "Nama kuliner wajib diisi.";
    }

    if ($asal_daerah === "") {
        $errors[] = "Asal daerah wajib diisi.";
    }

    if ($id_kategori <= 0) {
        $errors[] = "Kategori wajib dipilih.";
    }

    $foto_baru = $foto_lama;
    $upload_baru = false;

    if (
        isset($_FILES["foto"]) &&
        $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $processed = gokaltara_process_upload(
            $_FILES["foto"],
            __DIR__ . "/../assets/images",
            "kuliner",
            5 * 1024 * 1024,
            1400,
            640,
            82,
            78
        );

        if (!$processed["success"]) {
            $errors[] = $processed["message"] ?? "Foto gagal diproses.";
        } else {
            $foto_baru = $processed["filename"];
            $upload_baru = true;
        }
    }

    if (empty($errors)) {
        $stmt = $koneksi->prepare("
            UPDATE kuliner
            SET nama_kuliner = ?, asal_daerah = ?, id_kategori = ?, foto = ?
            WHERE id_kuliner = ?
        ");
        $stmt->bind_param("ssisi", $nama_kuliner, $asal_daerah, $id_kategori, $foto_baru, $id_kuliner);

        if ($stmt->execute()) {
            $stmt->close();

            if ($upload_baru && $foto_lama !== "") {
                gokaltara_delete_optimized_image(
                    $foto_lama,
                    __DIR__ . "/../assets/images"
                );
            }

            $_SESSION["flash_success"] = "Data kuliner berhasil diperbarui.";
            header("Location: kuliner.php");
            exit;
        }

        $stmt->close();

        if ($upload_baru) {
            gokaltara_delete_optimized_image(
                $foto_baru,
                __DIR__ . "/../assets/images"
            );
        }

        $errors[] = "Data kuliner gagal diperbarui.";
    }
}

$foto_preview = "";
if ($foto_lama !== "") {
    $foto_preview = gokaltara_image_url(
        $foto_lama,
        "../assets/images/",
        false
    );
}

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kuliner | Kuliner Kaltara</title>
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
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=7">
    <link rel="stylesheet" href="../assets/css/kuliner.css">

</head>
<body>

<a
    href="../logout.php"
    class="mobile-admin-logout-global"
    aria-label="Logout"
    title="Logout"
>
    <i class="bi bi-box-arrow-right"></i>
</a>
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
                        <img src="<?= htmlspecialchars(gokaltara_profile_image_url_direct($foto_profil, true)) ?>" alt="Foto Profil" class="avatar avatar-image" loading="eager" decoding="async">
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
                        <h1 class="dashboard-title">Edit Kuliner</h1>
                        <p class="dashboard-subtitle">Perbarui informasi data kuliner yang tersimpan.</p>
                    </div>
                    <a href="kuliner.php" class="website-button page-action-button">
                        <i class="bi bi-arrow-left me-2"></i>
                        Kembali
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger page-alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="dashboard-card form-card">
                    <form method="post" enctype="multipart/form-data" class="kuliner-form">
                        <input type="hidden" name="id_kuliner" value="<?= htmlspecialchars($id_kuliner) ?>">

                        <div class="form-header">
                            <h5 class="card-title-custom">Informasi Kuliner</h5>
                            <p class="card-subtitle-custom">Perbarui informasi yang diperlukan.</p>
                        </div>

                        <div class="form-body">
                            <div class="row g-4">
                                <div class="col-12 col-lg-8">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label for="nama_kuliner" class="form-label-custom">Nama Kuliner</label>
                                            <input type="text" id="nama_kuliner" name="nama_kuliner" class="form-control-custom" value="<?= htmlspecialchars($nama_kuliner) ?>" maxlength="150" required>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="asal_daerah" class="form-label-custom">Asal Daerah</label>
                                            <input type="text" id="asal_daerah" name="asal_daerah" class="form-control-custom" value="<?= htmlspecialchars($asal_daerah) ?>" maxlength="100" required>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="id_kategori" class="form-label-custom">Kategori</label>
                                            <select id="id_kategori" name="id_kategori" class="form-control-custom" required>
                                                <option value="">Pilih kategori</option>
                                                <?php if ($kategori): ?>
                                                    <?php while ($item = $kategori->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($item["id_kategori"]) ?>" <?= $id_kategori === (int) $item["id_kategori"] ? "selected" : "" ?>>
                                                            <?= htmlspecialchars($item["nama_kategori"]) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label-custom">Foto Kuliner</label>
                                    <label for="foto" class="image-upload-box">
                                        <div class="image-preview-wrapper">
                                            <img src="<?= htmlspecialchars($foto_preview) ?>" alt="Preview Foto" class="image-preview <?= $foto_preview !== "" ? "show" : "" ?>" data-image-preview loading="lazy" decoding="async">
                                            <div class="image-upload-placeholder <?= $foto_preview !== "" ? "hide" : "" ?>" data-image-placeholder>
                                                <i class="bi bi-cloud-arrow-up"></i>
                                                <strong><?= $foto_preview !== "" ? "Ganti foto" : "Pilih foto" ?></strong>
                                                <span>JPG, PNG, WEBP maksimal 5 MB</span>
                                            </div>
                                        </div>
                                    </label>
                                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" class="image-file-input" data-image-input>
                                </div>
                            </div>
                        </div>

                        <div class="form-footer">
                            <a href="kuliner.php" class="btn-secondary-custom">Batal</a>
                            <button type="submit" class="btn-primary-custom">
                                <i class="bi bi-check-lg me-2"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
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
            <i class="bi bi-egg-fried"></i>
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
<script src="../assets/js/kuliner.js" defer></script>

</body>
</html>
