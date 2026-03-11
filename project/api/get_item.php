<?php
// api/get_item.php — detail menu item + reviews
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../config.php';

$menu_id = (int)($_GET['menu_id'] ?? 0);
$user_id = (int)($_GET['user_id'] ?? 0);

if ($menu_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'menu_id tidak valid']);
    exit;
}

// Ambil data menu item
$stmt = $conn->prepare("SELECT m.*, s.nama as stand_nama, s.kategori FROM menu_items m JOIN stands s ON s.id = m.stand_id WHERE m.id = ?");
$stmt->bind_param('i', $menu_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(['error' => 'Item tidak ditemukan']);
    exit;
}

// Rating user untuk item ini
$my_rating = 0;
$my_komentar = '';
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT rating, komentar FROM reviews WHERE user_id = ? AND menu_id = ?");
    $stmt->bind_param('ii', $user_id, $menu_id);
    $stmt->execute();
    $ur = $stmt->get_result()->fetch_assoc();
    if ($ur) { $my_rating = (int)$ur['rating']; $my_komentar = $ur['komentar']; }
    $stmt->close();
}

// Ambil semua reviews
$stmt = $conn->prepare("
    SELECT r.rating, r.komentar, r.created_at, u.nama
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.menu_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->bind_param('i', $menu_id);
$stmt->execute();
$result  = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = [
        'nama'     => $row['nama'],
        'rating'   => (int)$row['rating'],
        'komentar' => $row['komentar'],
        'waktu'    => date('d M Y', strtotime($row['created_at'])),
    ];
}
$stmt->close();
$conn->close();

echo json_encode([
    'item' => [
        'id'         => $item['id'],
        'nama'       => $item['nama'],
        'harga'      => $item['harga'],
        'foto'       => $item['foto'],
        'rating'     => $item['rating'],
        'total_votes'=> $item['total_votes'],
        'kategori'   => $item['kategori'],
        'stand_nama' => $item['stand_nama'],
    ],
    'my_rating'   => $my_rating,
    'my_komentar' => $my_komentar,
    'reviews'     => $reviews,
]);