<?php
require_once 'config.php';
requireRole(['resident']);
$user = currentUser();

// Fetch user statistics
$stmt = $db->prepare('SELECT COUNT(*) as count FROM civic_issues WHERE user_id = ?');
$stmt->execute([$user['id']]);
$total_issues = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$stmt = $db->prepare('SELECT COUNT(*) as count FROM civic_issues WHERE user_id = ? AND status = "Resolved"');
$stmt->execute([$user['id']]);
$resolved = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$stmt = $db->prepare('SELECT COUNT(*) as count FROM legislation');
$stmt->execute();
$ordinances = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$recentIssuesStmt = $db->prepare('SELECT i.id, i.title, i.issue_type, i.location, i.status, i.created_at, u.full_name AS reporter_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 5');
$recentIssuesStmt->execute();
$recentIssues = $recentIssuesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">

<button class="hamburger" onclick="toggleSidebar('sb-resident')">☰</button>
<div class="sidebar" id="sb-resident">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Resident Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item active">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Dashboard
    </div>
    <div class="nav-item" onclick="location.href='officials.php'">♙ &nbsp; Barangay Officials</div>
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
    <div class="nav-item" onclick="location.href='certificate_requests.php'">Barangay Certificates</div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
    <div class="nav-item profile-footer-link" onclick="location.href='profile.php'">◎ &nbsp; My Profile</div>
  </div>
</div>

<div class="main" style="flex:1">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
      <div>
        <h1 class="page-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></h1>
        <p class="page-sub">Barangay 76-A · <?= $user['residency_verified'] ? 'Verified Resident' : 'Verification Pending' ?></p>
      </div>
      <a href="index.php" style="color:white;padding:8px 16px;background:var(--navy);border-radius:6px;text-decoration:none;font-size:14px;border:0.5px solid var(--navy);transition:all .2s;display:inline-block" onmouseover="this.style.background='var(--navy-light)'" onmouseout="this.style.background='var(--navy)'">← Home</a>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-label">My Reports</div><div class="stat-val"><?= $total_issues ?></div><div class="stat-delta"><?= $total_issues - $resolved ?> in progress</div></div>
    <div class="stat-card"><div class="stat-label">Resolved</div><div class="stat-val"><?= $resolved ?></div><div class="stat-delta">Completed reports</div></div>
    <div class="stat-card"><div class="stat-label">Ordinances</div><div class="stat-val"><?= $ordinances ?></div><div class="stat-delta">Active</div></div>
    <div class="stat-card"><div class="stat-label">Status</div><div class="stat-val" style="font-size:14px;color:var(--teal)">Verified ✓</div><div class="stat-delta">Active resident</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">Quick Actions</div>
      <div style="display:grid;gap:10px">
        <a href="report_issue.php" class="btn-primary" style="width:100%;text-align:center;text-decoration:none;display:block;padding:12px">Submit New Issue</a>
        <a href="issue_status.php" class="btn-secondary" style="width:100%;text-align:center;text-decoration:none;display:block;padding:12px;margin:0">Track Reports</a>
        <a href="certificate_requests.php" class="btn-secondary" style="width:100%;text-align:center;text-decoration:none;display:block;padding:12px;margin:0">Request Barangay Certificate</a>
      </div>
    </div>
    <div class="card">
      <div class="card-title">Latest Ordinances</div>
      <a href="legislation.php" class="btn-secondary" style="width:100%;text-align:center;text-decoration:none;display:block;padding:12px;margin:0">Browse All Ordinances</a>
    </div>
  </div>

  <div class="card" style="margin-top:16px;">
    <div class="card-title">Recent Community Reports</div>
    <?php if (empty($recentIssues)): ?>
      <p style="color:var(--muted);margin:0;">No recent civic issues reported yet.</p>
    <?php else: ?>
      <div class="overflow-table">
        <table class="data-table">
          <thead>
            <tr>
              <th>Issue</th>
              <th>Location</th>
              <th>Reporter</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentIssues as $issue): ?>
              <tr onclick="location.href='view_issue.php?id=<?= urlencode($issue['id']) ?>'" style="cursor:pointer;">
                <td>
                  <strong><?= htmlspecialchars($issue['title']) ?></strong><br>
                  <span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($issue['issue_type']) ?></span>
                </td>
                <td><?= htmlspecialchars($issue['location']) ?></td>
                <td><?= htmlspecialchars($issue['reporter_name'] ?? 'Resident') ?></td>
                <td>
                  <?php
                    $issueStatus = $issue['status'];
                    $issueBadge = 'badge-pending';
                    if ($issueStatus === 'In Progress') $issueBadge = 'badge-progress';
                    if ($issueStatus === 'Resolved') $issueBadge = 'badge-resolved';
                  ?>
                  <span class="badge <?= $issueBadge ?>"><?= htmlspecialchars($issueStatus) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
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
