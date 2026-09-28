<?php
require_once 'config.php';
$user = currentUser();
$redirect = $user && $user['role'] === 'admin' ? 'admin_dashboard.php' : ($user && $user['role'] === 'official' ? 'official_dashboard.php' : 'resident_dashboard.php');
header('Location: ' . $redirect);
exit;

$user = currentUser();
$canManage = $user && ($user['role'] === 'admin' || strtolower($user['email']) === 'maria.santos@barangay.gov');
$message = null;
$messageType = 'success';

$staff = $db->query('SELECT full_name, committee, email FROM users WHERE role = "official" ORDER BY full_name ASC')->fetchAll(PDO::FETCH_ASSOC);
$staffCount = count($staff);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $audience = trim($_POST['audience'] ?? 'All residents');
    $type = trim($_POST['type'] ?? 'Community Notice');

    if ($title === '' || $details === '' || $eventDate === '' || $eventTime === '' || $venue === '') {
      $message = 'Please complete the title, details, date, time, and venue.';
      $messageType = 'error';
    } else {
      $stmt = $db->prepare('INSERT INTO announcements (type, title, details, event_date, event_time, venue, audience, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([$type, $title, $details, $eventDate, $eventTime, $venue, $audience ?: 'All residents', $user['id']]);
      $message = 'Announcement added successfully.';
    }
  } elseif ($action === 'delete') {
    $announcementId = (int) ($_POST['announcement_id'] ?? 0);
    if ($announcementId > 0) {
      $stmt = $db->prepare('DELETE FROM announcements WHERE id = ?');
      $stmt->execute([$announcementId]);
      $message = 'Announcement removed from the bulletin board.';
    }
  }
}

if ((int) $db->query('SELECT COUNT(*) FROM announcements')->fetchColumn() === 0) {
  $seed = $db->prepare('INSERT INTO announcements (type, title, details, event_date, event_time, venue, audience) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $seedRows = [
    ['Community Event', 'Barangay Clean-Up Drive', 'Residents are invited to help clean the main road, drainage areas, and shared public spaces. Cleaning materials will be available at the barangay hall.', '2026-09-12', '8:00 AM - 12:00 NN', 'Assembly area: Barangay Hall', 'All residents'],
    ['Resident Meeting', 'Monthly Residents\' Assembly', 'The barangay council will discuss community concerns, upcoming projects, and the progress of reported civic issues. Residents may submit agenda items at the barangay office.', '2026-09-19', '2:00 PM - 4:00 PM', 'Barangay Covered Court', 'Residents and homeowners'],
    ['Public Service', 'Barangay Health and Assistance Day', 'Free basic health screening, consultation referrals, and assistance-desk services will be available for residents. Please bring a valid barangay ID when applicable.', '2026-09-26', '9:00 AM - 3:00 PM', 'Barangay Health Center', 'All residents']
  ];
  foreach ($seedRows as $seedRow) {
    $seed->execute($seedRow);
  }
}

