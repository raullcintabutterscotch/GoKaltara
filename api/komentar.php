<?php

declare(strict_types=1);

require_once "_auth.php";
require_once dirname(__DIR__) . "/config/image_optimizer.php";

$id_user = requireLogin($koneksi);

$id_kuliner = (int) ($_POST["id_kuliner"] ?? 0);

$parent_id = (int) (
    $_POST["parent_id"] ?? 0
);

$komentar = trim(
    (string) (
        $_POST["komentar"] ?? ""
    )
);

if (!validKuliner($koneksi, $id_kuliner)) {
    jsonResponse(
        false,
        "Kuliner tidak ditemukan."
    );
}

if ($komentar === "") {
    jsonResponse(
        false,
        "Komentar tidak boleh kosong."
    );
}

$comment_length =
    function_exists("mb_strlen")
        ? mb_strlen($komentar)
        : strlen($komentar);

if ($comment_length < 3) {
    jsonResponse(
        false,
        "Komentar minimal 3 karakter."
    );
}

if ($comment_length > 1000) {
    jsonResponse(
        false,
        "Komentar maksimal 1000 karakter."
    );
}