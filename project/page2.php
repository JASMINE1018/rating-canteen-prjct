<?php
require_once 'config.php';
require_once 'auth.php';

$user = currentUser();

// Ambil semua stand dari DB + jumlah menu items
$result = $conn->query("
    SELECT s.*, COUNT(m.id) as item_count
    FROM stands s
    LEFT JOIN menu_items m ON m.stand_id = s.id
    GROUP BY s.id
    ORDER BY s.kategori, s.id ASC
");
$stands = [];
while ($row = $result->fetch_assoc()) $stands[] = $row;

// Rating user untuk semua stand (kalau login)
$userStandRatings = [];
if (isLoggedIn()) {
    $uid = $user['id'];
    $r = $conn->query("SELECT stand_id, rating FROM ratings_stand WHERE user_id = $uid");
    while ($row = $r->fetch_assoc()) $userStandRatings[$row['stand_id']] = (int)$row['rating'];
}

$conn->close();

// Map foto berdasarkan urutan stand (image1.jpeg dst)
// Sesuaikan kalau nama file beda
$fotoMap = [1=>'image1.jpeg',2=>'image2.jpeg',3=>'image3 .jpeg',4=>'image4.jpeg',5=>'image5.jpeg'];

$emojiMap = ['berat'=>'🍛','ringan'=>'🧆','minuman'=>'🧋','dessert'=>'🧇'];
$labelMap = ['berat'=>'Makanan Berat','ringan'=>'Makanan Ringan','minuman'=>'Minuman','dessert'=>'Dessert'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Kantin — School Cafeteria</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Nunito', sans-serif; background: #f5f5f5; overflow-x: hidden; color: #111; }

/* ===== NAVBAR ===== */
nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; align-items: center; justify-content: space-between; padding: 16px 40px; background: rgba(255,255,255,0.92); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(0,0,0,0.07); }
.nav-logo { display: flex; align-items: center; gap: 10px; font-family: 'Oxanium', sans-serif; font-size: 13px; font-weight: 700; color: #111; text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.2; text-decoration: none; }
.logo-icon { width: 36px; height: 36px; border-radius: 6px; background: #111; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.nav-logo .sub { color: #888; }
.nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
.nav-links a { font-family: 'Oxanium', sans-serif; font-size: 12px; font-weight: 600; text-decoration: none; color: rgba(0,0,0,0.4); letter-spacing: 0.15em; text-transform: uppercase; transition: color 0.2s; }
.nav-links a:hover, .nav-links a.active { color: #111; }
.nav-links a.active { border-bottom: 2px solid #111; padding-bottom: 2px; }
.btn-join { font-family: 'Oxanium', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; padding: 9px 22px; background: #111; color: #fff; border: none; cursor: pointer; text-decoration: none; clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%); transition: opacity 0.2s; }
.btn-join:hover { opacity: 0.7; }
.hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 4px; }
.hamburger span { display: block; width: 24px; height: 2px; background: #111; transition: all 0.3s; }
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
.mobile-menu { display: none; position: fixed; top: 69px; left: 0; right: 0; background: rgba(255,255,255,0.98); border-bottom: 1px solid rgba(0,0,0,0.06); padding: 20px 0; z-index: 99; flex-direction: column; align-items: center; }
.mobile-menu.open { display: flex; }
.mobile-menu a { font-family: 'Oxanium', sans-serif; font-size: 14px; font-weight: 600; color: rgba(0,0,0,0.55); text-decoration: none; letter-spacing: 0.15em; text-transform: uppercase; padding: 14px 0; width: 100%; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.05); transition: color 0.2s; }
.mobile-menu a:hover { color: #111; }
.mobile-menu .btn-join { margin-top: 16px; clip-path: none; border-radius: 4px; }

/* ===== PAGE HEADER ===== */
.page-header { padding: 110px 40px 32px; background: #fff; border-bottom: 1px solid #e5e5e5; }
.page-header-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; }
.page-title-tag { font-family: 'Oxanium', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: #aaa; margin-bottom: 8px; }
.page-title { font-family: 'Oxanium', sans-serif; font-weight: 800; font-size: clamp(1.8rem, 4vw, 2.8rem); color: #111; line-height: 1.1; }
.page-title span { color: transparent; -webkit-text-stroke: 2px #111; }
.page-count { font-family: 'Oxanium', sans-serif; font-size: 12px; color: #aaa; letter-spacing: 0.08em; white-space: nowrap; }
.page-count strong { color: #111; font-size: 22px; display: block; text-align: right; }

/* ===== FILTER BAR ===== */
.gallery-bar { background: #fff; border-bottom: 1px solid #e5e5e5; padding: 16px 40px; position: sticky; top: 69px; z-index: 50; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
.gallery-bar-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.search-wrap { flex: 1; min-width: 200px; display: flex; align-items: center; gap: 10px; border: 1.5px solid #ddd; padding: 10px 16px; background: #fafafa; transition: border-color 0.2s; }
.search-wrap:focus-within { border-color: #111; background: #fff; }
.search-wrap svg { flex-shrink: 0; color: #aaa; }
.search-wrap input { border: none; background: transparent; outline: none; font-family: 'Nunito', sans-serif; font-size: 14px; color: #333; width: 100%; }
.search-wrap input::placeholder { color: #bbb; }
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.ftab { font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; padding: 8px 18px; cursor: pointer; border: 1.5px solid #ddd; background: #fff; color: #888; transition: all 0.15s; }
.ftab:hover { border-color: #111; color: #111; }
.ftab.active { background: #111; color: #fff; border-color: #111; }

/* ===== CARD GRID ===== */
.gallery-inner { max-width: 1200px; margin: 36px auto 80px; padding: 0 40px; }
.card-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.gcard { background: #fff; border: 1px solid #e8e8e8; overflow: hidden; cursor: pointer; transition: transform 0.22s, box-shadow 0.22s; animation: fadeUp 0.35s ease both; }
.gcard:hover { transform: translateY(-5px); box-shadow: 0 14px 36px rgba(0,0,0,0.1); }
.gcard.hidden { display: none; }
.gcard-img { position: relative; aspect-ratio: 4/3; overflow: hidden; background: #1a1a1a; display: flex; align-items: center; justify-content: center; }
.gcard-photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity 0.3s; }
.gcard-photo.loaded { opacity: 1; }
.gcard-placeholder { font-size: 38px; opacity: 0.2; color: #fff; z-index: 0; user-select: none; }
.gcard-tag { position: absolute; bottom: 12px; left: 12px; z-index: 2; font-family: 'Oxanium', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; background: #fff; color: #111; padding: 4px 10px; }
.gcard-body { padding: 16px 18px 20px; }
.gcard-title { font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 14px; color: #111; margin-bottom: 10px; line-height: 1.35; }
.gcard-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.gcard-stars { font-size: 12px; color: #111; display: flex; align-items: center; gap: 2px; }
.gcard-count { font-family: 'Oxanium', sans-serif; font-size: 11px; color: #bbb; letter-spacing: 0.05em; }
.gcard-link { font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #111; text-decoration: none; border-bottom: 1.5px solid #111; padding-bottom: 2px; display: inline-block; transition: opacity 0.2s; }
.gcard-link:hover { opacity: 0.45; }
.empty-state { text-align: center; padding: 80px 0; }
.empty-icon { font-size: 48px; margin-bottom: 14px; opacity: 0.25; }
.empty-text { font-family: 'Oxanium', sans-serif; font-size: 13px; color: #bbb; letter-spacing: 0.12em; text-transform: uppercase; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

/* ===== MODAL ===== */
.modal-overlay { display: none; position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.open { display: flex; }
.modal-box { background: #fff; width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; position: relative; animation: modalIn 0.28s ease both; border: 1px solid #e0e0e0; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(16px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.modal-hero { position: relative; aspect-ratio: 16/7; overflow: hidden; background: #1a1a1a; display: flex; align-items: flex-end; }
.modal-close { position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.55); color: #fff; border: none; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; z-index: 20; transition: background 0.2s; }
.modal-close:hover { background: #111; }
.modal-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.modal-hero-placeholder { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 56px; opacity: 0.15; color: #fff; }
.modal-hero::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 55%); z-index: 1; }
.modal-hero-info { position: relative; z-index: 2; padding: 20px 24px; }
.modal-tag { font-family: 'Oxanium', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; background: #111; color: #fff; padding: 4px 10px; display: inline-block; margin-bottom: 8px; }
.modal-title { font-family: 'Oxanium', sans-serif; font-weight: 800; font-size: clamp(1.3rem, 4vw, 1.8rem); color: #fff; line-height: 1.1; margin-bottom: 6px; }
.modal-stars { font-size: 13px; color: #fff; opacity: 0.9; display: flex; align-items: center; gap: 2px; }
.modal-body { padding: 24px; }
.modal-section-label { font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: #111; border-left: 3px solid #111; padding-left: 10px; margin-bottom: 16px; }
.modal-items { display: flex; flex-direction: column; gap: 12px; }
.menu-item { display: flex; align-items: center; gap: 14px; padding: 14px; border: 1px solid #e8e8e8; transition: border-color 0.18s, box-shadow 0.18s; }
.menu-item:hover { border-color: #111; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.menu-item-img { width: 64px; height: 64px; flex-shrink: 0; background: #1a1a1a; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 26px; }
.menu-item-img img { width: 100%; height: 100%; object-fit: cover; }
.menu-item-info { flex: 1; }
.menu-item-name { font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 14px; color: #111; margin-bottom: 4px; }
.menu-item-right { text-align: right; flex-shrink: 0; }
.menu-item-price { font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 14px; color: #111; margin-bottom: 8px; display: block; }
.btn-order { font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 8px 14px; background: #111; color: #fff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s; white-space: nowrap; }
.btn-order:hover { opacity: 0.7; }
.modal-loading { text-align: center; padding: 40px; font-family: 'Oxanium', sans-serif; font-size: 12px; color: #aaa; letter-spacing: 0.1em; }

/* ===== STAR RATING ===== */
.star { font-size: 14px; cursor: default; color: #ddd; transition: color 0.15s; user-select: none; }
.star.filled { color: #111; }
.menu-star { font-size: 16px; color: #ddd; transition: color 0.15s; user-select: none; }
.menu-star.filled { color: #111; }
<?php if (isLoggedIn()): ?>
.star[onclick], .menu-star[onclick] { cursor: pointer; }
.gcard-stars:hover .star[onclick] { color: #111; }
.gcard-stars .star[onclick]:hover ~ .star[onclick] { color: #ddd; }
<?php else: ?>
.star { cursor: not-allowed; }
<?php endif; ?>
.rating-num { font-size: 11px; color: #bbb; margin-left: 3px; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) { .card-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
  nav { padding: 14px 20px; }
  .nav-links, .btn-join.desktop { display: none; }
  .hamburger { display: flex; }
  .gallery-bar { padding: 14px 20px; top: 68px; }
  .gallery-bar-inner { flex-direction: column; align-items: stretch; gap: 12px; }
  .search-wrap { min-width: 100%; }
  .page-header { padding: 85px 20px 18px; }
  .page-header-inner { flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-title { font-size: clamp(1.2rem, 3vw, 2rem); }
  .page-count { width: 100%; }
  .gallery-inner { padding: 0 20px; margin-top: 20px; }
  .card-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
  .gcard { animation: none; }
  .gcard-title { font-size: 13px; }
  .gcard-meta { gap: 8px; flex-wrap: wrap; }
  .filter-tabs { gap: 6px; flex-wrap: wrap; }
  .ftab { padding: 6px 12px; font-size: 10px; border: 1px solid #ddd; }
  .modal-hero { aspect-ratio: 16/9; }
  .modal-title { font-size: clamp(1rem, 3vw, 1.5rem); }
  .modal-body { padding: 16px; }
  .modal-section-label { font-size: 10px; padding-left: 8px; }
  .modal-box { max-width: 95vw; }
  .menu-item { gap: 10px; padding: 10px; }
  .menu-item-img { width: 52px; height: 52px; font-size: 20px; }
  .menu-item-name { font-size: 13px; }
  .btn-order { padding: 7px 12px; font-size: 10px; }
}
@media (max-width: 480px) {
  nav { padding: 12px 14px; }
  .nav-logo { font-size: 11px; gap: 6px; }
  .logo-icon { width: 28px; height: 28px; font-size: 14px; }
  .hamburger { gap: 4px; padding: 2px; }
  .hamburger span { width: 20px; height: 1.5px; }
  .page-header { padding: 75px 14px 14px; }
  .page-title { font-size: 1.3rem; }
  .page-count strong { font-size: 18px; }
  .gallery-bar { padding: 12px 14px; top: 60px; }
  .gallery-bar-inner { gap: 10px; }
  .search-wrap { padding: 8px 12px; font-size: 13px; }
  .ftab { padding: 5px 10px; font-size: 9px; }
  .gallery-inner { padding: 0 14px; margin-top: 16px; margin-bottom: 60px; }
  .card-grid { grid-template-columns: 1fr; gap: 12px; }
  .gcard-img { aspect-ratio: 3/2; }
  .gcard-tag { bottom: 8px; left: 8px; font-size: 9px; padding: 3px 8px; }
  .gcard-body { padding: 12px 14px 14px; }
  .gcard-title { font-size: 12px; margin-bottom: 8px; }
  .gcard-meta { font-size: 11px; gap: 6px; }
  .gcard-stars { gap: 1px; }
  .star { font-size: 12px; }
  .rating-num { font-size: 10px; margin-left: 2px; }
  .gcard-count { font-size: 10px; }
  .gcard-link { font-size: 10px; }
  .empty-icon { font-size: 36px; }
  .empty-text { font-size: 11px; }
  .modal-overlay { padding: 12px; }
  .modal-box { border: none; border-radius: 8px 8px 0 0; max-height: 88vh; }
  .modal-hero { aspect-ratio: 2/1; }
  .modal-close { width: 32px; height: 32px; font-size: 18px; }
  .modal-hero-info { padding: 16px; }
  .modal-tag { font-size: 8px; padding: 3px 8px; }
  .modal-title { font-size: 1.3rem; }
  .modal-stars { font-size: 12px; }
  .modal-body { padding: 14px; }
  .modal-section-label { font-size: 9px; margin-bottom: 12px; }
  .modal-items { gap: 10px; }
  .menu-item { flex-direction: column; gap: 8px; padding: 10px; }
  .menu-item-img { width: 100%; height: 120px; margin-right: 0; }
  .menu-item-info { flex: 1; }
  .menu-item-right { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; gap: 8px; }
  .menu-item-price { margin-bottom: 0; font-size: 13px; }
  .btn-order { padding: 6px 10px; font-size: 9px; width: auto; }
  .menu-star { font-size: 14px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <a href="index.php" class="nav-logo">
    <div class="logo-icon">🍽️</div>
    <div><div>School</div><div class="sub">Cafeteria</div></div>
  </a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="page2.php" class="active">Menu</a></li>
    <li><a href="#">My Tray</a></li>
  </ul>
  <?php if (isLoggedIn()): ?>
    <div style="display:flex;align-items:center;gap:12px;">
      <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#888;letter-spacing:0.08em;">
        Hi, <strong style="color:#111"><?= htmlspecialchars($user['nama']) ?></strong>
      </span>
      <a href="api/logout.php" class="btn-join desktop" style="background:#555;">Logout</a>
    </div>
  <?php else: ?>
    <a href="login.php" class="btn-join desktop">Login</a>
  <?php endif; ?>
  <button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <a href="index.php">Home</a>
  <a href="page2.php">Menu</a>
  <a href="#">My Tray</a>
  <?php if (isLoggedIn()): ?>
    <a href="api/logout.php" class="btn-join">Logout</a>
  <?php else: ?>
    <a href="login.php" class="btn-join">Login</a>
  <?php endif; ?>
</div>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-inner">
    <div>
      <div class="page-title-tag">✦ Menu Kantin</div>
      <h1 class="page-title">Pilihan <span>Makanan</span></h1>
    </div>
    <div class="page-count">
      <strong id="visibleCount"><?= count($stands) ?></strong>
      stand tersedia
    </div>
  </div>
</div>

<!-- FILTER BAR -->
<div class="gallery-bar">
  <div class="gallery-bar-inner">
    <div class="search-wrap">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input type="text" id="searchInput" placeholder="Cari makanan atau stand..."/>
    </div>
    <div class="filter-tabs">
      <button class="ftab active" data-cat="all">Semua</button>
      <button class="ftab" data-cat="berat">Makanan Berat</button>
      <button class="ftab" data-cat="ringan">Makanan Ringan</button>
      <button class="ftab" data-cat="minuman">Minuman</button>
      <button class="ftab" data-cat="dessert">Dessert</button>
    </div>
  </div>
</div>

<!-- CARD GRID (dari DB) -->
<div class="gallery-inner">
  <div class="card-grid" id="cardGrid">
    <?php if (empty($stands)): ?>
      <p style="grid-column:1/-1;text-align:center;color:#aaa;font-family:'Oxanium',sans-serif;padding:60px 0;">
        Belum ada stand. Tambahkan data lewat phpMyAdmin!
      </p>
    <?php else: ?>
      <?php foreach ($stands as $i => $stand):
        $emoji  = $emojiMap[$stand['kategori']] ?? '🍽️';
        $label  = $labelMap[$stand['kategori']] ?? $stand['kategori'];
        $foto   = $stand['foto'] ?: ($fotoMap[$i+1] ?? '');
        $myRating = $userStandRatings[$stand['id']] ?? 0;
      ?>
      <div class="gcard"
        data-cat="<?= htmlspecialchars($stand['kategori']) ?>"
        data-title="<?= htmlspecialchars(strtolower($stand['nama'])) ?>"
        data-id="<?= $stand['id'] ?>"
        style="animation-delay:<?= $i * 0.05 ?>s">
        <div class="gcard-img" style="background:#1a1a1a;">
          <?php if ($foto): ?>
            <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($stand['nama']) ?>" class="gcard-photo"/>
          <?php endif; ?>
          <div class="gcard-placeholder"><?= $emoji ?></div>
          <span class="gcard-tag"><?= $label ?></span>
        </div>
        <div class="gcard-body">
          <div class="gcard-title"><?= htmlspecialchars($stand['nama']) ?></div>
          <div class="gcard-meta">
            <div class="gcard-stars" id="stand-stars-<?= $stand['id'] ?>">
              <?php for ($s = 1; $s <= 5; $s++):
                $filled = $s <= ($myRating ?: round($stand['rating'])) ? 'filled' : '';
              ?>
              <span class="star <?= $filled ?>"
                data-val="<?= $s ?>"
                data-type="stand"
                data-id="<?= $stand['id'] ?>"
                <?= isLoggedIn() ? 'onclick="submitRating(this)"' : 'title="Login dulu untuk rating"' ?>>★</span>
              <?php endfor; ?>
              <span class="rating-num" id="stand-num-<?= $stand['id'] ?>">(<?= $stand['rating'] ?>)</span>
            </div>
            <div class="gcard-count"><?= $stand['item_count'] ?> Items</div>
          </div>
          <a href="#" class="gcard-link">View Menu →</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="empty-state" id="emptyState" style="display:none;">
    <div class="empty-icon">🔍</div>
    <div class="empty-text">Tidak ada hasil ditemukan</div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box" id="modalBox">
    <div class="modal-hero">
      <button class="modal-close" id="modalClose">✕</button>
      <img src="image1.jpg" alt="" class="modal-hero-img" id="modalHeroImg" style="display:none"/>
      <div class="modal-hero-placeholder" id="modalHeroPlaceholder"></div>
      <div class="modal-hero-info">
        <span class="modal-tag" id="modalTag"></span>
        <h2 class="modal-title" id="modalTitle"></h2>
        <div class="modal-stars" id="modalStars"></div>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-section-label">Menu Items</div>
      <div class="modal-items" id="modalItems"></div>
    </div>
  </div>
</div>

<!-- ITEM DETAIL MODAL -->
<div class="modal-overlay" id="itemOverlay">
  <div class="modal-box item-modal-box" id="itemModalBox">
    <!-- Kiri: Foto -->
    <div class="item-modal-left" id="itemModalLeft">
      <button class="modal-close" id="itemModalClose">✕</button>
      <img src="" alt="" id="itemModalImg" style="width:100%;height:100%;object-fit:cover;display:none;"/>
      <div class="item-modal-img-placeholder" id="itemModalPlaceholder"></div>
    </div>
    <!-- Kanan: Detail -->
    <div class="item-modal-right" id="itemModalRight">
      <div class="item-detail-scroll">
        <div class="item-tag" id="itemTag"></div>
        <h2 class="item-name" id="itemName"></h2>
        <div class="item-price-row">
          <span class="item-price" id="itemPrice"></span>
          <div class="item-avg-stars" id="itemAvgStars"></div>
        </div>
        <button class="btn-add-tray" id="btnAddTray">🛒 Add to Tray</button>

        <div class="review-section">
          <div class="review-header">
            <span class="review-title">Reviews &amp; Feedback</span>
            <span class="review-count" id="reviewCount">0 reviews</span>
          </div>

          <?php if (isLoggedIn()): ?>
          <!-- Form Review -->
          <div class="review-form" id="reviewForm">
            <div class="rf-label">Your Rating</div>
            <div class="rf-stars" id="rfStars">
              <?php for ($s=1;$s<=5;$s++): ?>
              <span class="rf-star" data-val="<?=$s?>" onclick="setReviewStar(this)">★</span>
              <?php endfor; ?>
            </div>
            <textarea id="rfKomentar" class="rf-textarea" placeholder="Share your experience..."></textarea>
            <button class="rf-submit" onclick="submitReview()">Post Review ✉️</button>
            <div class="rf-msg" id="rfMsg"></div>
          </div>
          <?php else: ?>
          <div class="review-login-prompt">
            <a href="login.php">Login</a> dulu untuk menulis review.
          </div>
          <?php endif; ?>

          <!-- Daftar Review -->
          <div class="review-list" id="reviewList"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== ITEM MODAL ===== */
.item-modal-box {
  max-width: 860px !important;
  display: flex !important;
  flex-direction: row;
  max-height: 90vh;
  overflow: hidden;
}
.item-modal-left {
  position: relative;
  width: 46%;
  flex-shrink: 0;
  background: #1a1a1a;
  min-height: 420px;
  display: flex; align-items: center; justify-content: center;
}
.item-modal-img-placeholder { font-size: 64px; opacity: 0.2; color: #fff; }
.item-modal-right { flex: 1; overflow-y: auto; }
.item-detail-scroll { padding: 28px 24px; }

.item-tag { font-family: 'Oxanium', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; background: #111; color: #fff; padding: 4px 10px; display: inline-block; margin-bottom: 10px; }
.item-name { font-family: 'Oxanium', sans-serif; font-weight: 800; font-size: 1.4rem; color: #111; margin-bottom: 12px; line-height: 1.2; }
.item-price-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.item-price { font-family: 'Oxanium', sans-serif; font-weight: 800; font-size: 1.3rem; color: #111; }
.item-avg-stars { display: flex; align-items: center; gap: 2px; font-size: 15px; }
.item-avg-stars span.s { color: #111; }
.item-avg-stars span.e { color: #ddd; }
.item-avg-stars .avg-num { font-size: 12px; color: #888; margin-left: 4px; }

.btn-add-tray { width: 100%; padding: 13px; background: #111; color: #fff; font-family: 'Oxanium', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.2s; margin-bottom: 24px; }
.btn-add-tray:hover { opacity: 0.75; }

/* Review section */
.review-section { border-top: 1px solid #eee; padding-top: 20px; }
.review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.review-title { font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #111; }
.review-count { font-size: 12px; color: #aaa; font-family: 'Oxanium', sans-serif; }

/* Form */
.review-form { background: #f9f9f9; border: 1px solid #eee; padding: 16px; margin-bottom: 20px; }
.rf-label { font-family: 'Oxanium', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #555; margin-bottom: 8px; }
.rf-stars { display: flex; gap: 4px; margin-bottom: 12px; }
.rf-star { font-size: 22px; color: #ddd; cursor: pointer; transition: color 0.15s; user-select: none; }
.rf-star.active { color: #111; }
.rf-textarea { width: 100%; border: 1.5px solid #ddd; padding: 10px 12px; font-family: 'Nunito', sans-serif; font-size: 13px; color: #333; background: #fff; outline: none; resize: vertical; min-height: 80px; transition: border-color 0.2s; }
.rf-textarea:focus { border-color: #111; }
.rf-submit { margin-top: 10px; padding: 10px 20px; background: #111; color: #fff; font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; border: none; cursor: pointer; transition: opacity 0.2s; }
.rf-submit:hover { opacity: 0.75; }
.rf-msg { font-size: 12px; margin-top: 8px; color: #555; font-family: 'Oxanium', sans-serif; }
.rf-msg.ok { color: #1a7a3a; }
.rf-msg.err { color: #cc0000; }
.review-login-prompt { font-size: 13px; color: #888; margin-bottom: 16px; }
.review-login-prompt a { color: #111; font-weight: 700; }

/* Review items */
.review-item { padding: 14px 0; border-bottom: 1px solid #f0f0f0; }
.review-item:last-child { border-bottom: none; }
.ri-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.ri-name { font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 13px; color: #111; }
.ri-date { font-size: 11px; color: #bbb; font-family: 'Oxanium', sans-serif; }
.ri-stars { font-size: 13px; color: #111; margin-bottom: 6px; }
.ri-stars span { color: #ddd; }
.ri-komentar { font-size: 13px; color: #555; line-height: 1.5; }
.review-empty { text-align: center; padding: 24px 0; font-family: 'Oxanium', sans-serif; font-size: 12px; color: #ccc; letter-spacing: 0.1em; }

@media (max-width: 640px) {
  .item-modal-box { flex-direction: column; }
  .item-modal-left { width: 100%; min-height: 220px; }
}
</style>

<script>
const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const USER_ID = <?= isLoggedIn() ? $user['id'] : 0 ?>;
const emojiMap = { berat:'🍛', ringan:'🧆', minuman:'🧋', dessert:'🧇' };
const labelMap = { berat:'Makanan Berat', ringan:'Makanan Ringan', minuman:'Minuman', dessert:'Dessert' };

// Hamburger
const hamburger  = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
hamburger.addEventListener('click', () => { hamburger.classList.toggle('open'); mobileMenu.classList.toggle('open'); });
mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { hamburger.classList.remove('open'); mobileMenu.classList.remove('open'); }));

// Lazy load foto
document.querySelectorAll('.gcard-photo').forEach(img => {
  if (img.complete && img.naturalWidth) img.classList.add('loaded');
  img.addEventListener('load', () => img.classList.add('loaded'));
});

// Filter + Search
const ftabs = document.querySelectorAll('.ftab');
const cards = document.querySelectorAll('.gcard');
const emptyState = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const visibleCount = document.getElementById('visibleCount');
function filterCards() {
  const cat = document.querySelector('.ftab.active').dataset.cat;
  const query = searchInput.value.toLowerCase();
  let visible = 0;
  cards.forEach(card => {
    const ok = (cat === 'all' || card.dataset.cat === cat) && card.dataset.title.includes(query);
    card.classList.toggle('hidden', !ok);
    if (ok) visible++;
  });
  visibleCount.textContent = visible;
  emptyState.style.display = visible === 0 ? 'block' : 'none';
}
ftabs.forEach(tab => { tab.addEventListener('click', () => { ftabs.forEach(t => t.classList.remove('active')); tab.classList.add('active'); filterCards(); }); });
searchInput.addEventListener('input', filterCards);

// ===== STAND RATING =====
function submitRating(starEl) {
  if (!IS_LOGGED_IN) { window.location = 'login.php'; return; }
  const val = parseInt(starEl.dataset.val), type = starEl.dataset.type, id = starEl.dataset.id;
  const fd = new FormData();
  fd.append('type', type); fd.append('id', id); fd.append('rating', val);
  fetch('api/rate.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
    if (data.error) { alert(data.error); return; }
    if (type === 'stand') {
      document.getElementById(`stand-stars-${id}`).querySelectorAll('.star').forEach(s => s.classList.toggle('filled', parseInt(s.dataset.val) <= data.your_rating));
      document.getElementById(`stand-num-${id}`).textContent = `(${data.new_rating})`;
      if (currentOpenId == id) document.getElementById('modalStars').innerHTML = buildStarsHtml(data.new_rating, 'stand', id, data.your_rating, true);
    }
  }).catch(() => alert('Gagal kirim rating.'));
}

function buildStarsHtml(avg, type, id, myR, white) {
  let html = '';
  for (let i = 1; i <= 5; i++) {
    const f = i <= (myR || Math.round(avg));
    const cls = white ? 'menu-star' : 'star';
    const col = white ? (f ? '#fff' : 'rgba(255,255,255,0.3)') : (f ? '#111' : '#ddd');
    const click = IS_LOGGED_IN ? `onclick="submitRating(this)"` : `title="Login dulu"`;
    html += `<span class="${cls}${f?' filled':''}" data-val="${i}" data-type="${type}" data-id="${id}" ${click} style="color:${col}">★</span>`;
  }
  const ns = white ? 'opacity:.7;margin-left:4px;font-size:13px;color:#fff' : 'font-size:11px;color:#bbb;margin-left:3px';
  html += `<span id="${type==='menu'?'menu':'modal'}-num-${id}" style="${ns}">(${avg})</span>`;
  return html;
}

// ===== STAND MODAL =====
let currentOpenId = null;
const modalOverlay = document.getElementById('modalOverlay');
const modalClose   = document.getElementById('modalClose');
const modalTitle   = document.getElementById('modalTitle');
const modalTag     = document.getElementById('modalTag');
const modalStars   = document.getElementById('modalStars');
const modalItems   = document.getElementById('modalItems');
const modalHeroImg = document.getElementById('modalHeroImg');
const modalHeroPlaceholder = document.getElementById('modalHeroPlaceholder');

function openModal(standId) {
  currentOpenId = standId;
  modalTitle.textContent = 'Memuat...'; modalTag.textContent = ''; modalStars.innerHTML = '';
  modalHeroImg.style.display = 'none'; modalHeroPlaceholder.textContent = '⏳';
  modalItems.innerHTML = '<div class="modal-loading">Memuat menu...</div>';
  modalOverlay.classList.add('open'); document.body.style.overflow = 'hidden';

  fetch(`api/menu.php?stand_id=${standId}&user_id=${USER_ID}`)
    .then(r => r.json()).then(data => {
      if (data.error) { modalItems.innerHTML = `<div class="modal-loading">${data.error}</div>`; return; }
      const stand = data.stand, emoji = emojiMap[stand.kategori] || '🍽️';
      const cardEl = document.querySelector(`.gcard[data-id="${standId}"]`);
      const cp = cardEl ? cardEl.querySelector('.gcard-photo') : null;
      if (cp && cp.complete && cp.naturalWidth) { modalHeroImg.src = cp.src; modalHeroImg.style.display = 'block'; modalHeroPlaceholder.textContent = ''; }
      else if (stand.foto) { modalHeroImg.src = stand.foto; modalHeroImg.style.display = 'block'; modalHeroPlaceholder.textContent = ''; }
      else { modalHeroImg.style.display = 'none'; modalHeroPlaceholder.textContent = emoji; }
      modalTag.textContent = labelMap[stand.kategori] || stand.kategori;
      modalTitle.textContent = stand.nama;
      modalStars.innerHTML = buildStarsHtml(stand.rating, 'stand', stand.id, data.my_stand_rating || 0, true);
      if (data.items.length === 0) { modalItems.innerHTML = '<div class="modal-loading">Belum ada menu item.</div>'; return; }
      modalItems.innerHTML = data.items.map(item => `
        <div class="menu-item" onclick="openItemModal(${item.id}, '${item.nama.replace(/'/g,"\\'")}', ${item.harga}, '${stand.kategori}', '${item.foto||''}')">
          <div class="menu-item-img">${item.foto ? `<img src="${item.foto}" alt="${item.nama}"/>` : emoji}</div>
          <div class="menu-item-info">
            <div class="menu-item-name">${item.nama}</div>
            <div style="display:flex;align-items:center;gap:2px;margin-top:4px;font-size:12px;">
              ${[1,2,3,4,5].map(i=>`<span style="color:${i<=Math.round(item.rating)?'#111':'#ddd'}">★</span>`).join('')}
              <span style="font-size:11px;color:#bbb;margin-left:2px;">(${item.rating})</span>
            </div>
          </div>
          <div class="menu-item-right">
            <span class="menu-item-price">Rp ${Number(item.harga).toLocaleString('id-ID')}</span>
            <button class="btn-order" onclick="event.stopPropagation();openItemModal(${item.id},'${item.nama.replace(/'/g,"\\'")}',${item.harga},'${stand.kategori}','${item.foto||''}')">Detail →</button>
          </div>
        </div>
      `).join('');
    }).catch(() => { modalItems.innerHTML = '<div class="modal-loading">Gagal memuat data.</div>'; });
}
function closeModal() { modalOverlay.classList.remove('open'); document.body.style.overflow = ''; currentOpenId = null; }
document.querySelectorAll('.gcard').forEach(card => { card.addEventListener('click', e => { if (e.target.closest('.gcard-link')) e.preventDefault(); openModal(card.dataset.id); }); });
modalClose.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });

// ===== ITEM DETAIL MODAL =====
let currentItemId = null;
let reviewStarVal = 0;
const itemOverlay    = document.getElementById('itemOverlay');
const itemModalClose = document.getElementById('itemModalClose');

function openItemModal(menuId, nama, harga, kategori, foto) {
  currentItemId = menuId;
  reviewStarVal = 0;

  // Set basic info dulu
  document.getElementById('itemTag').textContent = labelMap[kategori] || kategori;
  document.getElementById('itemName').textContent = nama;
  document.getElementById('itemPrice').textContent = 'Rp ' + Number(harga).toLocaleString('id-ID');
  document.getElementById('itemAvgStars').innerHTML = '<span style="color:#aaa;font-size:13px;">Memuat...</span>';
  document.getElementById('reviewCount').textContent = '0 reviews';
  document.getElementById('reviewList').innerHTML = '<div class="review-empty">Memuat reviews...</div>';

  // Set foto
  const imgEl = document.getElementById('itemModalImg');
  const phEl  = document.getElementById('itemModalPlaceholder');
  if (foto) { imgEl.src = foto; imgEl.style.display = 'block'; phEl.style.display = 'none'; }
  else { imgEl.style.display = 'none'; phEl.style.display = 'flex'; phEl.textContent = emojiMap[kategori] || '🍽️'; }

  // Reset form
  if (IS_LOGGED_IN) {
    document.querySelectorAll('.rf-star').forEach(s => s.classList.remove('active'));
    document.getElementById('rfKomentar').value = '';
    document.getElementById('rfMsg').textContent = '';
    document.getElementById('rfMsg').className = 'rf-msg';
  }

  itemOverlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  // Fetch detail + reviews
  fetch(`api/get_item.php?menu_id=${menuId}&user_id=${USER_ID}`)
    .then(r => r.json()).then(data => {
      if (data.error) return;
      const item = data.item;

      // Rating bintang rata-rata
      const avgStars = [1,2,3,4,5].map(i =>
        `<span class="${i <= Math.round(item.rating) ? 's' : 'e'}">★</span>`
      ).join('') + `<span class="avg-num">(${item.rating}) · ${item.total_votes} votes</span>`;
      document.getElementById('itemAvgStars').innerHTML = avgStars;
      document.getElementById('reviewCount').textContent = `${data.reviews.length} reviews`;

      // Isi rating user yg sudah ada
      if (IS_LOGGED_IN && data.my_rating > 0) {
        reviewStarVal = data.my_rating;
        document.querySelectorAll('.rf-star').forEach(s => {
          s.classList.toggle('active', parseInt(s.dataset.val) <= data.my_rating);
        });
        if (data.my_komentar) document.getElementById('rfKomentar').value = data.my_komentar;
      }

      // Render reviews
      if (data.reviews.length === 0) {
        document.getElementById('reviewList').innerHTML = '<div class="review-empty">No reviews yet. Be the first to review!</div>';
      } else {
        document.getElementById('reviewList').innerHTML = data.reviews.map(rv => `
          <div class="review-item">
            <div class="ri-top">
              <span class="ri-name">${rv.nama}</span>
              <span class="ri-date">${rv.waktu}</span>
            </div>
           <div class="ri-stars">${[1,2,3,4,5].map(i=>`<span style="color:${i<=Number(rv.rating)?'#111':'#ddd'}">★</span>`).join('')}</div>
            <div class="ri-komentar">${rv.komentar}</div>
          </div>
        `).join('');
      }
    }).catch(() => {});
}

function closeItemModal() {
  itemOverlay.classList.remove('open');
  document.body.style.overflow = 'hidden';
  // Refresh stand modal supaya rating item ikut update
  if (currentOpenId) openModal(currentOpenId);
  currentItemId = null;
}
itemModalClose.addEventListener('click', closeItemModal);
itemOverlay.addEventListener('click', e => { if (e.target === itemOverlay) closeItemModal(); });

// Review star select
function setReviewStar(el) {
  reviewStarVal = parseInt(el.dataset.val);
  document.querySelectorAll('.rf-star').forEach(s => s.classList.toggle('active', parseInt(s.dataset.val) <= reviewStarVal));
}

// Submit review
function submitReview() {
  if (!IS_LOGGED_IN) { window.location = 'login.php'; return; }
  const komentar = document.getElementById('rfKomentar').value.trim();
  const msgEl    = document.getElementById('rfMsg');
  if (reviewStarVal === 0) { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Pilih rating dulu!'; return; }
  if (!komentar) { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Tulis komentar dulu!'; return; }
  msgEl.className = 'rf-msg'; msgEl.textContent = 'Mengirim...';
  const fd = new FormData();
  fd.append('menu_id', currentItemId); fd.append('rating', reviewStarVal); fd.append('komentar', komentar);
  fetch('api/review.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
    if (data.error) { msgEl.className = 'rf-msg err'; msgEl.textContent = data.error; return; }
    msgEl.className = 'rf-msg ok'; msgEl.textContent = '✓ Review terkirim!';
    // Update rating display
    const avgStars = [1,2,3,4,5].map(i => `<span class="${i<=Math.round(data.new_rating)?'s':'e'}">★</span>`).join('')
      + `<span class="avg-num">(${data.new_rating}) · ${data.total} votes</span>`;
    document.getElementById('itemAvgStars').innerHTML = avgStars;
    document.getElementById('reviewCount').textContent = `${data.total} reviews`;
    // Prepend review baru
    const rv = data.review;
    const newItem = `<div class="review-item">
      <div class="ri-top"><span class="ri-name">${rv.nama}</span><span class="ri-date">${rv.waktu}</span></div>
      <div class="ri-stars">${[1,2,3,4,5].map(i=>`<span style="color:${i<=Number(rv.rating)?'#111':'#ddd'}">★</span>`).join('')}</div>
      <div class="ri-komentar">${rv.komentar}</div>
    </div>`;
    const rl = document.getElementById('reviewList');
    if (rl.querySelector('.review-empty')) rl.innerHTML = newItem;
    else rl.insertAdjacentHTML('afterbegin', newItem);
  }).catch(() => { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Gagal mengirim.'; });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeItemModal(); closeModal(); } });
</script>
</body>
</html>