<?php
// auth.php — include di semua halaman
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'nama' => $_SESSION['user_nama'] ?? null,
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}