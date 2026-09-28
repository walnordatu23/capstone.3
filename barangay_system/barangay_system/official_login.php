<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND role IN ("official", "admin")');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = $user;
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: official_dashboard.php');
            }
            exit;
        }
        setFlash('Invalid official credentials.');
    } else {
        setFlash('Please complete both fields.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officials & Admin Login - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body>
<div class="auth-layout">
  <section class="auth-brand">
    <div class="auth-brand-logo"></div>
    <h1><span>Barangay</span><br>Population<br>Management</h1>
    <p>Tamang Datos, Maayos na Barangay</p>
    <small>© 2025 Barangay System. All rights reserved.</small>
  </section>
  <section class="auth-stage">
<div class="auth-box">
    <div class="auth-header">
      <div class="auth-seal">🏛</div>
      <h2 class="auth-title">Officials & Admin</h2>
      <p class="auth-sub">Access the officials management portal</p>
    </div>

    <?php if ($message = flashMessage()): ?>
      <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input class="form-input" type="email" name="email" placeholder="official@barangay.gov" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-primary">Sign In to Portal</button>
      <button type="button" class="btn-secondary" onclick="location.href='index.php'">← Back to Home</button>
      
      <p class="auth-switch">New account? <a href="official_register.php">Register as Official</a></p>
    </form>
</div>
</section>
</div>
</body>
</html>
