<?php

declare(strict_types=1);

require_once "_auth.php";

$id_user = requireLogin($koneksi);
$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);

if (!validKuliner($koneksi, $id_kuliner)) {
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
    jsonResponse(false, "Favorit gagal diproses.");
}

$cek->bind_param("ii", $id_user, $id_kuliner);
$cek->execute();
$result = $cek->get_result();

$status = false;
$success = false;

if ($result && $result->num_rows > 0) {
    $favorit = $result->fetch_assoc();
    $cek->close();

    $hapus = $koneksi->prepare("
        DELETE FROM favorit
        WHERE id_favorit = ?
        AND id_user = ?
        AND id_kuliner = ?
    ");

    if (!$hapus) {
        jsonResponse(false, "Favorit gagal dihapus.");
    }

    $id_favorit = (int) ($favorit["id_favorit"] ?? 0);

    $hapus->bind_param(
        "iii",
        $id_favorit,
        $id_user,
        $id_kuliner
    );

    $success = $hapus->execute();
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

    if (!$tambah) {
        jsonResponse(false, "Favorit gagal disiapkan.");
    }

    $tambah->bind_param(
        "ii",
        $id_user,
        $id_kuliner
    );

    $success = $tambah->execute();
    $tambah_error = $tambah->error;
    $tambah->close();

    $status = true;

    if (!$success && $tambah_error !== "") {
        if (str_contains($tambah_error, "Duplicate entry")) {
            $status = true;
            $success = true;
        }
    }
}

if (!$success) {
    jsonResponse(false, "Favorit gagal diperbarui.");
}

$count = $koneksi->prepare("
    SELECT COUNT(*) AS total
    FROM favorit
    WHERE id_kuliner = ?
");

if (!$count) {
    jsonResponse(false, "Jumlah favorit gagal diperbarui.");
}

$count->bind_param("i", $id_kuliner);
$count->execute();
$data_count = $count->get_result()->fetch_assoc();
$count->close();

jsonResponse(
    true,
    $status
        ? "Kuliner ditambahkan ke favorit."
        : "Kuliner dihapus dari favorit.",
    [
        "favorit" => $status,
        "total" => (int) ($data_count["total"] ?? 0)
    ]
);
