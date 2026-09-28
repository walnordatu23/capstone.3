<?php
require_once 'config.php';

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $committee = trim($_POST['committee'] ?? '');
    $role = trim($_POST['role'] ?? 'official');
    $phone = trim($_POST['phone'] ?? '');
    $position = $role === 'admin' ? 'Administrator' : 'Committee Chairperson';

    // Store form data for re-display
    $formData = ['full_name' => $full_name, 'birthdate' => $birthdate, 'email' => $email, 'committee' => $committee, 'role' => $role, 'phone' => $phone];

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($birthdate) || strtotime($birthdate) === false || $birthdate > date('Y-m-d')) {
        $errors[] = 'A valid birthdate is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($committee)) {
        $errors[] = 'Committee or office is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Contact number is required.';
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered. Use a different email or <a href="official_login.php">login here</a>.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $db->prepare('INSERT INTO users (full_name, birthdate, email, password_hash, role, committee, position, phone, residency_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        
        if ($insert->execute([$full_name, $birthdate, $email, $hash, $role, $committee, $position, $phone, 1])) {
            setFlash('✓ Official account created successfully! You can now login.', 'success');
            header('Location: official_login.php');
            exit;
        } else {
            $errors[] = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Registration - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="header-nav">
                <h1>Official Registration</h1>
                <a href="index.php" class="btn-primary" style="width:auto;padding:8px 14px;text-decoration:none;">← Back Home</a>
            </div>
            <p>Create a barangay official or administrator account</p>
        </div>
    </div>

    <div class="container" style="max-width:700px">
        <?php if ($message = flashMessage()): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul style="margin-top:8px;padding-left:20px">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Maria Santos" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Official Email *</label>
                    <input type="email" id="email" name="email" placeholder="maria@barangay.gov" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="birthdate">Birthdate *</label>
                <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($formData['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="official" <?= ($formData['role'] ?? 'official') === 'official' ? 'selected' : '' ?>>Official</option>
                        <option value="admin" <?= ($formData['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="committee">Committee / Office *</label>
                    <select id="committee" name="committee" required>
                        <option value="">-- Select Committee --</option>
                        <option value="Infrastructure" <?= ($formData['committee'] ?? '') === 'Infrastructure' ? 'selected' : '' ?>>Infrastructure</option>
                        <option value="Health" <?= ($formData['committee'] ?? '') === 'Health' ? 'selected' : '' ?>>Health</option>
                        <option value="Peace & Order" <?= ($formData['committee'] ?? '') === 'Peace & Order' ? 'selected' : '' ?>>Peace & Order</option>
                        <option value="Environment" <?= ($formData['committee'] ?? '') === 'Environment' ? 'selected' : '' ?>>Environment</option>
                        <option value="Livelihood" <?= ($formData['committee'] ?? '') === 'Livelihood' ? 'selected' : '' ?>>Livelihood</option>
                        <option value="All" <?= ($formData['committee'] ?? '') === 'All' ? 'selected' : '' ?>>All Committees</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Contact Number *</label>
                <input type="tel" id="phone" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <p style="font-size:12px;color:var(--muted);margin-top:4px">At least 6 characters</p>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn" style="width:100%;margin-bottom:12px">Create Official Account</button>
            
            <div style="text-align:center;color:var(--muted);font-size:14px">
                Already have an account? <a href="official_login.php" style="color:var(--navy);font-weight:600">Login here</a>
            </div>
        </form>
    </div>
</body>
</html>
