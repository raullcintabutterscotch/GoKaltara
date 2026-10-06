<?php

declare(strict_types=1);

require_once "api/_auth.php";

function loginRedirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {
    loginRedirect("login.php");
}

$username = trim(
    (string) (
        $_POST["username"] ?? ""
    )
);

$password = (string) (
    $_POST["password"] ?? ""
);

if (
    $username === "" ||
    $password === ""
) {
    loginRedirect(
        "login.php?error=1"
    );
}

$stmt = $koneksi->prepare(
    "SELECT
        id_user,
        username,
        password,
        nama_lengkap,
        foto_profil,
        level
    FROM `user`
    WHERE username = ?
    LIMIT 1"
);

if (!$stmt) {
    loginRedirect(
        "login.php?error=db"
    );
}

$stmt->bind_param(
    "s",
    $username
);

$stmt->execute();

$user =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

$valid = false;

if ($user) {

    $stored =
        (string) $user["password"];

    if (
        password_verify(
            $password,
            $stored
        )
    ) {

        $valid = true;

    } elseif (
        hash_equals(
            md5($password),
            $stored
        )
    ) {

        $valid = true;

        $new_hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        $upgrade = $koneksi->prepare(
            "UPDATE `user`
             SET password = ?
             WHERE id_user = ?"
        );

        if ($upgrade) {

            $id_user =
                (int) $user["id_user"];

            $upgrade->bind_param(
                "si",
                $new_hash,
                $id_user
            );

            $upgrade->execute();
            $upgrade->close();
        }
    }
}

if (!$valid) {
    loginRedirect(
        "login.php?error=1"
    );
}

session_regenerate_id(true);

gokaltaraSetSession(
    $user
);

issueRememberToken(
    $koneksi,
    (int) $user["id_user"]
);

if (
    $user["level"] === "admin"
) {
    loginRedirect(
        "admin/dashboard.php"
    );
}

loginRedirect("index.php");