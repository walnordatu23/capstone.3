<?php
require_once 'config.php';

function getLegislationImageGallery($title, $fallbackPath = '') {
    $rootDir = dirname(__DIR__);
    $folderMap = [
        'Waste Segregation Compliance Ordinance' => 'waste',
        'Resolution on Community Health Programs' => 'health',
        'Curfew Hours for Minors Ordinance' => 'Curfew',
        'Barangay Livelihood Support Program' => 'livelihood',
        'Resolution Supporting Anti-Plastic Campaign' => 'plastic',
    ];

    $images = [];
    $folderName = $folderMap[$title] ?? null;

    if ($folderName) {
        $folderPath = $rootDir . DIRECTORY_SEPARATOR . $folderName;
        if (is_dir($folderPath)) {
            $files = glob($folderPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
            if (is_array($files)) {
                foreach ($files as $file) {
                    $relative = str_replace('\\', '/', str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file));
                    $images[] = '/barangay_system/' . ltrim($relative, '/');
                }
            }
        }
    }

    if (empty($images) && $fallbackPath !== '') {
        $images[] = $fallbackPath;
    }

    return $images;
}

requireRole(['resident', 'official', 'admin']);
$user = currentUser();

$legislation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare('SELECT * FROM legislation WHERE id = ?');
$stmt->execute([$legislation_id]);
$legislation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$legislation) {
    header('Location: legislation.php');
    exit;
}

