<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';

// Cek login owner
if (!isset($_SESSION['owner_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';

// ── UPDATE SELLER STATUS ──
if ($action === 'update_seller') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['active','rejected','pending'])) {
        echo json_encode(['error' => 'Status tidak valid']); exit;
    }
    $stmt = $conn->prepare("UPDATE sellers SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute(); $stmt->close();
    $msg = $status === 'active' ? 'Seller berhasil di-approve!' : 'Seller berhasil di-reject.';
    echo json_encode(['success' => true, 'message' => $msg]);

// ── DELETE SELLER ──
} elseif ($action === 'delete_seller') {
    $id = (int)($_POST['id'] ?? 0);
    // Hapus stands milik seller (cascade ke menu_items)
    $conn->query("DELETE FROM menu_items WHERE stand_id IN (SELECT id FROM stands WHERE seller_id = $id)");
    $conn->query("DELETE FROM stands WHERE seller_id = $id");
    $stmt = $conn->prepare("DELETE FROM sellers WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Akun seller dihapus.']);

// ── DELETE USER ──
} elseif ($action === 'delete_user') {
    $id = (int)($_POST['id'] ?? 0);
    // Hapus reviews & ratings dulu
    $conn->query("DELETE FROM reviews WHERE user_id = $id");
    $conn->query("DELETE FROM ratings_stand WHERE user_id = $id");
    $conn->query("DELETE FROM ratings_menu WHERE user_id = $id");
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Akun user dihapus.']);

// ── DELETE STAND ──
} elseif ($action === 'delete_stand') {
    $id = (int)($_POST['id'] ?? 0);
    $conn->query("DELETE FROM menu_items WHERE stand_id = $id");
    $stmt = $conn->prepare("DELETE FROM stands WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Stand dihapus.']);

} else {
    echo json_encode(['error' => 'Action tidak valid']);
}
$conn->close();