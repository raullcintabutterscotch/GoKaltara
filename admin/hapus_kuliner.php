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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kuliner.php");
    exit;
}

$id_kuliner = (int) ($_POST["id"] ?? 0);

if ($id_kuliner <= 0) {
    $_SESSION["flash_error"] = "ID kuliner tidak valid.";
    header("Location: kuliner.php");
    exit;
}

$stmt = $koneksi->prepare("SELECT foto FROM kuliner WHERE id_kuliner = ? LIMIT 1");
$stmt->bind_param("i", $id_kuliner);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    $_SESSION["flash_error"] = "Data kuliner tidak ditemukan.";
    header("Location: kuliner.php");
    exit;
}

$stmt = $koneksi->prepare("DELETE FROM kuliner WHERE id_kuliner = ?");
$stmt->bind_param("i", $id_kuliner);

if ($stmt->execute()) {
    $stmt->close();

    $foto = $data["foto"] ?? "";
    if ($foto !== "") {
        gokaltara_delete_optimized_image(
            $foto,
            __DIR__ . "/../assets/images"
        );
    }

    $_SESSION["flash_success"] = "Data kuliner berhasil dihapus.";
} else {
    $stmt->close();
    $_SESSION["flash_error"] = "Data kuliner gagal dihapus.";
}

header("Location: kuliner.php");
exit;
