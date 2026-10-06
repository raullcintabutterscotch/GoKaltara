<?php

declare(strict_types=1);

$host = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USERNAME") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_DATABASE") ?: "kuliner_kaltara";
$port = (int) (getenv("DB_PORT") ?: 3306);

$koneksi = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($koneksi->connect_error) {
    die(
        "Koneksi database gagal: " .
        $koneksi->connect_error
    );
}

$koneksi->set_charset("utf8mb4");

function gokaltara_is_https(): bool
{
    $https = $_SERVER["HTTPS"] ?? "";

    $forwarded = strtolower(
        (string) (
            $_SERVER["HTTP_X_FORWARDED_PROTO"] ?? ""
        )
    );

    return (
        ($https !== "" && $https !== "off") ||
        $forwarded === "https"
    );
}

function gokaltara_cookie_options(
    int $expires
): array {
    return [
        "expires" => $expires,
        "path" => "/",
        "secure" => gokaltara_is_https(),
        "httponly" => true,
        "samesite" => "Lax"
    ];
}

function gokaltara_apply_session(
    array $user
): void {
    $_SESSION["login"] = true;

    $_SESSION["id_user"] = (int) (
        $user["id_user"] ?? 0
    );

    $_SESSION["username"] =
        $user["username"] ?? "";

    $_SESSION["nama_lengkap"] =
        $user["nama_lengkap"] ?? "";

    $_SESSION["foto_profil"] =
        $user["foto_profil"] ?? "";

    $_SESSION["level"] =
        $user["level"] ?? "user";

    $_SESSION["admin_name"] =
        $user["nama_lengkap"] ?? "";
}

function gokaltara_issue_remember_token(
    mysqli $koneksi,
    int $id_user
): void {
    if ($id_user <= 0) {
        return;
    }

    $selector = bin2hex(
        random_bytes(16)
    );

    $token = bin2hex(
        random_bytes(32)
    );

    $token_hash = hash(
        "sha256",
        $token
    );

    $expires_at = date(
        "Y-m-d H:i:s",
        time() + (60 * 60 * 24 * 30)
    );

    $cleanup = $koneksi->query(
        "DELETE FROM remember_tokens
         WHERE expires_at <= NOW()"
    );

    $stmt = $koneksi->prepare(
        "INSERT INTO remember_tokens
        (
            id_user,
            selector,
            token_hash,
            expires_at,
            last_used_at
        )
        VALUES (?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "isss",
        $id_user,
        $selector,
        $token_hash,
        $expires_at
    );

    $stmt->execute();

    $stmt->close();

    setcookie(
        "gokaltara_remember",
        $selector . "." . $token,
        gokaltara_cookie_options(
            time() + (60 * 60 * 24 * 30)
        )
    );

    $_COOKIE["gokaltara_remember"] =
        $selector . "." . $token;
}

function gokaltara_restore_remembered_login(
    mysqli $koneksi
): void {
    if (
        session_status() !==
        PHP_SESSION_ACTIVE
    ) {
        session_start();
    }

    if (
        ($_SESSION["login"] ?? false) === true
    ) {
        return;
    }

    $cookie =
        $_COOKIE["gokaltara_remember"] ?? "";

    if ($cookie === "") {
        return;
    }

    $parts = explode(
        ".",
        $cookie,
        2
    );

    if (
        count($parts) !== 2 ||
        $parts[0] === "" ||
        $parts[1] === ""
    ) {
        return;
    }

    $selector = $parts[0];
    $token = $parts[1];

    $stmt = $koneksi->prepare(
        "SELECT
            rt.id_token,
            rt.id_user,
            rt.token_hash,
            u.username,
            u.nama_lengkap,
            u.foto_profil,
            u.level
        FROM remember_tokens AS rt
        INNER JOIN `user` AS u
            ON u.id_user = rt.id_user
        WHERE rt.selector = ?
        AND rt.expires_at > NOW()
        LIMIT 1"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "s",
        $selector
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $user =
        $result->fetch_assoc();

    $stmt->close();

    if (!$user) {
        return;
    }

    $incoming_hash = hash(
        "sha256",
        $token
    );

    if (
        !hash_equals(
            (string) $user["token_hash"],
            $incoming_hash
        )
    ) {
        return;
    }

    session_regenerate_id(true);

    gokaltara_apply_session(
        $user
    );

    $new_expiry = date(
        "Y-m-d H:i:s",
        time() + (60 * 60 * 24 * 30)
    );

    $update = $koneksi->prepare(
        "UPDATE remember_tokens
         SET expires_at = ?,
             last_used_at = NOW()
         WHERE id_token = ?"
    );

    if ($update) {
        $id_token =
            (int) $user["id_token"];

        $update->bind_param(
            "si",
            $new_expiry,
            $id_token
        );

        $update->execute();
        $update->close();
    }

    setcookie(
        "gokaltara_remember",
        $selector . "." . $token,
        gokaltara_cookie_options(
            time() + (60 * 60 * 24 * 30)
        )
    );
}

function gokaltara_clear_remember_token(
    mysqli $koneksi
): void {
    $cookie =
        $_COOKIE["gokaltara_remember"] ?? "";

    if ($cookie !== "") {

        $parts = explode(
            ".",
            $cookie,
            2
        );

        if (
            count($parts) === 2
        ) {
            $selector = $parts[0];
            $token = $parts[1];

            $token_hash = hash(
                "sha256",
                $token
            );

            $stmt = $koneksi->prepare(
                "DELETE FROM remember_tokens
                 WHERE selector = ?
                 AND token_hash = ?"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "ss",
                    $selector,
                    $token_hash
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }

    setcookie(
        "gokaltara_remember",
        "",
        gokaltara_cookie_options(
            time() - 3600
        )
    );

    unset(
        $_COOKIE["gokaltara_remember"]
    );
}

gokaltara_restore_remembered_login(
    $koneksi
);