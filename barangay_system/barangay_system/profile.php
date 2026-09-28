<?php
require_once 'config.php';
requireLogin();

$currentUser = currentUser();
$message = flashMessage();
$messageType = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
unset($_SESSION['flash_type']);
$profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($profileUserId > 0) {
    $profileStmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $profileStmt->execute([$profileUserId]);
    $profileUser = $profileStmt->fetch(PDO::FETCH_ASSOC);
    if (!$profileUser) {
        $profileUserId = 0;
    }
}

$user = $profileUserId > 0 ? ($profileUser ?? $currentUser) : $currentUser;
$isOwnProfile = $profileUserId === 0 || (int)$profileUserId === (int)$currentUser['id'];
$uploadDirectory = __DIR__ . '/assets/uploads/profiles';
$webDirectory = 'assets/uploads/profiles';

if (!isset($_SESSION['profile_token'])) {
    $_SESSION['profile_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['profile_token'], $_POST['profile_token'] ?? '')) {
        $message = 'Your session expired. Please refresh and try again.';
        $messageType = 'error';
    } elseif (($_POST['action'] ?? '') === 'upload_photo') {
        $file = $_FILES['profile_photo'] ?? null;
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $detectedType = $file && is_uploaded_file($file['tmp_name']) ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please choose a profile image to upload.';
            $messageType = 'error';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $message = 'Profile images must be 2 MB or smaller.';
            $messageType = 'error';
        } elseif (!isset($allowedTypes[$detectedType]) || @getimagesize($file['tmp_name']) === false) {
            $message = 'Only JPG, PNG, and WEBP images are allowed.';
            $messageType = 'error';
        } else {
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }
            $fileName = 'user-' . (int) $user['id'] . '-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$detectedType];
            $destination = $uploadDirectory . '/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if (!empty($user['photo_path']) && str_starts_with($user['photo_path'], $webDirectory . '/')) {
                    $oldPhoto = __DIR__ . '/' . $user['photo_path'];
                    if (is_file($oldPhoto)) unlink($oldPhoto);
                }
                $photoPath = $webDirectory . '/' . $fileName;
                $stmt = $db->prepare('UPDATE users SET photo_path = ? WHERE id = ?');
                $stmt->execute([$photoPath, $user['id']]);
                $user['photo_path'] = $photoPath;
                $_SESSION['user'] = $user;
                $message = 'Profile picture updated successfully.';
            } else {
                $message = 'The profile image could not be saved.';
                $messageType = 'error';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $errors = [];

        if ($fullName === '') $errors[] = 'Full name is required.';
        if (empty($birthdate) || strtotime($birthdate) === false || $birthdate > date('Y-m-d')) $errors[] = 'A valid birthdate is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if ($user['role'] === 'official' && $position === '') $errors[] = 'Position is required for officials.';

        $emailCheck = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $emailCheck->execute([$email, $user['id']]);
        if ($emailCheck->fetch()) $errors[] = 'That email address is already in use.';

        if ($errors) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            $stmt = $db->prepare('UPDATE users SET full_name = ?, birthdate = ?, email = ?, phone = ?, address = ?, position = ? WHERE id = ?');
            $stmt->execute([$fullName, $birthdate, $email, $phone, $address, $position ?: null, $user['id']]);
            $freshUser = $db->prepare('SELECT * FROM users WHERE id = ?');
            $freshUser->execute([$user['id']]);
            $user = $freshUser->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user'] = $user;
            $message = 'Profile details saved.';
        }
    }
}

