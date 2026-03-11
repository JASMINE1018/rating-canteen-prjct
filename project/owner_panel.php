<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';

// Cek login owner
if (!isset($_SESSION['owner_id'])) { header('Location: login.php'); exit; }
$owner_nama = $_SESSION['owner_nama'];

// ── Data sellers ──
$sellers_pending = [];
$sellers_active  = [];
$sellers_rejected = [];
$r = $conn->query("SELECT s.*, COUNT(st.id) as total_stands FROM sellers s LEFT JOIN stands st ON st.seller_id = s.id GROUP BY s.id ORDER BY s.created_at DESC");
while ($row = $r->fetch_assoc()) {
    if ($row['status'] === 'pending')  $sellers_pending[]  = $row;
    elseif ($row['status'] === 'active') $sellers_active[] = $row;
    else $sellers_rejected[] = $row;
}

// ── Data users ──
$users = [];
$r = $conn->query("SELECT u.*, COUNT(DISTINCT rv.id) as total_reviews, COUNT(DISTINCT rs.id) as total_ratings FROM users u LEFT JOIN reviews rv ON rv.user_id = u.id LEFT JOIN ratings_stand rs ON rs.user_id = u.id GROUP BY u.id ORDER BY u.created_at DESC");
while ($row = $r->fetch_assoc()) $users[] = $row;

// ── Data stands ──
$stands = [];
$r = $conn->query("SELECT s.*, sl.nama as seller_nama, COUNT(m.id) as total_menu FROM stands s LEFT JOIN sellers sl ON sl.id = s.seller_id LEFT JOIN menu_items m ON m.stand_id = s.id GROUP BY s.id ORDER BY s.id DESC");
while ($row = $r->fetch_assoc()) $stands[] = $row;

