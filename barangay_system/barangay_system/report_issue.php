<?php
require_once 'config.php';
requireRole(['resident']);
$user = currentUser();

$issueType = $_POST['issue_type'] ?? '';
$urgency = $_POST['urgency'] ?? 'Normal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $issue_type = trim($_POST['issue_type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (!in_array($location, ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5'], true)) {
      $location = '';
    }
    if ($title === '') {
        $title = $issue_type !== '' ? $issue_type : 'Community issue';
        if ($location !== '') {
            $title .= ' in ' . $location;
        } elseif (mb_strlen($description) > 0) {
            $title = mb_substr($description, 0, 60);
        }
    }
    $photo = $_FILES['photo'] ?? null;
    $photoPath = '';
    $photoError = '';

    if (!$photo || !$photo['tmp_name']) {
      $photoError = 'An evidence photo is required for every civic issue report.';
    } elseif ($photo && $photo['tmp_name']) {
        // Validate file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($photo['size'] > $maxFileSize) {
            $photoError = "Photo file is too large. Maximum size is 5MB.";
        }
        // Validate the actual file MIME type, not only the browser-provided type.
        elseif (!in_array((new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            $photoError = "Only image files (JPEG, PNG, GIF, WebP) are allowed.";
        }
        else {
          $targetDir = __DIR__ . '/assets/uploads/issues';
          $webTargetDir = 'assets/uploads/issues';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
          $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
          $fileName = 'issue-' . $user['id'] . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
          $photoPath = $webTargetDir . '/' . $fileName;
          if (!move_uploaded_file($photo['tmp_name'], $targetDir . '/' . $fileName)) {
                $photoError = "Failed to upload photo. Please try again.";
                $photoPath = '';
            }
        }
    }

    if ($title && $issue_type && $location && $description && !$photoError) {
      setFlash('Your civic issue has been submitted successfully.', 'success');
      $committeeMap = [
        'Infrastructure' => 'Infrastructure',
        'Health' => 'Health',
        'Peace and Order' => 'Peace & Order',
        'Environment' => 'Environment',
        'Livelihood' => 'Livelihood',
        'Legislative' => 'Legislative',
        'Other' => 'All committees'
      ];
      $assigned_committee = $committeeMap[$issue_type] ?? 'All committees';
      $insert = $db->prepare('INSERT INTO civic_issues (user_id, title, issue_type, location, description, photo_path, status, assigned_committee, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
      $insert->execute([$user['id'], $title, $issue_type, $location, $description, $photoPath, 'Open', $assigned_committee]);
      header('Location: issue_status.php');
      exit;
    } elseif ($photoError) {
      setFlash("Issue was not submitted because the evidence photo failed: $photoError", 'error');
    } else {
        setFlash('Please complete all required fields.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Civic Issue - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">

<button class="hamburger" onclick="toggleSidebar('sb-resident')">☰</button>
<div class="sidebar" id="sb-resident">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role">Resident Portal</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
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
    <div class="nav-item active" onclick="location.href='report_issue.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
      Submit Issue
    </div>
    <div class="nav-item" onclick="location.href='issue_status.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
      My Reports
    </div>
    <div class="nav-item" onclick="location.href='notifications.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a3 3 0 00-3 3v2.1c0 .55-.23 1.08-.63 1.45L3 9.5v1.5h10V9.5l-1.37-1.95A1.99 1.99 0 0011 6.1V4a3 3 0 00-3-3zm0 14a2 2 0 002-2H6a2 2 0 002 2z"/></svg>
      Notifications
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
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
      <div>
        <h1 class="page-title">Submit a Civic Issue</h1>
        <p class="page-sub">Report concerns to your barangay officials</p>
      </div>
      <a href="resident_dashboard.php" class="btn-secondary" style="display:inline-flex;align-items:center;justify-content:center">← Back</a>
    </div>
  </div>

  <?php if ($message = flashMessage()): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label>Issue Category</label>
      <div class="issue-grid">
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Infrastructure" <?= $issueType === 'Infrastructure' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">🏗</span>
            <span class="option-title">Infrastructure</span>
            <span class="option-sub">Roads, drainage, and public works</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Health" <?= $issueType === 'Health' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">🏥</span>
            <span class="option-title">Health</span>
            <span class="option-sub">Health services and wellness programs</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Peace and Order" <?= $issueType === 'Peace and Order' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">🚔</span>
            <span class="option-title">Peace &amp; Order</span>
            <span class="option-sub">Security, noise, and public safety</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Environment" <?= $issueType === 'Environment' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">🌿</span>
            <span class="option-title">Environment</span>
            <span class="option-sub">Flooding, pollution, and green spaces</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Livelihood" <?= $issueType === 'Livelihood' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">💼</span>
            <span class="option-title">Livelihood</span>
            <span class="option-sub">Employment and business concerns</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Legislative" <?= $issueType === 'Legislative' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">📜</span>
            <span class="option-title">Legislative</span>
            <span class="option-sub">Ordinances and governance matters</span>
          </div>
        </label>
        <label class="issue-option">
          <input type="radio" name="issue_type" value="Other" <?= $issueType === 'Other' ? 'checked' : '' ?> required>
          <div class="option-content">
            <span class="option-icon">📋</span>
            <span class="option-title">Other</span>
            <span class="option-sub">Any other community concern</span>
          </div>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label for="description">Detailed Description *</label>
      <textarea id="description" name="description" placeholder="Describe the issue in detail — what, when, and how it affects the community..." rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="location">Location / Purok *</label>
        <select id="location" name="location" required>
          <option value="" disabled <?= empty($_POST['location']) ? 'selected' : '' ?>>Select a purok</option>
          <?php foreach (['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5'] as $purok): ?>
            <option value="<?= htmlspecialchars($purok) ?>" <?= ($_POST['location'] ?? '') === $purok ? 'selected' : '' ?>><?= htmlspecialchars($purok) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="urgency">Urgency Level</label>
        <select id="urgency" name="urgency">
          <option value="Normal" <?= $urgency === 'Normal' ? 'selected' : '' ?>>Normal</option>
          <option value="High" <?= $urgency === 'High' ? 'selected' : '' ?>>High</option>
          <option value="Urgent" <?= $urgency === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
        </select>
      </div>
    </div>

    <label class="upload-zone">
      <span>Upload evidence photo (JPG, PNG, GIF, or WEBP · Max 5MB) — Required</span>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" required>
    </label>

    <button type="submit" class="btn-primary">Submit Report</button>
  </form>
</div>

<script>
function toggleSidebar(id){
  var sb=document.getElementById(id);
  if(sb)sb.classList.toggle('open');
}
</script>

</body>
</html>
