<?php
require_once 'config.php';
requireRole(['admin']);
$user = currentUser();

$categoryQuery = $db->query('SELECT issue_type, COUNT(*) AS count FROM civic_issues GROUP BY issue_type');
$categoryCounts = $categoryQuery->fetchAll(PDO::FETCH_KEY_PAIR);

$statusQuery = $db->query('SELECT status, COUNT(*) AS count FROM civic_issues GROUP BY status');
$statusCounts = $statusQuery->fetchAll(PDO::FETCH_KEY_PAIR);

$issuesTotal = array_sum($categoryCounts);
$resolvedCount = intval($statusCounts['Resolved'] ?? 0);
$inProgressCount = intval($statusCounts['In Progress'] ?? 0);
$pendingCount = intval($statusCounts['Open'] ?? 0) + intval($statusCounts['Pending'] ?? 0);
$resolutionPercent = $issuesTotal ? round($resolvedCount / $issuesTotal * 100) : 0;

$committeeStatsStmt = $db->query('SELECT assigned_committee AS committee, AVG(DATEDIFF(updated_at, created_at)) AS avg_days, COUNT(*) AS issues FROM civic_issues WHERE assigned_committee IS NOT NULL AND assigned_committee <> "" GROUP BY assigned_committee ORDER BY issues DESC LIMIT 5');
$committeeStats = $committeeStatsStmt->fetchAll(PDO::FETCH_ASSOC);

$registrationsStmt = $db->query("SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS count FROM users WHERE role = 'resident' AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 5 MONTH) GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)");
$registrationRows = $registrationsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$months = [];
$monthIterator = new DateTimeImmutable('first day of -5 months');
for ($i = 0; $i < 6; $i++) {
    $months[] = $monthIterator->format('M');
    $monthIterator = $monthIterator->modify('+1 month');
}

$registrationSeries = [];
foreach ($months as $monthLabel) {
    $registrationSeries[$monthLabel] = intval($registrationRows[$monthLabel] ?? 0);
}

$categories = [
    'Infrastructure' => 'Infra',
    'Waste' => 'Waste',
    'Peace & Order' => 'P&O',
    'Utility' => 'Utility',
    'Environment' => 'Env',
    'Other' => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - Barangay Portal</title>
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
    <div class="nav-item" onclick="location.href='admin_dashboard.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      System Overview
    </div>
    <div class="nav-section">Management</div>
    <div class="nav-item" onclick="location.href='user_management.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 3h10v10H3z" stroke="currentColor" fill="none" stroke-width="1.2"/></svg>
      User Management
    </div>
    <div class="nav-item active" onclick="location.href='analytics_reports.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 12h3v2H2v-2zm4-3h3v5H6V9zm4-4h3v9h-3V5z"/></svg>
      Analytics & Reports
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar" style="background:var(--red-light);color:var(--red)"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname">Admin</div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
  </div>
</div>

<div class="main" style="flex:1">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;flex-wrap:wrap">
      <div>
        <h1 class="page-title">Analytics & Reports</h1>
        <p class="page-sub">Comprehensive system performance, civic data, and legislative tracking</p>
      </div>
    </div>
  </div>

  <div class="analytics-grid">
    <div class="card">
      <div class="card-title">Issues by Category (2025)</div>
      <div class="chart-list">
        <?php foreach ($categories as $type => $label): ?>
          <?php $count = intval($categoryCounts[$type] ?? 0); ?>
          <?php $width = $issuesTotal ? max(10, round($count / $issuesTotal * 100)) : 10; ?>
          <div class="chart-item">
            <div class="chart-label"><?= htmlspecialchars($label) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $width ?>%"></div></div>
            <div class="chart-value"><?= $count ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Resolution Status (YTD)</div>
      <div class="donut-card">
        <div class="donut-chart" style="--filled: <?= $resolutionPercent ?>;">
          <div class="donut-center"><?= $resolutionPercent ?>%</div>
        </div>
        <div class="donut-legend">
          <div><span class="legend-swatch legend-resolved"></span> Resolved — <?= $resolvedCount ?></div>
          <div><span class="legend-swatch legend-progress"></span> In Progress — <?= $inProgressCount ?></div>
          <div><span class="legend-swatch legend-pending"></span> Pending — <?= $pendingCount ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Committee Response Times</div>
      <div class="mini-table">
        <div class="mini-row mini-header"><span>Committee</span><span>Avg Days</span><span>Issues</span></div>
        <?php foreach ($committeeStats as $row): ?>
          <div class="mini-row">
            <span><?= htmlspecialchars($row['committee']) ?></span>
            <span><?= round($row['avg_days'], 1) ?>d</span>
            <span><?= intval($row['issues']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Resident Registrations (Monthly)</div>
      <div class="chart-list chart-list-vertical">
        <?php $maxReg = max(1, max($registrationSeries)); ?>
        <?php foreach ($registrationSeries as $month => $count): ?>
          <div class="chart-item small">
            <div class="chart-label"><?= htmlspecialchars($month) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($count / $maxReg * 100) ?>%"></div></div>
            <div class="chart-value"><?= $count ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="report-actions">
    <button class="btn-primary">Generate Full Report (PDF)</button>
    <button class="btn-secondary">Export to CSV</button>
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
