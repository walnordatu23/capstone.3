<?php
require_once 'config.php';
requireLogin();

$user = currentUser();
$redirect = $user['role'] === 'admin' ? 'admin_dashboard.php' : ($user['role'] === 'official' ? 'official_dashboard.php' : 'resident_dashboard.php');
header('Location: ' . $redirect);
exit;

$user = currentUser();
if (empty($user['photo_path'])) {
    setFlash('A real profile photo is required before you can use the group chat.', 'error');
    header('Location: profile.php');
    exit;
}

$memberCheck = $db->prepare('SELECT user_id FROM group_chat_members WHERE user_id = ?');
$memberCheck->execute([$user['id']]);
if (!$memberCheck->fetch()) {
    $joinStmt = $db->prepare('INSERT IGNORE INTO group_chat_members (user_id) VALUES (?)');
    $joinStmt->execute([$user['id']]);
}

$members = $db->query('SELECT u.id, u.full_name, u.role, u.committee, u.photo_path FROM group_chat_members g JOIN users u ON u.id = g.user_id ORDER BY u.full_name ASC')->fetchAll(PDO::FETCH_ASSOC);
$messages = $db->query('SELECT m.id, m.sender_id, m.message, m.created_at, u.full_name, u.role, u.committee, u.photo_path FROM group_chat_messages m LEFT JOIN users u ON u.id = m.sender_id ORDER BY m.created_at ASC')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && trim($_POST['message']) !== '') {
        $stmt = $db->prepare('INSERT INTO group_chat_messages (sender_id, message) VALUES (?, ?)');
        $stmt->execute([$user['id'], trim($_POST['message'])]);
        header('Location: group_chat.php');
        exit;
    }

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK && $_FILES['attachment']['name'] !== '') {
        $uploadDir = __DIR__ . '/uploads/group_chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $original = basename($_FILES['attachment']['name']);
        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
            $msgStmt = $db->prepare('INSERT INTO group_chat_messages (sender_id, message) VALUES (?, ?)');
            $msgStmt->execute([$user['id'], 'Shared a file: ' . $original]);
            $messageId = (int) $db->lastInsertId();
            $fileStmt = $db->prepare('INSERT INTO group_chat_message_files (message_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)');
            $fileStmt->execute([
                $messageId,
                $original,
                'uploads/group_chat/' . $safeName,
                mime_content_type($target) ?: 'application/octet-stream',
                $user['id']
            ]);
            header('Location: group_chat.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Group Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
    <style>
        body { margin: 0; background: #edf3ff; font-family: 'DM Sans', sans-serif; }
        .chat-shell { max-width: 1200px; margin: 24px auto; display: grid; grid-template-columns: 260px 1fr; gap: 20px; padding: 0 18px 24px; }
        .panel { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(31,41,55,.08); }
        .members-panel { padding: 20px; }
        .chat-panel { display: flex; flex-direction: column; min-height: 760px; }
        .chat-head { padding: 20px 22px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .chat-scroll { flex: 1; padding: 20px; overflow-y: auto; background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%); }
        .chat-form { border-top: 1px solid #e5e7eb; padding: 18px 20px; display: grid; grid-template-columns: 1fr auto; gap: 12px; }
        .msg-wrap { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px; }
        .msg-wrap.me { justify-content: flex-end; }
        .msg { max-width: 72%; border-radius: 14px; padding: 12px 14px; line-height: 1.5; }
        .msg.me { background: linear-gradient(135deg, #1d4ed8, #1e3a8a); color: white; }
        .msg.other { background: white; border: 1px solid #e5e7eb; }
        .meta { font-size: 11px; opacity: .8; margin-bottom: 6px; }
        .chat-avatar { width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #dbeafe, #c7d2fe); color: #1e3a8a; font-weight: 700; text-decoration: none; overflow: hidden; box-shadow: 0 4px 12px rgba(30,58,138,.12); position: relative; }
        .chat-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .chat-avatar .profile-hover-card { position: absolute; left: 50%; bottom: calc(100% + 12px); transform: translateX(-50%) translateY(10px); opacity: 0; pointer-events: none; transition: all .2s ease; width: 180px; background: rgba(15,23,42,0.96); color: #fff; padding: 10px 12px; border-radius: 12px; box-shadow: 0 16px 32px rgba(15,23,42,.24); z-index: 20; }
        .chat-avatar:hover .profile-hover-card, .chat-avatar:focus-within .profile-hover-card { opacity: 1; transform: translateX(-50%) translateY(0); }
        .profile-hover-card .hover-name { font-weight: 700; font-size: 13px; }
        .profile-hover-card .hover-role { font-size: 11px; color: #dbeafe; margin-top: 4px; }
        .profile-hover-card .hover-meta { font-size: 10px; color: #cbd5e1; margin-top: 6px; }
        .file-link { display: inline-block; margin-top: 8px; background: rgba(255,255,255,.12); color: inherit; text-decoration: none; padding: 8px 10px; border-radius: 8px; }
        .member-item { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-bottom: 1px solid #f0f2f5; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #dbeafe, #c7d2fe); font-weight: 700; color: #1e3a8a; }
        .badge { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; background: #ebf5ff; color: #1d4ed8; padding: 4px 8px; border-radius: 999px; }
        .header-btn { background: #1e3a8a; color: white; border: none; border-radius: 10px; padding: 10px 14px; cursor: pointer; }
        @media (max-width: 900px) { .chat-shell { grid-template-columns: 1fr; } .msg { max-width: 90%; } }
    </style>
</head>
<body>
    <div class="chat-shell">
        <aside class="panel members-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                <h2 style="margin:0;font-size:24px;">Group Chat</h2>
                <a href="index.php" class="header-btn" style="text-decoration:none;">Home</a>
            </div>
            <div style="margin-bottom:16px; color:#475569; font-weight:600;">Online community</div>
            <?php foreach ($members as $member): ?>
                <a href="profile.php?user_id=<?= (int)$member['id'] ?>" style="text-decoration:none;color:inherit;display:block;">
                    <div class="member-item">
                        <div class="avatar" style="overflow:hidden;">
                            <?php if (!empty($member['photo_path'])): ?>
                                <img src="<?= htmlspecialchars($member['photo_path']) ?>" alt="<?= htmlspecialchars($member['full_name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper(substr($member['full_name'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:600;"> <?= htmlspecialchars($member['full_name']) ?> </div>
                            <div style="font-size:12px;color:#64748b;"> <?= htmlspecialchars($member['role']) ?> <?= $member['committee'] ? '· ' . htmlspecialchars($member['committee']) : '' ?> </div>
                        </div>
                        <span class="badge">Live</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="panel chat-panel">
            <div class="chat-head">
                <div>
                    <div style="font-size:26px;font-weight:700;">Barangay Community</div>
                    <div style="color:#64748b; font-size:14px;">Residents and committee members can chat together</div>
                </div>
            </div>

            <div class="chat-scroll">
                <?php foreach ($messages as $msg): ?>
                    <?php
                        $isMine = (int)$msg['sender_id'] === (int)$user['id'];
                        $fileRows = $db->prepare('SELECT * FROM group_chat_message_files WHERE message_id = ? ORDER BY created_at ASC');
                        $fileRows->execute([$msg['id']]);
                        $files = $fileRows->fetchAll(PDO::FETCH_ASSOC);
                        $senderInitials = strtoupper(substr($msg['full_name'] ?? 'U', 0, 1));
                    ?>
                    <div class="msg-wrap <?= $isMine ? 'me' : '' ?>">
                        <?php if (!$isMine): ?>
                            <a class="chat-avatar" href="profile.php?user_id=<?= (int)$msg['sender_id'] ?>" title="View profile">
                                <?php if (!empty($msg['photo_path'])): ?>
                                    <img src="<?= htmlspecialchars($msg['photo_path']) ?>" alt="<?= htmlspecialchars($msg['full_name'] ?? 'User') ?> profile">
                                <?php else: ?>
                                    <?= htmlspecialchars($senderInitials) ?>
                                <?php endif; ?>
                                <div class="profile-hover-card">
                                    <div class="hover-name"><?= htmlspecialchars($msg['full_name'] ?? 'Unknown user') ?></div>
                                    <div class="hover-role"><?= htmlspecialchars($msg['role'] ?? 'User') ?><?= !empty($msg['committee']) ? ' · ' . htmlspecialchars($msg['committee']) : '' ?></div>
                                    <div class="hover-meta">Click to view full profile</div>
                                </div>
                            </a>
                        <?php endif; ?>
                        <div class="msg <?= $isMine ? 'me' : 'other' ?>">
                            <div class="meta"><?= htmlspecialchars($msg['full_name'] ?? 'Unknown user') ?> · <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?></div>
                            <?php if (!empty($msg['message'])): ?>
                                <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <?php endif; ?>
                            <?php foreach ($files as $file): ?>
                                <a class="file-link" href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" rel="noopener noreferrer">
                                    📎 <?= htmlspecialchars($file['file_name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($isMine): ?>
                            <a class="chat-avatar" href="profile.php?user_id=<?= (int)$msg['sender_id'] ?>" title="View profile">
                                <?php if (!empty($user['photo_path'])): ?>
                                    <img src="<?= htmlspecialchars($user['photo_path']) ?>" alt="My profile">
                                <?php else: ?>
                                    <?= htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))) ?>
                                <?php endif; ?>
                                <div class="profile-hover-card">
                                    <div class="hover-name"><?= htmlspecialchars($user['full_name']) ?></div>
                                    <div class="hover-role"><?= htmlspecialchars($user['role'] ?? 'User') ?><?= !empty($user['committee']) ? ' · ' . htmlspecialchars($user['committee']) : '' ?></div>
                                    <div class="hover-meta">Click to view full profile</div>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <form class="chat-form" method="POST" enctype="multipart/form-data">
                <input type="text" name="message" placeholder="Type your message to the barangay group..." style="padding:14px 16px;border:1px solid #dbe3ef;border-radius:12px;font-size:15px;outline:none;">
                <div style="display:flex;gap:8px;align-items:center;">
                    <label style="cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:12px 14px;border-radius:10px;background:#eef2ff;color:#1e3a8a;font-weight:600;">
                        📎
                        <input type="file" name="attachment" style="display:none;">
                    </label>
                    <button type="submit" class="header-btn">Send</button>
                </div>
            </form>
        </section>
    </div>
</body>
</html>
