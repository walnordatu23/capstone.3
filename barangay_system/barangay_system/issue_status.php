<?php
require_once 'config.php';
requireRole(['resident', 'official', 'admin']);
$user = currentUser();

// Build query based on user role
if ($user['role'] === 'resident') {
    // Residents only see their own issues
    $where = 'i.user_id = ?';
    $params = [$user['id']];
} elseif ($user['role'] === 'official') {
  // Officials see active reports for their committee, including broadcast and legacy unassigned reports.
  $where = '(? = "All committees" OR i.assigned_committee = ? OR i.assigned_committee = "All committees" OR i.assigned_committee IS NULL)';
  $params = [$user['committee'], $user['committee']];
} else {
    // Admins see all issues
    $where = '1=1';
    $params = [];
}

$stmt = $db->prepare('SELECT i.*, u.full_name, u.photo_path AS resident_photo_path, o.full_name as official_name FROM civic_issues i LEFT JOIN users u ON i.user_id = u.id LEFT JOIN users o ON i.assigned_official_id = o.id WHERE ' . $where . ' ORDER BY i.updated_at DESC');
$stmt->execute($params);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = ['All' => count($issues), 'Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
foreach ($issues as $issue) {
    if ($issue['status'] === 'Open') {
        $counts['Pending']++;
    } elseif ($issue['status'] === 'In Progress') {
        $counts['In Progress']++;
    } elseif ($issue['status'] === 'Resolved') {
        $counts['Resolved']++;
    }
}

$selectedTab = $_GET['tab'] ?? 'All';
if (!in_array($selectedTab, ['All', 'Pending', 'In Progress', 'Resolved'])) {
    $selectedTab = 'All';
}

$filteredIssues = array_filter($issues, function ($issue) use ($selectedTab) {
    if ($selectedTab === 'All') {
        return true;
    }
    if ($selectedTab === 'Pending') {
        return $issue['status'] === 'Open';
    }
    return $issue['status'] === $selectedTab;
});

$pageTitle = $user['role'] !== 'resident' ? 'Civic Issue Reports' : 'My Reports';
$pageSubtitle = $user['role'] !== 'resident'
    ? 'Review, assign to committees, and resolve civic complaints from residents'
    : 'Track the real-time status of your submitted civic issues';
$backLink = $user['role'] === 'resident' ? 'resident_dashboard.php' : ($user['role'] === 'official' ? 'official_dashboard.php' : 'admin_dashboard.php');

function formatTicket($issue) {
    return '#2025-' . str_pad($issue['id'], 3, '0', STR_PAD_LEFT);
}

function getStatusBadge($status) {
    if ($status === 'Open') return 'badge-pending';
    if ($status === 'In Progress') return 'badge-progress';
    if ($status === 'Resolved') return 'badge-resolved';
    return 'badge-default';
}

function getStatusLabel($status) {
    if ($status === 'Open') return 'Pending';
    if ($status === 'In Progress') return 'Working On It';
  if ($status === 'Resolved') return 'Resolved';
    return $status;
}

function getPrimaryAction($issue, $role, $userCommittee = null, $userId = null) {
    if ($role === 'resident') {
        return ['label' => 'View', 'url' => 'view_issue.php?id=' . $issue['id']];
    }

    $isAssigned = !empty($issue['assigned_committee']) && $issue['assigned_committee'] !== 'Unassigned' && $issue['assigned_committee'] !== 'All committees';
    if ($isAssigned) {
        return ['label' => 'View & Update', 'url' => 'manage_issue.php?id=' . $issue['id'] . '&form=update'];
    }

    if ($issue['status'] === 'Open') {
        return ['label' => 'Assign', 'url' => 'manage_issue.php?id=' . $issue['id'] . '&form=assign'];
    }
    if ($issue['status'] === 'In Progress') {
        // Only show Update if this official is the one assigned to the issue
        if ($userId && $issue['assigned_official_id'] && $issue['assigned_official_id'] === $userId) {
            return ['label' => 'Update & Reply', 'url' => 'manage_issue.php?id=' . $issue['id'] . '&form=update', 'authorized' => true];
        }
        return ['label' => 'View', 'url' => 'view_issue.php?id=' . $issue['id']];
    }
    return ['label' => 'View', 'url' => 'view_issue.php?id=' . $issue['id']];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">

<button class="hamburger" onclick="toggleSidebar('sb-issues')">☰</button>
<div class="sidebar" id="sb-issues">
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
      <div class="nav-item active" onclick="location.href='issue_status.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
        My Reports
      </div>
      <div class="nav-item" onclick="location.href='notifications.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a3 3 0 00-3 3v2.1c0 .55-.23 1.08-.63 1.45L3 9.5v1.5h10V9.5l-1.37-1.95A1.99 1.99 0 0011 6.1V4a3 3 0 00-3-3zm0 14a2 2 0 002-2H6a2 2 0 002 2z"/></svg>
        Notifications
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
      <div class="nav-item active">
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
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="page-sub"><?= htmlspecialchars($pageSubtitle) ?></p>
      </div>
      <a href="<?= $backLink ?>" class="btn-secondary" style="display:inline-flex;align-items:center;justify-content:center">← Back</a>
    </div>
    <div class="tab-bar">
      <?php foreach (['All', 'Pending', 'In Progress', 'Resolved'] as $tab): ?>
        <a href="?tab=<?= urlencode($tab) ?>" class="tab<?= $selectedTab === $tab ? ' active' : '' ?>"><?= $tab ?> (<?= $counts[$tab] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($filteredIssues)): ?>
      <div class="card" style="padding:24px;margin:0;border-radius:0;">
        <p style="margin:0;color:var(--muted)">No reports found yet. Submit a new issue to see updates here.</p>
      </div>
    <?php else: ?>
      <div class="overflow-table">
        <table class="data-table" style="margin:0;border:none;">
          <thead>
            <tr>
              <th>TICKET</th>
              <th>ISSUE</th>
              <th>CATEGORY</th>
              <th>RESIDENT</th>
              <th>STATUS</th>
              <th>ASSIGNED TO</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredIssues as $issue): ?>
              <tr>
                <td><?= htmlspecialchars(formatTicket($issue)) ?></td>
                <td><strong><?= htmlspecialchars($issue['title']) ?></strong><br><span style="color:var(--muted);font-size:13px">Submitted <?= date('M j, Y', strtotime($issue['created_at'])) ?></span></td>
                <td><?= htmlspecialchars($issue['issue_type']) ?></td>
                <td>
                  <div class="issue-resident">
                    <?php if (!empty($issue['resident_photo_path'])): ?>
                      <img src="<?= htmlspecialchars($issue['resident_photo_path']) ?>" alt="Resident profile picture">
                    <?php else: ?>
                      <span><?= htmlspecialchars(strtoupper(substr($issue['full_name'] ?? 'R', 0, 1))) ?></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($issue['full_name'] ?? 'Resident') ?></span>
                  </div>
                </td>
                <td><span class="badge <?= getStatusBadge($issue['status']) ?>"><?= htmlspecialchars(getStatusLabel($issue['status'])) ?></span></td>
                <td><?= htmlspecialchars($issue['assigned_committee'] ?: '—') ?></td>
                <td>
                  <?php $action = getPrimaryAction($issue, $user['role'], $user['committee'], $user['id']); ?>
                  <?php $issueData = htmlspecialchars(json_encode([
                      'ticket' => formatTicket($issue),
                      'title' => $issue['title'],
                      'status' => getStatusLabel($issue['status']),
                      'issue_type' => $issue['issue_type'],
                      'location' => $issue['location'],
                      'resident' => $issue['full_name'] ?? 'Resident',
                      'committee' => $issue['assigned_committee'] ?: 'Unassigned',
                      'official' => $issue['official_name'] ?? '',
                      'created_at' => date('M d, Y \a\t H:i', strtotime($issue['created_at'])),
                      'description' => $issue['description'],
                      'photo_path' => $issue['photo_path'] ?? ''
                  ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>
                  <?php if ($action['label'] === 'View'): ?>
                    <a href="#issue-modal" class="action-link" data-issue="<?= $issueData ?>" onclick="openIssueModal(this); return false;" style="color:var(--navy);font-weight:600;text-decoration:none;white-space:nowrap;display:inline-block;padding:4px 8px;">View</a>
                  <?php else: ?>
                    <a href="<?= $action['url'] ?>" class="action-link" style="color:var(--navy);font-weight:700;text-decoration:none;white-space:nowrap;display:inline-block;background:rgba(255,152,0,0.1);border-radius:4px;padding:6px 10px;"><?= htmlspecialchars($action['label']) ?></a>
                  <?php endif; ?>
                  <?php if ($user['role'] !== 'resident'): ?>
                    · <a href="#issue-modal" class="action-link" data-issue="<?= $issueData ?>" onclick="openIssueModal(this); return false;" style="color:var(--muted);text-decoration:none;white-space:nowrap;display:inline-block;padding:4px 8px;">View</a>
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

<div class="modal-overlay" id="issue-modal" role="dialog" aria-modal="true" aria-labelledby="issue-modal-title" onclick="closeIssueModal(event)">
  <div class="modal-box">
    <button type="button" class="modal-close" aria-label="Close issue details" onclick="closeIssueModal()">×</button>
    <div class="modal-sub" id="issue-modal-ticket"></div>
    <h2 class="modal-title" id="issue-modal-title"></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 20px;">
      <span class="badge" id="issue-modal-status"></span>
      <span class="badge badge-default" id="issue-modal-type"></span>
    </div>
    <div style="display:grid;gap:12px;font-size:14px;">
      <div><strong>Location:</strong> <span id="issue-modal-location"></span></div>
      <div><strong>Reported by:</strong> <span id="issue-modal-resident"></span></div>
      <div><strong>Assigned to:</strong> <span id="issue-modal-assigned"></span></div>
      <div><strong>Submitted:</strong> <span id="issue-modal-date"></span></div>
      <div><strong>Description:</strong><p id="issue-modal-description" style="margin:6px 0 0;line-height:1.6;white-space:pre-wrap;"></p></div>
      <div id="issue-modal-photo-wrap" style="display:none;"><strong>Evidence photo:</strong><br><img id="issue-modal-photo" class="evidence-photo" style="margin-top:8px;max-width:100%;" tabindex="0" role="button" aria-label="Enlarge evidence photo" alt="Evidence photo submitted with this issue"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;margin-top:24px;">
      <a id="issue-modal-full-link" class="btn-secondary" href="#" target="_blank" rel="noopener">Open full details</a>
    </div>
  </div>
</div>

<div class="photo-lightbox" id="evidence-lightbox" role="dialog" aria-modal="true" aria-label="Evidence photo preview" aria-hidden="true" onclick="if(event.target === this) closeEvidenceLightbox();">
  <button type="button" class="photo-lightbox-close" aria-label="Close photo preview" onclick="closeEvidenceLightbox()">×</button>
  <img id="evidence-lightbox-image" alt="Enlarged evidence photo">
  <div class="photo-lightbox-caption">Click outside the image or press Escape to close</div>
</div>

<script>
function toggleSidebar(id){
  var sb=document.getElementById(id);
  if(sb)sb.classList.toggle('open');
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

document.getElementById('issue-modal-photo').addEventListener('click', function() {
  if (this.src) openEvidenceLightbox(this);
});
document.getElementById('issue-modal-photo').addEventListener('keydown', function(event) {
  if ((event.key === 'Enter' || event.key === ' ') && this.src) {
    event.preventDefault();
    openEvidenceLightbox(this);
  }
});

function openIssueModal(trigger) {
  var issue = JSON.parse(trigger.getAttribute('data-issue'));
  var modal = document.getElementById('issue-modal');
  document.getElementById('issue-modal-ticket').textContent = issue.ticket;
  document.getElementById('issue-modal-title').textContent = issue.title;
  document.getElementById('issue-modal-status').textContent = issue.status;
  document.getElementById('issue-modal-status').className = 'badge ' + (issue.status === 'Working On It' ? 'badge-progress' : issue.status === 'Resolved' ? 'badge-resolved' : 'badge-pending');
  document.getElementById('issue-modal-type').textContent = issue.issue_type;
  document.getElementById('issue-modal-location').textContent = issue.location;
  document.getElementById('issue-modal-resident').textContent = issue.resident;
  document.getElementById('issue-modal-assigned').textContent = issue.committee + (issue.official ? ' - ' + issue.official : '');
  document.getElementById('issue-modal-date').textContent = issue.created_at;
  document.getElementById('issue-modal-description').textContent = issue.description;
  document.getElementById('issue-modal-full-link').href = 'view_issue.php?id=' + encodeURIComponent(issue.ticket.replace('#2025-', ''));
  var photoWrap = document.getElementById('issue-modal-photo-wrap');
  var photo = document.getElementById('issue-modal-photo');
  photoWrap.style.display = issue.photo_path ? 'block' : 'none';
  photo.src = issue.photo_path || '';
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  modal.querySelector('.modal-close').focus();
}

function closeIssueModal(event) {
  if (event && event.target !== event.currentTarget) return;
  document.getElementById('issue-modal').classList.remove('open');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    if (document.getElementById('evidence-lightbox').classList.contains('open')) {
      closeEvidenceLightbox();
    } else {
      closeIssueModal();
    }
  }
});
</script>

</body>
</html>
