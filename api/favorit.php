<?php

declare(strict_types=1);

require_once "_auth.php";

$id_user = requireLogin($koneksi);
$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);

if ($id_kuliner <= 0 || !validKuliner($koneksi, $id_kuliner)) {
    jsonResponse(false, "Kuliner tidak ditemukan.");
}

$cek = $koneksi->prepare("
    SELECT id_favorit
    FROM favorit
    WHERE id_user = ?
    AND id_kuliner = ?
    LIMIT 1
");

if (!$cek) {
    jsonResponse(false, "Permintaan favorit tidak dapat diproses.");
}

$cek->bind_param("ii", $id_user, $id_kuliner);
$cek->execute();
$result = $cek->get_result();

if ($result && $result->num_rows > 0) {
    $favorit = $result->fetch_assoc();
    $cek->close();

    $hapus = $koneksi->prepare("
        DELETE FROM favorit
        WHERE id_favorit = ?
        AND id_user = ?
        LIMIT 1
    ");

    if (!$hapus) {
        jsonResponse(false, "Favorit gagal dihapus.");
    }

    $id_favorit = (int) ($favorit["id_favorit"] ?? 0);
    $hapus->bind_param("ii", $id_favorit, $id_user);
    $success = $hapus->execute();
    $hapus->close();

    if (!$success) {
        jsonResponse(false, "Favorit gagal dihapus.");
    }

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

    if (!$tambah) {
        jsonResponse(false, "Favorit gagal disimpan.");
    }

    $tambah->bind_param("ii", $id_user, $id_kuliner);

    if (!$tambah->execute()) {
        $error_code = $tambah->errno;
        $tambah->close();

        if ($error_code === 1062) {
            $status = true;
        } else {
            jsonResponse(false, "Favorit gagal disimpan.");
        }
    } else {
        $tambah->close();
        $status = true;
    }
}

$count = $koneksi->prepare("
    SELECT COUNT(*) AS total
    FROM favorit
    WHERE id_kuliner = ?
");

$total = 0;

if ($count) {
    $count->bind_param("i", $id_kuliner);
    $count->execute();
    $data_count = $count->get_result()->fetch_assoc();
    $total = (int) ($data_count["total"] ?? 0);
    $count->close();
}

jsonResponse(
    true,
    $status
        ? "Kuliner ditambahkan ke favorit."
        : "Kuliner dihapus dari favorit.",
    [
        "favorit" => $status,
        "total" => $total
    ]
);
