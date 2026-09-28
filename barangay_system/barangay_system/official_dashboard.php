<?php
require_once 'config.php';
requireRole(['official', 'admin']);
$user = currentUser();
$committeeScope = '(? = "All committees" OR assigned_committee = ? OR assigned_committee = "All committees" OR assigned_committee IS NULL)';
$committeeParams = [$user['committee'], $user['committee']];

$pendingStmt = $db->prepare('SELECT COUNT(*) FROM civic_issues WHERE ' . $committeeScope . ' AND status = "Open"');
$pendingStmt->execute($committeeParams);
$pendingCount = $pendingStmt->fetchColumn();

$inProgressStmt = $db->prepare('SELECT COUNT(*) FROM civic_issues WHERE ' . $committeeScope . ' AND status = "In Progress"');
$inProgressStmt->execute($committeeParams);
$inProgressCount = $inProgressStmt->fetchColumn();

$resolvedMonthStmt = $db->prepare('SELECT COUNT(*) FROM civic_issues WHERE ' . $committeeScope . ' AND status = "Resolved" AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE())');
$resolvedMonthStmt->execute($committeeParams);
$resolvedMonthCount = $resolvedMonthStmt->fetchColumn();

$avgResponseStmt = $db->prepare('SELECT AVG(DATEDIFF(updated_at, created_at)) AS avg_days FROM civic_issues WHERE ' . $committeeScope . ' AND status = "Resolved"');
$avgResponseStmt->execute($committeeParams);
$avgResponseDays = round($avgResponseStmt->fetchColumn() ?: 0, 1);

$pendingReportsStmt = $db->prepare('SELECT i.id, i.title, i.issue_type, i.location, i.status, i.assigned_committee, i.assigned_official_id, i.created_at, u.full_name AS resident_name, u.photo_path AS resident_photo_path FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id WHERE ' . str_replace('assigned_committee', 'i.assigned_committee', $committeeScope) . ' AND i.status IN ("Open","In Progress") ORDER BY i.created_at DESC');
$pendingReportsStmt->execute($committeeParams);
$pendingReports = $pendingReportsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentLegislation = $db->query('SELECT title, type, published_date FROM legislation ORDER BY published_date DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);

$recentIssuesStmt = $db->prepare('SELECT i.id, i.title, i.issue_type, i.location, i.status, i.created_at, u.full_name AS reporter_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 5');
$recentIssuesStmt->execute();
$recentIssues = $recentIssuesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Dashboard - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">

<button class="hamburger" onclick="toggleSidebar('sb-admin')">☰</button>
<div class="sidebar" id="sb-admin">
  <div class="sidebar-brand">
    <div class="sb-seal">🏛</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Officials Portal</div>
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
      Legislative Database
    </div>
    <div class="nav-section">Civic Issues</div>
    <div class="nav-item" onclick="location.href='issue_status.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
      Civic Reports
    </div>
    <div class="nav-item" onclick="location.href='certificate_requests.php'">Certificate Requests</div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar" style="background:var(--teal-light);color:var(--teal)"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
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
        <h1 class="page-title">Officials Dashboard</h1>
        <p class="page-sub">Barangay Secretary · Committee on <?= htmlspecialchars($user['committee']) ?> · Role-based Access</p>
      </div>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Pending Reports</div><div class="stat-val" style="color:var(--gold)"><?= $pendingCount ?></div><div class="stat-delta">Needs assignment</div></div>
    <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-val" style="color:var(--blue)"><?= $inProgressCount ?></div><div class="stat-delta">Assigned to committees</div></div>
    <div class="stat-card"><div class="stat-label">Resolved (Month)</div><div class="stat-val" style="color:var(--teal)"><?= $resolvedMonthCount ?></div><div class="stat-delta">Up from last month</div></div>
    <div class="stat-card"><div class="stat-label">Avg. Response</div><div class="stat-val" style="font-size:24px"><?= $avgResponseDays ?>d</div><div class="stat-delta">↓ 0.5d improved</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">Pending Reports
        <button class="card-action" onclick="location.href='issue_status.php'">Manage all →</button>
      </div>
      <?php if (empty($pendingReports)): ?>
        <p style="color:var(--muted)">No pending or in-progress issues assigned to your committee.</p>
      <?php else: ?>
        <div class="overflow-table">
          <table class="data-table">
            <thead>
              <tr>
                <th>TICKET</th>
                <th>ISSUE</th>
                <th>RESIDENT</th>
                <th>STATUS</th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingReports as $report): ?>
              <?php $hasAssignedCommittee = (!empty($report['assigned_committee']) && $report['assigned_committee'] !== 'Unassigned' && $report['assigned_committee'] !== 'All committees') || (!empty($report['assigned_official_id'])); ?>
              <?php $actionLabel = $hasAssignedCommittee ? 'View & Update' : 'Assign'; ?>
              <?php $actionUrl = $hasAssignedCommittee ? 'manage_issue.php?id=' . urlencode($report['id']) . '&form=update' : 'manage_issue.php?id=' . urlencode($report['id']) . '&form=assign'; ?>
              <tr>
                <td>#<?= htmlspecialchars($report['id']) ?></td>
                <td><?= htmlspecialchars($report['title']) ?><?= $report['location'] ? ' · ' . htmlspecialchars($report['location']) : '' ?></td>
                <td>
                  <div class="issue-resident">
                    <?php if (!empty($report['resident_photo_path'])): ?>
                      <img src="<?= htmlspecialchars($report['resident_photo_path']) ?>" alt="Resident profile picture">
                    <?php else: ?>
                      <span><?= htmlspecialchars(strtoupper(substr($report['resident_name'] ?? 'R', 0, 1))) ?></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($report['resident_name'] ?? 'Resident') ?></span>
                  </div>
                </td>
                <td>
                  <?php
                    $status = $report['status'];
                    $badgeClass = 'badge-pending';
                    if ($status === 'In Progress') $badgeClass = 'badge-progress';
                    if ($status === 'Resolved') $badgeClass = 'badge-resolved';
                  ?>
                  <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                </td>
                <td><a href="<?= htmlspecialchars($actionUrl) ?>" style="color:var(--navy);font-weight:600;text-decoration:none"><?= htmlspecialchars($actionLabel) ?></a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title">Recent Legislative Actions</div>
      <?php if (empty($recentLegislation)): ?>
        <p style="color:var(--muted)">No recent legislative records available.</p>
      <?php else: ?>
        <?php foreach ($recentLegislation as $item): ?>
          <div class="ordi-card" onclick="location.href='legislation.php'" style="cursor:pointer">
            <div class="ordi-num"><?= strtoupper(substr($item['type'], 0, 3)) ?><?= date('y', strtotime($item['published_date'])) ?></div>
            <div>
              <div class="ordi-title"><?= htmlspecialchars($item['title']) ?></div>
              <div class="ordi-date">Published <?= date('M d, Y', strtotime($item['published_date'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <button class="btn-secondary" style="width:100%;margin-top:12px" onclick="location.href='legislation.php'">Manage Legislative Database</button>
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
