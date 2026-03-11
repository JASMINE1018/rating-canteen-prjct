<?php
require_once 'auth.php';
require_once 'config.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$nama || !$email || !$password || !$confirm) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek email duplikat
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $error = 'Email sudah terdaftar.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama, $email, $password);
            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! <a href="login.php">Login sekarang →</a>';
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar — School Cafeteria</title>
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Nunito', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.auth-box { background: #fff; border: 1px solid #e0e0e0; width: 100%; max-width: 420px; padding: 40px; }
.auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 32px; font-family: 'Oxanium', sans-serif; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #111; text-decoration: none; }
.logo-icon { width: 36px; height: 36px; background: #111; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.auth-logo .sub { color: #888; font-size: 12px; display: block; }
.auth-title { font-family: 'Oxanium', sans-serif; font-weight: 800; font-size: 1.6rem; color: #111; margin-bottom: 6px; }
.auth-sub { font-size: 13px; color: #888; margin-bottom: 28px; }
.field { margin-bottom: 18px; }
.field label { font-family: 'Oxanium', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #555; display: block; margin-bottom: 7px; }
.field input { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; font-family: 'Nunito', sans-serif; font-size: 14px; color: #111; background: #fafafa; outline: none; transition: border-color 0.2s; }
.field input:focus { border-color: #111; background: #fff; }
.error-msg { background: #fff0f0; border: 1px solid #ffcccc; color: #cc0000; font-size: 13px; padding: 10px 14px; margin-bottom: 18px; }
.success-msg { background: #f0fff4; border: 1px solid #b2f5c8; color: #1a7a3a; font-size: 13px; padding: 10px 14px; margin-bottom: 18px; }
.success-msg a { color: #1a7a3a; font-weight: 700; }
.btn-submit { width: 100%; padding: 13px; background: #111; color: #fff; font-family: 'Oxanium', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; border: none; cursor: pointer; transition: opacity 0.2s; }
.btn-submit:hover { opacity: 0.75; }
.auth-footer { text-align: center; margin-top: 20px; font-size: 13px; color: #888; }
.auth-footer a { color: #111; font-weight: 700; text-decoration: none; border-bottom: 1.5px solid #111; }
</style>
</head>
<body>
<div class="auth-box">
  <a href="index.php" class="auth-logo">
    <div class="logo-icon">🍽️</div>
    <div><span>School</span><span class="sub">Cafeteria</span></div>
  </a>

  <h1 class="auth-title">Daftar</h1>
  <p class="auth-sub">Buat akun buat kasih rating makanan favoritmu!</p>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="success-msg"><?= $success ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST">
    <div class="field">
      <label>Nama</label>
      <input type="text" name="nama" placeholder="Nama kamu" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"/>
    </div>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" placeholder="email@kamu.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Min. 6 karakter" required/>
    </div>
    <div class="field">
      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" placeholder="Ulangi password" required/>
    </div>
    <button type="submit" class="btn-submit">Buat Akun →</button>
  </form>
  <?php endif; ?>

  <p class="auth-footer">Sudah punya akun? <a href="login.php">Login</a></p>
</div>
</body>
</html>