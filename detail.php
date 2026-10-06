<?php

session_start();
require_once "config/koneksi.php";
require_once "config/image_optimizer.php";

$is_login = isset($_SESSION["login"]) &&
    (
        $_SESSION["login"] === true ||
        $_SESSION["login"] === 1 ||
        $_SESSION["login"] === "1"
    );

$id_user = (int) ($_SESSION["id_user"] ?? 0);
$nama_user = $_SESSION["nama_lengkap"] ?? $_SESSION["username"] ?? "Pengguna";
$level_user = $_SESSION["level"] ?? "user";
$id_kuliner = (int) ($_GET["id"] ?? 0);

if ($id_kuliner <= 0) {
    header("Location: katalog.php");
    exit;
}

function e($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function fotoKuliner($foto, $thumbnail = false)
{
    return gokaltara_image_url(
        (string) ($foto ?? ""),
        "assets/images/",
        (bool) $thumbnail
    );
}

function fotoProfil($foto, $thumbnail = false)
{
    return gokaltara_profile_image_url(
        (string) ($foto ?? ""),
        (bool) $thumbnail
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax"])) {

    header("Content-Type: application/json; charset=UTF-8");

    $action = $_POST["action"] ?? "";

    if (!$is_login || $id_user <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Silakan login terlebih dahulu."
        ]);
        exit;
    }

    if ($action === "favorit") {

        $stmt = $koneksi->prepare("
            SELECT id_favorit
            FROM favorit
            WHERE id_user = ?
            AND id_kuliner = ?
            LIMIT 1
        ");

        $stmt->bind_param("ii", $id_user, $id_kuliner);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();
            $stmt->close();

            $delete = $koneksi->prepare("
                DELETE FROM favorit
                WHERE id_favorit = ?
                AND id_user = ?
            ");

            $delete->bind_param(
                "ii",
                $row["id_favorit"],
                $id_user
            );

            $success = $delete->execute();
            $delete->close();

            $favorit = false;

        } else {

            $stmt->close();

            $insert = $koneksi->prepare("
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

            $insert->bind_param(
                "ii",
                $id_user,
                $id_kuliner
            );

            $success = $insert->execute();
            $insert->close();

            $favorit = true;
        }

        if (!$success) {
            echo json_encode([
                "success" => false,
                "message" => "Favorit gagal diperbarui."
            ]);
            exit;
        }

        $count = $koneksi->prepare("
            SELECT COUNT(*) AS total
            FROM favorit
            WHERE id_kuliner = ?
        ");

        $count->bind_param("i", $id_kuliner);
        $count->execute();

        $count_row = $count->get_result()->fetch_assoc();
        $count->close();

        echo json_encode([
            "success" => true,
            "favorit" => $favorit,
            "total" => (int) ($count_row["total"] ?? 0)
        ]);

        exit;
    }

    if ($action === "rating") {

        $nilai = (int) ($_POST["rating"] ?? 0);

        if ($nilai < 0 || $nilai > 5) {
            echo json_encode([
                "success" => false,
                "message" => "Rating harus antara 1 sampai 5."
            ]);
            exit;
        }

        if ($nilai === 0) {

            $stmt = $koneksi->prepare("
                DELETE FROM rating
                WHERE id_user = ?
                AND id_kuliner = ?
            ");

            $stmt->bind_param(
                "ii",
                $id_user,
                $id_kuliner
            );

            $success = $stmt->execute();
            $stmt->close();

        } else {

            $check = $koneksi->prepare("
                SELECT id_rating
                FROM rating
                WHERE id_user = ?
                AND id_kuliner = ?
                LIMIT 1
            ");

            $check->bind_param(
                "ii",
                $id_user,
                $id_kuliner
            );

            $check->execute();

            $existing = $check->get_result();

            if ($existing->num_rows > 0) {

                $old = $existing->fetch_assoc();
                $check->close();

                $stmt = $koneksi->prepare("
                    UPDATE rating
                    SET
                        nilai = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id_rating = ?
                    AND id_user = ?
                    AND id_kuliner = ?
                ");

                $stmt->bind_param(
                    "iiii",
                    $nilai,
                    $old["id_rating"],
                    $id_user,
                    $id_kuliner
                );

            } else {

                $check->close();

                $stmt = $koneksi->prepare("
                    INSERT INTO rating
                    (
                        id_user,
                        id_kuliner,
                        nilai
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->bind_param(
                    "iii",
                    $id_user,
                    $id_kuliner,
                    $nilai
                );
            }

            $success = $stmt->execute();
            $stmt->close();
        }

        if (!$success) {
            echo json_encode([
                "success" => false,
                "message" => "Rating gagal diperbarui."
            ]);
            exit;
        }

        $stmt = $koneksi->prepare("
            SELECT
                AVG(nilai) AS average,
                COUNT(*) AS total
            FROM rating
            WHERE id_kuliner = ?
        ");

        $stmt->bind_param(
            "i",
            $id_kuliner
        );

        $stmt->execute();

        $rating_data =
            $stmt->get_result()->fetch_assoc();

        $stmt->close();

        echo json_encode([
            "success" => true,
            "rating" => $nilai,
            "average" => round(
                (float) ($rating_data["average"] ?? 0),
                1
            ),
            "total" => (int) ($rating_data["total"] ?? 0),
            "cancelled" => $nilai === 0
        ]);

        exit;
    }

    if ($action === "komentar_tambah") {

        $komentar = trim(
            $_POST["komentar"] ?? ""
        );

        $parent_id = (int) (
            $_POST["parent_id"] ?? 0
        );

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

            $check_parent->bind_param(
                "ii",
                $parent_id,
                $id_kuliner
            );

            $check_parent->execute();

            $parent =
                $check_parent
                    ->get_result()
                    ->fetch_assoc();

            $check_parent->close();

            if (!$parent) {
                echo json_encode([
                    "success" => false,
                    "message" => "Komentar tujuan tidak ditemukan."
                ]);
                exit;
            }

            $pemilik_parent =
                (int) $parent["id_user"];

            $insert = $koneksi->prepare("
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

            $insert->bind_param(
                "iiis",
                $id_user,
                $id_kuliner,
                $parent_id,
                $komentar
            );

        } else {

            $insert = $koneksi->prepare("
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

            $insert->bind_param(
                "iis",
                $id_user,
                $id_kuliner,
                $komentar
            );
        }

        if (!$insert->execute()) {

            $error = $insert->error;
            $insert->close();

            echo json_encode([
                "success" => false,
                "message" => "Komentar gagal dikirim: " . $error
            ]);

            exit;
        }

        $id_komentar = $koneksi->insert_id;
        $insert->close();

        if (
            $parent_id > 0 &&
            $pemilik_parent > 0 &&
            $pemilik_parent !== $id_user
        ) {

            $food_query = $koneksi->prepare("
                SELECT nama_kuliner
                FROM kuliner
                WHERE id_kuliner = ?
                LIMIT 1
            ");

            $food_query->bind_param(
                "i",
                $id_kuliner
            );

            $food_query->execute();

            $food =
                $food_query
                    ->get_result()
                    ->fetch_assoc();

            $food_query->close();

            $nama_food =
                $food["nama_kuliner"] ??
                "kuliner ini";

            $pesan =
                $nama_user .
                " membalas komentar kamu pada " .
                $nama_food .
                ".";

            $notif = $koneksi->prepare("
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

            if ($notif) {

                $notif->bind_param(
                    "iiiis",
                    $pemilik_parent,
                    $id_user,
                    $id_kuliner,
                    $id_komentar,
                    $pesan
                );

                $notif->execute();
                $notif->close();
            }
        }

        $stmt = $koneksi->prepare("
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
                u.foto_profil,

                (
                    SELECT COUNT(*)
                    FROM komentar_like AS kl
                    WHERE kl.id_komentar =
                        k.id_komentar
                ) AS total_like,

                (
                    SELECT COUNT(*)
                    FROM komentar_like AS kl2
                    WHERE kl2.id_komentar =
                        k.id_komentar
                    AND kl2.id_user = ?
                ) AS user_like

            FROM komentar AS k

            INNER JOIN `user` AS u
                ON k.id_user = u.id_user

            WHERE k.id_komentar = ?

            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $id_user,
            $id_komentar
        );

        $stmt->execute();

        $item =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $stmt->close();

        if (!$item) {

            echo json_encode([
                "success" => false,
                "message" => "Komentar tersimpan tetapi gagal dimuat."
            ]);

            exit;
        }

        $item["foto_url"] =
            fotoProfil(
                $item["foto_profil"],
                true
            );

        $item["total_like"] =
            (int) $item["total_like"];

        $item["user_like"] =
            (int) $item["user_like"] > 0;

        $item["can_edit"] =
            $id_user ===
            (int) $item["id_user"];

        $item["can_delete"] =
            $id_user ===
            (int) $item["id_user"];

        echo json_encode([
            "success" => true,
            "message" =>
                $parent_id > 0
                    ? "Balasan berhasil ditambahkan."
                    : "Komentar berhasil ditambahkan.",
            "data" => $item
        ]);

        exit;
    }

    if ($action === "komentar_edit") {

        $id_komentar =
            (int) (
                $_POST["id_komentar"] ?? 0
            );

        $komentar =
            trim(
                $_POST["komentar"] ?? ""
            );

        if (
            $id_komentar <= 0 ||
            mb_strlen($komentar) < 3 ||
            mb_strlen($komentar) > 1000
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Komentar harus 3 sampai 1000 karakter."
            ]);

            exit;
        }

        $stmt =
            $koneksi->prepare("
                UPDATE komentar
                SET komentar = ?
                WHERE id_komentar = ?
                AND id_user = ?
                AND id_kuliner = ?
            ");

        $stmt->bind_param(
            "siii",
            $komentar,
            $id_komentar,
            $id_user,
            $id_kuliner
        );

        $success =
            $stmt->execute();

        $stmt->close();

        echo json_encode([
            "success" => $success,
            "message" =>
                $success
                    ? "Komentar berhasil diperbarui."
                    : "Komentar gagal diperbarui.",
            "komentar" => $komentar
        ]);

        exit;
    }

    if ($action === "komentar_hapus") {

        $id_komentar =
            (int) (
                $_POST["id_komentar"] ?? 0
            );

        if ($id_komentar <= 0) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Komentar tidak valid."
            ]);

            exit;
        }

        $child_check =
            $koneksi->prepare("
                SELECT COUNT(*) AS total
                FROM komentar
                WHERE parent_id = ?
            ");

        $child_check->bind_param(
            "i",
            $id_komentar
        );

        $child_check->execute();

        $child_data =
            $child_check
                ->get_result()
                ->fetch_assoc();

        $child_check->close();

        if (
            (int) (
                $child_data["total"] ??
                0
            ) > 0
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Komentar yang memiliki balasan tidak dapat dihapus."
            ]);

            exit;
        }

        $stmt =
            $koneksi->prepare("
                DELETE FROM komentar
                WHERE id_komentar = ?
                AND id_user = ?
                AND id_kuliner = ?
            ");

        $stmt->bind_param(
            "iii",
            $id_komentar,
            $id_user,
            $id_kuliner
        );

        $stmt->execute();

        $success =
            $stmt->affected_rows > 0;

        $stmt->close();

        echo json_encode([
            "success" => $success,
            "message" =>
                $success
                    ? "Komentar berhasil dihapus."
                    : "Komentar bukan milik kamu."
        ]);

        exit;
    }

    if ($action === "komentar_like") {

        $id_komentar =
            (int) (
                $_POST["id_komentar"] ?? 0
            );

        if ($id_komentar <= 0) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Komentar tidak valid."
            ]);

            exit;
        }

        $check =
            $koneksi->prepare("
                SELECT id_like
                FROM komentar_like
                WHERE id_user = ?
                AND id_komentar = ?
                LIMIT 1
            ");

        $check->bind_param(
            "ii",
            $id_user,
            $id_komentar
        );

        $check->execute();

        $result =
            $check->get_result();

        if ($result->num_rows > 0) {

            $row =
                $result->fetch_assoc();

            $check->close();

            $stmt =
                $koneksi->prepare("
                    DELETE FROM komentar_like
                    WHERE id_like = ?
                    AND id_user = ?
                ");

            $stmt->bind_param(
                "ii",
                $row["id_like"],
                $id_user
            );

            $success =
                $stmt->execute();

            $stmt->close();

            $liked =
                false;

        } else {

            $check->close();

            $stmt =
                $koneksi->prepare("
                    INSERT INTO komentar_like
                    (
                        id_user,
                        id_komentar
                    )
                    VALUES
                    (
                        ?,
                        ?
                    )
                ");

            $stmt->bind_param(
                "ii",
                $id_user,
                $id_komentar
            );

            $success =
                $stmt->execute();

            $stmt->close();

            $liked =
                true;
        }

        if (!$success) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Like gagal diperbarui."
            ]);

            exit;
        }

        $count =
            $koneksi->prepare("
                SELECT COUNT(*) AS total
                FROM komentar_like
                WHERE id_komentar = ?
            ");

        $count->bind_param(
            "i",
            $id_komentar
        );

        $count->execute();

        $row =
            $count
                ->get_result()
                ->fetch_assoc();

        $count->close();

        echo json_encode([
            "success" => true,
            "liked" => $liked,
            "total" => (int) (
                $row["total"] ?? 0
            )
        ]);

        exit;
    }

    echo json_encode([
        "success" => false,
        "message" => "Permintaan tidak valid."
    ]);

    exit;
}

$stmt =
    $koneksi->prepare("
        SELECT
            k.id_kuliner,
            k.nama_kuliner,
            k.id_kategori,
            k.asal_daerah,
            k.bahan_utama,
            k.deskripsi,
            k.cara_penyajian,
            k.sejarah,
            k.foto,
            c.nama_kategori
        FROM kuliner AS k
        LEFT JOIN kategori AS c
            ON k.id_kategori = c.id_kategori
        WHERE k.id_kuliner = ?
        LIMIT 1
    ");

$stmt->bind_param(
    "i",
    $id_kuliner
);

$stmt->execute();

$data =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$data) {
    header("Location: katalog.php");
    exit;
}

$foto =
    fotoKuliner(
        $data["foto"] ?? ""
    );

$stmt =
    $koneksi->prepare("
        SELECT
            AVG(nilai) AS average,
            COUNT(*) AS total
        FROM rating
        WHERE id_kuliner = ?
    ");

$stmt->bind_param(
    "i",
    $id_kuliner
);

$stmt->execute();

$rating_info =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

$rating_average =
    round(
        (float) (
            $rating_info["average"] ?? 0
        ),
        1
    );

$rating_total =
    (int) (
        $rating_info["total"] ?? 0
    );

$user_rating =
    0;

if ($is_login) {

    $stmt =
        $koneksi->prepare("
            SELECT nilai
            FROM rating
            WHERE id_user = ?
            AND id_kuliner = ?
            LIMIT 1
        ");

    $stmt->bind_param(
        "ii",
        $id_user,
        $id_kuliner
    );

    $stmt->execute();

    $row =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    $user_rating =
        (int) (
            $row["nilai"] ?? 0
        );
}

$stmt =
    $koneksi->prepare("
        SELECT COUNT(*) AS total
        FROM favorit
        WHERE id_kuliner = ?
    ");

$stmt->bind_param(
    "i",
    $id_kuliner
);

$stmt->execute();

$row =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

$total_favorit =
    (int) (
        $row["total"] ?? 0
    );

$user_favorit =
    false;

if ($is_login) {

    $stmt =
        $koneksi->prepare("
            SELECT id_favorit
            FROM favorit
            WHERE id_user = ?
            AND id_kuliner = ?
            LIMIT 1
        ");

    $stmt->bind_param(
        "ii",
        $id_user,
        $id_kuliner
    );

    $stmt->execute();

    $user_favorit =
        $stmt
            ->get_result()
            ->num_rows > 0;

    $stmt->close();
}

$komentar_list =
    [];

$stmt =
    $koneksi->prepare("
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
            u.foto_profil,

            (
                SELECT COUNT(*)
                FROM komentar_like AS kl
                WHERE kl.id_komentar =
                    k.id_komentar
            ) AS total_like,

            (
                SELECT COUNT(*)
                FROM komentar_like AS kl2
                WHERE kl2.id_komentar =
                    k.id_komentar
                AND kl2.id_user = ?
            ) AS user_like

        FROM komentar AS k

        INNER JOIN `user` AS u
            ON k.id_user = u.id_user

        WHERE k.id_kuliner = ?

        ORDER BY k.id_komentar ASC
    ");

$stmt->bind_param(
    "ii",
    $id_user,
    $id_kuliner
);

$stmt->execute();

$result =
    $stmt->get_result();

while (
    $row =
    $result->fetch_assoc()
) {

    $row["foto_url"] =
        fotoProfil(
            $row["foto_profil"],
            true
        );

    $row["total_like"] =
        (int) $row["total_like"];

    $row["user_like"] =
        (int) $row["user_like"] > 0;

    $row["can_edit"] =
        $is_login &&
        $id_user ===
        (int) $row["id_user"];

    $row["can_delete"] =
        $is_login &&
        $id_user ===
        (int) $row["id_user"];

    $komentar_list[] =
        $row;
}

$stmt->close();

$comment_map = [];

foreach (
    $komentar_list as $item
) {

    $comment_map[
        (int) $item["id_komentar"]
    ] = $item;
}

$roots = [];
$reply_groups = [];

foreach (
    $komentar_list as $item
) {

    $parent_id =
        (int) (
            $item["parent_id"] ?? 0
        );

    if ($parent_id === 0) {

        $roots[] =
            $item;

        continue;
    }

    $root_id =
        $parent_id;

    $guard =
        0;

    while (
        isset(
            $comment_map[$root_id]
        ) &&
        (
            (int) (
                $comment_map[$root_id]["parent_id"] ??
                0
            ) > 0
        ) &&
        $guard < 100
    ) {

        $root_id =
            (int) (
                $comment_map[$root_id]["parent_id"]
            );

        $guard++;
    }

    if (
        !isset(
            $reply_groups[$root_id]
        )
    ) {

        $reply_groups[$root_id] =
            [];
    }

    $reply_groups[$root_id][] =
        $item;
}

$recommended =
    [];

$stmt =
    $koneksi->prepare("
        SELECT
            k.id_kuliner,
            k.nama_kuliner,
            k.id_kategori,
            k.asal_daerah,
            k.foto,
            c.nama_kategori,

            (
                SELECT AVG(r.nilai)
                FROM rating AS r
                WHERE r.id_kuliner =
                    k.id_kuliner
            ) AS average_rating

        FROM kuliner AS k

        LEFT JOIN kategori AS c
            ON k.id_kategori =
                c.id_kategori

        WHERE k.id_kategori = ?
        AND k.id_kuliner != ?

        ORDER BY
            average_rating DESC,
            k.id_kuliner DESC

        LIMIT 4
    ");

$stmt->bind_param(
    "ii",
    $data["id_kategori"],
    $id_kuliner
);

$stmt->execute();

$result =
    $stmt->get_result();

while (
    $row =
    $result->fetch_assoc()
) {

    $recommended[] =
        $row;
}

$stmt->close();

$unread_count =
    0;

if ($is_login) {

    $stmt =
        $koneksi->prepare("
            SELECT COUNT(*) AS total
            FROM notifikasi
            WHERE id_user = ?
            AND dibaca = 0
        ");

    $stmt->bind_param(
        "i",
        $id_user
    );

    $stmt->execute();

    $row =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    $unread_count =
        (int) (
            $row["total"] ?? 0
        );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<style id="gokaltara-performance-inline">
img.perf-image{background-color:#eef3f0;background-image:linear-gradient(90deg,#eef3f0 0%,#f8faf9 50%,#eef3f0 100%);background-size:220% 100%;background-repeat:no-repeat}
img.perf-image.is-loaded{background-image:none}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
@media(max-width:991.98px){html{scroll-behavior:auto}}
</style>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($data["nama_kuliner"]) ?>
        | GoKaltara Kuliner
    </title>

    <link
        rel="icon"
        href="assets/images/logo.svg"
        sizes="48x48"
    >
    <link
        rel="preconnect"
        href="https://cdn.jsdelivr.net"
        crossorigin
    >


    <link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"></noscript>

    <link rel="preload" as="style" href="assets/css/detail.css?v=50" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="assets/css/detail.css?v=50"></noscript>

    <link rel="preload" as="style" href="assets/css/notifikasi.css?v=60" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="assets/css/notifikasi.css?v=60"></noscript>
    <link rel="preload" as="style" href="assets/css/footer.css?v=70" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="assets/css/footer.css?v=70"></noscript>
    <noscript>
        <link rel="preload" as="style" href="assets/css/footer.css?v=70" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="assets/css/footer.css?v=70"></noscript>
    </noscript>
    <noscript>
        </noscript>

</head>

<body
    data-kuliner-id="<?= $id_kuliner ?>"
    data-user-id="<?= $id_user ?>"
    data-logged-in="<?= $is_login ? "1" : "0" ?>"
>

<nav class="public-navbar">

    <div class="nav-container">

        <a
            href="index.php"
            class="brand-public"
        >

            <span class="brand-logo">

                <img
                    src="assets/images/logo.svg"
                    alt="Logo GoKaltara Kuliner" loading="eager" decoding="async" width="58" height="58">

            </span>

            <span class="brand-text">

                GoKaltara
                <strong>Kuliner</strong>

            </span>

        </a>

        <div class="desktop-menu">

            <a
                href="index.php"
                class="nav-item-public"
            >
                Beranda
            </a>

            <a
                href="katalog.php"
                class="nav-item-public active"
            >
                Katalog
            </a>

            <a
                href="tentang.php"
                class="nav-item-public"
            >
                Tentang
            </a>

            <a
                href="pencarian.php"
                class="nav-item-public"
            >
                Pencarian
            </a>

            <?php if ($is_login): ?>

                <a
                    href="notifikasi.php"
                    class="notification-nav"
                 aria-label="Notifikasi">

                    <i class="bi bi-bell"></i>

                    <?php if ($unread_count > 0): ?>

                        <span class="notification-badge">
                            <?= $unread_count > 99
                                ? "99+"
                                : $unread_count ?>
                        </span>

                    <?php endif; ?>

                </a>

                <?php if ($level_user === "admin"): ?>

                    <a
                        href="admin/dashboard.php"
                        class="btn-admin"
                    >

                        <i class="bi bi-person"></i>

                        <span>

                            <?= e($nama_user) ?>

                            <small>
                                Admin
                            </small>

                        </span>

                    </a>

                <?php else: ?>

                    <a
                        href="profil-user.php"
                        class="btn-admin"
                    >

                        <i class="bi bi-person"></i>

                        <span>
                            <?= e($nama_user) ?>
                        </span>

                    </a>

                <?php endif; ?>

            <?php else: ?>

                <a
                    href="login.php"
                    class="btn-admin"
                >

                    <i class="bi bi-person"></i>

                    <span>
                        Login
                    </span>

                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

<main class="detail-main">

    <section class="detail-hero-section">

        <div class="detail-container">

            <a
                href="katalog.php"
                class="detail-back"
            >

                <i class="bi bi-arrow-left"></i>

                Kembali ke Katalog

            </a>

            <div class="detail-hero">

                <div class="detail-photo-wrap">

                    <img
                        src="<?= e($foto) ?>"
                        alt="<?= e($data["nama_kuliner"]) ?>"
                        class="detail-photo"
                        data-no-lazy
                        fetchpriority="high"
                     loading="eager" decoding="async">

                </div>

                <div class="detail-info">

                    <span class="detail-category">

                        <?= e(
                            $data["nama_kategori"] ??
                            "Kuliner"
                        ) ?>

                    </span>

                    <h1>

                        <?= e(
                            $data["nama_kuliner"]
                        ) ?>

                    </h1>

                    <div class="detail-location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <?= e(
                            $data["asal_daerah"] ??
                            "-"
                        ) ?>

                    </div>

                    <div class="detail-rating-summary">

                        <div class="rating-stars-static">

                            <?php for (
                                $i = 1;
                                $i <= 5;
                                $i++
                            ): ?>

                                <i
                                    class="bi <?= $i <= round($rating_average)
                                        ? "bi-star-fill"
                                        : "bi-star" ?>"
                                ></i>

                            <?php endfor; ?>

                        </div>

                        <strong
                            id="ratingAverage"
                        >

                            <?= number_format(
                                $rating_average,
                                1
                            ) ?>

                        </strong>

                        <span
                            id="ratingTotal"
                        >

                            (<?= $rating_total ?>
                            rating)

                        </span>

                    </div>

                    <p class="detail-description">

                        <?= nl2br(
                            e(
                                $data["deskripsi"] ??
                                ""
                            )
                        ) ?>

                    </p>

                    <?php if ($is_login): ?>

                        <div class="rating-box">

                            <span>
                                Beri rating
                            </span>

                            <div
                                class="rating-input"
                                id="ratingInput"
                                data-current="<?= $user_rating ?>"
                            >

                                <?php for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ): ?>

                                    <button
                                        type="button"
                                        class="rating-star <?= $i <= $user_rating
                                            ? "active"
                                            : "" ?>"
                                        data-rating="<?= $i ?>"
                                    >

                                        <i
                                            class="bi bi-star-fill"
                                        ></i>

                                    </button>

                                <?php endfor; ?>

                            </div>

                            <small
                                id="ratingMessage"
                            ></small>

                        </div>

                    <?php endif; ?>

                    <div class="detail-actions">

                        <button
                            type="button"
                            id="favoriteButton"
                            class="favorite-button <?= $user_favorit
                                ? "active"
                                : "" ?>"
                            <?= !$is_login
                                ? "disabled"
                                : "" ?>
                        >

                            <i
                                class="bi <?= $user_favorit
                                    ? "bi-heart-fill"
                                    : "bi-heart" ?>"
                            ></i>

                            <span id="favoriteText">

                                <?= $user_favorit
                                    ? "Hapus Favorit"
                                    : "Tambah Favorit" ?>

                            </span>

                        </button>

                        <div class="favorite-total">

                            <strong
                                id="favoriteCount"
                            >

                                <?= $total_favorit ?>

                            </strong>

                            favorit

                        </div>

                        <?php if ($is_login): ?>

                            <a
                                href="favorit.php"
                                class="favorite-page-link"
                            >

                                Lihat Favorit

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="detail-information">

        <div class="detail-container">

            <div class="info-grid">

                <article class="info-card">

                    <span class="info-label">
                        INFORMASI
                    </span>

                    <h2>
                        Bahan Utama
                    </h2>

                    <div class="info-text">

                        <?= nl2br(
                            e(
                                $data["bahan_utama"] ??
                                "-"
                            )
                        ) ?>

                    </div>

                </article>

                <article class="info-card">

                    <span class="info-label">
                        PENYAJIAN
                    </span>

                    <h2>
                        Cara Penyajian
                    </h2>

                    <div class="info-text">

                        <?= nl2br(
                            e(
                                $data["cara_penyajian"] ??
                                "-"
                            )
                        ) ?>

                    </div>

                </article>

            </div>

            <article class="info-card history-card">

                <span class="info-label">
                    CERITA KULINER
                </span>

                <h2>
                    Sejarah & Nilai Budaya
                </h2>

                <div class="info-text">

                    <?= nl2br(
                        e(
                            $data["sejarah"] ??
                            "-"
                        )
                    ) ?>

                </div>

            </article>

        </div>

    </section>

    <?php if (!empty($recommended)): ?>

        <section class="recommend-section">

            <div class="detail-container">

                <div class="section-heading">

                    <span>
                        REKOMENDASI
                    </span>

                    <h2>
                        Kuliner Serupa
                    </h2>

                    <p>
                        Rekomendasi dari kategori yang sama.
                    </p>

                </div>

                <div class="recommend-grid">

                    <?php foreach (
                        $recommended as $item
                    ): ?>

                        <?php
                        $recommend_photo =
                            fotoKuliner(
                                $item["foto"] ?? "",
                                true
                            );

                        $recommend_rating =
                            (float) (
                                $item["average_rating"] ??
                                0
                            );
                        ?>

                        <a
                            href="detail.php?id=<?= (int) $item["id_kuliner"] ?>"
                            class="recommend-card"
                        >

                            <div class="recommend-image">

                                <img
                                    src="<?= e(
                                        $recommend_photo
                                    ) ?>"
                                    alt="<?= e(
                                        $item["nama_kuliner"]
                                    ) ?>"
                                 loading="lazy" decoding="async">

                            </div>

                            <div class="recommend-body">

                                <span>

                                    <?= e(
                                        $item["nama_kategori"] ??
                                        "Kuliner"
                                    ) ?>

                                </span>

                                <h3>

                                    <?= e(
                                        $item["nama_kuliner"]
                                    ) ?>

                                </h3>

                                <p>

                                    <i
                                        class="bi bi-geo-alt"
                                    ></i>

                                    <?= e(
                                        $item["asal_daerah"] ??
                                        "-"
                                    ) ?>

                                </p>

                                <div class="recommend-rating">

                                    <i
                                        class="bi bi-star-fill"
                                    ></i>

                                    <?= number_format(
                                        $recommend_rating,
                                        1
                                    ) ?>

                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>

    <section class="comment-section">

        <div class="detail-container">

            <div class="section-heading">

                <span>
                    ULASAN
                </span>

                <h2>
                    Komentar Pengguna
                </h2>

                <p>
                    Bagikan pendapat kamu tentang kuliner ini.
                </p>

            </div>

            <?php if ($is_login): ?>

                <div class="comment-form">

                    <textarea
                        id="commentInput"
                        maxlength="1000"
                        placeholder="Tulis komentar kamu..."
                    ></textarea>

                    <div class="comment-form-footer">

                        <span>
                            Maksimal 1000 karakter
                        </span>

                        <button
                            type="button"
                            id="submitComment"
                        >
                            Kirim Komentar
                        </button>

                    </div>

                </div>

                <div
                    id="commentMessage"
                    class="comment-message"
                ></div>

            <?php else: ?>

                <div class="comment-login">

                    <div class="comment-login-icon">

                        <i class="bi bi-person"></i>

                    </div>

                    <div class="comment-login-content">

                        <strong>
                            Ingin memberikan komentar?
                        </strong>

                        <span>
                            Login terlebih dahulu untuk memberikan ulasan.
                        </span>

                    </div>

                    <a
                        href="login.php"
                    >
                        Login
                    </a>

                </div>

            <?php endif; ?>

            <div
                id="commentList"
                class="comment-list"
            >

                <?php if (!empty($roots)): ?>

                    <?php foreach (
                        $roots as $item
                    ): ?>

                        <?php
                        $root_id =
                            (int) $item["id_komentar"];

                        $replies =
                            $reply_groups[
                                $root_id
                            ] ?? [];
                        ?>

                        <article
                            class="comment-thread"
                            id="comment-<?= $root_id ?>"
                            data-comment-id="<?= $root_id ?>"
                        >

                            <div
                                class="comment-item"
                                data-comment-id="<?= $root_id ?>"
                            >

                                <div class="comment-main">

                                    <?php if (
                                        !empty(
                                            $item["foto_url"]
                                        )
                                    ): ?>

                                        <img
                                            src="<?= e(
                                                $item["foto_url"]
                                            ) ?>"
                                            alt="Foto profil"
                                            class="comment-avatar"
                                         loading="eager" decoding="async">

                                    <?php else: ?>

                                        <div class="comment-avatar comment-avatar-fallback">

                                            <?= e(
                                                strtoupper(
                                                    substr(
                                                        $item["nama_lengkap"] ??
                                                        $item["username"] ??
                                                        "P",
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <div class="comment-body">

                                        <div class="comment-top">

                                            <div class="comment-user">

                                                <strong>

                                                    <?= e(
                                                        $item["nama_lengkap"] ??
                                                        $item["username"] ??
                                                        "Pengguna"
                                                    ) ?>

                                                </strong>

                                                <span class="comment-role">

                                                    <?= strtolower(
                                                        $item["level"] ??
                                                        "user"
                                                    ) ===
                                                    "admin"
                                                        ? "Admin"
                                                        : "Pengguna" ?>

                                                </span>

                                            </div>

                                            <small>

                                                <?= e(
                                                    $item["created_at"]
                                                ) ?>

                                            </small>

                                        </div>

                                        <p class="comment-text">

                                            <?= e(
                                                $item["komentar"]
                                            ) ?>

                                        </p>

                                        <div class="comment-actions">

                                            <button
                                                type="button"
                                                class="comment-like <?= $item["user_like"]
                                                    ? "liked"
                                                    : "" ?>"
                                                data-id="<?= $root_id ?>"
                                            >

                                                <i
                                                    class="bi <?= $item["user_like"]
                                                        ? "bi-heart-fill"
                                                        : "bi-heart" ?>"
                                                ></i>

                                                <span class="like-count">

                                                    <?= $item["total_like"] ?>

                                                </span>

                                            </button>

                                            <?php if ($is_login): ?>

                                                <button
                                                    type="button"
                                                    class="comment-reply"
                                                    data-id="<?= $root_id ?>"
                                                >

                                                    <i
                                                        class="bi bi-reply"
                                                    ></i>

                                                    Balas

                                                </button>

                                                <?php if (
                                                    $id_user ===
                                                    (int) $item["id_user"]
                                                ): ?>

                                                    <button
                                                        type="button"
                                                        class="comment-edit"
                                                        data-id="<?= $root_id ?>"
                                                    >

                                                        <i
                                                            class="bi bi-pencil"
                                                        ></i>

                                                        Edit

                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="comment-delete"
                                                        data-id="<?= $root_id ?>"
                                                    >

                                                        <i
                                                            class="bi bi-trash3"
                                                        ></i>

                                                        Hapus

                                                    </button>

                                                <?php endif; ?>

                                            <?php endif; ?>

                                        </div>

                                        <div class="reply-form-slot"></div>

                                    </div>

                                </div>

                            </div>

                            <?php if (!empty($replies)): ?>

                                <div class="reply-toggle-wrap">

                                    <button
                                        type="button"
                                        class="reply-toggle"
                                        data-target="<?= $root_id ?>"
                                        data-open="0"
                                    >

                                        Lihat
                                        <?= count($replies) ?>
                                        balasan

                                    </button>

                                </div>

                                <div
                                    class="comment-replies"
                                    id="replies-<?= $root_id ?>"
                                >

                                    <?php foreach (
                                        $replies as $reply
                                    ): ?>

                                        <?php
                                        $reply_id =
                                            (int) $reply["id_komentar"];
                                        ?>

                                        <article
                                            class="comment-item reply-item"
                                            id="comment-<?= $reply_id ?>"
                                            data-comment-id="<?= $reply_id ?>"
                                        >

                                            <div class="comment-main">

                                                <?php if (
                                                    !empty(
                                                        $reply["foto_url"]
                                                    )
                                                ): ?>

                                                    <img
                                                        src="<?= e(
                                                            $reply["foto_url"]
                                                        ) ?>"
                                                        alt="Foto profil"
                                                        class="comment-avatar"
                                                     loading="eager" decoding="async">

                                                <?php else: ?>

                                                    <div class="comment-avatar comment-avatar-fallback">

                                                        <?= e(
                                                            strtoupper(
                                                                substr(
                                                                    $reply["nama_lengkap"] ??
                                                                    $reply["username"] ??
                                                                    "P",
                                                                    0,
                                                                    1
                                                                )
                                                            )
                                                        ) ?>

                                                    </div>

                                                <?php endif; ?>

                                                <div class="comment-body">

                                                    <div class="comment-top">

                                                        <div class="comment-user">

                                                            <strong>

                                                                <?= e(
                                                                    $reply["nama_lengkap"] ??
                                                                    $reply["username"] ??
                                                                    "Pengguna"
                                                                ) ?>

                                                            </strong>

                                                            <span class="comment-role">

                                                                <?= strtolower(
                                                                    $reply["level"] ??
                                                                    "user"
                                                                ) ===
                                                                "admin"
                                                                    ? "Admin"
                                                                    : "Pengguna" ?>

                                                            </span>

                                                        </div>

                                                        <small>

                                                            <?= e(
                                                                $reply["created_at"]
                                                            ) ?>

                                                        </small>

                                                    </div>

                                                    <p class="comment-text">

                                                        <?= e(
                                                            $reply["komentar"]
                                                        ) ?>

                                                    </p>

                                                    <div class="comment-actions">

                                                        <button
                                                            type="button"
                                                            class="comment-like <?= $reply["user_like"]
                                                                ? "liked"
                                                                : "" ?>"
                                                            data-id="<?= $reply_id ?>"
                                                        >

                                                            <i
                                                                class="bi <?= $reply["user_like"]
                                                                    ? "bi-heart-fill"
                                                                    : "bi-heart" ?>"
                                                            ></i>

                                                            <span class="like-count">

                                                                <?= $reply["total_like"] ?>

                                                            </span>

                                                        </button>

                                                        <?php if ($is_login): ?>

                                                            <button
                                                                type="button"
                                                                class="comment-reply"
                                                                data-id="<?= $reply_id ?>"
                                                            >

                                                                <i
                                                                    class="bi bi-reply"
                                                                ></i>

                                                                Balas

                                                            </button>

                                                            <?php if (
                                                                $id_user ===
                                                                (int) $reply["id_user"]
                                                            ): ?>

                                                                <button
                                                                    type="button"
                                                                    class="comment-edit"
                                                                    data-id="<?= $reply_id ?>"
                                                                >

                                                                    <i
                                                                        class="bi bi-pencil"
                                                                    ></i>

                                                                    Edit

                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="comment-delete"
                                                                    data-id="<?= $reply_id ?>"
                                                                >

                                                                    <i
                                                                        class="bi bi-trash3"
                                                                    ></i>

                                                                    Hapus

                                                                </button>

                                                            <?php endif; ?>

                                                        <?php endif; ?>

                                                    </div>

                                                    <div class="reply-form-slot"></div>

                                                </div>

                                            </div>

                                        </article>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="comment-empty">

                        <i class="bi bi-chat-left-text"></i>

                        <span>
                            Belum ada komentar.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>

<footer class="public-footer">
        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; 2026 GoKaltara | Muhammad Raul Zia Parsa | All Rights Reserved.
            </p>
        </div>
    </div>
</footer>

<nav class="mobile-public-nav<?= $is_login ? " logged-in" : "" ?>">

    <a
        href="index.php"
        class="mobile-public-link"
    >
        <i class="bi bi-house-fill"></i>
        <span>Beranda</span>
    </a>

    <a
        href="katalog.php"
        class="mobile-public-link active"
    >
        <i class="bi bi-fork-knife"></i>
        <span>Katalog</span>
    </a>

    <a
        href="pencarian.php"
        class="mobile-public-link"
    >
        <i class="bi bi-search"></i>
        <span>Cari</span>
    </a>

    <a
        href="notifikasi.php"
        class="mobile-public-link"
    >
        <i class="bi bi-bell-fill"></i>
        <span>Notif</span>
    </a>

    <?php if ($is_login): ?>

        <a
            href="profil-user.php"
            class="mobile-public-link"
        >
            <i class="bi bi-person-fill"></i>
            <span>Profil</span>
        </a>

    <?php else: ?>

        <a
            href="login.php"
            class="mobile-public-link"
        >
            <i class="bi bi-person-fill"></i>
            <span>Login</span>
        </a>

    <?php endif; ?>


    <?php if ($is_login): ?>

        <a
            href="logout.php"
            class="mobile-public-link mobile-public-logout"
            aria-label="Logout"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    <?php endif; ?>
</nav>

<div
    id="notificationModal"
    class="notification-modal"
>

    <div class="notification-modal-card">

        <div class="notification-modal-icon">

            <i class="bi bi-bell-fill"></i>

        </div>

        <h3>
            Nyalakan Notifikasi?
        </h3>

        <p>
            Dapatkan pemberitahuan ketika ada pengguna yang membalas komentar kamu.
        </p>

        <div class="notification-modal-actions">

            <button
                type="button"
                id="notificationLater"
                class="notification-later"
            >
                Nanti
            </button>

            <button
                type="button"
                id="notificationAllow"
                class="notification-allow"
            >
                Aktifkan
            </button>

        </div>

    </div>

</div>

<script src="assets/js/detail.js?v=50" defer></script>
<?php if ($is_login): ?>
    <script src="assets/js/notifikasi.js?v=60" defer></script>
<?php endif; ?>

    <script src="assets/js/performance.js?v=1" defer></script>

</body>
</html>