<?php
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "database_kantin_akun";

$conn = mysqli_connect($dbHost, $dbUser, $dbPass);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($conn, $createDbSql)) {
    die("Gagal membuat database: " . mysqli_error($conn));
}

if (!mysqli_select_db($conn, $dbName)) {
    die("Gagal memilih database: " . mysqli_error($conn));
}

mysqli_set_charset($conn, "utf8mb4");

$createTableSql = "CREATE TABLE IF NOT EXISTS akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(30) NOT NULL,
    deskripsi VARCHAR(250) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $createTableSql)) {
    die("Gagal membuat tabel: " . mysqli_error($conn));
}

echo "hello world";
?>