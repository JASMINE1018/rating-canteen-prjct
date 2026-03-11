<?php
require_once 'seller_auth.php';
require_once 'config.php';
requireSeller();
$seller = currentSeller();
$sid = $seller['id'];

// ── Ambil stand milik seller ini ──
$stands_result = $conn->query("SELECT * FROM stands WHERE seller_id = $sid ORDER BY id ASC");
$stands = [];
while ($row = $stands_result->fetch_assoc()) $stands[] = $row;
$stand_ids = array_column($stands, 'id');

// ── Stats global seller ──
$total_stands = count($stands);
$total_menu   = 0;
$total_orders = 0;
$total_reviews = 0;
$avg_rating   = 0;

if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    $r = $conn->query("SELECT COUNT(*) as c, SUM(total_orders) as o FROM menu_items WHERE stand_id IN ($ids_str)");
    $row = $r->fetch_assoc(); $total_menu = $row['c']; $total_orders = $row['o'] ?? 0;
    $r = $conn->query("SELECT COUNT(*) as c FROM reviews r JOIN menu_items m ON m.id = r.menu_id WHERE m.stand_id IN ($ids_str)");
    $total_reviews = $r->fetch_assoc()['c'];
    $r = $conn->query("SELECT AVG(rating) as avg FROM stands WHERE seller_id = $sid AND rating > 0");
    $avg_rating = round($r->fetch_assoc()['avg'] ?? 0, 1);
}

// ── Reviews terbaru ──
$recent_reviews = [];
if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    $r = $conn->query("
        SELECT rv.rating, rv.komentar, rv.created_at, u.nama as user_nama,
               mi.nama as item_nama, s.nama as stand_nama
        FROM reviews rv
        JOIN users u ON u.id = rv.user_id
        JOIN menu_items mi ON mi.id = rv.menu_id
        JOIN stands s ON s.id = mi.stand_id
        WHERE s.id IN ($ids_str)
        ORDER BY rv.created_at DESC LIMIT 5
    ");
    while ($row = $r->fetch_assoc()) $recent_reviews[] = $row;
}

// ── Menu items per stand ──
$menu_by_stand = [];
if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    $r = $conn->query("SELECT * FROM menu_items WHERE stand_id IN ($ids_str) ORDER BY stand_id, id ASC");
    while ($row = $r->fetch_assoc()) $menu_by_stand[$row['stand_id']][] = $row;
}

$conn->close();