$legislationType = trim((string) ($legislation['type'] ?? 'Ordinance'));
$legislationStatus = trim((string) ($legislation['status'] ?? 'Active'));
$legislationCategory = trim((string) ($legislation['category'] ?? 'General'));
$legislationDescription = trim((string) ($legislation['description'] ?? ''));
$documentPath = trim((string) ($legislation['document_image_path'] ?? ''));
$legislationGallery = getLegislationImageGallery($legislation['title'], $documentPath);
$publishedDate = $legislation['published_date'] ? date('F d, Y', strtotime($legislation['published_date'])) : 'Not specified';
$badgeClass = $legislationType === 'Ordinance' ? 'badge-ordinance' : 'badge-resolution';
$statusClass = $legislationStatus === 'Active' ? 'badge-resolved' : ($legislationStatus === 'Inactive' ? 'badge-pending' : 'badge-progress');
$ordinanceNumber = sprintf('%s-%s-%02d', strtoupper(substr($legislationType, 0, 3)), date('Y', strtotime($legislation['published_date'] ?? 'now')), (int) $legislation['id']);
$portalLabel = $user['role'] === 'resident' ? 'Resident Portal' : ($user['role'] === 'official' ? 'Officials Portal' : 'Admin Portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($legislation['title'], ENT_QUOTES, 'UTF-8') ?> - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260922-ordinance-view">
</head>
<body class="ordinance-layout">
<button class="hamburger" onclick="toggleSidebar('sb-view')">☰</button>
<div class="sidebar" id="sb-view">
  <div class="sidebar-brand">
    <div class="sb-seal">⚖</div>
    <div class="sb-name">Barangay 76-A</div>
    <div class="sb-role"><?= htmlspecialchars($portalLabel, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <?php if ($user['role'] === 'resident'): ?>
      <div class="nav-item" onclick="location.href='resident_dashboard.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        Dashboard
      </div>
      <div class="nav-section">Legislative</div>
      <div class="nav-item active" onclick="location.href='legislation.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v12H2V2zm1 1v10h10V3H3zm2 2h6v1H5V5zm0 2h6v1H5V7zm0 2h4v1H5V9z"/></svg>
        Ordinances & Resolutions
      </div>
      <div class="nav-section">Civic Issues</div>
      <div class="nav-item" onclick="location.href='report_issue.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm1 10H7V7h2v4zm0-6H7V3h2v2z"/></svg>
        Submit Issue
      </div>
      <div class="nav-item" onclick="location.href='issue_status.php'">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h10v2H1v-2z"/></svg>
        My Reports
      </div>
    <?php else: ?>
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
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) . strtoupper(substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sb-logout" onclick="location.href='logout.php'">Sign out</div>
      </div>
    </div>
  </div>
</div>

<div class="main legislation-detail-main">
  <div class="page-header detail-header">
    <div class="detail-header-copy">
      <div class="detail-breadcrumbs">
        <a href="legislation.php">Legislative Database</a>
        <span>/</span>
        <span><?= htmlspecialchars($legislationType === 'Ordinance' ? 'Ordinance' : 'Resolution', ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <h1 class="page-title"><?= htmlspecialchars($legislation['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="page-sub">Official legislative record summary and body text.</p>
    </div>
    <a href="legislation.php" class="btn-secondary">← Back to Database</a>
  </div>

  <article class="card legislation-detail-card">
    <div class="legislation-hero">
      <div>
        <div class="section-kicker">Barangay Legislative Record</div>
        <h2 class="legislation-hero-title"><?= htmlspecialchars($legislation['title'], ENT_QUOTES, 'UTF-8') ?></h2>
      </div>
      <div class="header-badges">
        <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($legislationType, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($legislationStatus, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    </div>

    <div class="legislation-meta-grid">
      <div class="meta-item">
        <span class="meta-label">Reference No.</span>
        <strong><?= htmlspecialchars($ordinanceNumber, ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <div class="meta-item">
        <span class="meta-label">Date Enacted</span>
        <strong><?= htmlspecialchars($publishedDate, ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <div class="meta-item">
        <span class="meta-label">Category</span>
        <strong><?= htmlspecialchars($legislationCategory, ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <div class="meta-item">
        <span class="meta-label">Legislative Office</span>
        <strong><?= htmlspecialchars($legislationType === 'Ordinance' ? 'Sangguniang Barangay' : 'Barangay Council', ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
    </div>

    <div class="detail-section">
      <div class="section-header">
        <span class="section-tag">Overview</span>
        <h3>Legislative Summary</h3>
      </div>

      <div class="detail-list">
        <div class="detail-row">
          <span class="detail-label">Title</span>
          <span class="detail-value"><?= htmlspecialchars($legislation['title'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Type</span>
          <span class="detail-value"><?= htmlspecialchars($legislationType, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Category</span>
          <span class="detail-value"><?= htmlspecialchars($legislationCategory, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status</span>
          <span class="detail-value"><?= htmlspecialchars($legislationStatus, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date Enacted</span>
          <span class="detail-value"><?= htmlspecialchars($publishedDate, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
    </div>

    <div class="detail-section">
      <div class="section-header">
        <span class="section-tag">Body</span>
        <h3><?= htmlspecialchars($legislationType === 'Ordinance' ? 'Ordinance Text' : 'Resolution Text', ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <div class="body-copy"><?= nl2br(htmlspecialchars($legislationDescription, ENT_QUOTES, 'UTF-8')) ?></div>
    </div>

    <?php if (!empty($legislationGallery)): ?>
      <div class="detail-section">
        <div class="section-header">
          <span class="section-tag">Reference</span>
          <h3>Official Document</h3>
        </div>
        <div class="document-card">
          <p><?= htmlspecialchars($legislationType === 'Ordinance' ? 'Official ordinance file(s)' : 'Official resolution file(s)', ENT_QUOTES, 'UTF-8') ?></p>
          <div class="legislation-gallery">
            <?php foreach ($legislationGallery as $galleryImage): ?>
              <a href="<?= htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="gallery-item">
                <img src="<?= htmlspecialchars($galleryImage, ENT_QUOTES, 'UTF-8') ?>" alt="Official <?= htmlspecialchars($legislationType, ENT_QUOTES, 'UTF-8') ?> document">
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </article>
</div>

<script>
function toggleSidebar(id) {
  var sb = document.getElementById(id);
  if (sb) sb.classList.toggle('open');
}
</script>
</body>
</html>
