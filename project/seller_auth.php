<?php
// seller_auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isSellerLoggedIn() {
    return isset($_SESSION['seller_id']);
}

function currentSeller() {
    return [
        'id'   => $_SESSION['seller_id']   ?? null,
        'nama' => $_SESSION['seller_nama'] ?? null,
    ];
}

function requireSeller() {
    if (!isSellerLoggedIn()) {
        header('Location: seller_login.php');
        exit;
    }
}