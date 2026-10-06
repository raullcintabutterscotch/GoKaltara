<?php

declare(strict_types=1);

require_once "_auth.php";

$id_user = requireLogin($koneksi);
$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);
$parent_id = (int) ($_POST["parent_id"] ?? 0);
$komentar = trim((string) ($_POST["komentar"] ?? ""));

if ($id_kuliner <= 0 || !validKuliner($koneksi, $id_kuliner)) {
    jsonResponse(false, "Kuliner tidak ditemukan.");
}

if ($komentar === "") {
    jsonResponse(false, "Komentar tidak boleh kosong.");
}

if (mb_strlen($komentar) < 3) {
    jsonResponse(false, "Komentar minimal 3 karakter.");
}

if (mb_strlen($komentar) > 1000) {
    jsonResponse(false, "Komentar maksimal 1000 karakter.");
}

$pemilik_parent = 0;

if ($parent_id > 0) {
    $check_parent = $koneksi->prepare("
        SELECT
            id_komentar,
            id_user
        FROM komentar
        WHERE id_komentar = ?
        AND id_kuliner = ?
        LIMIT 1
    ");

    if (!$check_parent) {
        jsonResponse(false, "Komentar tujuan tidak dapat diperiksa.");
    }

    $check_parent->bind_param("ii", $parent_id, $id_kuliner);
    $check_parent->execute();
    $parent = $check_parent->get_result()->fetch_assoc();
    $check_parent->close();

    if (!$parent) {
        jsonResponse(false, "Komentar tujuan tidak ditemukan.");
    }

    $pemilik_parent = (int) ($parent["id_user"] ?? 0);
}

if ($parent_id > 0) {
    $stmt = $koneksi->prepare("
        INSERT INTO komentar
        (
            id_user,
            id_kuliner,
            parent_id,
            komentar
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    if (!$stmt) {
        jsonResponse(false, "Komentar gagal disiapkan.");
    }

    $stmt->bind_param("iiis", $id_user, $id_kuliner, $parent_id, $komentar);
} else {
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

    if (!$stmt) {
        jsonResponse(false, "Komentar gagal disiapkan.");
    }

    $stmt->bind_param("iis", $id_user, $id_kuliner, $komentar);
}

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();

    jsonResponse(false, "Komentar gagal disimpan: " . $error);
}

$id_komentar = (int) $koneksi->insert_id;
$stmt->close();

if ($parent_id > 0 && $pemilik_parent > 0 && $pemilik_parent !== $id_user) {
    $food_query = $koneksi->prepare("
        SELECT nama_kuliner
        FROM kuliner
        WHERE id_kuliner = ?
        LIMIT 1
    ");

    if ($food_query) {
        $food_query->bind_param("i", $id_kuliner);
        $food_query->execute();
        $food = $food_query->get_result()->fetch_assoc();
        $food_query->close();

        $nama_food = $food["nama_kuliner"] ?? "kuliner ini";
        $pesan = "membalas komentar kamu di " . $nama_food . ".";

        $notification = $koneksi->prepare("
            INSERT INTO notifikasi
            (
                id_user,
                id_pengirim,
                id_kuliner,
                id_komentar,
                pesan
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        if ($notification) {
            $notification->bind_param(
                "iiiis",
                $pemilik_parent,
                $id_user,
                $id_kuliner,
                $id_komentar,
                $pesan
            );
            $notification->execute();
            $notification->close();
        }
    }
}

$get = $koneksi->prepare("
    SELECT
        k.id_komentar,
        k.id_user,
        k.id_kuliner,
        k.parent_id,
        k.komentar,
        k.created_at,
        u.nama_lengkap,
        u.username,
        u.level,
        u.foto_profil
    FROM komentar AS k
    INNER JOIN `user` AS u
        ON k.id_user = u.id_user
    WHERE k.id_komentar = ?
    LIMIT 1
");

$data = null;

if ($get) {
    $get->bind_param("i", $id_komentar);
    $get->execute();
    $data = $get->get_result()->fetch_assoc();
    $get->close();
}

jsonResponse(
    true,
    "Komentar berhasil ditambahkan.",
    [
        "data" => $data
    ]
);
