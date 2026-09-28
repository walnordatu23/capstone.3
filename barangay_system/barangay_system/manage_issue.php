<?php
require_once 'config.php';
requireRole(['official', 'admin']);
$user = currentUser();

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form_type = isset($_GET['form']) ? $_GET['form'] : 'view';

$stmt = $db->prepare('SELECT * FROM civic_issues WHERE id = ?');
$stmt->execute([$issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    setFlash('Issue not found.');
    header('Location: issue_status.php');
    exit;
}

if ($user['role'] === 'official') {
    if ($issue['assigned_official_id'] && $issue['assigned_official_id'] !== $user['id']) {
        setFlash('This issue is assigned to another official.');
        header('Location: issue_status.php');
        exit;
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_issue') {
        $assigned_committee = trim($_POST['assigned_committee'] ?? '');
        $assigned_official_id = isset($_POST['assigned_official_id']) ? (int)$_POST['assigned_official_id'] : 0;
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($assigned_committee) && $assigned_official_id > 0) {
            $update = $db->prepare('UPDATE civic_issues SET assigned_committee = ?, assigned_official_id = ?, status = ?, updated_at = NOW() WHERE id = ?');
            if ($update->execute([$assigned_committee, $assigned_official_id, 'In Progress', $issue_id])) {
                if ($notes) {
                    $logsDir = 'logs';
                    if (!is_dir($logsDir)) {
                        mkdir($logsDir, 0755, true);
                    }
                    file_put_contents(
                        "$logsDir/issue_updates.txt",
                        date('Y-m-d H:i:s') . " - Issue #$issue_id assigned to '$assigned_committee' by {$user['full_name']}: $notes\n",
                        FILE_APPEND | LOCK_EX
                    );
                }
                $message = "Issue assigned successfully and status set to 'In Progress'!";
                $messageType = 'success';

                $stmt = $db->prepare('SELECT * FROM civic_issues WHERE id = ?');
                $stmt->execute([$issue_id]);
                $issue = $stmt->fetch(PDO::FETCH_ASSOC);
                $form_type = 'view';
            } else {
                $message = 'Error assigning issue. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Please select both a committee and an official.';
            $messageType = 'error';
        }
    } elseif ($action === 'update_status') {
        $new_status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $photo = $_FILES['photo'] ?? null;
        $photoPath = '';
        $photoError = '';

        if ($photo && $photo['tmp_name']) {
            $maxFileSize = 5 * 1024 * 1024;
            if ($photo['size'] > $maxFileSize) {
                $photoError = 'Photo file is too large. Maximum size is 5MB.';
            } elseif (!preg_match('/^image\/(jpeg|png|gif|webp)$/', $photo['type'])) {
                $photoError = 'Only image files (JPEG, PNG, GIF, WebP) are allowed.';
            } else {
                $targetDir = 'uploads';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $fileName = basename($photo['name']);
                $photoPath = $targetDir . '/' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                if (!move_uploaded_file($photo['tmp_name'], $photoPath)) {
                    $photoError = 'Failed to upload photo. Please try again.';
                    $photoPath = '';
                }
            }
        }

        if (in_array($new_status, ['Open', 'In Progress', 'Resolved'])) {
            if ($photoError) {
                $message = "Status updated but: $photoError";
                $messageType = 'warning';
            } else {
                $message = '';
                $messageType = 'success';
            }

            $update = $db->prepare('UPDATE civic_issues SET status = ?, updated_at = NOW() WHERE id = ?');
            if ($update->execute([$new_status, $issue_id])) {
                if ($notes || $photoPath) {
                    $insertUpdate = $db->prepare('INSERT INTO issue_updates (issue_id, user_id, status, notes, photo_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $insertUpdate->execute([$issue_id, $user['id'], $new_status, $notes, $photoPath]);
                }

                if ($notes) {
                    $logsDir = 'logs';
                    if (!is_dir($logsDir)) {
                        mkdir($logsDir, 0755, true);
                    }
                    file_put_contents(
                        "$logsDir/issue_updates.txt",
                        date('Y-m-d H:i:s') . " - Issue #$issue_id status updated to '$new_status' by {$user['full_name']}: $notes\n",
                        FILE_APPEND | LOCK_EX
                    );
                }

                $message = "Issue status updated to $new_status successfully!";
                $messageType = 'success';

                $stmt = $db->prepare('SELECT * FROM civic_issues WHERE id = ?');
                $stmt->execute([$issue_id]);
                $issue = $stmt->fetch(PDO::FETCH_ASSOC);
                $form_type = 'view';
            } else {
                $message = 'Error updating issue. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid status. Please select a valid status.';
            $messageType = 'error';
        }
    }
}

$stmt = $db->prepare('SELECT full_name, email FROM users WHERE id = ?');
$stmt->execute([$issue['user_id']]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'Unknown', 'email' => 'N/A'];

$committees = ['Infrastructure', 'Health', 'Peace & Order', 'Environment', 'Livelihood', 'Legislative', 'All committees'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Issue #<?= $issue_id ?> - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body class="issue-management-page">
    <div class="issue-management-header">
        <div class="container issue-management-inner">
            <div>
                <p class="issue-management-kicker">Civic Issue Management</p>
                <h1 class="issue-management-title">Manage Issue #<?= $issue_id ?></h1>
            </div>
            <a href="issue_status.php" class="issue-back-button">← Back</a>
        </div>
    </div>

    <div class="container issue-management-shell">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="issue-summary-card">
            <div class="issue-summary-head">
                <div>
                    <p class="section-label">Issue Overview</p>
                    <h2><?= htmlspecialchars($issue['title']) ?></h2>
                </div>
                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $issue['status'])) ?>"><?= htmlspecialchars($issue['status']) ?></span>
            </div>

            <div class="issue-meta-grid">
                <div class="meta-item"><span class="meta-label">Issue Type</span><span class="meta-value"><?= htmlspecialchars($issue['issue_type']) ?></span></div>
                <div class="meta-item"><span class="meta-label">Location</span><span class="meta-value"><?= htmlspecialchars($issue['location']) ?></span></div>
                <div class="meta-item"><span class="meta-label">Reported By</span><span class="meta-value"><?= htmlspecialchars($resident['full_name'] ?? 'Unknown') ?><small><?= htmlspecialchars($resident['email'] ?? 'N/A') ?></small></span></div>
                <div class="meta-item"><span class="meta-label">Assigned To</span><span class="meta-value"><?= htmlspecialchars($issue['assigned_committee'] ?? 'Unassigned') ?></span></div>
                <div class="meta-item"><span class="meta-label">Date Submitted</span><span class="meta-value"><?= date('M d, Y \a\t H:i', strtotime($issue['created_at'])) ?></span></div>
                <div class="meta-item"><span class="meta-label">Last Updated</span><span class="meta-value"><?= date('M d, Y \a\t H:i', strtotime($issue['updated_at'])) ?></span></div>
            </div>

            <div class="issue-description-box">
                <span class="meta-label">Description</span>
                <p><?= nl2br(htmlspecialchars($issue['description'])) ?></p>
            </div>

            <?php if ($issue['photo_path']): ?>
                <div class="issue-evidence-box">
                    <span class="meta-label">Evidence Photo</span>
                    <a href="<?= htmlspecialchars($issue['photo_path']) ?>" target="_blank" rel="noopener">View Photo</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="update-form">
            <?php if ($issue['status'] === 'Open' || $form_type === 'assign'): ?>
                <h3>Assign to Responsible Official</h3>
                <p>Select the committee and the assigned official for this case.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_issue">

                    <div class="form-group">
                        <label class="form-label">Select Committee *</label>
                        <select name="assigned_committee" id="committee_select" class="form-control" required onchange="updateOfficialsList()">
                            <option value="">-- Choose a Committee --</option>
                            <?php foreach ($committees as $comm): ?>
                                <option value="<?= htmlspecialchars($comm) ?>" <?= $issue['assigned_committee'] === $comm ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($comm) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Official *</label>
                        <select name="assigned_official_id" id="official_select" class="form-control" required>
                            <option value="">-- Choose an Official --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assignment Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Explain the issue details and priority..."></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn-primary">Assign Issue</button>
                        <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                    </div>
                </form>
            <?php elseif ($issue['status'] === 'In Progress' || $form_type === 'update'): ?>
                <h3>Send Status Update &amp; Reply to Resident</h3>
                <p>Send a reply to the resident with your progress update. Include a completion photo when the issue is resolved.</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_status">

                    <div class="form-group">
                        <label class="form-label">Issue Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <option value="In Progress" <?= $issue['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress - Currently being addressed</option>
                            <option value="Resolved" <?= $issue['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved - Issue has been fixed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message to Resident *</label>
                        <textarea name="notes" class="form-control" rows="5" placeholder="Write your reply to the resident. Describe what action was taken, progress made, or confirmation that the issue has been resolved..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proof Photo (Optional - Recommended for Resolved Issues)</label>
                        <input type="file" name="photo" accept="image/*" class="form-control">
                    </div>

                    <div>
                        <button type="submit" class="btn-primary">Update Status</button>
                        <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="resolved-state-box">
                    <p>This issue has been resolved and is no longer open for modification.</p>
                    <button type="button" class="btn-secondary" onclick="history.back()">Back to Issues</button>
                </div>
            <?php endif; ?>
        </div>

        <a href="issue_status.php" class="back-link">← Back to Issues</a>
    </div>

    <script>
    const officialsByCommittee = {
        'Infrastructure': [{id: 5, name: 'Roberto Cruz'}],
        'Health': [{id: 6, name: 'Luz Peralta'}],
        'Peace & Order': [{id: 11, name: 'Carlos Mendoza'}],
        'Environment': [{id: 12, name: 'Elena Fernandez'}],
        'Livelihood': [{id: 13, name: 'David Santos'}],
        'Legislative': [{id: 14, name: 'Susan Morales'}],
        'All committees': [{id: 2, name: 'Maria Santos'}]
    };

    function updateOfficialsList() {
        const committee = document.getElementById('committee_select').value;
        const officialSelect = document.getElementById('official_select');

        officialSelect.innerHTML = '<option value="">-- Choose an Official --</option>';

        if (committee && officialsByCommittee[committee]) {
            const officials = officialsByCommittee[committee];
            officials.forEach(official => {
                const option = document.createElement('option');
                option.value = official.id;
                option.textContent = official.name;
                officialSelect.appendChild(option);
            });
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        const committeeSelect = document.getElementById('committee_select');
        if (committeeSelect && committeeSelect.value) {
            updateOfficialsList();
        }
    });
    </script>
</body>
</html>