$kategori_opts = ['berat'=>'Makanan Berat','ringan'=>'Makanan Ringan','minuman'=>'Minuman','dessert'=>'Dessert'];
$emoji_map = ['berat'=>'🍛','ringan'=>'🧆','minuman'=>'🧋','dessert'=>'🧇'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seller Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Nunito',sans-serif;background:#0f0f0f;color:#eee;min-height:100vh;display:flex;}

/* ── SIDEBAR ── */
.sidebar{width:220px;background:#111;border-right:1px solid #1e1e1e;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #1e1e1e;}
.sidebar-logo .brand{font-family:'Oxanium',sans-serif;font-weight:800;font-size:13px;color:#fff;text-transform:uppercase;letter-spacing:0.08em;}
.sidebar-logo .sub{font-size:11px;color:#444;margin-top:2px;font-family:'Oxanium',sans-serif;}
.sidebar-seller{padding:16px 20px;border-bottom:1px solid #1e1e1e;}
.seller-avatar{width:36px;height:36px;background:#222;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;}
.seller-name{font-family:'Oxanium',sans-serif;font-size:12px;font-weight:700;color:#fff;}
.seller-role{font-size:11px;color:#444;font-family:'Oxanium',sans-serif;text-transform:uppercase;letter-spacing:0.1em;}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 20px;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#555;cursor:pointer;transition:all 0.15s;text-decoration:none;border-left:2px solid transparent;}
.nav-item:hover{color:#fff;background:#1a1a1a;}
.nav-item.active{color:#fff;background:#1a1a1a;border-left-color:#fff;}
.nav-icon{font-size:14px;width:18px;text-align:center;}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1e1e1e;}
.btn-logout{display:block;width:100%;padding:10px;background:#1a1a1a;color:#666;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #222;cursor:pointer;text-align:center;text-decoration:none;transition:all 0.2s;}
.btn-logout:hover{background:#222;color:#fff;}

/* ── MAIN ── */
.main{margin-left:220px;flex:1;min-height:100vh;}
.topbar{background:#111;border-bottom:1px solid #1e1e1e;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;}
.page-title{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;letter-spacing:0.05em;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-right a{font-family:'Oxanium',sans-serif;font-size:11px;color:#555;text-decoration:none;letter-spacing:0.08em;text-transform:uppercase;transition:color 0.2s;}
.topbar-right a:hover{color:#fff;}
.content{padding:28px 32px;}

/* ── SECTIONS ── */
.section{display:none;}
.section.active{display:block;}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:#111;border:1px solid #1e1e1e;padding:20px;transition:border-color 0.2s;}
.stat-card:hover{border-color:#333;}
.stat-label{font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#444;margin-bottom:10px;}
.stat-value{font-family:'Oxanium',sans-serif;font-weight:800;font-size:2rem;color:#fff;line-height:1;}
.stat-sub{font-size:12px;color:#444;margin-top:6px;}
.stat-icon{font-size:22px;margin-bottom:8px;}

/* ── SECTION HEADER ── */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.section-title{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1rem;color:#fff;letter-spacing:0.05em;}
.btn-primary{padding:9px 20px;background:#fff;color:#111;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:none;cursor:pointer;transition:opacity 0.2s;}
.btn-primary:hover{opacity:0.8;}
.btn-sm{padding:6px 14px;font-size:10px;letter-spacing:0.08em;}
.btn-danger{background:#1a0000;color:#ff6666;border:1px solid #330000;}
.btn-danger:hover{background:#2a0000;opacity:1;}
.btn-edit{background:#1a1a2a;color:#8888ff;border:1px solid #222244;}
.btn-edit:hover{background:#222233;opacity:1;}

/* ── STAND CARDS ── */
.stand-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;}
.stand-card{background:#111;border:1px solid #1e1e1e;overflow:hidden;}
.stand-card-top{padding:18px 20px;border-bottom:1px solid #1e1e1e;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.stand-info{}
.stand-name{font-family:'Oxanium',sans-serif;font-weight:700;font-size:14px;color:#fff;margin-bottom:4px;}
.stand-cat{font-family:'Oxanium',sans-serif;font-size:10px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#555;background:#1a1a1a;padding:3px 8px;display:inline-block;}
.stand-rating{display:flex;align-items:center;gap:4px;margin-top:8px;font-size:13px;}
.stand-actions{display:flex;gap:8px;flex-shrink:0;}
.stand-card-body{padding:16px 20px;}
.stand-stats{display:flex;gap:20px;}
.ss-item{text-align:center;}
.ss-val{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.2rem;color:#fff;}
.ss-lbl{font-family:'Oxanium',sans-serif;font-size:9px;color:#444;letter-spacing:0.1em;text-transform:uppercase;margin-top:2px;}

/* ── MENU TABLE ── */
.stand-menu-section{margin-bottom:28px;}
.smenu-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#111;border:1px solid #1e1e1e;border-bottom:none;cursor:pointer;}
.smenu-title{font-family:'Oxanium',sans-serif;font-weight:700;font-size:12px;color:#fff;letter-spacing:0.08em;text-transform:uppercase;}
.smenu-toggle{font-size:11px;color:#444;font-family:'Oxanium',sans-serif;}
.menu-table{width:100%;border-collapse:collapse;background:#111;border:1px solid #1e1e1e;}
.menu-table th{font-family:'Oxanium',sans-serif;font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#444;padding:10px 14px;border-bottom:1px solid #1e1e1e;text-align:left;}
.menu-table td{padding:12px 14px;border-bottom:1px solid #141414;font-size:13px;color:#ccc;vertical-align:middle;}
.menu-table tr:last-child td{border-bottom:none;}
.menu-table tr:hover td{background:#151515;}
.item-emoji{font-size:20px;margin-right:8px;}
.item-name-cell{font-family:'Oxanium',sans-serif;font-weight:600;font-size:13px;color:#fff;}
.price-cell{font-family:'Oxanium',sans-serif;font-weight:700;color:#fff;}
.rating-cell{font-size:12px;}
.actions-cell{display:flex;gap:8px;}

/* ── REVIEWS ── */
.review-card{background:#111;border:1px solid #1e1e1e;padding:16px 20px;margin-bottom:12px;}
.rc-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;}
.rc-user{font-family:'Oxanium',sans-serif;font-weight:700;font-size:13px;color:#fff;}
.rc-date{font-size:11px;color:#444;font-family:'Oxanium',sans-serif;}
.rc-item{font-size:12px;color:#555;font-family:'Oxanium',sans-serif;letter-spacing:0.05em;margin-bottom:6px;}
.rc-stars{font-size:13px;margin-bottom:6px;}
.rc-comment{font-size:13px;color:#aaa;line-height:1.5;}

/* ── FORMS (MODAL) ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:200;align-items:center;justify-content:center;padding:20px;}
.modal-bg.open{display:flex;}
.modal-form{background:#111;border:1px solid #222;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;padding:28px;}
.mf-title{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1rem;color:#fff;margin-bottom:24px;letter-spacing:0.05em;}
.form-field{margin-bottom:16px;}
.form-field label{font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#555;display:block;margin-bottom:7px;}
.form-field input,.form-field select,.form-field textarea{width:100%;padding:10px 14px;border:1.5px solid #222;font-family:'Nunito',sans-serif;font-size:14px;color:#fff;background:#1a1a1a;outline:none;transition:border-color 0.2s;}
.form-field input:focus,.form-field select:focus,.form-field textarea:focus{border-color:#fff;}
.form-field select option{background:#1a1a1a;}
.form-actions{display:flex;gap:10px;margin-top:20px;}
.btn-cancel{padding:10px 20px;background:transparent;color:#555;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #222;cursor:pointer;transition:all 0.2s;}
.btn-cancel:hover{color:#fff;border-color:#555;}
.form-msg{font-size:12px;margin-top:10px;font-family:'Oxanium',sans-serif;padding:8px 12px;}
.form-msg.ok{background:#002a0e;color:#66ff99;border:1px solid #005520;}
.form-msg.err{background:#2a0000;color:#ff6666;border:1px solid #550000;}

/* ── EMPTY STATE ── */
.empty-dash{text-align:center;padding:60px 20px;color:#333;}
.empty-dash .ei{font-size:40px;margin-bottom:12px;opacity:0.3;}
.empty-dash p{font-family:'Oxanium',sans-serif;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;}

/* MOBILE MENU TOGGLE */
.hamburger-btn{display:none;flex-direction:column;gap:5px;cursor:pointer;background:none;border:none;padding:8px;color:#fff;position:absolute;left:12px;top:14px;z-index:51;}
.hamburger-btn span{display:block;width:24px;height:2px;background:#fff;transition:all 0.3s;}
.hamburger-btn.open span:nth-child(1){transform:translateY(7px) rotate(45deg);}
.hamburger-btn.open span:nth-child(2){opacity:0;}
.hamburger-btn.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}

/* ── RESPONSIVE ── */
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}.stand-grid{grid-template-columns:1fr}}
@media(max-width:768px){.hamburger-btn{display:flex}.sidebar{position:fixed;left:0;top:0;height:100vh;z-index:49;border-right:1px solid #1e1e1e;width:220px;transform:translateX(-100%);transition:transform 0.3s}.sidebar.open{transform:translateX(0)}.sidebar::after{content:'';position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:-1;display:none}.sidebar.open::after{display:block;z-index:-1;left:220px;right:0}.main{margin-left:0}.topbar{padding:14px 16px;position:relative}.page-title{font-size:0.95rem;margin-left:40px}.topbar-right a{font-size:10px}.content{padding:16px}.stats-grid{gap:12px}.stat-value{font-size:1.4rem}.stat-label{font-size:8px}.stat-icon{font-size:16px}.stand-grid{gap:12px}.stand-card{overflow:hidden}.stand-card-top{padding:14px 16px;gap:8px}.stand-name{font-size:13px}.stand-rating{font-size:12px}.stand-actions{gap:6px}.btn-primary,.btn-edit{padding:6px 12px;font-size:9px}.section-title{font-size:0.95rem}.menu-table{font-size:12px}.menu-table th{padding:9px 10px;font-size:8px}.menu-table td{padding:10px}.menu-item-name{font-size:13px}.review-card{padding:12px 16px;margin-bottom:10px}.rc-user{font-size:12px}.rc-item{font-size:11px}.rc-comment{font-size:12px}.mf-title{font-size:0.95rem}.section-header{flex-direction:column;align-items:flex-start;gap:12px}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr;gap:10px}.stat-value{font-size:1.2rem}.stat-card{padding:14px}.stand-grid{gap:10px}.stand-card-top{flex-direction:column;align-items:flex-start}.stand-stats{gap:14px;flex-wrap:wrap}.ss-val{font-size:1rem}.menu-table{display:block;overflow-x:auto;border:none;margin-bottom:16px}.menu-table thead{display:none}.menu-table tbody{display:block}.menu-table tr{display:block;border:1px solid #1e1e1e;margin-bottom:10px;padding:12px}.menu-table td{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border:none}.menu-table td:before{content:attr(data-label);font-family:'Oxanium',sans-serif;font-weight:700;color:#555;font-size:8px;text-transform:uppercase}.price-cell,.actions-cell{flex-wrap:wrap;gap:6px}.topbar{padding:12px}.page-title{font-size:0.85rem;margin-left:38px}.content{padding:12px}.review-card{padding:10px 14px}.rc-top{flex-direction:column;gap:6px}.section-title{font-size:0.9rem}.smenu-header{padding:12px;font-size:10px}.btn-primary,.btn-edit{padding:7px 10px;font-size:8px;width:100%}.mf-title{font-size:0.85rem}.modal-form{max-width:90vw;padding:16px}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">🍽️ Cafeteria</div>
    <div class="sub">Seller Dashboard</div>
  </div>
  <div class="sidebar-seller">
    <div class="seller-avatar">👤</div>
    <div class="seller-name"><?= htmlspecialchars($seller['nama']) ?></div>
    <div class="seller-role">Seller</div>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-item active" data-section="overview" onclick="showSection('overview')">
      <span class="nav-icon">📊</span> Overview
    </a>
    <a class="nav-item" data-section="stands" onclick="showSection('stands')">
      <span class="nav-icon">🏪</span> My Stands
    </a>
    <a class="nav-item" data-section="menu" onclick="showSection('menu')">
      <span class="nav-icon">🍽️</span> Menu Items
    </a>
    <a class="nav-item" data-section="reviews" onclick="showSection('reviews')">
      <span class="nav-icon">⭐</span> Reviews
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="api/seller_logout.php" class="btn-logout">Logout →</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <button class="hamburger-btn" id="sidebarToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div class="page-title" id="topbarTitle">Overview</div>
    <div class="topbar-right">
      <a href="page2.php" target="_blank">Lihat Halaman →</a>
    </div>
  </div>

  <div class="content">

    <!-- ══ OVERVIEW ══ -->
    <div class="section active" id="section-overview">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🏪</div>
          <div class="stat-label">Total Stand</div>
          <div class="stat-value"><?= $total_stands ?></div>
          <div class="stat-sub">stand aktif</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🍽️</div>
          <div class="stat-label">Menu Items</div>
          <div class="stat-value"><?= $total_menu ?></div>
          <div class="stat-sub">item di semua stand</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⭐</div>
          <div class="stat-label">Avg Rating</div>
          <div class="stat-value"><?= $avg_rating ?: '—' ?></div>
          <div class="stat-sub">rata-rata semua stand</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💬</div>
          <div class="stat-label">Total Reviews</div>
          <div class="stat-value"><?= $total_reviews ?></div>
          <div class="stat-sub">dari semua item</div>
        </div>
      </div>

      <!-- Recent Reviews -->
      <div class="section-header">
        <div class="section-title">Reviews Terbaru</div>
      </div>
      <?php if (empty($recent_reviews)): ?>
        <div class="empty-dash"><div class="ei">💬</div><p>Belum ada reviews</p></div>
      <?php else: ?>
        <?php foreach ($recent_reviews as $rv): ?>
        <div class="review-card">
          <div class="rc-top">
            <div>
              <div class="rc-user"><?= htmlspecialchars($rv['user_nama']) ?></div>
              <div class="rc-item"><?= htmlspecialchars($rv['item_nama']) ?> · <?= htmlspecialchars($rv['stand_nama']) ?></div>
            </div>
            <div class="rc-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
          </div>
          <div class="rc-stars"><?= str_repeat('★', $rv['rating']) ?><span style="color:#333"><?= str_repeat('★', 5 - $rv['rating']) ?></span></div>
          <div class="rc-comment"><?= htmlspecialchars($rv['komentar']) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ STANDS ══ -->
    <div class="section" id="section-stands">
      <div class="section-header">
        <div class="section-title">My Stands</div>
        <button class="btn-primary" onclick="openAddStand()">+ Tambah Stand</button>
      </div>
      <?php if (empty($stands)): ?>
        <div class="empty-dash"><div class="ei">🏪</div><p>Belum ada stand. Tambah sekarang!</p></div>
      <?php else: ?>
      <div class="stand-grid">
        <?php foreach ($stands as $stand): ?>
        <div class="stand-card">
          <div class="stand-card-top">
            <div class="stand-info">
              <div class="stand-name"><?= htmlspecialchars($stand['nama']) ?></div>
              <span class="stand-cat"><?= $kategori_opts[$stand['kategori']] ?? $stand['kategori'] ?></span>
              <div class="stand-rating">
                <?php for ($s=1;$s<=5;$s++): ?>
                <span style="color:<?= $s<=round($stand['rating'])?'#fff':'#333' ?>">★</span>
                <?php endfor; ?>
                <span style="font-size:11px;color:#444;margin-left:4px;">(<?= $stand['rating'] ?>)</span>
              </div>
            </div>
            <div class="stand-actions">
              <button class="btn-primary btn-sm btn-edit" onclick="openEditStand(<?= $stand['id'] ?>, '<?= addslashes($stand['nama']) ?>', '<?= $stand['kategori'] ?>', '<?= addslashes($stand['foto'] ?? '') ?>')">Edit</button>
              <button class="btn-primary btn-sm btn-danger" onclick="deleteStand(<?= $stand['id'] ?>)">Hapus</button>
            </div>
          </div>
          <div class="stand-card-body">
            <div class="stand-stats">
              <div class="ss-item">
                <div class="ss-val"><?= count($menu_by_stand[$stand['id']] ?? []) ?></div>
                <div class="ss-lbl">Menu</div>
              </div>
              <div class="ss-item">
                <div class="ss-val"><?= $stand['total_votes'] ?></div>
                <div class="ss-lbl">Votes</div>
              </div>
              <div class="ss-item">
                <div class="ss-val"><?= $stand['rating'] ?></div>
                <div class="ss-lbl">Rating</div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ MENU ITEMS ══ -->
    <div class="section" id="section-menu">
      <div class="section-header">
        <div class="section-title">Menu Items</div>
        <button class="btn-primary" onclick="openAddMenu()">+ Tambah Item</button>
      </div>
      <?php if (empty($stands)): ?>
        <div class="empty-dash"><div class="ei">🍽️</div><p>Buat stand dulu sebelum tambah menu</p></div>
      <?php else: ?>
        <?php foreach ($stands as $stand): ?>
        <div class="stand-menu-section">
          <div class="smenu-header" onclick="toggleMenuTable(<?= $stand['id'] ?>)">
            <div class="smenu-title"><?= $emoji_map[$stand['kategori']] ?? '🍽️' ?> <?= htmlspecialchars($stand['nama']) ?></div>
            <div class="smenu-toggle" id="toggle-<?= $stand['id'] ?>">▲ tutup</div>
          </div>
          <div id="menu-table-<?= $stand['id'] ?>">
          <?php $items = $menu_by_stand[$stand['id']] ?? []; ?>
          <?php if (empty($items)): ?>
            <div style="background:#111;border:1px solid #1e1e1e;border-top:none;padding:20px;text-align:center;font-family:'Oxanium',sans-serif;font-size:11px;color:#333;letter-spacing:0.1em;">BELUM ADA ITEM</div>
          <?php else: ?>
          <table class="menu-table">
            <thead><tr>
              <th>Item</th><th>Harga</th><th>Rating</th><th>Votes</th><th>Aksi</th>
            </tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><span class="item-emoji"><?= $emoji_map[$stand['kategori']] ?? '🍽️' ?></span><span class="item-name-cell"><?= htmlspecialchars($item['nama']) ?></span></td>
                <td class="price-cell">Rp <?= number_format($item['harga'],0,',','.') ?></td>
                <td class="rating-cell"><?= str_repeat('★', round($item['rating'])) ?><span style="color:#333"><?= str_repeat('★', 5-round($item['rating'])) ?></span> <span style="color:#444;font-size:11px;">(<?= $item['rating'] ?>)</span></td>
                <td style="color:#444;font-family:'Oxanium',sans-serif;font-size:12px;"><?= $item['total_votes'] ?></td>
                <td><div class="actions-cell">
                  <button class="btn-primary btn-sm btn-edit" onclick="openEditMenu(<?= $item['id'] ?>, '<?= addslashes($item['nama']) ?>', <?= $item['harga'] ?>, <?= $item['stand_id'] ?>, '<?= addslashes($item['foto'] ?? '') ?>')">Edit</button>
                  <button class="btn-primary btn-sm btn-danger" onclick="deleteMenu(<?= $item['id'] ?>)">Hapus</button>
                </div></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ REVIEWS ══ -->
    <div class="section" id="section-reviews">
      <div class="section-header">
        <div class="section-title">Semua Reviews</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#444;"><?= $total_reviews ?> total</span>
      </div>
      <div id="reviewsContainer">
        <?php if (empty($stand_ids)): ?>
          <div class="empty-dash"><div class="ei">⭐</div><p>Belum ada reviews</p></div>
        <?php else: ?>
          <?php
          require_once 'config.php';
          $ids_str = implode(',', $stand_ids);
          $r = $conn->query("
            SELECT rv.rating, rv.komentar, rv.created_at, u.nama as user_nama,
                   mi.nama as item_nama, s.nama as stand_nama
            FROM reviews rv JOIN users u ON u.id=rv.user_id
            JOIN menu_items mi ON mi.id=rv.menu_id JOIN stands s ON s.id=mi.stand_id
            WHERE s.id IN ($ids_str) ORDER BY rv.created_at DESC
          ");
          $all_reviews = [];
          while($row=$r->fetch_assoc()) $all_reviews[]=$row;
          $conn->close();
          ?>
          <?php if (empty($all_reviews)): ?>
            <div class="empty-dash"><div class="ei">💬</div><p>Belum ada reviews</p></div>
          <?php else: ?>
            <?php foreach ($all_reviews as $rv): ?>
            <div class="review-card">
              <div class="rc-top">
                <div>
                  <div class="rc-user"><?= htmlspecialchars($rv['user_nama']) ?></div>
                  <div class="rc-item"><?= htmlspecialchars($rv['item_nama']) ?> · <?= htmlspecialchars($rv['stand_nama']) ?></div>
                </div>
                <div class="rc-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
              </div>
              <div class="rc-stars"><?= str_repeat('★',$rv['rating']) ?><span style="color:#333"><?= str_repeat('★',5-$rv['rating']) ?></span></div>
              <div class="rc-comment"><?= htmlspecialchars($rv['komentar']) ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ══ MODAL: ADD/EDIT STAND ══ -->
<div class="modal-bg" id="modalStand">
  <div class="modal-form">
    <div class="mf-title" id="modalStandTitle">Tambah Stand</div>
    <input type="hidden" id="standId" value=""/>
    <div class="form-field"><label>Nama Stand</label><input type="text" id="standNama" placeholder="e.g. Warung Nasi Bu Endang"/></div>
    <div class="form-field"><label>Kategori</label>
      <select id="standKategori">
        <?php foreach ($kategori_opts as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-field"><label>Foto (nama file / path)</label><input type="text" id="standFoto" placeholder="e.g. image1.jpeg"/></div>
    <div class="form-actions">
      <button class="btn-primary" onclick="saveStand()">Simpan</button>
      <button class="btn-cancel" onclick="closeModal('modalStand')">Batal</button>
    </div>
    <div class="form-msg" id="standMsg" style="display:none"></div>
  </div>
</div>

<!-- ══ MODAL: ADD/EDIT MENU ══ -->
<div class="modal-bg" id="modalMenu">
  <div class="modal-form">
    <div class="mf-title" id="modalMenuTitle">Tambah Menu Item</div>
    <input type="hidden" id="menuId" value=""/>
    <div class="form-field"><label>Stand</label>
      <select id="menuStandId">
        <?php foreach ($stands as $s): ?><option value="<?=$s['id']?>"><?= htmlspecialchars($s['nama']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-field"><label>Nama Item</label><input type="text" id="menuNama" placeholder="e.g. Nasi Rames Spesial"/></div>
    <div class="form-field"><label>Harga (Rp)</label><input type="number" id="menuHarga" placeholder="15000" min="0"/></div>
    <div class="form-field"><label>Foto (nama file / path)</label><input type="text" id="menuFoto" placeholder="e.g. image1.jpeg (opsional)"/></div>
    <div class="form-actions">
      <button class="btn-primary" onclick="saveMenu()">Simpan</button>
      <button class="btn-cancel" onclick="closeModal('modalMenu')">Batal</button>
    </div>
    <div class="form-msg" id="menuMsg" style="display:none"></div>
  </div>
</div>

<script>
// ── SECTION NAVIGATION ──
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + name).classList.add('active');
  document.querySelector(`[data-section="${name}"]`).classList.add('active');
  const titles = { overview:'Overview', stands:'My Stands', menu:'Menu Items', reviews:'Reviews' };
  document.getElementById('topbarTitle').textContent = titles[name] || name;
  // Close sidebar on mobile after selecting
  if (window.innerWidth <= 768) {
    const hambtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (hambtn && sidebar) {
      hambtn.classList.remove('open');
      sidebar.classList.remove('open');
    }
  }
}

// ── HAMBURGER MENU ──
const hambtn = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');
if (hambtn && sidebar) {
  hambtn.addEventListener('click', () => {
    hambtn.classList.toggle('open');
    sidebar.classList.toggle('open');
  });
}

// ── TOGGLE MENU TABLE ──
function toggleMenuTable(standId) {
  const el = document.getElementById('menu-table-' + standId);
  const tog = document.getElementById('toggle-' + standId);
  const hidden = el.style.display === 'none';
  el.style.display = hidden ? 'block' : 'none';
  tog.textContent = hidden ? '▲ tutup' : '▼ buka';
}

// ── MODAL ──
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id)  { document.getElementById(id).classList.add('open'); }

// ── STAND CRUD ──
function openAddStand() {
  document.getElementById('standId').value = '';
  document.getElementById('standNama').value = '';
  document.getElementById('standFoto').value = '';
  document.getElementById('standMsg').style.display = 'none';
  document.getElementById('modalStandTitle').textContent = 'Tambah Stand';
  openModal('modalStand');
}
function openEditStand(id, nama, kat, foto) {
  document.getElementById('standId').value = id;
  document.getElementById('standNama').value = nama;
  document.getElementById('standKategori').value = kat;
  document.getElementById('standFoto').value = foto;
  document.getElementById('standMsg').style.display = 'none';
  document.getElementById('modalStandTitle').textContent = 'Edit Stand';
  openModal('modalStand');
}
function saveStand() {
  const id    = document.getElementById('standId').value;
  const nama  = document.getElementById('standNama').value.trim();
  const kat   = document.getElementById('standKategori').value;
  const foto  = document.getElementById('standFoto').value.trim();
  const msgEl = document.getElementById('standMsg');
  if (!nama) { showMsg(msgEl, 'Nama stand wajib diisi!', 'err'); return; }
  const fd = new FormData();
  fd.append('action', id ? 'edit' : 'add');
  fd.append('id', id); fd.append('nama', nama);
  fd.append('kategori', kat); fd.append('foto', foto);
  fetch('api/item_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => {
      if (data.error) { showMsg(msgEl, data.error, 'err'); return; }
      showMsg(msgEl, data.message, 'ok');
      setTimeout(() => { closeModal('modalStand'); location.reload(); }, 800);
    }).catch(() => showMsg(msgEl, 'Gagal menyimpan.', 'err'));
}
function deleteStand(id) {
  if (!confirm('Hapus stand ini? Semua menu item di dalamnya juga akan terhapus!')) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
  fetch('api/item_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
}

// ── MENU CRUD ──
function openAddMenu() {
  document.getElementById('menuId').value = '';
  document.getElementById('menuNama').value = '';
  document.getElementById('menuHarga').value = '';
  document.getElementById('menuFoto').value = '';
  document.getElementById('menuMsg').style.display = 'none';
  document.getElementById('modalMenuTitle').textContent = 'Tambah Menu Item';
  openModal('modalMenu');
}
function openEditMenu(id, nama, harga, standId, foto) {
  document.getElementById('menuId').value = id;
  document.getElementById('menuNama').value = nama;
  document.getElementById('menuHarga').value = harga;
  document.getElementById('menuStandId').value = standId;
  document.getElementById('menuFoto').value = foto;
  document.getElementById('menuMsg').style.display = 'none';
  document.getElementById('modalMenuTitle').textContent = 'Edit Menu Item';
  openModal('modalMenu');
}
function saveMenu() {
  const id     = document.getElementById('menuId').value;
  const standId= document.getElementById('menuStandId').value;
  const nama   = document.getElementById('menuNama').value.trim();
  const harga  = document.getElementById('menuHarga').value;
  const foto   = document.getElementById('menuFoto').value.trim();
  const msgEl  = document.getElementById('menuMsg');
  if (!nama || !harga) { showMsg(msgEl, 'Nama dan harga wajib diisi!', 'err'); return; }
  const fd = new FormData();
  fd.append('action', id ? 'edit' : 'add');
  fd.append('id', id); fd.append('stand_id', standId);
  fd.append('nama', nama); fd.append('harga', harga); fd.append('foto', foto);
  fetch('api/menu_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => {
      if (data.error) { showMsg(msgEl, data.error, 'err'); return; }
      showMsg(msgEl, data.message, 'ok');
      setTimeout(() => { closeModal('modalMenu'); location.reload(); }, 800);
    }).catch(() => showMsg(msgEl, 'Gagal menyimpan.', 'err'));
}
function deleteMenu(id) {
  if (!confirm('Hapus menu item ini?')) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
  fetch('api/menu_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
}

function showMsg(el, msg, type) {
  el.textContent = msg; el.className = 'form-msg ' + type; el.style.display = 'block';
}

// Close modal on overlay click
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
</body>
</html>