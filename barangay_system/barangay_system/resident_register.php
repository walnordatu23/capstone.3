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
    $address = trim($_POST['address'] ?? '');
    $id_type = trim($_POST['id_type'] ?? '');
    $identification = trim($_POST['identification'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Store form data for re-display
    $formData = ['full_name' => $full_name, 'birthdate' => $birthdate, 'email' => $email, 'address' => $address, 'id_type' => $id_type, 'identification' => $identification, 'phone' => $phone];

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
    if (empty($address)) {
        $errors[] = 'Home address is required.';
    }
    if (empty($id_type) || empty($identification)) {
        $errors[] = 'Government ID type and number are required.';
    }
    if (empty($phone)) {
        $errors[] = 'Contact number is required.';
    }

    $registryMatch = null;
    if ($full_name !== '') {
        $registryStmt = $db->prepare('SELECT resident_id FROM residents WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
        $registryStmt->execute([$full_name]);
        $registryMatch = $registryStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered. Use a different email or <a href="resident_login.php">login here</a>.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insert = $db->prepare('INSERT INTO users (full_name, birthdate, email, password_hash, role, committee, residency_verified, address, identification, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        
        if ($insert->execute([$full_name, $birthdate, $email, $hash, 'resident', NULL, 0, $address, $id_type . ': ' . $identification, $phone])) {
            $registrationNotice = $registryMatch
                ? 'Registration successful. Your name matches the official resident list and is awaiting admin approval.'
                : 'Registration submitted. Your name was not found in the official resident list. Please request inclusion from the barangay office before your account can be approved.';
            setFlash($registrationNotice, 'success');
            header('Location: resident_login.php');
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
    <title>Resident Registration - Barangay Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20260909-logo">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

<div class="auth-box" style="max-width:500px">
    <div class="auth-header">
      <div class="auth-seal">📋</div>
      <h2 class="auth-title">Resident Registration</h2>
      <p class="auth-sub">Create your barangay profile to access the portal</p>
    </div>

    <?php if ($message = flashMessage()): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul style="margin-top:8px;padding-left:20px;margin-bottom:0">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input class="form-input" type="text" name="full_name" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input class="form-input" type="email" name="email" placeholder="juan@example.com" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Birthdate</label>
            <input class="form-input" type="date" name="birthdate" value="<?= htmlspecialchars($formData['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input class="form-input" type="tel" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Home Address</label>
                <input class="form-input" type="text" name="address" placeholder="Purok, Street, Barangay" value="<?= htmlspecialchars($formData['address'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Valid ID Type</label>
                <select class="form-input" name="id_type" required style="cursor:pointer">
                    <option value="">-- Select ID Type --</option>
                    <option value="PhilSys ID" <?= ($formData['id_type'] ?? '') === 'PhilSys ID' ? 'selected' : '' ?>>PhilSys ID</option>
                    <option value="Voter's ID" <?= ($formData['id_type'] ?? '') === "Voter's ID" ? 'selected' : '' ?>>Voter's ID</option>
                    <option value="Barangay ID" <?= ($formData['id_type'] ?? '') === 'Barangay ID' ? 'selected' : '' ?>>Barangay ID</option>
                    <option value="Postal ID" <?= ($formData['id_type'] ?? '') === 'Postal ID' ? 'selected' : '' ?>>Postal ID</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ID Number</label>
                <input class="form-input" type="text" name="identification" placeholder="XXXX-XXXX-XXXX" value="<?= htmlspecialchars($formData['identification'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input class="form-input" type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input class="form-input" type="password" name="password_confirm" placeholder="••••••••" required>
            </div>
        </div>

        <div class="alert alert-warn">⚠ Your residency will be verified by barangay staff before full access is granted. This may take 1–2 business days.</div>

        <button type="submit" class="btn-primary">Submit Registration</button>
        <button type="button" class="btn-secondary" onclick="location.href='resident_login.php'">← Back to Sign In</button>
        
        <p class="auth-switch">Already have an account? <a href="resident_login.php">Sign in here</a></p>
    </form>
</div>

</body>
</html>
