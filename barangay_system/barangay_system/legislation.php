<?php
require_once 'config.php';
requireRole(['resident','official','admin']);
$user = currentUser();

$search = trim($_GET['q'] ?? '');
$tab = trim($_GET['tab'] ?? 'all');
$typeFilter = trim($_GET['type'] ?? '');
$isResident = $user['role'] === 'resident';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_legislation') {
  if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized action.');
  }

  $legislationId = isset($_POST['legislation_id']) ? (int)$_POST['legislation_id'] : 0;
  if ($legislationId > 0) {
    $delete = $db->prepare('DELETE FROM legislation WHERE id = ?');
    $delete->execute([$legislationId]);
  }

  $redirect = 'legislation.php';
  $query = [];
  if ($search !== '') {
    $query['q'] = $search;
  }
  if ($tab !== 'all') {
    $query['tab'] = $tab;
  }
  if ($typeFilter !== '') {
    $query['type'] = $typeFilter;
  }
  if ($query) {
    $redirect .= '?' . http_build_query($query);
  }
  header('Location: ' . $redirect);
  exit;
}

$sql = 'SELECT * FROM legislation WHERE 1=1';
$params = [];

if ($search) {
    $sql .= ' AND (title LIKE ? OR description LIKE ? OR category LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($tab === 'ordinance') {
    $sql .= ' AND type = "Ordinance"';
} elseif ($tab === 'resolution') {
    $sql .= ' AND type = "Resolution"';
}
if ($typeFilter) {
    $sql .= ' AND type = ?';
    $params[] = $typeFilter;
}
$sql .= ' ORDER BY published_date DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = [
    'all' => $db->query('SELECT COUNT(*) FROM legislation')->fetchColumn(),
    'ordinance' => $db->query('SELECT COUNT(*) FROM legislation WHERE type = "Ordinance"')->fetchColumn(),
    'resolution' => $db->query('SELECT COUNT(*) FROM legislation WHERE type = "Resolution"')->fetchColumn(),
];

$portalType = $user['role'] === 'resident' ? 'Resident Portal' : 'Officials Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordinances & Resolutions - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">

<button class="hamburger" onclick="toggleSidebar('sb-legis')">☰</button>
<div class="sidebar" id="sb-legis">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role"><?= $portalType ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item" onclick="location.href='resident_dashboard.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Dashboard
    </div>
    <div class="nav-section">Legislative</div>
    <div class="nav-item active">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2V2zm1 1v10h10V3H3zm2 2h6v1H5V5zm0 2h6v1H5V7zm0 2h4v1H5V9z"/></svg>
      Ordinances & Resolutions
    </div>
    <div class="nav-section">Civic Issues</div>
    <div class="nav-item" onclick="location.href='report_issue.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
      Submit Issue
    </div>
    <div class="nav-item" onclick="location.href='issue_status.php'">
      <svg class="nav-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
      My Reports
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
    <h1 class="page-title">Legislative Database</h1>
    <p class="page-sub">Upload, categorize, and manage ordinances and resolutions</p>
  </div>

  <div class="legislation-actions">
    <div class="card action-card">
      <div class="action-card-row">
        <?php if (!$isResident): ?>
          <div class="action-buttons">
            <a href="upload_ordinance.php" class="btn-primary">+ Upload Ordinance</a>
            <a href="add_resolution.php" class="btn-secondary">+ Add Resolution</a>
          </div>
        <?php endif; ?>
        <form method="get" class="table-actions" style="flex:1;min-width:0;">
          <input type="search" name="q" placeholder="Search..." class="search-input" value="<?= htmlspecialchars($search) ?>" />
          <button type="submit" class="search-btn">Search</button>
          <?php if (!empty($search)): ?>
            <a href="legislation.php" class="btn-secondary" style="margin-left:10px;display:inline-block;text-decoration:none;">← Back</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($records)): ?>
      <div class="card" style="padding:32px;margin:0;border-radius:0;">
        <p style="margin:0;color:var(--muted);">No legislation records found.</p>
      </div>
    <?php else: ?>
      <div class="overflow-table">
        <table class="data-table" style="margin:0;border:none;">
          <thead>
            <tr>
              <th>NUMBER</th>
              <th>TITLE</th>
              <th>TYPE</th>
              <th>COMMITTEE</th>
              <th>DATE</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $record): ?>
              <?php
                $code = $record['type'] === 'Resolution' ? 'RES' : 'ORD';
                $year = date('Y', strtotime($record['published_date']));
                $idCode = sprintf('%s-%s-%02d', $code, $year, $record['id']);
              ?>
              <tr>
                <td><?= htmlspecialchars($idCode) ?></td>
                <td><?= htmlspecialchars($record['title']) ?></td>
                <td><span class="badge <?= $record['type'] === 'Ordinance' ? 'badge-ordinance' : 'badge-resolution' ?>"><?= htmlspecialchars($record['type']) ?></span></td>
                <td><?= htmlspecialchars($record['category'] ?: 'General') ?></td>
                <td><?= date('M j, Y', strtotime($record['published_date'])) ?></td>
                <td>
                  <?php if (!$isResident): ?>
                    <a href="edit_legislation.php?id=<?= $record['id'] ?>" style="color:var(--navy);font-weight:600;text-decoration:none;">Edit</a> ·
                  <?php endif; ?>
                  <a href="view_legislation.php?id=<?= $record['id'] ?>" style="color:var(--navy);font-weight:600;text-decoration:none;">View</a><?php if ($user['role'] === 'admin'): ?>
                    · <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('Remove this <?= htmlspecialchars(strtolower($record['type']), ENT_QUOTES) ?> from the legislative database?');">
                      <input type="hidden" name="action" value="remove_legislation">
                      <input type="hidden" name="legislation_id" value="<?= (int)$record['id'] ?>">
                      <button type="submit" class="table-remove-action">Remove</button>
                    </form>
                  <?php endif; ?>
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
