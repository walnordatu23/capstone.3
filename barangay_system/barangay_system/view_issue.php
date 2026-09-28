<?php
require_once 'config.php';
requireRole(['resident', 'official', 'admin']);
$user = currentUser();

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare('SELECT i.*, u.full_name, u.email, u.photo_path as resident_photo_path, o.full_name as official_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id LEFT JOIN users o ON i.assigned_official_id = o.id WHERE i.id = ?');
$stmt->execute([$issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    header('Location: issue_status.php');
    exit;
}

$updateStmt = $db->prepare('SELECT iu.*, u.full_name FROM issue_updates iu LEFT JOIN users u ON iu.user_id = u.id WHERE iu.issue_id = ? ORDER BY iu.created_at DESC');
$updateStmt->execute([$issue_id]);
$updates = $updateStmt->fetchAll(PDO::FETCH_ASSOC);

$statusClass = 'badge-pending';
if ($issue['status'] === 'In Progress') $statusClass = 'badge-progress';
if ($issue['status'] === 'Resolved') $statusClass = 'badge-resolved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Issue #<?= htmlspecialchars($issue['id']) ?> - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">
<button class="hamburger" onclick="toggleSidebar('sb-view')">☰</button>
<div class="sidebar" id="sb-view">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role"><?= $user['role'] === 'resident' ? 'Resident Portal' : ($user['role'] === 'official' ? 'Officials Portal' : 'Admin Portal') ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <?php if ($user['role'] === 'resident'): ?>
      <div class="nav-item" onclick="location.href='resident_dashboard.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        Dashboard
      </div>
      <div class="nav-section">Civic Issues</div>
      <div class="nav-item" onclick="location.href='report_issue.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
        Submit Issue
      </div>
      <div class="nav-item active" onclick="location.href='issue_status.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
        My Reports
      </div>
    <?php else: ?>
      <div class="nav-item" onclick="location.href='official_dashboard.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        Dashboard
      </div>
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
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar" style="background:var(--teal-light);color:var(--teal)"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
  </div>
</div>

<div class="main" style="flex:1; padding:28px;">
  <div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px">
      <div>
        <h1 class="page-title">View Issue #<?= htmlspecialchars($issue['id']) ?></h1>
        <p class="page-sub">View the request details and current status</p>
      </div>
      <a href="issue_status.php" class="btn-secondary">← Back to Reports</a>
    </div>
  </div>

  <div class="card">
    <h2 style="margin-top:0;"><?= htmlspecialchars($issue['title']) ?></h2>
    <div style="display:grid;gap:14px;margin-top:18px;">
      <div><strong>Status:</strong> <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($issue['status']) ?></span></div>
      <div><strong>Issue Type:</strong> <?= htmlspecialchars($issue['issue_type']) ?></div>
      <div class="issue-location"><strong>Issue Area / Location:</strong><span><?= htmlspecialchars($issue['location']) ?></span></div>
      <div class="reporter-profile">
        <?php if (!empty($issue['resident_photo_path'])): ?><img src="<?= htmlspecialchars($issue['resident_photo_path']) ?>" alt="Resident profile picture">
        <?php else: ?><span><?= htmlspecialchars(strtoupper(substr($issue['full_name'] ?? 'R', 0, 1))) ?></span><?php endif; ?>
        <div><strong>Reported By:</strong><br><?= htmlspecialchars($issue['full_name'] ?? 'Resident') ?> (<?= htmlspecialchars($issue['email'] ?? 'N/A') ?>)</div>
      </div>
      <div><strong>Assigned To:</strong> 
        <?php if ($issue['assigned_committee']): ?>
          <?= htmlspecialchars($issue['assigned_committee']) ?> 
          <?php if ($issue['official_name']): ?>
            - <em><?= htmlspecialchars($issue['official_name']) ?></em>
          <?php endif; ?>
        <?php else: ?>
          Unassigned
        <?php endif; ?>
      </div>
      <div><strong>Date Submitted:</strong> <?= date('M d, Y \\a\\t H:i', strtotime($issue['created_at'])) ?></div>
      <div><strong>Last Updated:</strong> <?= date('M d, Y \\a\\t H:i', strtotime($issue['updated_at'])) ?></div>
      <div><strong>Description:</strong><br><?= nl2br(htmlspecialchars($issue['description'])) ?></div>
      <?php if (!empty($issue['photo_path'])): ?>
        <div class="evidence-photo-block"><strong>Evidence Photo:</strong><a href="<?= htmlspecialchars($issue['photo_path']) ?>" onclick="openEvidenceLightbox(document.getElementById('view-issue-evidence-photo')); return false;"><img id="view-issue-evidence-photo" class="evidence-photo" src="<?= htmlspecialchars($issue['photo_path']) ?>" alt="Photo submitted with this civic issue"></a><a class="evidence-photo-link" href="<?= htmlspecialchars($issue['photo_path']) ?>" target="_blank" rel="noopener">Open full-size photo</a></div>
      <?php else: ?>
        <div class="evidence-missing"><strong>Evidence Photo:</strong><span>No evidence photo was submitted with this report.</span></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($updates)): ?>
    <div class="card" style="margin-top:20px;">
      <h3 style="margin-top:0;">Status Updates & Replies from Barangay</h3>
      <p style="color:var(--muted);font-size:13px;margin-bottom:16px;">Track the progress of your submitted issue and read updates from the assigned barangay staff.</p>
      <?php foreach ($updates as $update): ?>
        <div style="padding:16px;border:1px solid #dfe3ea;border-radius:12px;margin-bottom:12px;background:#f8f9fb;">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
            <strong><?= htmlspecialchars($update['full_name'] ?: 'Barangay Staff') ?></strong>
            <span class="badge <?= $update['status'] === 'In Progress' ? 'badge-progress' : ($update['status'] === 'Resolved' ? 'badge-resolved' : 'badge-pending') ?>"><?= $update['status'] === 'In Progress' ? 'Working On It' : ($update['status'] === 'Resolved' ? 'Resolved' : htmlspecialchars($update['status'])) ?></span>
          </div>
          <div style="color:var(--muted);font-size:13px;margin-bottom:12px;">
            <?= date('M d, Y \\a\\t H:i', strtotime($update['created_at'])) ?>
          </div>
          <?php if (!empty($update['notes'])): ?>
            <div style="line-height:1.6;white-space:pre-wrap;margin-bottom:12px;padding:12px;background:white;border-radius:6px;border-left:3px solid var(--teal);">
              <?= nl2br(htmlspecialchars($update['notes'])) ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($update['photo_path'])): ?>
            <div style="margin-top:12px;">
              <strong style="font-size:12px;color:var(--muted);">Proof Photo:</strong><br>
              <a href="<?= htmlspecialchars($update['photo_path']) ?>" target="_blank" style="display:inline-block;margin-top:8px;padding:8px 12px;background:var(--navy);color:white;border-radius:4px;text-decoration:none;font-size:13px;">View Photo</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="photo-lightbox" id="evidence-lightbox" role="dialog" aria-modal="true" aria-label="Evidence photo preview" aria-hidden="true" onclick="if(event.target === this) closeEvidenceLightbox();">
  <button type="button" class="photo-lightbox-close" aria-label="Close photo preview" onclick="closeEvidenceLightbox()">×</button>
  <img id="evidence-lightbox-image" alt="Enlarged evidence photo">
  <div class="photo-lightbox-caption">Click outside the image or press Escape to close</div>
</div>

<script>
function toggleSidebar(id) {
  var sb = document.getElementById(id);
  if (sb) sb.classList.toggle('open');
}

var evidenceLightboxTrigger = null;
function openEvidenceLightbox(photo) {
  var lightbox = document.getElementById('evidence-lightbox');
  var preview = document.getElementById('evidence-lightbox-image');
  evidenceLightboxTrigger = photo;
  lightbox.dataset.previousOverflow = document.body.style.overflow;
  preview.src = photo.currentSrc || photo.src;
  preview.alt = photo.alt || 'Enlarged evidence photo';
  lightbox.classList.add('open');
  lightbox.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  lightbox.querySelector('.photo-lightbox-close').focus();
}

function closeEvidenceLightbox() {
  var lightbox = document.getElementById('evidence-lightbox');
  if (!lightbox.classList.contains('open')) return;
  lightbox.classList.remove('open');
  lightbox.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = lightbox.dataset.previousOverflow || '';
  document.getElementById('evidence-lightbox-image').src = '';
  if (evidenceLightboxTrigger) evidenceLightboxTrigger.focus();
  evidenceLightboxTrigger = null;
}

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') closeEvidenceLightbox();
});
</script>
</body>
</html>
