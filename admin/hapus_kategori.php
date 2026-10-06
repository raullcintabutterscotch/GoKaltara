<?php

session_start();
require_once "../config/koneksi.php";

if (
    !isset($_SESSION["login"]) ||
    $_SESSION["login"] !== true ||
    !isset($_SESSION["level"]) ||
    $_SESSION["level"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

$id_kategori = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_kategori) {
    $_SESSION["kategori_message"] = "ID kategori tidak valid.";
    $_SESSION["kategori_message_type"] = "danger";
    header("Location: kategori.php");
    exit;
}

$stmt = $koneksi->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ? LIMIT 1");
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

$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM kuliner WHERE id_kategori = ?");
$stmt->bind_param("i", $id_kategori);
$stmt->execute();
$total_digunakan = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

if ($total_digunakan > 0) {
    $_SESSION["kategori_message"] = "Kategori \"{$data_kategori["nama_kategori"]}\" masih digunakan oleh {$total_digunakan} data kuliner dan tidak dapat dihapus.";
    $_SESSION["kategori_message_type"] = "warning";
    header("Location: kategori.php");
    exit;
}

$stmt = $koneksi->prepare("DELETE FROM kategori WHERE id_kategori = ?");
$stmt->bind_param("i", $id_kategori);
$berhasil = $stmt->execute();
$stmt->close();

if ($berhasil) {
    $_SESSION["kategori_message"] = "Kategori \"{$data_kategori["nama_kategori"]}\" berhasil dihapus.";
    $_SESSION["kategori_message_type"] = "success";
} else {
    $_SESSION["kategori_message"] = "Kategori gagal dihapus.";
    $_SESSION["kategori_message_type"] = "danger";
}

header("Location: kategori.php");
exit;
