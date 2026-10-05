<?php

$host = getenv("DB_HOST");
$username = getenv("DB_USERNAME");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_DATABASE");
$port = getenv("DB_PORT") ?: "3306";

$koneksi = new mysqli(
    $host,
    $username,
    $password,
    $database,
    (int) $port
);

if ($koneksi->connect_error) {
    die(
        "Koneksi database gagal: " .
        $koneksi->connect_error
    );
}

$koneksi->set_charset("utf8mb4");