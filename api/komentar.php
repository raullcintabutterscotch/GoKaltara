<?php

declare(strict_types=1);

require_once "_auth.php";
require_once dirname(__DIR__) . "/config/image_optimizer.php";

$id_user = requireLogin($koneksi);
$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);
$parent_id = (int) ($_POST["parent_id"] ?? 0);
$komentar = trim((string) ($_POST["komentar"] ?? ""));

if (!validKuliner($koneksi, $id_kuliner)) {
    jsonResponse(false, "Kuliner tidak ditemukan.");
}

if ($komentar === "") {
    jsonResponse(false, "Komentar tidak boleh kosong.");
}

$comment_length = function_exists("mb_strlen")
    ? mb_strlen($komentar)
    : strlen($komentar);

if ($comment_length < 3) {
    jsonResponse(false, "Komentar minimal 3 karakter.");
}

if ($comment_length > 1000) {
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

    $check_parent->bind_param(
        "ii",
        $parent_id,
        $id_kuliner
    );

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

    $stmt->bind_param(
        "iiis",
        $id_user,
        $id_kuliner,
        $parent_id,
        $komentar
    );
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

    $stmt->bind_param(
        "iis",
        $id_user,
        $id_kuliner,
        $komentar
    );
}

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();

    jsonResponse(
        false,
        $error !== ""
            ? "Komentar gagal disimpan: " . $error
            : "Komentar gagal disimpan."
    );
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

        $nama_food = (string) ($food["nama_kuliner"] ?? "kuliner ini");
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

if ($data) {
    if (!empty($data["foto_profil"])) {
        $data["foto_url"] = gokaltara_profile_image_url(
            (string) $data["foto_profil"],
            true
        );
    } else {
        $data["foto_url"] = "";
    }

    $data["total_like"] = 0;
    $data["user_like"] = false;

    $like_count = $koneksi->prepare("
        SELECT COUNT(*) AS total
        FROM komentar_like
        WHERE id_komentar = ?
    ");

    if ($like_count) {
        $like_count->bind_param("i", $id_komentar);
        $like_count->execute();
        $like_data = $like_count->get_result()->fetch_assoc();
        $like_count->close();

        $data["total_like"] = (int) ($like_data["total"] ?? 0);
    }

    $user_like = $koneksi->prepare("
        SELECT id_like
        FROM komentar_like
        WHERE id_user = ?
        AND id_komentar = ?
        LIMIT 1
    ");

    if ($user_like) {
        $user_like->bind_param("ii", $id_user, $id_komentar);
        $user_like->execute();
        $user_like_result = $user_like->get_result();
        $data["user_like"] = $user_like_result && $user_like_result->num_rows > 0;
        $user_like->close();
    }
}

jsonResponse(
    true,
    "Komentar berhasil ditambahkan.",
    [
        "data" => $data,
        "id_komentar" => $id_komentar
    ]
);