$bulletins = $db->query('SELECT id, type, title, details, DATE_FORMAT(event_date, "%M %e, %Y") AS display_date, event_time, venue, audience FROM announcements ORDER BY event_date ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Announcements - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">
<button class="hamburger" onclick="toggleSidebar('sb-announcements')">☰</button>
<div class="sidebar" id="sb-announcements">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Community Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item" onclick="goToDashboard()">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Dashboard
    </div>
    <div class="nav-item active">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2V2zm1 1v10h10V3H3zm2 2h6v1H5V5zm0 2h6v1H5V7zm0 2h4v1H5V9z"/></svg>
      Announcements
    </div>
    <div class="nav-section">Legislative</div>
    <div class="nav-item" onclick="location.href='legislation.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 2h10v12H3V2zm2 2v1h6V4H5zm0 3v1h6V7H5zm0 3v1h4v-1H5z"/></svg>
      Ordinances & Resolutions
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= $user ? strtoupper(substr($user['full_name'], 0, 1)) : 'G' ?></div>
      <div>
        <?php if ($user): ?>
          <div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
          <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
        <?php else: ?>
          <div class="sb-uname">Guest visitor</div>
          <div class="sb-logout" onclick="location.href='index.php'">Sign in</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="main" style="flex:1">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap">
      <div>
        <p class="eyebrow">Office of the Barangay</p>
        <h1 class="page-title">Community Bulletin Board</h1>
        <p class="page-sub">Stay informed about upcoming events, resident meetings, public services, and announcements from Barangay 76-A leadership.</p>
      </div>
      <a href="index.php" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">← Home</a>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($canManage): ?>
    <div class="announcement-editor">
      <button type="button" class="announcement-add-button" onclick="toggleAnnouncementForm()">+ Add Announcement</button>
      <div id="announcement-form" class="announcement-form" <?= $messageType === 'error' ? '' : 'hidden' ?>>
        <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label class="form-label" for="type">Announcement type</label><select class="form-control" id="type" name="type"><option>Community Event</option><option>Resident Meeting</option><option>Public Service</option><option>Important Notice</option></select></div>
          <div class="form-group"><label class="form-label" for="title">Title</label><input class="form-control" id="title" name="title" maxlength="200" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label" for="event_date">Date</label><input class="form-control" id="event_date" name="event_date" type="date" required></div>
          <div class="form-group"><label class="form-label" for="event_time">Time</label><input class="form-control" id="event_time" name="event_time" placeholder="e.g. 8:00 AM - 12:00 NN" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label" for="venue">Venue</label><input class="form-control" id="venue" name="venue" maxlength="200" required></div>
          <div class="form-group"><label class="form-label" for="audience">For</label><input class="form-control" id="audience" name="audience" value="All residents" maxlength="120"></div>
        </div>
        <div class="form-group"><label class="form-label" for="details">Announcement details</label><textarea class="form-control" id="details" name="details" required></textarea></div>
          <button class="btn-primary" type="submit" style="width:auto;padding:12px 24px">Publish Announcement</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <div class="bulletin-heading">
    <div>
      <p class="eyebrow">What's happening</p>
      <h2 class="section-heading">Upcoming Announcements</h2>
    </div>
    <span class="bulletin-count"><?= count($bulletins) ?> upcoming notices</span>
  </div>
  <div class="bulletin-grid">
    <?php foreach ($bulletins as $bulletin): ?>
      <article class="bulletin-card">
        <div class="bulletin-card-top">
          <span class="bulletin-type"><?= htmlspecialchars($bulletin['type']) ?></span>
          <span class="bulletin-date"><?= htmlspecialchars($bulletin['display_date']) ?></span>
        </div>
        <h3><?= htmlspecialchars($bulletin['title']) ?></h3>
        <p><?= htmlspecialchars($bulletin['details']) ?></p>
        <div class="bulletin-meta"><span>Time: <?= htmlspecialchars($bulletin['event_time']) ?></span><span>Venue: <?= htmlspecialchars($bulletin['venue']) ?></span><span>For: <?= htmlspecialchars($bulletin['audience']) ?></span></div>
        <?php if ($canManage): ?>
          <form method="post" class="remove-announcement-form" onsubmit="return confirm('Remove this announcement from the bulletin board?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="announcement_id" value="<?= (int) $bulletin['id'] ?>">
            <button class="remove-announcement" type="submit">Remove announcement</button>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="announcement-hero">
    <div class="announcement-kicker">Message from the Barangay Captain</div>
    <div class="announcement-hero-content">
      <div>
        <h2>Working together for a safer, cleaner, and more responsive barangay.</h2>
        <p>Our office remains committed to listening to residents, acting on community concerns, and keeping public service transparent. Please use the portal to report issues and stay informed about barangay programs.</p>
        <div class="announcement-signature">Hon. Maria L. Santos <span>· Barangay Captain</span></div>
      </div>
      <div class="captain-seal">MS</div>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Barangay Captain</div><div class="stat-val" style="font-size:22px">1</div><div class="stat-delta">Executive office</div></div>
    <div class="stat-card"><div class="stat-label">Active Staff</div><div class="stat-val"><?= $staffCount ?></div><div class="stat-delta">Registered officials</div></div>
    <div class="stat-card"><div class="stat-label">Service</div><div class="stat-val" style="font-size:22px">Open</div><div class="stat-delta">Community access</div></div>
  </div>

  <div class="table-actions">
    <div>
      <h2 class="section-heading">Barangay Staff</h2>
      <p class="page-sub" style="margin-top:3px">Contact the appropriate committee through the barangay office.</p>
    </div>
    <input id="staff-search" class="search-input" type="search" placeholder="Search staff or committee" aria-label="Search staff or committee">
  </div>

  <div class="staff-grid" id="staff-grid">
    <?php foreach ($staff as $member): ?>
      <article class="staff-card" data-search="<?= htmlspecialchars(strtolower($member['full_name'] . ' ' . ($member['committee'] ?? '') . ' ' . $member['email'])) ?>">
        <div class="staff-avatar"><?= strtoupper(substr($member['full_name'], 0, 1)) ?></div>
        <div class="staff-info">
          <h3><?= htmlspecialchars($member['full_name']) ?></h3>
          <p class="staff-role">Barangay Official</p>
          <p class="staff-committee"><?= htmlspecialchars($member['committee'] ?: 'General Administration') ?></p>
          <a href="mailto:<?= htmlspecialchars($member['email']) ?>" class="staff-email"><?= htmlspecialchars($member['email']) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
    <p id="empty-staff" class="empty-state" hidden>No staff members match your search.</p>
  </div>
</div>
<script>
function toggleSidebar(id) {
  var sidebar = document.getElementById(id);
  if (sidebar) sidebar.classList.toggle('open');
}
function goToDashboard() {
  var role = <?= json_encode($user['role'] ?? null) ?>;
  location.href = !role ? 'index.php' : (role === 'resident' ? 'resident_dashboard.php' : (role === 'official' ? 'official_dashboard.php' : 'admin_dashboard.php'));
}
function toggleAnnouncementForm() {
  var form = document.getElementById('announcement-form');
  if (form) form.hidden = !form.hidden;
}
var search = document.getElementById('staff-search');
search.addEventListener('input', function () {
  var query = this.value.toLowerCase().trim();
  var visible = 0;
  document.querySelectorAll('.staff-card').forEach(function (card) {
    var matches = card.dataset.search.indexOf(query) !== -1;
    card.hidden = !matches;
    if (matches) visible++;
  });
  document.getElementById('empty-staff').hidden = visible !== 0;
});
</script>
</body>
</html>
