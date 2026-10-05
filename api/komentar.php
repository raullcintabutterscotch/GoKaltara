<?php

session_start();

require_once "../config/koneksi.php";
require_once "_auth.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isUserLoggedIn()) {
    echo json_encode([
        "success" => false,
        "message" => "Silakan login terlebih dahulu."
    ]);
    exit;
}

$id_user = getUserId();
$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);
$komentar = trim($_POST["komentar"] ?? "");

if ($id_user <= 0 || $id_kuliner <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak valid."
    ]);
    exit;
}

if ($komentar === "") {
    echo json_encode([
        "success" => false,
        "message" => "Komentar tidak boleh kosong."
    ]);
    exit;
}

if (mb_strlen($komentar) < 3) {
    echo json_encode([
        "success" => false,
        "message" => "Komentar minimal 3 karakter."
    ]);
    exit;
}

if (mb_strlen($komentar) > 1000) {
    echo json_encode([
        "success" => false,
        "message" => "Komentar maksimal 1000 karakter."
    ]);
    exit;
}

$cek_kuliner = $koneksi->prepare("
    SELECT id_kuliner
    FROM kuliner
    WHERE id_kuliner = ?
    LIMIT 1
");

$cek_kuliner->bind_param(
    "i",
    $id_kuliner
);

$cek_kuliner->execute();

$result_kuliner = $cek_kuliner->get_result();

if ($result_kuliner->num_rows === 0) {

    $cek_kuliner->close();

    echo json_encode([
        "success" => false,
        "message" => "Kuliner tidak ditemukan."
    ]);

    exit;
}

$cek_kuliner->close();

$stmt = $koneksi->prepare("
    INSERT INTO komentar
        (
            id_user,
            id_kuliner,
            komentar
        )
    VALUES
        (
            ?,
            ?,
            ?
        )
");

$stmt->bind_param(
    "iis",
    $id_user,
    $id_kuliner,
    $komentar
);

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "Komentar gagal disimpan."
    ]);

    exit;
}

$id_komentar = $koneksi->insert_id;

$stmt->close();

$get = $koneksi->prepare("
    SELECT
        k.id_komentar,
        k.komentar,
        k.created_at,
        u.nama_lengkap,
        u.username
    FROM komentar AS k
    INNER JOIN `user` AS u
        ON k.id_user = u.id_user
    WHERE k.id_komentar = ?
    LIMIT 1
");

$get->bind_param(
    "i",
    $id_komentar
);

$get->execute();

$data = $get->get_result()->fetch_assoc();

$get->close();

echo json_encode([
    "success" => true,
    "message" => "Komentar berhasil ditambahkan.",
    "data" => $data
]);