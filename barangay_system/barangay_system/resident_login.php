<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
        $stmt->execute([$email, 'resident']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            if ((int) ($user['residency_verified'] ?? 0) === 2) {
                setFlash('Your registration was not approved because your name was not found in the official registered resident list. Please visit the Barangay Hall to register your name in the official resident list.');
                header('Location: resident_login.php');
                exit;
            }

            $_SESSION['user'] = $user;
            header('Location: resident_dashboard.php');
            exit;
        }
        setFlash('Invalid resident credentials.');
    } else {
        setFlash('Please fill in both fields.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Login - Barangay Portal</title>
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
      <div class="auth-seal">⚖</div>
      <h2 class="auth-title">Resident Sign In</h2>
      <p class="auth-sub">Access your resident portal</p>
    </div>

    <?php if ($message = flashMessage()): ?>
      <div class="alert alert-error auth-alert">
        <span class="auth-alert-icon">!</span>
        <div><?= htmlspecialchars($message) ?></div>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input class="form-input" type="email" name="email" placeholder="juan.delacruz@email.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-primary">Sign In to Portal</button>
      <button type="button" class="btn-secondary" onclick="location.href='index.php'">← Back to Home</button>
      
      <p class="auth-switch">No account? <a href="resident_register.php">Register as Resident</a></p>
    </form>
</div>
</section>
</div>
</body>
</html>
