<?php
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'barangay_system';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS issue_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        issue_id INT NOT NULL,
        user_id INT NOT NULL,
        status ENUM('Open', 'In Progress', 'Resolved') NOT NULL,
        notes TEXT DEFAULT NULL,
        photo_path VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_issue_id (issue_id),
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS residents (
        resident_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        contact_number VARCHAR(40) NOT NULL,
        age TINYINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_resident_name (name),
        KEY idx_resident_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ((int) $db->query('SELECT COUNT(*) FROM residents')->fetchColumn() === 0) {
        $db->exec("INSERT INTO residents (name, contact_number, age)
            WITH first_names AS (
                SELECT 'Juan' AS first_name UNION ALL SELECT 'Maria' UNION ALL SELECT 'Jose' UNION ALL SELECT 'Ana' UNION ALL SELECT 'Ramon'
                UNION ALL SELECT 'Liza' UNION ALL SELECT 'Carlo' UNION ALL SELECT 'Sofia' UNION ALL SELECT 'Rogelio' UNION ALL SELECT 'Grace'
            ), family_names AS (
                SELECT 'Dela Cruz' AS family_name UNION ALL SELECT 'Santos' UNION ALL SELECT 'Reyes' UNION ALL SELECT 'Garcia' UNION ALL SELECT 'Bautista'
                UNION ALL SELECT 'Mendoza' UNION ALL SELECT 'Flores' UNION ALL SELECT 'Cruz' UNION ALL SELECT 'Navarro' UNION ALL SELECT 'Villanueva'
            ), numbered AS (
                SELECT CONCAT(f.first_name, ' ', l.family_name) AS resident_name,
                       ROW_NUMBER() OVER (ORDER BY f.first_name, l.family_name) AS resident_number
                FROM first_names f CROSS JOIN family_names l
            )
            SELECT resident_name, CONCAT('09', LPAD(resident_number, 9, '0')), 18 + MOD(resident_number * 7, 63)
            FROM numbered");
    }

    $db->exec("CREATE TABLE IF NOT EXISTS certificate_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        resident_id INT NOT NULL,
        purpose VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        birthdate DATE DEFAULT NULL,
        address VARCHAR(255) NOT NULL,
        civil_status VARCHAR(50) DEFAULT NULL,
        phone VARCHAR(40) DEFAULT NULL,
        valid_id_path VARCHAR(255) DEFAULT NULL,
        status ENUM('Pending', 'Processing', 'Ready for Pick-up', 'Released', 'Rejected') NOT NULL DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        release_date DATETIME DEFAULT NULL,
        processed_by INT DEFAULT NULL,
        FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        KEY idx_certificate_resident (resident_id),
        KEY idx_certificate_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach ([
        'birthdate' => 'DATE DEFAULT NULL AFTER full_name',
        'position' => 'VARCHAR(120) DEFAULT NULL AFTER committee',
        'phone' => 'VARCHAR(40) DEFAULT NULL AFTER position',
        'photo_path' => 'VARCHAR(255) DEFAULT NULL AFTER identification',
        'rejection_reason' => 'VARCHAR(255) DEFAULT NULL AFTER residency_verified'
    ] as $column => $definition) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1060) {
                throw $e;
            }
        }
    }

    try {
        $db->exec("ALTER TABLE legislation ADD COLUMN document_image_path VARCHAR(255) DEFAULT NULL AFTER description");
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== 1060) {
            throw $e;
        }
    }

    $db->exec("UPDATE legislation SET document_image_path = CASE title
        WHEN 'Curfew Hours for Minors Ordinance' THEN 'assets/uploads/legislation/curfew.jpg'
        WHEN 'Barangay Livelihood Support Program' THEN 'assets/uploads/legislation/livelihood.png'
        WHEN 'Waste Segregation Compliance Ordinance' THEN 'assets/uploads/legislation/waste.jpg'
        ELSE document_image_path
    END
    WHERE title IN ('Curfew Hours for Minors Ordinance', 'Barangay Livelihood Support Program', 'Waste Segregation Compliance Ordinance')
      AND (document_image_path IS NULL OR document_image_path = '')");

    $db->exec("UPDATE users SET
        position = CASE email
            WHEN 'maria.santos@barangay.gov' THEN 'Barangay Captain'
            WHEN 'roberto.cruz@barangay.gov' THEN 'Committee Chairperson'
            WHEN 'luz.peralta@barangay.gov' THEN 'Committee Chairperson'
            WHEN 'carlos.mendoza@barangay.gov' THEN 'Committee Chairperson'
            WHEN 'elena.fernandez@barangay.gov' THEN 'Committee Chairperson'
            WHEN 'david.santos@barangay.gov' THEN 'Committee Chairperson'
            WHEN 'susan.morales@barangay.gov' THEN 'Committee Chairperson'
            ELSE COALESCE(position, 'Barangay Official')
        END,
        phone = COALESCE(NULLIF(phone, ''), 'Contact through Barangay Hall'),
        address = COALESCE(NULLIF(address, ''), 'Barangay Hall, Barangay 76-A')
        WHERE role = 'official'");
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

function currentUser() {
    if (!isset($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    if (empty($user['id'])) {
        return $user;
    }

    global $db;

    try {
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($freshUser) {
            $_SESSION['user'] = $freshUser;
            return $freshUser;
        }
    } catch (Throwable $e) {
        return $user;
    }

    return $user;
}

function requireLogin() {
    if (!currentUser()) {
        header('Location: index.php');
        exit;
    }
}

function requireRole($roles = []) {
    $user = currentUser();
    if (!$user || !in_array($user['role'], (array) $roles)) {
        header('Location: index.php');
        exit;
    }

    if (($user['role'] ?? '') === 'resident' && ((int) ($user['residency_verified'] ?? 0) === 2)) {
        unset($_SESSION['user']);
        $_SESSION['flash'] = 'Your registration was not approved because your name was not found in the official registered resident list. Please visit the Barangay Hall to register your name in the official resident list.';
        $_SESSION['flash_type'] = 'error';
        header('Location: resident_login.php');
        exit;
    }
}

function flashMessage() {
    if (isset($_SESSION['flash'])) {
        $message = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $message;
    }
    return null;
}

function setFlash($message, $type = 'error') {
    $_SESSION['flash'] = $message;
    $_SESSION['flash_type'] = $type;
}

function uploadLegislationDocument($file) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('The document image must be a valid file up to 10 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WebP document images are allowed.');
    }

    $directory = __DIR__ . '/assets/uploads/legislation';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('The document upload directory could not be created.');
    }

    $filename = 'legislation_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The document image could not be saved.');
    }

    return 'assets/uploads/legislation/' . $filename;
}
