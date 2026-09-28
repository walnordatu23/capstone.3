<?php
require_once 'config.php';
requireRole(['admin']);
$user = currentUser();

$message = ''; 
$messageType = '';

// Handle verification/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'verify') {
      $matchStmt = $db->prepare('SELECT 1 FROM users u JOIN residents r ON LOWER(TRIM(u.full_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(r.name)) COLLATE utf8mb4_unicode_ci WHERE u.id = ? AND u.role = "resident" LIMIT 1');
      $matchStmt->execute([$user_id]);
      if (!$matchStmt->fetchColumn()) {
        $message = 'Registration cannot be approved because the name is not in the official resident list. Ask the registrant to request inclusion first.';
        $messageType = 'error';
      } else {
        $stmt = $db->prepare('UPDATE users SET residency_verified = 1 WHERE id = ? AND role = "resident"');
        $stmt->execute([$user_id]);
            $message = "Resident verified successfully.";
            $messageType = 'success';
        }
    } elseif ($action === 'reject') {
        $stmt = $db->prepare('UPDATE users SET residency_verified = 2, rejection_reason = ? WHERE id = ? AND role = "resident" AND residency_verified = 0');
        if ($stmt->execute(['Name not found in official resident list.', $user_id])) {
            $message = "Resident registration rejected.";
            $messageType = 'error';
        }
    }
}

$totalResidents = $db->query('SELECT COUNT(*) FROM users WHERE role = "resident"')->fetchColumn();
$residentsThisMonth = $db->query('SELECT COUNT(*) FROM users WHERE role = "resident" AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())')->fetchColumn();
$verifiedResidents = $db->query('SELECT COUNT(*) FROM users WHERE role = "resident" AND residency_verified = 1')->fetchColumn();
$pendingResidents = $totalResidents - $verifiedResidents;
$totalOfficials = $db->query('SELECT COUNT(*) FROM users WHERE role = "official"')->fetchColumn();
$officialCommittees = $db->query('SELECT COUNT(DISTINCT committee) FROM users WHERE role = "official" AND committee IS NOT NULL AND committee <> ""')->fetchColumn();
$issuesYTD = $db->query('SELECT COUNT(*) FROM civic_issues WHERE YEAR(created_at) = YEAR(CURRENT_DATE())')->fetchColumn();
$resolvedIssues = $db->query('SELECT COUNT(*) FROM civic_issues WHERE status = "Resolved"')->fetchColumn();
$openIssues = $db->query('SELECT COUNT(*) FROM civic_issues WHERE status = "Open"')->fetchColumn();
$ordinancesCount = $db->query('SELECT COUNT(*) FROM legislation WHERE LOWER(type) LIKE "%ordinance%"')->fetchColumn();
$avgResponseStmt = $db->query('SELECT AVG(DATEDIFF(updated_at, created_at)) FROM civic_issues WHERE status = "Resolved"');
$avgResponseDays = round($avgResponseStmt->fetchColumn() ?: 0, 1);

$pendingStmt = $db->query('SELECT u.id, u.full_name, u.email, u.address, u.identification, u.created_at, r.resident_id FROM users u LEFT JOIN residents r ON LOWER(TRIM(u.full_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(r.name)) COLLATE utf8mb4_unicode_ci WHERE u.role = "resident" AND u.residency_verified = 0 ORDER BY u.created_at DESC');
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pending as &$pendingEntry) {
    $pendingEntry['matches_resident_list'] = !empty($pendingEntry['resident_id']);
}
unset($pendingEntry);

$pendingReportsStmt = $db->query('SELECT i.id, i.title, i.issue_type, i.location, i.status, i.assigned_committee, i.created_at, u.full_name AS resident_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id WHERE i.status IN ("Open", "In Progress") ORDER BY i.created_at DESC');
$pendingReports = $pendingReportsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentIssuesStmt = $db->prepare('SELECT i.id, i.title, i.issue_type, i.location, i.status, i.created_at, u.full_name AS reporter_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 5');
$recentIssuesStmt->execute();
$recentIssues = $recentIssuesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">

<button class="hamburger" onclick="toggleSidebar('sb-admin')">☰</button>
<div class="sidebar" id="sb-admin">
  <div class="sidebar-brand">
    <div class="sb-seal">⚙</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">System Administrator</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item active">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      System Overview
    </div>
    <div class="nav-item" onclick="location.href='officials.php'">♙ &nbsp; Barangay Officials</div>
    <div class="nav-section">Management</div>
    <div class="nav-item" onclick="location.href='user_management.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 3h10v10H3z" stroke="currentColor" fill="none" stroke-width="1.2"/></svg>
      User Management
    </div>
    <div class="nav-item" onclick="location.href='analytics_reports.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 12h3v2H2v-2zm4-3h3v5H6V9zm4-4h3v9h-3V5z"/></svg>
      Analytics & Reports
    </div>
    <div class="nav-item" onclick="location.href='certificate_requests.php'">Certificate Requests</div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar" style="background:var(--red-light);color:var(--red)"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname">Admin</div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
    <div class="nav-item profile-footer-link" onclick="location.href='profile.php'">◎ &nbsp; My Profile</div>
  </div>
