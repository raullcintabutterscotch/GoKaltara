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

if ($id_user <= 0 || $id_kuliner <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak valid."
    ]);
    exit;
}

$cek = $koneksi->prepare("
    SELECT id_favorit
    FROM favorit
    WHERE id_user = ?
    AND id_kuliner = ?
    LIMIT 1
");

$cek->bind_param(
    "ii",
    $id_user,
    $id_kuliner
);

$cek->execute();

$result = $cek->get_result();

if ($result->num_rows > 0) {

    $favorit = $result->fetch_assoc();

    $cek->close();

    $hapus = $koneksi->prepare("
        DELETE FROM favorit
        WHERE id_favorit = ?
    ");

    $hapus->bind_param(
        "i",
        $favorit["id_favorit"]
    );

    $hapus->execute();
    $hapus->close();

    $status = false;

} else {

    $cek->close();

    $tambah = $koneksi->prepare("
        INSERT INTO favorit
            (
                id_user,
                id_kuliner
            )
        VALUES
            (
                ?,
                ?
            )
    ");

    $tambah->bind_param(
        "ii",
        $id_user,
        $id_kuliner
    );

    if (!$tambah->execute()) {

        $tambah->close();

        echo json_encode([
            "success" => false,
            "message" => "Favorit gagal disimpan."
        ]);

        exit;
    }

    $tambah->close();

    $status = true;
}

$count = $koneksi->prepare("
    SELECT COUNT(*) AS total
    FROM favorit
    WHERE id_kuliner = ?
");

$count->bind_param(
    "i",
    $id_kuliner
);

$count->execute();

$data_count = $count->get_result()->fetch_assoc();

$count->close();

echo json_encode([
    "success" => true,
    "favorit" => $status,
    "total" => (int) ($data_count["total"] ?? 0)
]);