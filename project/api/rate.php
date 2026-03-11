<?php
// api/rate.php — submit rating (POST)
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../seller_auth.php';
require_once '../config.php';

// Harus login sebagai user regular
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login dulu ya!']);
    exit;
}

// Seller tidak bisa rating
if (isSellerLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Seller tidak bisa memberi rating.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type    = $_POST['type']   ?? '';   // 'stand' atau 'menu'
$target  = (int)($_POST['id'] ?? 0); // stand_id atau menu_id
$rating  = (int)($_POST['rating'] ?? 0);

if (!in_array($type, ['stand', 'menu']) || $target <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak valid.']);
    exit;
}

if ($type === 'stand') {
    $table_vote   = 'ratings_stand';
    $table_target = 'stands';
    $col_target   = 'stand_id';
} else {
    $table_vote   = 'ratings_menu';
    $table_target = 'menu_items';
    $col_target   = 'menu_id';
}

// Insert atau update vote (ON DUPLICATE KEY UPDATE)
$stmt = $conn->prepare(
    "INSERT INTO {$table_vote} (user_id, {$col_target}, rating)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating)"
);
$stmt->bind_param('iii', $user_id, $target, $rating);
$stmt->execute();
$stmt->close();

// Hitung ulang rata-rata & total votes lalu update ke tabel target
$stmt = $conn->prepare(
    "SELECT AVG(rating) as avg_rating, COUNT(*) as total
     FROM {$table_vote} WHERE {$col_target} = ?"
);
$stmt->bind_param('i', $target);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$new_rating = round($res['avg_rating'], 1);
$new_total  = $res['total'];

$stmt = $conn->prepare(
    "UPDATE {$table_target} SET rating = ?, total_votes = ? WHERE id = ?"
);
$stmt->bind_param('dii', $new_rating, $new_total, $target);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode([
    'success'     => true,
    'new_rating'  => $new_rating,
    'total_votes' => $new_total,
    'your_rating' => $rating,
]);