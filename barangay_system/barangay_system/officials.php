<?php
require_once 'config.php';

$user = currentUser();
$officials = $db->query("SELECT full_name, email, committee, position, phone, address, photo_path FROM users WHERE role = 'official' ORDER BY CASE WHEN email = 'maria.santos@barangay.gov' THEN 0 ELSE 1 END, committee, full_name")->fetchAll(PDO::FETCH_ASSOC);
$captain = $officials[0] ?? null;
$members = array_slice($officials, 1);

function officialInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }
    return $initials;
}

function dashboardLink(?array $user): string {
    if (!$user) return 'index.php';
    return $user['role'] === 'resident' ? 'resident_dashboard.php' : ($user['role'] === 'official' ? 'official_dashboard.php' : 'admin_dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Officials - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">
<button class="hamburger" onclick="toggleSidebar('sb-officials')">☰</button>
<div class="sidebar" id="sb-officials">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Community Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item" onclick="location.href='<?= htmlspecialchars(dashboardLink($user)) ?>'">▦ &nbsp; Dashboard</div>
    <div class="nav-item active">♙ &nbsp; Barangay Officials</div>
    <div class="nav-section">Legislative</div>
    <div class="nav-item" onclick="location.href='legislation.php'">▤ &nbsp; Ordinances & Resolutions</div>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= $user ? htmlspecialchars(officialInitials($user['full_name'])) : 'G' ?></div>
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

<main class="main officials-page">
  <div class="page-header">
    <div class="officials-header">
      <div>
        <p class="eyebrow">Office of the Barangay</p>
        <h1 class="page-title">Barangay Officials</h1>
        <p class="page-sub">Meet the people serving Barangay 76-A and find the right committee for your concern.</p>
      </div>
      <a href="<?= htmlspecialchars(dashboardLink($user)) ?>" class="btn-secondary">← Back</a>
    </div>
  </div>

  <?php if ($captain): ?>
    <section class="official-captain">
      <div class="official-captain-copy">
        <p class="eyebrow">Executive Office</p>
        <h2><?= htmlspecialchars($captain['position'] ?: 'Barangay Captain') ?></h2>
        <h3><?= htmlspecialchars($captain['full_name']) ?></h3>
        <p class="official-intro">Leading local service, public programs, and community coordination for Barangay 76-A.</p>
        <div class="official-contact-list">
          <a href="mailto:<?= htmlspecialchars($captain['email']) ?>">✉ <?= htmlspecialchars($captain['email']) ?></a>
          <a href="tel:<?= htmlspecialchars($captain['phone'] ?: '') ?>">☎ <?= htmlspecialchars($captain['phone'] ?: 'Contact through Barangay Hall') ?></a>
            <span>⌖ <?= htmlspecialchars($captain['address'] ?: 'Barangay Hall, Barangay 76-A') ?></span>
        </div>
      </div>
      <div class="official-photo official-photo-large">
        <?php if (!empty($captain['photo_path'])): ?>
          <img src="<?= htmlspecialchars($captain['photo_path']) ?>" alt="<?= htmlspecialchars($captain['full_name']) ?>">
        <?php else: ?>
          <span><?= htmlspecialchars(officialInitials($captain['full_name'])) ?></span>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <div class="officials-section-heading">
    <div>
      <p class="eyebrow">Committees and offices</p>
      <h2 class="section-heading">Barangay Council Members</h2>
    </div>
    <span class="official-count"><?= count($officials) ?> officials</span>
  </div>

  <section class="official-grid">
    <?php foreach ($members as $member): ?>
      <article class="official-card">
        <div class="official-photo">
          <?php if (!empty($member['photo_path'])): ?>
            <img src="<?= htmlspecialchars($member['photo_path']) ?>" alt="<?= htmlspecialchars($member['full_name']) ?>">
          <?php else: ?>
            <span><?= htmlspecialchars(officialInitials($member['full_name'])) ?></span>
          <?php endif; ?>
        </div>
        <div class="official-card-body">
          <p class="official-committee"><?= htmlspecialchars($member['committee'] ?: 'General Administration') ?></p>
          <h3><?= htmlspecialchars($member['full_name']) ?></h3>
          <p class="official-position"><?= htmlspecialchars($member['position'] ?: 'Barangay Official') ?></p>
          <div class="official-details">
            <a href="mailto:<?= htmlspecialchars($member['email']) ?>">✉ <?= htmlspecialchars($member['email']) ?></a>
            <a href="tel:<?= htmlspecialchars($member['phone'] ?: '') ?>">☎ <?= htmlspecialchars($member['phone'] ?: 'Contact through Barangay Hall') ?></a>
            <span>⌖ <?= htmlspecialchars($member['address'] ?: 'Barangay Hall, Barangay 76-A') ?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<script>
function toggleSidebar(id) {
  var sidebar = document.getElementById(id);
  if (sidebar) sidebar.classList.toggle('open');
}
</script>
</body>
</html>