// ── Stats ──
$total_users    = count($users);
$total_sellers  = count($sellers_active) + count($sellers_pending);
$total_pending  = count($sellers_pending);
$total_stands   = count($stands);
$r = $conn->query("SELECT COUNT(*) as c FROM reviews"); 
$total_reviews  = $r->fetch_assoc()['c'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Nunito',sans-serif;background:#0a0a14;color:#eee;min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:220px;background:#0f0f1a;border-right:1px solid #1e1e33;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #1e1e33;}
.brand{font-family:'Oxanium',sans-serif;font-weight:800;font-size:13px;color:#fff;text-transform:uppercase;letter-spacing:0.08em;}
.brand-sub{font-size:11px;color:#3333aa;margin-top:2px;font-family:'Oxanium',sans-serif;letter-spacing:0.1em;}
.sidebar-owner{padding:16px 20px;border-bottom:1px solid #1e1e33;}
.owner-avatar{width:36px;height:36px;background:#3333aa;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;}
.owner-name{font-family:'Oxanium',sans-serif;font-size:12px;font-weight:700;color:#fff;}
.owner-role{font-size:10px;color:#3333aa;font-family:'Oxanium',sans-serif;text-transform:uppercase;letter-spacing:0.12em;}
.sidebar-nav{flex:1;padding:16px 0;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 20px;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#444;cursor:pointer;transition:all 0.15s;border-left:2px solid transparent;}
.nav-item:hover{color:#fff;background:#1a1a2a;}
.nav-item.active{color:#fff;background:#1a1a2a;border-left-color:#3333aa;}
.nav-icon{font-size:14px;width:18px;text-align:center;}
.badge{background:#3333aa;color:#fff;font-size:9px;padding:2px 6px;border-radius:10px;margin-left:auto;font-family:'Oxanium',sans-serif;font-weight:700;}
.badge.red{background:#aa0000;}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1e1e33;}
.btn-logout{display:block;width:100%;padding:10px;background:#1a1a2a;color:#444;font-family:'Oxanium',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #1e1e33;cursor:pointer;text-align:center;text-decoration:none;transition:all 0.2s;}
.btn-logout:hover{background:#222244;color:#fff;}
/* MAIN */
.main{margin-left:220px;flex:1;}
.topbar{background:#0f0f1a;border-bottom:1px solid #1e1e33;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;}
.page-title{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;}
.topbar-link{font-family:'Oxanium',sans-serif;font-size:11px;color:#444;text-decoration:none;letter-spacing:0.08em;text-transform:uppercase;transition:color 0.2s;}
.topbar-link:hover{color:#fff;}
.content{padding:28px 32px;}
/* SECTIONS */
.section{display:none;}
.section.active{display:block;}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:28px;}
.stat-card{background:#0f0f1a;border:1px solid #1e1e33;padding:18px;}
.stat-label{font-family:'Oxanium',sans-serif;font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#333;margin-bottom:8px;}
.stat-value{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.8rem;color:#fff;line-height:1;}
.stat-sub{font-size:11px;color:#333;margin-top:5px;}
.stat-icon{font-size:18px;margin-bottom:6px;}
.stat-card.highlight{border-color:#3333aa;}
.stat-card.highlight .stat-value{color:#aaaaff;}
/* SECTION HEADER */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.section-title{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1rem;color:#fff;}
/* TABLES */
.data-table{width:100%;border-collapse:collapse;background:#0f0f1a;border:1px solid #1e1e33;margin-bottom:24px;}
.data-table th{font-family:'Oxanium',sans-serif;font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#333;padding:12px 16px;border-bottom:1px solid #1e1e33;text-align:left;}
.data-table td{padding:13px 16px;border-bottom:1px solid #141420;font-size:13px;color:#aaa;vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:#141420;}
.name-cell{font-family:'Oxanium',sans-serif;font-weight:700;font-size:13px;color:#fff;}
.email-cell{font-size:12px;color:#444;}
/* STATUS BADGES */
.badge-status{font-family:'Oxanium',sans-serif;font-size:9px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;padding:4px 10px;display:inline-block;}
.badge-pending{background:#332200;color:#ffaa00;border:1px solid #443300;}
.badge-active{background:#003322;color:#00cc88;border:1px solid #004433;}
.badge-rejected{background:#220000;color:#ff4444;border:1px solid #330000;}
/* ACTION BUTTONS */
.btn-approve{padding:6px 14px;background:#003322;color:#00cc88;font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #004433;cursor:pointer;transition:all 0.2s;}
.btn-approve:hover{background:#004433;}
.btn-reject{padding:6px 14px;background:#220000;color:#ff4444;font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #330000;cursor:pointer;transition:all 0.2s;}
.btn-reject:hover{background:#330000;}
.btn-delete{padding:6px 14px;background:#1a0000;color:#ff6666;font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #330000;cursor:pointer;transition:all 0.2s;}
.btn-delete:hover{background:#2a0000;}
.actions-cell{display:flex;gap:6px;align-items:center;}
/* EMPTY */
.empty-row td{text-align:center;color:#333 !important;font-family:'Oxanium',sans-serif;font-size:11px;letter-spacing:0.1em;padding:32px !important;}
/* PENDING ALERT */
.pending-alert{background:#1a1500;border:1px solid #443300;color:#ffcc44;padding:14px 18px;margin-bottom:20px;font-family:'Oxanium',sans-serif;font-size:12px;letter-spacing:0.06em;display:flex;align-items:center;gap:10px;}
/* TABS inside section */
.sub-tabs{display:flex;gap:0;margin-bottom:20px;border:1px solid #1e1e33;overflow:hidden;width:fit-content;}
.sub-tab{padding:9px 20px;font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;border:none;background:transparent;color:#444;transition:all 0.2s;}
.sub-tab:not(:last-child){border-right:1px solid #1e1e33;}
.sub-tab.active{background:#3333aa;color:#fff;}
.sub-tab:hover:not(.active){color:#fff;background:#1a1a2a;}
.sub-section{display:none;}
.sub-section.active{display:block;}
/* NOTIFY */
.notify{position:fixed;bottom:24px;right:24px;padding:12px 20px;font-family:'Oxanium',sans-serif;font-size:12px;font-weight:700;letter-spacing:0.08em;z-index:999;transition:opacity 0.3s;opacity:0;}
.notify.show{opacity:1;}
.notify.ok{background:#003322;color:#00cc88;border:1px solid #004433;}
.notify.err{background:#220000;color:#ff4444;border:1px solid #330000;}
/* MOBILE MENU TOGGLE */
.hamburger-btn{display:none;flex-direction:column;gap:5px;cursor:pointer;background:none;border:none;padding:8px;color:#fff;position:absolute;left:12px;top:14px;z-index:51;}
.hamburger-btn span{display:block;width:24px;height:2px;background:#fff;transition:all 0.3s;}
.hamburger-btn.open span:nth-child(1){transform:translateY(7px) rotate(45deg);}
.hamburger-btn.open span:nth-child(2){opacity:0;}
.hamburger-btn.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}.sub-tabs{overflow-x:auto;flex-wrap:nowrap;}}
@media(max-width:768px){.hamburger-btn{display:flex}.sidebar{position:fixed;left:0;top:0;height:100vh;z-index:49;border-right:1px solid #1e1e33;width:220px;transform:translateX(-100%);transition:transform 0.3s}.sidebar.open{transform:translateX(0)}.sidebar::after{content:'';position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:-1;display:none}.sidebar.open::after{display:block;z-index:-1;left:220px;right:0}.main{margin-left:0}.topbar{padding:14px 16px;position:relative}.topbar-link{font-size:10px}.page-title{font-size:0.95rem;margin-left:40px}.content{padding:16px}.pending-alert{padding:10px 12px;font-size:11px;gap:6px}.section-header{flex-direction:column;align-items:flex-start;gap:12px}.data-table{font-size:12px}.data-table th{padding:10px 12px;font-size:8px}.data-table td{padding:10px 12px;font-size:12px}.name-cell{font-size:12px}.email-cell{font-size:11px}.actions-cell{flex-wrap:wrap;gap:4px}.btn-approve,.btn-reject,.btn-delete{padding:5px 10px;font-size:9px}.stat-value{font-size:1.4rem}.stat-label{font-size:8px}.stat-icon{font-size:16px}.sub-tabs{width:100%;overflow-x:auto}.sub-tab{padding:8px 14px;font-size:9px}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}.topbar{padding:12px;gap:6px}.page-title{font-size:0.85rem;margin-left:38px}.topbar-link{font-size:9px}.content{padding:12px}.data-table{display:block;overflow-x:auto;border:none}.data-table thead{display:none}.data-table tbody{display:block}.data-table tr{display:block;border:1px solid #1e1e33;margin-bottom:12px;padding:12px}.data-table td{display:grid;grid-template-columns:100px 1fr;gap:8px;padding:8px 0;border:none}.data-table td:before{content:attr(data-label);font-family:'Oxanium',sans-serif;font-weight:700;color:#444;font-size:9px;text-transform:uppercase}.actions-cell{flex-direction:column;gap:6px}.btn-approve,.btn-reject,.btn-delete{width:100%;padding:8px 6px;font-size:8px}.section-title{font-size:0.95rem}.stat-value{font-size:1.2rem}.sub-tab{padding:6px 10px;font-size:8px}.pending-alert{font-size:10px;padding:8px}.name-cell{font-size:11px}}
</style>
</head>
<body>
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">🍽️ Cafeteria</div>
    <div class="brand-sub">Owner Panel</div>
  </div>
  <div class="sidebar-owner">
    <div class="owner-avatar">👑</div>
    <div class="owner-name"><?= htmlspecialchars($owner_nama) ?></div>
    <div class="owner-role">Owner</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item active" data-section="overview" onclick="showSection('overview',this)">
      <span class="nav-icon">📊</span> Overview
    </div>
    <div class="nav-item" data-section="sellers" onclick="showSection('sellers',this)">
      <span class="nav-icon">🏪</span> Sellers
      <?php if ($total_pending > 0): ?><span class="badge red"><?= $total_pending ?></span><?php endif; ?>
    </div>
    <div class="nav-item" data-section="users" onclick="showSection('users',this)">
      <span class="nav-icon">👥</span> Users
    </div>
    <div class="nav-item" data-section="stands" onclick="showSection('stands',this)">
      <span class="nav-icon">🏬</span> Stands
    </div>
  </nav>
  <div class="sidebar-footer">
    <a href="api/owner_logout.php" class="btn-logout">Logout →</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <button class="hamburger-btn" id="sidebarToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div class="page-title" id="topbarTitle">Overview</div>
    <a href="page2.php" target="_blank" class="topbar-link">Lihat Website →</a>
  </div>
  <div class="content">

    <!-- ══ OVERVIEW ══ -->
    <div class="section active" id="section-overview">
      <?php if ($total_pending > 0): ?>
      <div class="pending-alert">
        ⚠️ Ada <strong><?= $total_pending ?> seller</strong> menunggu persetujuan!
        <span style="cursor:pointer;text-decoration:underline;margin-left:8px;" onclick="showSection('sellers',document.querySelector('[data-section=sellers]'));showSubTab('pending')">Review sekarang →</span>
      </div>
      <?php endif; ?>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?= $total_users ?></div>
          <div class="stat-sub">terdaftar</div>
        </div>
        <div class="stat-card highlight">
          <div class="stat-icon">🏪</div>
          <div class="stat-label">Sellers Active</div>
          <div class="stat-value"><?= count($sellers_active) ?></div>
          <div class="stat-sub"><?= $total_pending ?> pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏬</div>
          <div class="stat-label">Total Stands</div>
          <div class="stat-value"><?= $total_stands ?></div>
          <div class="stat-sub">semua seller</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💬</div>
          <div class="stat-label">Total Reviews</div>
          <div class="stat-value"><?= $total_reviews ?></div>
          <div class="stat-sub">dari users</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏳</div>
          <div class="stat-label">Pending</div>
          <div class="stat-value" style="color:<?= $total_pending>0?'#ffcc44':'#fff' ?>"><?= $total_pending ?></div>
          <div class="stat-sub">seller request</div>
        </div>
      </div>

      <!-- Pending sellers di overview -->
      <?php if (!empty($sellers_pending)): ?>
      <div class="section-header"><div class="section-title">⏳ Seller Menunggu Approve</div></div>
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Daftar</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($sellers_pending as $s): ?>
          <tr id="seller-row-<?= $s['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
            <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
            <td style="color:#444;font-size:12px;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td><div class="actions-cell">
              <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">✓ Approve</button>
              <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">✗ Reject</button>
            </div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- ══ SELLERS ══ -->
    <div class="section" id="section-sellers">
      <div class="section-header"><div class="section-title">Manajemen Sellers</div></div>
      <div class="sub-tabs">
        <button class="sub-tab active" onclick="showSubTab('pending',this)">⏳ Pending <span style="color:#ffaa00">(<?= count($sellers_pending) ?>)</span></button>
        <button class="sub-tab" onclick="showSubTab('active',this)">✓ Active (<?= count($sellers_active) ?>)</button>
        <button class="sub-tab" onclick="showSubTab('rejected',this)">✗ Rejected (<?= count($sellers_rejected) ?>)</button>
      </div>

      <!-- PENDING -->
      <div class="sub-section active" id="sub-pending">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Daftar</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_pending)): ?>
            <tr class="empty-row"><td colspan="4">TIDAK ADA SELLER PENDING</td></tr>
            <?php else: foreach ($sellers_pending as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td style="color:#444;font-size:12px;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
              <td><div class="actions-cell">
                <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">✓ Approve</button>
                <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">✗ Reject</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- ACTIVE -->
      <div class="sub-section" id="sub-active">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Stands</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_active)): ?>
            <tr class="empty-row"><td colspan="5">BELUM ADA SELLER ACTIVE</td></tr>
            <?php else: foreach ($sellers_active as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td style="color:#aaa;font-family:'Oxanium',sans-serif;font-size:12px;"><?= $s['total_stands'] ?> stand</td>
              <td><span class="badge-status badge-active">Active</span></td>
              <td><div class="actions-cell">
                <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">Suspend</button>
                <button class="btn-delete"  onclick="deleteSeller(<?= $s['id'] ?>)">Hapus</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- REJECTED -->
      <div class="sub-section" id="sub-rejected">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_rejected)): ?>
            <tr class="empty-row"><td colspan="4">TIDAK ADA SELLER REJECTED</td></tr>
            <?php else: foreach ($sellers_rejected as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td><span class="badge-status badge-rejected">Rejected</span></td>
              <td><div class="actions-cell">
                <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">Re-Approve</button>
                <button class="btn-delete"  onclick="deleteSeller(<?= $s['id'] ?>)">Hapus</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ USERS ══ -->
    <div class="section" id="section-users">
      <div class="section-header">
        <div class="section-title">Semua Users</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#333;"><?= $total_users ?> terdaftar</span>
      </div>
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Reviews</th><th>Ratings</th><th>Bergabung</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr class="empty-row"><td colspan="6">BELUM ADA USER</td></tr>
          <?php else: foreach ($users as $u): ?>
          <tr id="user-row-<?= $u['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($u['nama']) ?></div></td>
            <td class="email-cell"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $u['total_reviews'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $u['total_ratings'] ?></td>
            <td style="font-size:12px;color:#444;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td><button class="btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">Hapus</button></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ══ STANDS ══ -->
    <div class="section" id="section-stands">
      <div class="section-header">
        <div class="section-title">Semua Stands</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#333;"><?= $total_stands ?> stand</span>
      </div>
      <table class="data-table">
        <thead><tr><th>Nama Stand</th><th>Seller</th><th>Kategori</th><th>Menu</th><th>Rating</th><th>Votes</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($stands)): ?>
          <tr class="empty-row"><td colspan="7">BELUM ADA STAND</td></tr>
          <?php else: foreach ($stands as $s): ?>
          <tr id="stand-row-<?= $s['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
            <td style="font-size:12px;color:#aaa;"><?= $s['seller_nama'] ? htmlspecialchars($s['seller_nama']) : '<span style="color:#333">—</span>' ?></td>
            <td><span class="badge-status" style="background:#1a1a2a;color:#aaaaff;border:1px solid #222244;"><?= ucfirst($s['kategori']) ?></span></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $s['total_menu'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $s['rating'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#444;"><?= $s['total_votes'] ?></td>
            <td><button class="btn-delete" onclick="deleteStand(<?= $s['id'] ?>)">Hapus</button></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- NOTIFY -->
<div class="notify" id="notify"></div>

<script>
// ── SECTION NAV ──
const sectionTitles = {overview:'Overview', sellers:'Manajemen Sellers', users:'Semua Users', stands:'Semua Stands'};
function showSection(name, el) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + name).classList.add('active');
  if (el) el.classList.add('active');
  else document.querySelector(`[data-section="${name}"]`).classList.add('active');
  document.getElementById('topbarTitle').textContent = sectionTitles[name] || name;
}

// ── SUB TABS ──
function showSubTab(name, el) {
  document.querySelectorAll('.sub-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('sub-' + name).classList.add('active');
  if (el) el.classList.add('active');
  else document.querySelectorAll('.sub-tab').forEach(t => { if (t.textContent.includes(name)) t.classList.add('active'); });
}

// ── NOTIFY ──
function showNotify(msg, type) {
  const el = document.getElementById('notify');
  el.textContent = msg; el.className = 'notify ' + type + ' show';
  setTimeout(() => el.classList.remove('show'), 3000);
}

// ── HAMBURGER MENU ──
const hambtn = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');
if (hambtn) {
  hambtn.addEventListener('click', () => {
    hambtn.classList.toggle('open');
    sidebar.classList.toggle('open');
  });
  // Close sidebar when clicking nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        hambtn.classList.remove('open');
        sidebar.classList.remove('open');
      }
    });
  });
}

// ── API CALL ──
function apiPost(url, data, onSuccess) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  fetch(url, { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => { if (d.success) onSuccess(d); else showNotify(d.error || 'Gagal!', 'err'); })
    .catch(() => showNotify('Koneksi gagal.', 'err'));
}

// ── SELLER ACTIONS ──
function updateSeller(id, status) {
  const label = status === 'active' ? 'approve' : (status === 'rejected' ? 'reject/suspend' : '');
  if (!confirm(`${label} seller ini?`)) return;
  apiPost('api/owner_action.php', { action:'update_seller', id, status }, d => {
    showNotify(d.message, 'ok');
    // Hapus row & reload ringan
    document.querySelectorAll(`#seller-row-${id}`).forEach(r => r.remove());
  });
}
function deleteSeller(id) {
  if (!confirm('Hapus akun seller ini? Semua stand-nya juga akan terhapus!')) return;
  apiPost('api/owner_action.php', { action:'delete_seller', id }, d => {
    showNotify(d.message, 'ok');
    document.querySelectorAll(`#seller-row-${id}`).forEach(r => r.remove());
  });
}

// ── USER ACTIONS ──
function deleteUser(id) {
  if (!confirm('Hapus akun user ini?')) return;
  apiPost('api/owner_action.php', { action:'delete_user', id }, d => {
    showNotify(d.message, 'ok');
    document.getElementById(`user-row-${id}`).remove();
  });
}

// ── STAND ACTIONS ──
function deleteStand(id) {
  if (!confirm('Hapus stand ini?')) return;
  apiPost('api/owner_action.php', { action:'delete_stand', id }, d => {
    showNotify(d.message, 'ok');
    document.getElementById(`stand-row-${id}`).remove();
  });
}
</script>
</body>
</html>