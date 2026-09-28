<?php
require_once 'config.php';

// If user is already logged in, redirect to their dashboard
if (currentUser()) {
    $user = currentUser();
    if ($user['role'] === 'resident') {
        header('Location: resident_dashboard.php');
    } elseif ($user['role'] === 'official') {
        header('Location: official_dashboard.php');
    } else {
        header('Location: admin_dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Integrated Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body>
        <div id="s-landing" class="screen active">
            <section class="home-brand-panel">
                <div class="landing-seal">⚖</div>
                <h1><span>Barangay</span><br>Integrated<br>Portal</h1>
                <p>Barangay 76-A &middot; City of Davao</p>
                <small>Tamang Datos, Maayos na Barangay</small>
            </section>
            <section class="home-portal-panel">
                <?php
                    date_default_timezone_set('Asia/Manila');
                    $currentDate = date('F j, Y');
                ?>
                <div class="home-date">◷ <?php echo htmlspecialchars($currentDate); ?></div>
                <p class="landing-brgy">Community access portal</p>
                <h2 class="home-title">Welcome to the Barangay Portal</h2>
                <p class="landing-sub">Access services, view legislation, and connect with your barangay.</p>
                <div class="landing-cards">
            <div class="landing-card" onclick="location.href='resident_login.php'">
                <div class="lc-icon" style="background:rgba(201,149,42,0.18)">👤</div>
                <div class="lc-title">Resident Portal</div>
                <div class="lc-desc">View ordinances, submit civic issues, and track your reports</div>
            </div>
            <div class="landing-card" onclick="location.href='official_login.php'">
                <div class="lc-icon" style="background:rgba(15,110,86,0.18)">🏛</div>
                <div class="lc-title">Officials & Staff</div>
                <div class="lc-desc">Manage legislative records and process civic complaints</div>
            </div>
            <div class="landing-card" onclick="location.href='official_login.php'">
                <div class="lc-icon" style="background:rgba(163,45,45,0.18)">⚙</div>
                <div class="lc-title">Administrator</div>
                <div class="lc-desc">Full system access, analytics, and user management</div>
            </div>
                </div>
                <div class="home-actions">
                    <button class="btn-primary" onclick="location.href='resident_register.php'">Create Resident Account</button>
                    <button class="btn-secondary" onclick="location.href='officials.php'">Meet Barangay Leadership</button>
                </div>
                <p class="landing-footer">Powered by PHP 8.4 &middot; MySQL 8.2.12 &middot; &copy; 2025 Barangay 76-A</p>
            </section>
    </div>
    
<!-- Floating Chatbot Button -->
<button id="chatbot-btn" onclick="toggleChat()" style="
  position:fixed; bottom:24px; right:24px; z-index:9999;
  width:58px; height:58px; border-radius:50%; border:none;
  background:linear-gradient(135deg,#1e3a8a,#1d4ed8);
  color:white; font-size:24px; cursor:pointer;
  box-shadow:0 4px 16px rgba(29,78,216,0.5);
  transition: transform 0.2s;">💬</button>

<div id="chatbot-overlay" style="
  display:none; position:fixed; bottom:90px; right:24px;
  z-index:9998; width:380px; height:520px;
  border-radius:16px; overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,0.25);">
  <iframe src="chatbot_page.php" style="width:100%;height:100%;border:none;"></iframe>
</div>

<script>
function toggleChat(){
  const o = document.getElementById('chatbot-overlay');
  const b = document.getElementById('chatbot-btn');
  const open = o.style.display === 'block';
  o.style.display = open ? 'none' : 'block';
  b.textContent = open ? '💬' : '✕';
}
</script>
</body>
</html>
