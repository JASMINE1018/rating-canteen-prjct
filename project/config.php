<?php
// config.php — sesuaikan dengan setting phpMyAdmin lo
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username MySQL lo
define('DB_PASS', '');           // password MySQL lo
define('DB_NAME', 'kantinproject');  // nama database lo

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi gagal: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');