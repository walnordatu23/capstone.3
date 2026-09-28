<?php
require_once 'config.php';
requireRole(['admin']);
$user = currentUser();

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $userId = (int) ($_POST['user_id'] ?? 0);
  if ($action === 'approve') {
    $match = $db->prepare('SELECT 1 FROM users u JOIN residents r ON LOWER(TRIM(u.full_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(r.name)) COLLATE utf8mb4_unicode_ci WHERE u.id = ? AND u.role = "resident" LIMIT 1');
    $match->execute([$userId]);
    if (!$match->fetchColumn()) {
      $message = 'This registration cannot be approved because the name is not in the official resident list. Ask the registrant to request inclusion first.';
      $messageType = 'error';
    } else {
      $approve = $db->prepare('UPDATE users SET residency_verified = 1 WHERE id = ? AND role = "resident"');
      $approve->execute([$userId]);
      $message = 'Resident registration approved.';
    }
  } elseif ($action === 'reject') {
    $reject = $db->prepare('UPDATE users SET residency_verified = 2, rejection_reason = ? WHERE id = ? AND role = "resident" AND residency_verified = 0');
    $reject->execute(['Name not found in official resident list.', $userId]);
    $message = 'Resident registration rejected.';
    $messageType = 'error';
  }
}

$search = trim($_GET['q'] ?? '');
$registryFilter = $_GET['filter'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$registryWhere = [];
$registryParams = [];
if ($search !== '') {
  $registryWhere[] = '(name LIKE ? OR contact_number LIKE ?)';
  $registryParams[] = "%{$search}%";
  $registryParams[] = "%{$search}%";
}
if ($registryFilter === 'young') {
  $registryWhere[] = 'age < 30';
} elseif ($registryFilter === 'senior') {
  $registryWhere[] = 'age >= 60';
}
$registryCondition = $registryWhere ? ' WHERE ' . implode(' AND ', $registryWhere) : '';
$countStmt = $db->prepare('SELECT COUNT(*) FROM residents' . $registryCondition);
$countStmt->execute($registryParams);
$registryTotal = (int) $countStmt->fetchColumn();
$pageCount = max(1, (int) ceil($registryTotal / $perPage));
$page = min($page, $pageCount);
$residentStmt = $db->prepare('SELECT resident_id, name, contact_number, age FROM residents' . $registryCondition . ' ORDER BY name LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$residentStmt->execute($registryParams);
$residents = $residentStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingStmt = $db->query('SELECT u.id, u.full_name, u.email, u.phone, u.address, u.created_at, r.resident_id FROM users u LEFT JOIN residents r ON LOWER(TRIM(u.full_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(r.name)) COLLATE utf8mb4_unicode_ci WHERE u.role = "resident" AND u.residency_verified = 0 ORDER BY u.created_at DESC');
$pendingRegistrations = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count($pendingRegistrations);
$registryTotalCount = (int) $db->query('SELECT COUNT(*) FROM residents')->fetchColumn();
$approvedCount = (int) $db->query('SELECT COUNT(*) FROM users WHERE role = "resident" AND residency_verified = 1')->fetchColumn();
$unlistedCount = count(array_filter($pendingRegistrations, static function ($registration) { return empty($registration['resident_id']); }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Barangay Portal</title>
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
    <div class="nav-item active" onclick="location.href='user_management.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 3h10v10H3z" stroke="currentColor" fill="none" stroke-width="1.2"/></svg>
      User Management
    </div>
    <div class="nav-item" onclick="location.href='analytics_reports.php'">
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
        <h1 class="page-title">User Management</h1>
        <p class="page-sub">Authenticate, manage, and control role-based access for all users</p>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="official_register_new.php" class="btn-primary" style="white-space:nowrap">+ Add Official</a>
        <a href="#pending-registrations" class="btn-secondary">Pending Registrations</a>
      </div>
    </div>
  </div>

  <?php if ($message): ?><div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Official Registry</div><div class="stat-val"><?= $registryTotalCount ?></div><div class="stat-delta">Registered residents</div></div>
    <div class="stat-card"><div class="stat-label">Approved Accounts</div><div class="stat-val" style="color:var(--teal)"><?= $approvedCount ?></div><div class="stat-delta">Verified portal users</div></div>
    <div class="stat-card"><div class="stat-label">Pending Registrations</div><div class="stat-val" style="color:var(--gold)"><?= $pendingCount ?></div><div class="stat-delta">Awaiting review</div></div>
    <div class="stat-card"><div class="stat-label">Not Listed</div><div class="stat-val" style="color:var(--red)"><?= $unlistedCount ?></div><div class="stat-delta">Need registry inclusion</div></div>
  </div>

  <div class="card">
    <div class="card-title">Official Resident List</div>
    <form method="get" class="table-actions" style="margin-bottom:16px">
      <input type="search" name="q" class="search-input" placeholder="Search by name or contact number" value="<?= htmlspecialchars($search) ?>" style="max-width:420px">
      <select name="filter" class="form-control" style="max-width:180px" onchange="this.form.submit()">
        <option value="all" <?= $registryFilter === 'all' ? 'selected' : '' ?>>All ages</option>
        <option value="young" <?= $registryFilter === 'young' ? 'selected' : '' ?>>Under 30</option>
        <option value="senior" <?= $registryFilter === 'senior' ? 'selected' : '' ?>>60 and above</option>
      </select>
      <button class="search-btn" type="submit">Search</button>
    </form>
    <div class="overflow-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th><th>Resident Name</th><th>Contact Number</th><th>Age</th><th>Registry Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$residents): ?><tr><td colspan="5">No residents match the current search or filter.</td></tr><?php endif; ?>
          <?php foreach ($residents as $resident): ?><tr><td><?= (int) $resident['resident_id'] ?></td><td><strong><?= htmlspecialchars($resident['name']) ?></strong></td><td><?= htmlspecialchars($resident['contact_number']) ?></td><td><?= (int) $resident['age'] ?></td><td><span class="badge badge-resolved">Official record</span></td></tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;gap:12px;flex-wrap:wrap"><span style="color:var(--muted);font-size:13px">Page <?= $page ?> of <?= $pageCount ?> · <?= $registryTotal ?> matching residents</span><div style="display:flex;gap:8px"><?php if ($page > 1): ?><a class="btn-secondary" style="padding:7px 12px" href="?q=<?= urlencode($search) ?>&filter=<?= urlencode($registryFilter) ?>&page=<?= $page - 1 ?>">Previous</a><?php endif; ?><?php if ($page < $pageCount): ?><a class="btn-secondary" style="padding:7px 12px" href="?q=<?= urlencode($search) ?>&filter=<?= urlencode($registryFilter) ?>&page=<?= $page + 1 ?>">Next</a><?php endif; ?></div></div>
  </div>

  <div class="card" id="pending-registrations">
    <div class="card-title">Pending User Registrations</div>
    <p class="page-sub" style="margin:0 0 16px">Only registrations whose name matches the official resident list can be approved. Unlisted registrants must request inclusion from the barangay office.</p>
    <div class="overflow-table"><table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Contact</th><th>Registry Match</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
      <?php if (!$pendingRegistrations): ?><tr><td colspan="6">No pending registrations.</td></tr><?php endif; ?>
      <?php foreach ($pendingRegistrations as $registration): ?><tr><td><strong><?= htmlspecialchars($registration['full_name']) ?></strong></td><td><?= htmlspecialchars($registration['email']) ?></td><td><?= htmlspecialchars($registration['phone'] ?: '—') ?></td><td><?php if ($registration['resident_id']): ?><span class="badge badge-resolved">Name found</span><?php else: ?><span class="badge badge-rejected">Not in resident list</span><?php endif; ?></td><td><?= date('M d, Y', strtotime($registration['created_at'])) ?></td><td><form method="post" style="display:flex;gap:6px;flex-wrap:wrap;margin:0"><input type="hidden" name="user_id" value="<?= (int) $registration['id'] ?>"><button class="btn-primary" type="submit" name="action" value="approve" style="padding:7px 10px" <?= !$registration['resident_id'] ? 'disabled title="Request registry inclusion first"' : '' ?>>Approve</button><button class="btn-secondary" type="submit" name="action" value="reject" style="padding:7px 10px">Reject</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
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