</div>

<div class="main" style="flex:1">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;flex-wrap:wrap">
      <div>
        <h1 class="page-title">System Overview</h1>
        <p class="page-sub">Punong Barangay · Full administrative access to all modules</p>
      </div>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Total Residents</div><div class="stat-val"><?= $totalResidents ?></div><div class="stat-delta">▲ <?= $residentsThisMonth ?> this month</div></div>
    <div class="stat-card"><div class="stat-label">Verified</div><div class="stat-val" style="color:var(--teal)"><?= $verifiedResidents ?></div><div class="stat-delta"><?= $totalResidents ? round($verifiedResidents / max(1, $totalResidents) * 100) : 0 ?>% verified</div></div>
    <div class="stat-card"><div class="stat-label">Active Officials</div><div class="stat-val" style="color:var(--navy)"><?= $totalOfficials ?></div><div class="stat-delta"><?= $officialCommittees ?> committees</div></div>
    <div class="stat-card"><div class="stat-label">Issues YTD</div><div class="stat-val" style="color:var(--gold)"><?= $issuesYTD ?></div><div class="stat-delta"><?= $resolvedIssues ?> resolved</div></div>
    <div class="stat-card"><div class="stat-label">Ordinances</div><div class="stat-val" style="color:var(--blue)"><?= $ordinancesCount ?></div><div class="stat-delta">Active in repository</div></div>
    <div class="stat-card"><div class="stat-label">Avg. Response</div><div class="stat-val" style="font-size:24px"><?= $avgResponseDays ?>d</div><div class="stat-delta">↓ improved</div></div>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-title">Pending Reports
      <button class="card-action" onclick="location.href='issue_status.php?tab=Pending'">Manage all →</button>
    </div>
    <div class="overflow-table">
      <?php if (empty($pendingReports)): ?>
        <p style="color:var(--muted);margin:0;">No pending or in-progress reports at this time.</p>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Ticket</th>
              <th>Issue</th>
              <th>Reporter</th>
              <th>Committee</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingReports as $report): ?>
              <?php $reportBadge = $report['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending'; ?>
              <?php $hasAssignedCommittee = !empty($report['assigned_committee']) && $report['assigned_committee'] !== 'Unassigned' && $report['assigned_committee'] !== 'All committees'; ?>
              <?php $actionLabel = $hasAssignedCommittee ? 'View & Update' : 'Assign'; ?>
              <?php $actionUrl = $hasAssignedCommittee ? 'manage_issue.php?id=' . urlencode($report['id']) . '&form=update' : 'manage_issue.php?id=' . urlencode($report['id']) . '&form=assign'; ?>
              <tr>
                <td>#<?= htmlspecialchars($report['id']) ?></td>
                <td><strong><?= htmlspecialchars($report['title']) ?></strong><br><span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($report['location']) ?></span></td>
                <td><?= htmlspecialchars($report['resident_name'] ?? 'Resident') ?></td>
                <td><?= htmlspecialchars($report['assigned_committee'] ?? 'Unassigned') ?></td>
                <td><span class="badge <?= $reportBadge ?>"><?= htmlspecialchars($report['status']) ?></span></td>
                <td><a href="<?= htmlspecialchars($actionUrl) ?>" style="color:var(--navy);font-weight:600;text-decoration:none"><?= htmlspecialchars($actionLabel) ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Pending Verifications
      <button class="card-action" onclick="document.getElementById('pending-section').scrollIntoView({ behavior: 'smooth' })">Manage all →</button>
    </div>
    <div class="overflow-table" id="pending-section">
      <?php if (empty($pending)): ?>
        <p style="color:var(--muted)">No pending verifications at this time.</p>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Address</th>
              <th>ID Type</th>
              <th>Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $p): ?>
            <tr>
              <td>
                <div style="display:flex;flex-direction:column;align-items:flex-start;gap:6px;">
                  <strong><?= htmlspecialchars($p['full_name']) ?></strong>
                  <?php if (!empty($p['resident_id'])): ?>
                    <span class="badge badge-resolved">In resident list</span>
                  <?php else: ?>
                    <span class="badge badge-rejected">Not in resident list</span>
                  <?php endif; ?>
                </div>
              </td>
              <td><?= htmlspecialchars($p['email']) ?></td>
              <td><?= htmlspecialchars($p['address'] ?? '—') ?></td>
              <td><?= htmlspecialchars($p['identification'] ?? '—') ?></td>
              <td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
              <td>
                <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;margin:0">
                  <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                  <button type="submit" name="action" value="verify" class="btn-primary" style="padding:8px 14px;font-size:13px">Approve</button>
                  <button type="submit" name="action" value="reject" class="btn-secondary" style="padding:8px 14px;font-size:13px">Reject</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
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
function toggleSidebar(id) {
  var sb = document.getElementById(id);
  if (sb) sb.classList.toggle('open');
}
</script>

</body>
</html>
