<?php
require_once 'config.php';

// If user is already logged in and is admin, redirect to admin dashboard
if (currentUser() && currentUser()['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        setFlash('Email and password are required.', 'error');
    } else {
        $stmt = $db->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] !== 'admin') {
                setFlash('⚠ This account does not have admin access. Please use the appropriate login page.', 'error');
            } else {
                $_SESSION['user_id'] = $user['id'];
                header('Location: admin_dashboard.php');
                exit;
            }
        } else {
            setFlash('Invalid email or password.', 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login - Barangay Portal</title>
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
                <div class="auth-seal">⚙</div>
                <h2 class="auth-title">Administrator Login</h2>
                <p class="auth-sub">System administration and full control access</p>
            </div>
        <?php if ($message = flashMessage()): ?>
            <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <fieldset>
                <div class="form-group">
                    <label for="email">Admin Email Address</label>
                    <input type="email" id="email" name="email" placeholder="admin@barangay.gov" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary" style="width:100%">Sign In</button>
                </div>
                
                <div style="text-align:center;margin-top:24px;padding-top:24px;border-top:1px solid var(--border)">
                    <p style="color:var(--muted);margin-bottom:12px">Need a different login?</p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                        <a href="resident_login.php" class="btn btn-secondary" style="flex:1;min-width:150px">Resident Login</a>
                        <a href="official_login.php" class="btn btn-secondary" style="flex:1;min-width:150px">Official Login</a>
                    </div>
                </div>
            </fieldset>
        </form>
        </div>
        </section>
    </div>
</body>
</html>
