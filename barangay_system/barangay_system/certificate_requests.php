<?php
require_once 'config.php';
requireRole(['resident', 'official', 'admin']);
$user = currentUser();
$isStaff = in_array($user['role'], ['official', 'admin'], true);
$errors = [];

function certificateBadgeClass($status) {
    return $status === 'Rejected' ? 'badge-rejected' : ($status === 'Processing' ? 'badge-progress' : ($status === 'Ready for Pick-up' || $status === 'Released' ? 'badge-resolved' : 'badge-pending'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isStaff) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $allowed = ['Pending', 'Processing', 'Ready for Pick-up', 'Released', 'Rejected'];
    if ($requestId < 1 || !in_array($status, $allowed, true)) {
        $errors[] = 'Choose a valid request status.';
    } else {
        $releaseDate = in_array($status, ['Ready for Pick-up', 'Released'], true) ? date('Y-m-d H:i:s') : null;
        $update = $db->prepare('UPDATE certificate_requests SET status = ?, notes = ?, release_date = ?, processed_by = ? WHERE request_id = ?');
        $update->execute([$status, $notes ?: null, $releaseDate, $user['id'], $requestId]);
        setFlash('Certificate request updated successfully.', 'success');
        header('Location: certificate_requests.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isStaff) {
    $fullName = trim($_POST['full_name'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $civilStatus = trim($_POST['civil_status'] ?? '');
    $validIdPath = null;
    if ($fullName === '' || preg_match('/\s/', $fullName) !== 1) {
        $errors[] = 'Please enter your complete name, including your first name and surname.';
    }
    if ($purpose === '' || $address === '') {
        $errors[] = 'Purpose and address are required.';
    }
    $validId = $_FILES['valid_id'] ?? null;
    if ($validId && ($validId['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($validId['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || $validId['size'] > 5 * 1024 * 1024) {
            $errors[] = 'The valid ID must be a file up to 5 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($validId['tmp_name']);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
            if (!isset($extensions[$mime])) {
                $errors[] = 'Valid ID must be a JPG, PNG, or PDF file.';
            } else {
                $directory = __DIR__ . '/assets/uploads/certificates';
                if (!is_dir($directory)) mkdir($directory, 0755, true);
                $filename = 'valid-id-' . $user['id'] . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
                if (move_uploaded_file($validId['tmp_name'], $directory . '/' . $filename)) {
                    $validIdPath = 'assets/uploads/certificates/' . $filename;
                } else {
                    $errors[] = 'The valid ID could not be uploaded.';
                }
            }
        }
    }
    if (!$errors) {
        $insert = $db->prepare('INSERT INTO certificate_requests (resident_id, purpose, full_name, birthdate, address, civil_status, phone, valid_id_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$user['id'], $purpose, $fullName, $user['birthdate'] ?: null, $address, $civilStatus ?: null, $user['phone'] ?: null, $validIdPath]);
        setFlash('Your certificate request has been submitted.', 'success');
        header('Location: certificate_requests.php');
        exit;
    }
}

if ($isStaff) {
    $requests = $db->query('SELECT c.*, u.email AS resident_email FROM certificate_requests c JOIN users u ON u.id = c.resident_id ORDER BY c.requested_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare('SELECT * FROM certificate_requests WHERE resident_id = ? ORDER BY requested_at DESC');
    $stmt->execute([$user['id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$flash = flashMessage();
$dashboard = $isStaff ? ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'official_dashboard.php') : 'resident_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isStaff ? 'Manage' : 'Request' ?> Barangay Certificates</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body>
<main class="main" style="max-width:1240px;margin:auto;">
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
    <div><h1 class="page-title"><?= $isStaff ? 'Certificate Requests' : 'Barangay Certificate' ?></h1><p class="page-sub"><?= $isStaff ? 'Review, update, and release resident certificate requests.' : 'Request an official certificate and track its release status.' ?></p></div>
    <a href="<?= $dashboard ?>" class="btn-secondary">← Back to dashboard</a>
  </div>
  <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endforeach; ?>

  <?php if (!$isStaff): ?>
  <div class="card">
    <div class="card-title">New Certificate Request</div>
    <form method="post" enctype="multipart/form-data">
      <div class="form-group"><label class="form-label" for="purpose">Purpose of certificate *</label><input class="form-control" id="purpose" name="purpose" value="<?= htmlspecialchars($_POST['purpose'] ?? '') ?>" placeholder="e.g. Employment, scholarship, legal requirement" required></div>
    <div class="form-row"><div class="form-group"><label class="form-label" for="full_name">Complete name *</label><input class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz" required><small class="form-help">Enter the name exactly as it should appear on the certificate.</small></div><div class="form-group"><label class="form-label">Birthdate</label><input class="form-control" value="<?= htmlspecialchars($user['birthdate'] ?: 'Not provided') ?>" disabled></div></div>
      <div class="form-group"><label class="form-label" for="address">Complete address *</label><input class="form-control" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? ($user['address'] ?? '')) ?>" required></div>
      <div class="form-row"><div class="form-group"><label class="form-label" for="civil_status">Civil status</label><select class="form-control" id="civil_status" name="civil_status"><option value="">Select</option><?php foreach (['Single','Married','Widowed','Separated'] as $option): ?><option <?= ($_POST['civil_status'] ?? '') === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></div><div class="form-group"><label class="form-label" for="valid_id">Valid ID attachment</label><input class="form-control" id="valid_id" type="file" name="valid_id" accept="image/jpeg,image/png,application/pdf"><small class="form-help">Optional. JPG, PNG, or PDF up to 5 MB.</small></div></div>
      <button class="btn-primary" type="submit">Submit Certificate Request</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="card"><div class="card-title"><?= $isStaff ? 'Incoming Requests' : 'My Requests' ?></div>
    <div class="overflow-table"><table class="data-table"><thead><tr><th>Request</th><?php if ($isStaff): ?><th>Resident</th><?php endif; ?><th>Purpose</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php if (!$requests): ?><tr><td colspan="6">No certificate requests yet.</td></tr><?php endif; ?>
    <?php foreach ($requests as $request): ?><tr><td>#<?= (int) $request['request_id'] ?></td><?php if ($isStaff): ?><td><?= htmlspecialchars($request['full_name']) ?><br><small><?= htmlspecialchars($request['resident_email']) ?></small></td><?php endif; ?><td><?= htmlspecialchars($request['purpose']) ?></td><td><?= date('M d, Y', strtotime($request['requested_at'])) ?></td><td><span class="badge <?= certificateBadgeClass($request['status']) ?>"><?= htmlspecialchars($request['status']) ?></span></td><td><?php if ($isStaff): ?><form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap"><input type="hidden" name="request_id" value="<?= (int) $request['request_id'] ?>"><select name="status" class="form-control" style="width:auto;padding:7px"><option <?= $request['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option><option <?= $request['status'] === 'Ready for Pick-up' ? 'selected' : '' ?>>Ready for Pick-up</option><option <?= $request['status'] === 'Released' ? 'selected' : '' ?>>Released</option><option <?= $request['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option></select><input name="notes" class="form-control" style="width:150px;padding:7px" placeholder="Note" value="<?= htmlspecialchars($request['notes'] ?? '') ?>"><button class="btn-secondary" type="submit" style="padding:7px 10px">Update</button><a href="print_certificate.php?id=<?= (int) $request['request_id'] ?>" target="_blank" class="btn-secondary" style="padding:7px 10px">Print</a></form><?php else: ?><?php if (in_array($request['status'], ['Ready for Pick-up', 'Released'], true)): ?><a href="print_certificate.php?id=<?= (int) $request['request_id'] ?>" target="_blank" class="action-link">View certificate</a><?php else: ?>Awaiting processing<?php endif; ?><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</main>
</body></html>