<?php
require_once 'config.php';
requireRole(['resident']);
$user = currentUser();

$notifications = [
    [
        'status' => 'new',
        'text' => 'Your report #2025-041 (Pothole on Purok 3) has been assigned to the Committee on Infrastructure. Expected resolution within 7 days.',
        'time' => 'Apr 9, 2025 · 2:34 PM'
    ],
    [
        'status' => 'read',
        'text' => 'New ordinance enacted: Waste Segregation Compliance Ordinance (ORD 2025-12). This affects all households in the barangay.',
        'time' => 'Mar 16, 2025 · 10:00 AM'
    ],
    [
        'status' => 'read',
        'text' => 'Your report #2025-029 has been marked Resolved. Area cleaned and informational signage installed. Thank you for your report!',
        'time' => 'Apr 1, 2025 · 4:15 PM'
    ],
    [
        'status' => 'read',
        'text' => 'Community General Assembly scheduled on April 20, 2025 at 8:00 AM at the Barangay Hall. All residents are encouraged to attend.',
        'time' => 'Mar 28, 2025 · 9:00 AM'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">

<button class="hamburger" onclick="toggleSidebar('sb-notifs')">☰</button>
<div class="sidebar" id="sb-notifs">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Resident Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item" onclick="location.href='resident_dashboard.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Dashboard
    </div>
    <div class="nav-section">Legislative</div>
    <div class="nav-item" onclick="location.href='legislation.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2V2zm1 1v10h10V3H3zm2 2h6v1H5V5zm0 2h6v1H5V7zm0 2h4v1H5V9z"/></svg>
      Ordinances & Resolutions
    </div>
    <div class="nav-section">Civic Issues</div>
    <div class="nav-item" onclick="location.href='report_issue.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
      Submit Issue
    </div>
    <div class="nav-item" onclick="location.href='issue_status.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
      My Reports
    </div>
    <div class="nav-item active">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a3 3 0 00-3 3v2.1c0 .55-.23 1.08-.63 1.45L3 9.5v1.5h10V9.5l-1.37-1.95A1.99 1.99 0 0011 6.1V4a3 3 0 00-3-3zm0 14a2 2 0 002-2H6a2 2 0 002 2z"/></svg>
      Notifications
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
  </div>
</div>

<div class="main" style="flex:1; padding:28px;">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
      <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-sub">Automated updates from barangay officials on your reports</p>
      </div>
      <a href="resident_dashboard.php" class="btn-secondary" style="display:inline-flex;align-items:center;justify-content:center">← Back</a>
    </div>
  </div>

  <div class="card" style="padding:24px;">
    <?php foreach ($notifications as $notification): ?>
      <div class="notif-item">
        <div class="notif-dot<?= $notification['status'] === 'read' ? ' read' : '' ?>"></div>
        <div class="notif-text">
          <p style="margin:0"><?= htmlspecialchars($notification['text']) ?></p>
          <div class="notif-time"><?= htmlspecialchars($notification['time']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function toggleSidebar(id){
  var sb=document.getElementById(id);
  if(sb)sb.classList.toggle('open');
}
</script>

</body>
</html>
