<?php
require_once 'seller_auth.php';
require_once 'config.php';

if (isSellerLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$nama || !$email || !$pass || !$confirm) { $error = 'Semua field wajib diisi!'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    elseif (strlen($pass) < 6) { $error = 'Password minimal 6 karakter.'; }
    elseif ($pass !== $confirm) { $error = 'Konfirmasi password tidak cocok.'; }
    else {
        $stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
        $stmt->bind_param('s', $email); $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($existing) { $error = 'Email sudah terdaftar.'; }
        else {
            $stmt = $conn->prepare("INSERT INTO sellers (nama, email, password, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param('sss', $nama, $email, $pass);
            if ($stmt->execute()) $success = 'Akun seller berhasil dibuat! Silahkan menunggu, pending selama 1x24 jam untuk verifikasi admin.';
            else $error = 'Gagal membuat akun.';
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Seller</title>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Nunito',sans-serif;background:#0a0a0a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:#111;border:1px solid #222;width:100%;max-width:400px;padding:40px;}
.logo{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:32px;display:flex;align-items:center;gap:10px;}
.logo-icon{width:36px;height:36px;background:#fff;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.logo .sub{color:#666;font-size:11px;display:block;font-weight:400;}
h1{font-family:'Oxanium',sans-serif;font-weight:800;font-size:1.5rem;color:#fff;margin-bottom:6px;}
.sub-text{font-size:13px;color:#555;margin-bottom:28px;}
.field{margin-bottom:16px;}
.field label{font-family:'Oxanium',sans-serif;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#555;display:block;margin-bottom:7px;}
.field input{width:100%;padding:12px 14px;border:1.5px solid #222;font-family:'Nunito',sans-serif;font-size:14px;color:#fff;background:#1a1a1a;outline:none;transition:border-color 0.2s;}
.field input:focus{border-color:#fff;}
.err{background:#2a0000;border:1px solid #550000;color:#ff6666;font-size:13px;padding:10px 14px;margin-bottom:18px;}
.ok{background:#002a0e;border:1px solid #005520;color:#66ff99;font-size:13px;padding:10px 14px;margin-bottom:18px;}
.btn{width:100%;padding:13px;background:#fff;color:#111;font-family:'Oxanium',sans-serif;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;border:none;cursor:pointer;transition:opacity 0.2s;}
.btn:hover{opacity:0.85;}
.footer{text-align:center;margin-top:20px;font-size:13px;color:#444;}
.footer a{color:#fff;font-weight:700;text-decoration:none;border-bottom:1px solid #fff;}
.back{display:block;text-align:center;margin-top:16px;font-family:'Oxanium',sans-serif;font-size:11px;color:#444;letter-spacing:0.1em;text-decoration:none;text-transform:uppercase;}
.back:hover{color:#fff;}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><div class="logo-icon">🍽️</div><div><span>Seller</span><span class="sub">Dashboard</span></div></div>
  <h1>Daftar Seller</h1>
  <p class="sub-text">Buat akun penjual baru</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="ok"><?= $success ?> <a href="seller_login.php" style="color:#66ff99;">Login →</a></div><?php endif; ?>
  <?php if (!$success): ?>
  <form method="POST">
    <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" placeholder="Nama lo" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"/></div>
    <div class="field"><label>Email</label><input type="email" name="email" placeholder="email@seller.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/></div>
    <div class="field"><label>Password</label><input type="password" name="password" placeholder="Min. 6 karakter" required/></div>
    <div class="field"><label>Konfirmasi Password</label><input type="password" name="confirm" placeholder="Ulangi password" required/></div>
    <button type="submit" class="btn">Buat Akun →</button>
  </form>
  <?php endif; ?>
  <div class="footer">Sudah punya akun? <a href="seller_login.php">Login</a></div>
  <a href="index.php" class="back">← Kembali ke halaman utama</a>
</div>
</body>
</html>