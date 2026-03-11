<?php
// api/review.php — submit review untuk menu item
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login dulu ya!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$menu_id = (int)($_POST['menu_id'] ?? 0);
$rating  = (int)($_POST['rating']  ?? 0);
$komentar = trim($_POST['komentar'] ?? '');

if ($menu_id <= 0 || $rating < 1 || $rating > 5 || $komentar === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap.']);
    exit;
}

// Cek sudah review belum
$stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND menu_id = ?");
$stmt->bind_param('ii', $user_id, $menu_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update review
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, komentar = ?, created_at = NOW() WHERE user_id = ? AND menu_id = ?");
    $stmt->bind_param('isii', $rating, $komentar, $user_id, $menu_id);
} else {
    // Insert baru
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, menu_id, rating, komentar) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiis', $user_id, $menu_id, $rating, $komentar);
}
$stmt->execute();
$stmt->close();

// Update rata-rata rating menu_items dari reviews
$stmt = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews WHERE menu_id = ?");
$stmt->bind_param('i', $menu_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$new_rating = round($res['avg_r'], 1);
$total      = $res['total'];

$stmt = $conn->prepare("UPDATE menu_items SET rating = ?, total_votes = ? WHERE id = ?");
$stmt->bind_param('dii', $new_rating, $total, $menu_id);
$stmt->execute();
$stmt->close();

// Ambil nama user untuk ditampilkan langsung
$stmt = $conn->prepare("SELECT nama FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$urow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    'success'    => true,
    'new_rating' => $new_rating,
    'total'      => $total,
    'review' => [
        'nama'     => $urow['nama'],
        'rating'   => $rating,
        'komentar' => $komentar,
        'waktu'    => 'Baru saja',
    ]
]);