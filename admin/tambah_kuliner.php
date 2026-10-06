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

$id_user = (int) ($_SESSION["id_user"] ?? 0);

$stmt_user = $koneksi->prepare("
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

$nama_kuliner = trim($_POST["nama_kuliner"] ?? "");
$asal_daerah = trim($_POST["asal_daerah"] ?? "");
$id_kategori = (int) ($_POST["id_kategori"] ?? 0);
$bahan_utama = trim($_POST["bahan_utama"] ?? "");
$deskripsi = trim($_POST["deskripsi"] ?? "");
$cara_penyajian = trim($_POST["cara_penyajian"] ?? "");
$sejarah = trim($_POST["sejarah"] ?? "");

$errors = [];

$kategori = $koneksi->query("
    SELECT
        id_kategori,
        nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($nama_kuliner === "") {
        $errors[] = "Nama kuliner wajib diisi.";
    }

    if ($asal_daerah === "") {
        $errors[] = "Asal daerah wajib diisi.";
    }

    if ($id_kategori <= 0) {
        $errors[] = "Kategori wajib dipilih.";
    }

    if ($bahan_utama === "") {
        $errors[] = "Bahan utama wajib diisi.";
    }

    if ($deskripsi === "") {
        $errors[] = "Deskripsi wajib diisi.";
    }

    if ($cara_penyajian === "") {
        $errors[] = "Cara penyajian wajib diisi.";
    }

    if ($sejarah === "") {
        $errors[] = "Sejarah atau nilai budaya wajib diisi.";
    }

    $nama_file = "";

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
            $nama_file = $processed["filename"];
        }
    }

    if (empty($errors)) {

        $stmt = $koneksi->prepare("
            INSERT INTO kuliner
            (
                nama_kuliner,
                asal_daerah,
                id_kategori,
                bahan_utama,
                deskripsi,
                cara_penyajian,
                sejarah,
                foto
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssisssss",
            $nama_kuliner,
            $asal_daerah,
            $id_kategori,
            $bahan_utama,
            $deskripsi,
            $cara_penyajian,
            $sejarah,
            $nama_file
        );

        if ($stmt->execute()) {

            $stmt->close();

            $_SESSION["flash_success"] =
                "Data kuliner berhasil ditambahkan.";

            header("Location: kuliner.php");
            exit;
        }

        $stmt->close();

        if ($nama_file !== "") {
            gokaltara_delete_optimized_image(
                $nama_file,
                __DIR__ . "/../assets/images"
            );
        }

        $errors[] =
            "Data kuliner gagal disimpan.";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<style id="gokaltara-performance-inline">
img.perf-image{background-color:#eef3f0;background-image:linear-gradient(90deg,#eef3f0 0%,#f8faf9 50%,#eef3f0 100%);background-size:220% 100%;background-repeat:no-repeat}
img.perf-image.is-loaded{background-image:none}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
@media(max-width:991.98px){html{scroll-behavior:auto}}
</style>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Tambah Kuliner | Kuliner Kaltara
    </title>
    
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


    <link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></noscript>

    <link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"></noscript>

    <link rel="preload" as="style" href="../assets/css/dashboard.css?v=4" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="../assets/css/dashboard.css?v=4"></noscript>

    <link rel="preload" as="style" href="../assets/css/kuliner.css?v=4" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="../assets/css/kuliner.css?v=4"></noscript>

    </head>

<body>

<div>

    <aside class="sidebar">

        <div>

            <div class="brand">

                <div class="brand-title">

                    <img
                        src="../assets/images/logo.svg"
                        alt="Logo Kuliner Kaltara" loading="eager" decoding="async" width="58" height="58">

                    <div>
                        GoKaltara<br>
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
                    class="sidebar-link">

                    <i class="bi bi-grid-1x2-fill"></i>

                    <span>
                        Dashboard
                    </span>

                </a>

                <a
                    href="kuliner.php"
                    class="sidebar-link">

                    <i class="bi bi-fork-knife"></i>

                    <span>
                        Data Kuliner
                    </span>

                </a>

                <a
                    href="kategori.php"
                    class="sidebar-link">

                    <i class="bi bi-tags-fill"></i>

                    <span>
                        Kategori
                    </span>

                </a>

                <a
                    href="tambah_kuliner.php"
                    class="sidebar-link active">

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
                class="admin-profile">

                <?php if (!empty($foto_profil)): ?>

                    <img
                        src="<?= htmlspecialchars(
                            gokaltara_profile_image_url(
                                $foto_profil,
                                true
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Foto Profil"
                        class="avatar avatar-image" loading="eager" decoding="async">

                <?php else: ?>

                    <div class="avatar">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $nama_admin,
                                    0,
                                    1
                                )
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>

                <div class="flex-grow-1 min-w-0">

                    <div class="admin-name">

                        <?= htmlspecialchars(
                            $nama_admin,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                    <div class="admin-role">
                        Administrator
                    </div>

                </div>

                <i class="bi bi-chevron-right text-white-50"></i>

            </a>

            <a
                href="../logout.php"
                class="sidebar-logout">

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
                        Administrator
                    </p>

                    <h1 class="dashboard-title">
                        Tambah Kuliner
                    </h1>

                    <p class="dashboard-subtitle">
                        Tambahkan data kuliner baru ke database.
                    </p>

                </div>

                <a
                    href="kuliner.php"
                    class="website-button page-action-button">

                    <i class="bi bi-arrow-left me-2"></i>

                    Kembali

                </a>

            </div>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger page-alert">

                    <i class="bi bi-exclamation-circle me-2"></i>

                    <div>

                        <?php foreach ($errors as $error): ?>

                            <div>
                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <div class="dashboard-card form-card">

                <form
                    method="post"
                    enctype="multipart/form-data"
                    class="kuliner-form">

                    <div class="form-header">

                        <h5 class="card-title-custom">
                            Informasi Kuliner
                        </h5>

                        <p class="card-subtitle-custom">
                            Isi informasi kuliner dengan lengkap.
                        </p>

                    </div>

                    <div class="form-body">

                        <div class="row g-4">

                            <div class="col-12 col-lg-8">

                                <div class="row g-4">

                                    <div class="col-12">

                                        <label
                                            for="nama_kuliner"
                                            class="form-label-custom">

                                            Nama Kuliner

                                        </label>

                                        <input
                                            type="text"
                                            id="nama_kuliner"
                                            name="nama_kuliner"
                                            class="form-control-custom"
                                            value="<?= htmlspecialchars(
                                                $nama_kuliner,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            placeholder="Contoh: Nasi Subut"
                                            maxlength="100"
                                            required>

                                    </div>

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="asal_daerah"
                                            class="form-label-custom">

                                            Asal Daerah

                                        </label>

                                        <input
                                            type="text"
                                            id="asal_daerah"
                                            name="asal_daerah"
                                            class="form-control-custom"
                                            value="<?= htmlspecialchars(
                                                $asal_daerah,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            placeholder="Contoh: Bulungan"
                                            maxlength="100"
                                            required>

                                    </div>

                                    <div class="col-12 col-md-6">

                                        <label
                                            for="id_kategori"
                                            class="form-label-custom">

                                            Kategori

                                        </label>

                                        <select
                                            id="id_kategori"
                                            name="id_kategori"
                                            class="form-control-custom"
                                            required>

                                            <option value="">
                                                Pilih kategori
                                            </option>

                                            <?php if ($kategori): ?>

                                                <?php while (
                                                    $item =
                                                        $kategori->fetch_assoc()
                                                ): ?>

                                                    <option
                                                        value="<?= (int) $item["id_kategori"] ?>"
                                                        <?= $id_kategori ===
                                                            (int) $item["id_kategori"]
                                                            ? "selected"
                                                            : "" ?>>

                                                        <?= htmlspecialchars(
                                                            $item["nama_kategori"],
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>

                                                    </option>

                                                <?php endwhile; ?>

                                            <?php endif; ?>

                                        </select>

                                    </div>

                                    <div class="col-12">

                                        <label
                                            for="bahan_utama"
                                            class="form-label-custom">

                                            Bahan Utama

                                        </label>

                                        <textarea
                                            id="bahan_utama"
                                            name="bahan_utama"
                                            class="form-control-custom"
                                            rows="4"
                                            placeholder="Contoh: beras, jagung, ikan, kelapa, rempah-rempah..."
                                            required><?= htmlspecialchars(
                                                $bahan_utama,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></textarea>

                                    </div>

                                    <div class="col-12">

                                        <label
                                            for="deskripsi"
                                            class="form-label-custom">

                                            Deskripsi

                                        </label>

                                        <textarea
                                            id="deskripsi"
                                            name="deskripsi"
                                            class="form-control-custom"
                                            rows="5"
                                            placeholder="Jelaskan secara singkat mengenai kuliner ini..."
                                            required><?= htmlspecialchars(
                                                $deskripsi,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></textarea>

                                    </div>

                                    <div class="col-12">

                                        <label
                                            for="cara_penyajian"
                                            class="form-label-custom">

                                            Cara Penyajian

                                        </label>

                                        <textarea
                                            id="cara_penyajian"
                                            name="cara_penyajian"
                                            class="form-control-custom"
                                            rows="4"
                                            placeholder="Jelaskan bagaimana kuliner ini biasanya disajikan..."
                                            required><?= htmlspecialchars(
                                                $cara_penyajian,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></textarea>

                                    </div>

                                    <div class="col-12">

                                        <label
                                            for="sejarah"
                                            class="form-label-custom">

                                            Sejarah / Nilai Budaya

                                        </label>

                                        <textarea
                                            id="sejarah"
                                            name="sejarah"
                                            class="form-control-custom"
                                            rows="5"
                                            placeholder="Masukkan sejarah, asal-usul, atau nilai budaya kuliner..."
                                            required><?= htmlspecialchars(
                                                $sejarah,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></textarea>

                                    </div>

                                </div>

                            </div>

                            <div class="col-12 col-lg-4">

                                <label
                                    class="form-label-custom">

                                    Foto Kuliner

                                </label>

                                <label
                                    for="foto"
                                    class="image-upload-box">

                                    <div class="image-preview-wrapper">

                                        <img
                                            src=""
                                            alt="Preview Foto"
                                            class="image-preview"
                                            data-image-preview loading="lazy" decoding="async">

                                        <div
                                            class="image-upload-placeholder"
                                            data-image-placeholder>

                                            <i class="bi bi-cloud-arrow-up"></i>

                                            <strong>
                                                Pilih foto
                                            </strong>

                                            <span>
                                                JPG, PNG, WEBP maksimal 5 MB
                                            </span>

                                        </div>

                                    </div>

                                </label>

                                <input
                                    type="file"
                                    id="foto"
                                    name="foto"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="image-file-input"
                                    data-image-input>

                            </div>

                        </div>

                    </div>

                    <div class="form-footer">

                        <a
                            href="kuliner.php"
                            class="btn-secondary-custom">

                            Batal

                        </a>

                        <button
                            type="submit"
                            class="btn-primary-custom">

                            <i class="bi bi-check-lg me-2"></i>

                            Simpan Kuliner

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

<nav class="mobile-bottom-nav">

    <a
        href="dashboard.php"
        class="mobile-nav-link">

        <i class="bi bi-grid-1x2-fill"></i>

        <span>
            Dashboard
        </span>

    </a>

    <a
        href="kuliner.php"
        class="mobile-nav-link">

        <i class="bi bi-fork-knife"></i>

        <span>
            Kuliner
        </span>

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
        class="mobile-nav-link">

        <i class="bi bi-tags-fill"></i>

        <span>
            Kategori
        </span>

    </a>

    <a
        href="profil.php"
        class="mobile-nav-link">

        <i class="bi bi-person-circle"></i>

        <span>
            Profil
        </span>

    </a>



        <a
            href="../logout.php"
            class="mobile-nav-link mobile-nav-logout"
            aria-label="Logout"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
</nav>

<script src="../assets/js/dashboard.js?v=4" defer></script>

<script src="../assets/js/kuliner.js?v=4" defer></script>

    <script src="../assets/js/performance.js?v=1" defer></script>

</body>
</html>