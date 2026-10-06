<?php

session_start();

require_once "../config/koneksi.php";
require_once "../config/image_optimizer.php";
require_once "../config/profile_images.php";

header("Content-Type: application/json; charset=UTF-8");

$is_login = isset($_SESSION["login"]) &&
    (
        $_SESSION["login"] === true ||
        $_SESSION["login"] === 1 ||
        $_SESSION["login"] === "1"
    );

$id_user = (int) ($_SESSION["id_user"] ?? 0);

if (!$is_login || $id_user <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Tidak memiliki akses."
    ]);
    exit;
}

$after = (int) ($_GET["after"] ?? 0);

$stmt = $koneksi->prepare("
    SELECT
        n.id_notifikasi,
        n.id_pengirim,
        n.id_kuliner,
        n.id_komentar,
        n.pesan,
        n.dibaca,
        n.created_at,
        u.nama_lengkap,
        u.username,
        u.foto_profil
    FROM notifikasi AS n
    INNER JOIN `user` AS u
        ON n.id_pengirim = u.id_user
    WHERE n.id_user = ?
    AND n.id_notifikasi > ?
    ORDER BY n.id_notifikasi ASC
");

$stmt->bind_param(
    "ii",
    $id_user,
    $after
);

$stmt->execute();

$result = $stmt->get_result();

$data = [];
$ids = [];

while ($row = $result->fetch_assoc()) {

    if (!empty($row["foto_profil"])) {
        $row["foto_url"] = gokaltara_profile_image_url_direct(
            $row["foto_profil"],
            true
        );
    } else {
        $row["foto_url"] = "";
    }

    $data[] = $row;
    $ids[] = (int) $row["id_notifikasi"];
}

$stmt->close();

if (!empty($ids)) {

    $placeholders =
        implode(
            ",",
            array_fill(
                0,
                count($ids),
                "?"
            )
        );

    $types =
        "i" .
        str_repeat(
            "i",
            count($ids)
        );

    $sql = "
        UPDATE notifikasi
        SET dibaca = 1
        WHERE id_user = ?
        AND id_notifikasi IN ($placeholders)
    ";

    $update = $koneksi->prepare($sql);

    $params = [];
    $params[] = $types;
    $params[] = &$id_user;

    foreach ($ids as $key => $value) {
        $params[] = &$ids[$key];
    }

    call_user_func_array(
        [$update, "bind_param"],
        $params
    );

    $update->execute();
    $update->close();
}

echo json_encode([
    "success" => true,
    "data" => $data
]);