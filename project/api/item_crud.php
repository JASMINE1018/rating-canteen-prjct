<?php
header('Content-Type: application/json');
require_once '../seller_auth.php';
require_once '../config.php';
requireSeller();

$seller_id = $_SESSION['seller_id'];
$action    = $_POST['action'] ?? '';

// Helper: cek apakah stand milik seller ini
function isMyStand($conn, $stand_id, $seller_id) {
    $stmt = $conn->prepare("SELECT id FROM stands WHERE id=? AND seller_id=?");
    $stmt->bind_param('ii', $stand_id, $seller_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$r;
}

if ($action === 'add') {
    $stand_id = (int)($_POST['stand_id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $harga    = (int)($_POST['harga'] ?? 0);
    $foto     = trim($_POST['foto'] ?? '') ?: null;
    if (!$nama || !$harga || !$stand_id) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
    if (!isMyStand($conn, $stand_id, $seller_id)) { echo json_encode(['error'=>'Stand tidak valid']); exit; }
    $stmt = $conn->prepare("INSERT INTO menu_items (stand_id, nama, harga, foto) VALUES (?,?,?,?)");
    $stmt->bind_param('isis', $stand_id, $nama, $harga, $foto);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Menu item berhasil ditambahkan!']);

} elseif ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $stand_id = (int)($_POST['stand_id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $harga    = (int)($_POST['harga'] ?? 0);
    $foto     = trim($_POST['foto'] ?? '') ?: null;
    if (!$id || !$nama || !$harga) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
    if (!isMyStand($conn, $stand_id, $seller_id)) { echo json_encode(['error'=>'Stand tidak valid']); exit; }
    $stmt = $conn->prepare("UPDATE menu_items SET nama=?, harga=?, foto=?, stand_id=? WHERE id=?");
    $stmt->bind_param('siisi', $nama, $harga, $foto, $stand_id, $id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Menu item berhasil diupdate!']);

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Cek item milik stand yang punya seller ini
    $stmt = $conn->prepare("SELECT m.id FROM menu_items m JOIN stands s ON s.id=m.stand_id WHERE m.id=? AND s.seller_id=?");
    $stmt->bind_param('ii', $id, $seller_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) { echo json_encode(['error'=>'Item tidak valid']); exit; }
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id=?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true]);

} else {
    echo json_encode(['error'=>'Action tidak valid']);
}
$conn->close();