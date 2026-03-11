<?php
// api/menu.php — return JSON menu items berdasarkan stand_id
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

$stand_id = isset($_GET['stand_id']) ? (int)$_GET['stand_id'] : 0;

if ($stand_id <= 0) {
    echo json_encode(['error' => 'stand_id tidak valid']);
    exit;
}

// Ambil info stand
$stmt = $conn->prepare("SELECT * FROM stands WHERE id = ?");
$stmt->bind_param('i', $stand_id);
$stmt->execute();
$stand = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stand) {
    echo json_encode(['error' => 'Stand tidak ditemukan']);
    exit;
}

// Cek user_id dari query param (dikirim kalau login)
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Ambil rating user untuk stand ini (kalau login)
$my_stand_rating = 0;
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT rating FROM ratings_stand WHERE user_id = ? AND stand_id = ?");
    $stmt->bind_param('ii', $user_id, $stand_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $my_stand_rating = $row ? (int)$row['rating'] : 0;
    $stmt->close();
}

// Ambil menu items
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE stand_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $stand_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    // Rating user untuk tiap menu item
    $my_menu_rating = 0;
    if ($user_id > 0) {
        $s2 = $conn->prepare("SELECT rating FROM ratings_menu WHERE user_id = ? AND menu_id = ?");
        $s2->bind_param('ii', $user_id, $row['id']);
        $s2->execute();
        $mr = $s2->get_result()->fetch_assoc();
        $my_menu_rating = $mr ? (int)$mr['rating'] : 0;
        $s2->close();
    }
    $items[] = [
        'id'        => $row['id'],
        'nama'      => $row['nama'],
        'harga'     => $row['harga'],
        'foto'      => $row['foto'],
        'rating'    => $row['rating'],
        'my_rating' => $my_menu_rating,
    ];
}
$stmt->close();
$conn->close();

echo json_encode([
    'stand' => [
        'id'       => $stand['id'],
        'nama'     => $stand['nama'],
        'kategori' => $stand['kategori'],
        'foto'     => $stand['foto'],
        'rating'   => $stand['rating'],
    ],
    'my_stand_rating' => $my_stand_rating,
    'items'           => $items,
]);