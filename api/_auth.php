<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {

    $is_https =
        (
            (!empty($_SERVER["HTTPS"]) &&
                $_SERVER["HTTPS"] !== "off")
            ||
            strtolower(
                (string) (
                    $_SERVER["HTTP_X_FORWARDED_PROTO"]
                    ?? ""
                )
            ) === "https"
        );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $is_https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    ini_set(
        "session.use_strict_mode",
        "1"
    );

    ini_set(
        "session.use_only_cookies",
        "1"
    );

    session_start();
}

require_once dirname(__DIR__) . "/config/koneksi.php";

function jsonResponse(
    bool $success,
    string $message,
    array $extra = []
): void {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function gokaltaraIsSecure(): bool
{
    $https =
        (string) (
            $_SERVER["HTTPS"] ?? ""
        );

    $forwarded =
        strtolower(
            (string) (
                $_SERVER["HTTP_X_FORWARDED_PROTO"]
                ?? ""
            )
        );

    return (
        (
            $https !== "" &&
            $https !== "off"
        )
        ||
        $forwarded === "https"
    );
}

function gokaltaraCookieOptions(
    int $expires
): array {

    return [
        "expires" => $expires,
        "path" => "/",
        "secure" => gokaltaraIsSecure(),
        "httponly" => true,
        "samesite" => "Lax"
    ];
}

function gokaltaraSetSession(
    array $user
): void {

    $_SESSION["login"] = true;

    $_SESSION["id_user"] =
        (int) (
            $user["id_user"] ?? 0
        );

    $_SESSION["username"] =
        (string) (
            $user["username"] ?? ""
        );

    $_SESSION["nama_lengkap"] =
        (string) (
            $user["nama_lengkap"] ?? ""
        );

    $_SESSION["foto_profil"] =
        (string) (
            $user["foto_profil"] ?? ""
        );

    $_SESSION["level"] =
        (string) (
            $user["level"] ?? "user"
        );
}

function gokaltaraLoginState(): bool
{
    return (
        isset($_SESSION["login"])
        &&
        (
            $_SESSION["login"] === true
            ||
            $_SESSION["login"] === 1
            ||
            $_SESSION["login"] === "1"
        )
    );
}

function currentUserId(
    mysqli $koneksi
): int {

    $id_user =
        (int) (
            $_SESSION["id_user"] ?? 0
        );

    if ($id_user > 0) {
        return $id_user;
    }

    $username =
        trim(
            (string) (
                $_SESSION["username"] ?? ""
            )
        );

    if ($username === "") {
        return 0;
    }

    $stmt = $koneksi->prepare(
        "SELECT id_user
         FROM `user`
         WHERE username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $data =
        $result
            ? $result->fetch_assoc()
            : null;

    $stmt->close();

    $id_user =
        (int) (
            $data["id_user"] ?? 0
        );

    if ($id_user > 0) {
        $_SESSION["id_user"] =
            $id_user;
    }

    return $id_user;
}

function issueRememberToken(
    mysqli $koneksi,
    int $id_user
): bool {

    if ($id_user <= 0) {
        return false;
    }

    $cleanup =
        $koneksi->query(
            "DELETE FROM remember_tokens
             WHERE expires_at <= NOW()"
        );

    $selector =
        bin2hex(
            random_bytes(16)
        );

    $token =
        bin2hex(
            random_bytes(32)
        );

    $token_hash =
        hash(
            "sha256",
            $token
        );

    $expires_at =
        date(
            "Y-m-d H:i:s",
            time() + (
                60 * 60 * 24 * 30
            )
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
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NOW()
        )"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "isss",
        $id_user,
        $selector,
        $token_hash,
        $expires_at
    );

    $success =
        $stmt->execute();

    $stmt->close();

    if (!$success) {
        return false;
    }

    $cookie_value =
        $selector .
        "." .
        $token;

    setcookie(
        "gokaltara_remember",
        $cookie_value,
        gokaltaraCookieOptions(
            time() + (
                60 * 60 * 24 * 30
            )
        )
    );

    return true;
}

function restoreRememberedLogin(
    mysqli $koneksi
): bool {

    if (gokaltaraLoginState()) {
        return true;
    }

    $cookie =
        trim(
            (string) (
                $_COOKIE["gokaltara_remember"]
                ?? ""
            )
        );

    if ($cookie === "") {
        return false;
    }

    $parts =
        explode(
            ".",
            $cookie,
            2
        );

    if (
        count($parts) !== 2
    ) {
        clearRememberToken(
            $koneksi
        );

        return false;
    }

    $selector =
        trim($parts[0]);

    $token =
        trim($parts[1]);

    if (
        $selector === ""
        ||
        $token === ""
    ) {
        clearRememberToken(
            $koneksi
        );

        return false;
    }

    $stmt = $koneksi->prepare(
        "SELECT
            rt.id_token,
            rt.id_user,
            rt.token_hash,
            rt.expires_at,
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
        return false;
    }

    $stmt->bind_param(
        "s",
        $selector
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $user =
        $result
            ? $result->fetch_assoc()
            : null;

    $stmt->close();

    if (!$user) {

        clearRememberToken(
            $koneksi
        );

        return false;
    }

    $incoming_hash =
        hash(
            "sha256",
            $token
        );

    if (
        !hash_equals(
            (string) $user["token_hash"],
            $incoming_hash
        )
    ) {

        clearRememberToken(
            $koneksi
        );

        return false;
    }

    session_regenerate_id(true);

    gokaltaraSetSession(
        $user
    );

    $new_expiry =
        date(
            "Y-m-d H:i:s",
            time() + (
                60 * 60 * 24 * 30
            )
        );

    $update =
        $koneksi->prepare(
            "UPDATE remember_tokens
             SET expires_at = ?,
                 last_used_at = NOW()
             WHERE id_token = ?
             AND id_user = ?"
        );

    if ($update) {

        $id_token =
            (int) (
                $user["id_token"] ?? 0
            );

        $id_user =
            (int) (
                $user["id_user"] ?? 0
            );

        $update->bind_param(
            "sii",
            $new_expiry,
            $id_token,
            $id_user
        );

        $update->execute();

        $update->close();
    }

    setcookie(
        "gokaltara_remember",
        $selector . "." . $token,
        gokaltaraCookieOptions(
            time() + (
                60 * 60 * 24 * 30
            )
        )
    );

    return true;
}

function clearRememberToken(
    mysqli $koneksi
): void {

    $cookie =
        trim(
            (string) (
                $_COOKIE["gokaltara_remember"]
                ?? ""
            )
        );

    if ($cookie !== "") {

        $parts =
            explode(
                ".",
                $cookie,
                2
            );

        if (
            count($parts) === 2
        ) {

            $selector =
                trim($parts[0]);

            $token =
                trim($parts[1]);

            $token_hash =
                hash(
                    "sha256",
                    $token
                );

            if (
                $selector !== ""
                &&
                $token !== ""
            ) {

                $stmt =
                    $koneksi->prepare(
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
    }

    setcookie(
        "gokaltara_remember",
        "",
        gokaltaraCookieOptions(
            time() - 3600
        )
    );

    unset(
        $_COOKIE["gokaltara_remember"]
    );
}

function requireLogin(
    mysqli $koneksi
): int {

    if (!gokaltaraLoginState()) {

        restoreRememberedLogin(
            $koneksi
        );
    }

    if (!gokaltaraLoginState()) {

        jsonResponse(
            false,
            "Silakan login terlebih dahulu."
        );
    }

    $id_user =
        currentUserId(
            $koneksi
        );

    if ($id_user <= 0) {

        $_SESSION = [];

        jsonResponse(
            false,
            "Data akun tidak ditemukan."
        );
    }

    return $id_user;
}

function requireAdmin(
    mysqli $koneksi
): int {

    $id_user =
        requireLogin(
            $koneksi
        );

    $level =
        (string) (
            $_SESSION["level"] ?? ""
        );

    if ($level !== "admin") {

        jsonResponse(
            false,
            "Akses admin diperlukan."
        );
    }

    return $id_user;
}

function requirePageLogin(
    mysqli $koneksi
): int {

    if (!gokaltaraLoginState()) {

        restoreRememberedLogin(
            $koneksi
        );
    }

    if (!gokaltaraLoginState()) {

        $return =
            $_SERVER["REQUEST_URI"]
            ?? "/";

        $return =
            urlencode(
                $return
            );

        header(
            "Location: /login.php?return=" .
            $return
        );

        exit;
    }

    $id_user =
        currentUserId(
            $koneksi
        );

    if ($id_user <= 0) {

        $_SESSION = [];

        header(
            "Location: /login.php"
        );

        exit;
    }

    return $id_user;
}

function requireAdminPage(
    mysqli $koneksi
): int {

    $id_user =
        requirePageLogin(
            $koneksi
        );

    if (
        (string) (
            $_SESSION["level"] ?? ""
        )
        !== "admin"
    ) {

        header(
            "Location: /index.php"
        );

        exit;
    }

    return $id_user;
}

function validKuliner(
    mysqli $koneksi,
    int $id_kuliner
): bool {

    if ($id_kuliner <= 0) {
        return false;
    }

    $stmt =
        $koneksi->prepare(
            "SELECT id_kuliner
             FROM kuliner
             WHERE id_kuliner = ?
             LIMIT 1"
        );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "i",
        $id_kuliner
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $exists =
        $result &&
        $result->num_rows > 0;

    $stmt->close();

    return $exists;
}

restoreRememberedLogin(
    $koneksi
);