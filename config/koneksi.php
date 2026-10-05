<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "kuliner_kaltara";

$koneksi = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

$koneksi->set_charset("utf8mb4");