$initials = strtoupper(substr($user['full_name'], 0, 1));
$nameParts = preg_split('/\s+/', trim($user['full_name']));
if (count($nameParts) > 1) $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
$dashboard = $user['role'] === 'resident' ? 'resident_dashboard.php' : ($user['role'] === 'official' ? 'official_dashboard.php' : 'admin_dashboard.php');
$roleLabel = ucfirst($user['role']);
$canEdit = $isOwnProfile;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Barangay Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display:flex">
  <button class="hamburger" onclick="document.getElementById('profile-sidebar').classList.toggle('open')">☰</button>
  <aside class="sidebar" id="profile-sidebar">
    <div class="sidebar-brand">
      <div class="sb-seal"></div>
      <div class="sb-name">Barangay 76-A</div>
      <div class="sb-role"><?= htmlspecialchars($roleLabel) ?> Portal</div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Account</div>
      <div class="nav-item active">◎ &nbsp; My Profile</div>
      <div class="nav-item" onclick="location.href='<?= htmlspecialchars($dashboard) ?>'">▦ &nbsp; Dashboard</div>
      <div class="nav-section">Community</div>
      <div class="nav-item" onclick="location.href='officials.php'">♙ &nbsp; Barangay Officials</div>
    </nav>
    <div class="sidebar-footer">
      <div class="sb-user">
        <div class="sb-avatar profile-sidebar-avatar"><?= htmlspecialchars($initials) ?></div>
        <div><div class="sb-uname"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div><div class="sb-logout" onclick="location.href='logout.php'">Sign out</div></div>
      </div>
    </div>
  </aside>

  <main class="main profile-page">
    <div class="page-header">
      <div class="profile-header-row">
        <div><p class="eyebrow">Account settings</p><h1 class="page-title">My Profile</h1><p class="page-sub">Manage your profile picture and personal information.</p></div>
        <a class="btn-secondary" href="<?= htmlspecialchars($dashboard) ?>">← Back to Dashboard</a>
      </div>
    </div>

    <?php if ($message): ?><div class="alert alert-<?= $messageType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if (!$isOwnProfile): ?><div class="alert alert-info" style="margin-bottom:16px;">Viewing profile for <?= htmlspecialchars($user['full_name']) ?>.</div><?php endif; ?>

    <section class="profile-hero card">
      <div class="profile-avatar-large">
        <?php if (!empty($user['photo_path'])): ?><img src="<?= htmlspecialchars($user['photo_path']) ?>" alt="Profile picture of <?= htmlspecialchars($user['full_name']) ?>">
        <?php else: ?><span><?= htmlspecialchars($initials) ?></span><?php endif; ?>
      </div>
      <div class="profile-hero-copy"><p class="eyebrow"><?= htmlspecialchars($roleLabel) ?> account</p><h2><?= htmlspecialchars($user['full_name']) ?></h2><p><?= htmlspecialchars($user['email']) ?></p><span class="profile-role-badge"><?= htmlspecialchars($user['role'] === 'resident' ? ($user['residency_verified'] ? 'Verified Resident' : 'Verification Pending') : $roleLabel) ?></span></div>
      <?php if ($canEdit): ?>
      <form class="profile-upload" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_photo"><input type="hidden" name="profile_token" value="<?= htmlspecialchars($_SESSION['profile_token']) ?>"><label for="profile_photo" class="btn-primary">Choose Profile Picture</label><input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" required onchange="this.form.submit()"><small>JPG, PNG, or WEBP · Max 2 MB</small></form>
      <?php endif; ?>
    </section>

    <div class="profile-content-grid">
      <section class="card"><div class="card-title"><?= $canEdit ? 'Personal Information' : 'Profile Information' ?></div>
        <?php if ($canEdit): ?>
        <form method="post"><input type="hidden" name="action" value="update_profile"><input type="hidden" name="profile_token" value="<?= htmlspecialchars($_SESSION['profile_token']) ?>"><div class="form-row"><div class="form-group"><label class="form-label" for="full_name">Full Name</label><input class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></div><div class="form-group"><label class="form-label" for="birthdate">Birthdate</label><input class="form-control" id="birthdate" type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required></div></div><div class="form-row"><div class="form-group"><label class="form-label" for="email">Email Address</label><input class="form-control" id="email" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div><div class="form-group"><label class="form-label" for="phone">Contact Number</label><input class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09XX XXX XXXX"></div></div><div class="form-group"><label class="form-label" for="address">Address</label><input class="form-control" id="address" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Purok or street address"></div><?php if ($user['role'] === 'official'): ?><div class="form-group"><label class="form-label" for="position">Position</label><input class="form-control" id="position" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>" required></div><?php endif; ?><button type="submit" class="btn-primary" style="width:auto">Save Profile</button></form>
        <?php else: ?>
        <div class="profile-detail"><span>Full Name</span><strong><?= htmlspecialchars($user['full_name']) ?></strong></div>
        <div class="profile-detail"><span>Email</span><strong><?= htmlspecialchars($user['email']) ?></strong></div>
        <div class="profile-detail"><span>Contact Number</span><strong><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></strong></div>
        <div class="profile-detail"><span>Address</span><strong><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></strong></div>
        <?php if ($user['role'] === 'official'): ?><div class="profile-detail"><span>Position</span><strong><?= htmlspecialchars($user['position'] ?? 'Not provided') ?></strong></div><?php endif; ?>
        <?php endif; ?>
      </section>
      <section class="card profile-summary-card"><div class="card-title">Account Information</div><div class="profile-detail"><span>Role</span><strong><?= htmlspecialchars($roleLabel) ?></strong></div><?php if ($user['committee']): ?><div class="profile-detail"><span>Committee</span><strong><?= htmlspecialchars($user['committee']) ?></strong></div><?php endif; ?><div class="profile-detail"><span>Birthdate</span><strong><?= !empty($user['birthdate']) ? htmlspecialchars(date('F j, Y', strtotime($user['birthdate']))) : 'Not provided' ?></strong></div><div class="profile-detail"><span>Member since</span><strong><?= htmlspecialchars(date('F j, Y', strtotime($user['created_at']))) ?></strong></div><div class="profile-detail"><span>Account email</span><strong><?= htmlspecialchars($user['email']) ?></strong></div><?php if ($canEdit): ?><a class="btn-secondary" href="logout.php">Sign out</a><?php else: ?><a class="btn-secondary" href="<?= htmlspecialchars($dashboard) ?>">Back to Dashboard</a><?php endif; ?></section>
    </div>
  </main>
</body>
</html>
