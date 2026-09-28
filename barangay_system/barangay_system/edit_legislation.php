<?php
require_once 'config.php';
requireRole(['official','admin']);
$user = currentUser();

$legislation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare('SELECT * FROM legislation WHERE id = ?');
$stmt->execute([$legislation_id]);
$legislation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$legislation) {
    header('Location: legislation.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $published_date = trim($_POST['published_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($title) || empty($category) || empty($description) || empty($published_date)) {
        $error = 'All fields are required.';
    } else {
      try {
        $documentImagePath = uploadLegislationDocument($_FILES['document_image'] ?? null);
        if ($documentImagePath) {
          $update = $db->prepare('UPDATE legislation SET title = ?, category = ?, description = ?, document_image_path = ?, published_date = ?, status = ? WHERE id = ?');
          $update->execute([$title, $category, $description, $documentImagePath, $published_date, $status, $legislation_id]);
        } else {
          $update = $db->prepare('UPDATE legislation SET title = ?, category = ?, description = ?, published_date = ?, status = ? WHERE id = ?');
          $update->execute([$title, $category, $description, $published_date, $status, $legislation_id]);
        }
        header('Location: legislation.php');
        exit;
      } catch (RuntimeException $e) {
        $error = $e->getMessage();
      }
    }
}

$portalType = 'Officials Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($legislation['type']) ?> - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex;min-height:100vh;">
<button class="hamburger" onclick="toggleSidebar('sb-edit')">☰</button>
    <div class="sidebar" id="sb-edit">
  <div class="sidebar-brand">
    <div class="sb-seal">🏛</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role"><?= htmlspecialchars($portalType) ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <div class="nav-item" onclick="location.href='official_dashboard.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
      Dashboard
    </div>
    <div class="nav-section">Legislative</div>
    <div class="nav-item active" onclick="location.href='legislation.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2V2zm1 1v10h10V3H3zm2 2h6v1H5V5zm0 2h6v1H5V7zm0 2h4v1H5V9z"/></svg>
      Legislative Database
    </div>
    <div class="nav-section">Civic Issues</div>
    <div class="nav-item" onclick="location.href='issue_status.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
      Civic Reports
    </div>
    <div class="nav-item" onclick="location.href='user_management.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2a3 3 0 100 6 3 3 0 000-6zm0 8c-2.67 0-8 1.34-8 4v1h16v-1c0-2.66-5.33-4-8-4z"/></svg>
      User Management
    </div>
    <div class="nav-item" onclick="location.href='analytics_reports.php'">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 12h2V6H2v6zm4 0h2V2H6v10zm4 0h2V9H10v3zm4 0h2V4h-2v8z"/></svg>
      Analytics
    </div>
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
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
                <div>
                    <h1 class="page-title">Edit <?= htmlspecialchars($legislation['type']) ?></h1>
                    <p class="page-sub">Update the legislative record</p>
                </div>
                <a href="legislation.php" class="btn-secondary">← Back to Database</a>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-card">
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?= htmlspecialchars($legislation['title']) ?>">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select category</option>
                    <option value="Environment" <?= $legislation['category'] === 'Environment' ? 'selected' : '' ?>>Environment</option>
                    <option value="Health" <?= $legislation['category'] === 'Health' ? 'selected' : '' ?>>Health</option>
                    <option value="Peace & Order" <?= $legislation['category'] === 'Peace & Order' ? 'selected' : '' ?>>Peace & Order</option>
                    <option value="Infrastructure" <?= $legislation['category'] === 'Infrastructure' ? 'selected' : '' ?>>Infrastructure</option>
                    <option value="Governance" <?= $legislation['category'] === 'Governance' ? 'selected' : '' ?>>Governance</option>
                    <option value="Livelihood" <?= $legislation['category'] === 'Livelihood' ? 'selected' : '' ?>>Livelihood</option>
                    <option value="Education" <?= $legislation['category'] === 'Education' ? 'selected' : '' ?>>Education</option>
                    <option value="Other" <?= $legislation['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($legislation['description']) ?></textarea>
            </div>
            <div class="form-group">
              <label for="document_image">Official Document Image</label>
              <input type="file" id="document_image" name="document_image" accept="image/jpeg,image/png,image/webp">
              <small class="form-help">
                <?= $legislation['document_image_path'] ? 'Upload a new image to replace the current attached document.' : 'Attach the scanned official document for residents and administrators.' ?>
              </small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="published_date">Published Date</label>
                    <input type="date" id="published_date" name="published_date" required value="<?= htmlspecialchars($legislation['published_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Active" <?= $legislation['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $legislation['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">Save Changes</button>
        </form>
    </div>

<script>
function toggleSidebar(id) {
  var sb = document.getElementById(id);
  if (sb) sb.classList.toggle('open');
}
</script>
</body>
</html>
