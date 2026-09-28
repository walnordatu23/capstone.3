-- Barangay Legislative and Civic Issue Monitoring Database Schema

CREATE DATABASE IF NOT EXISTS barangay_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    birthdate DATE DEFAULT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('resident','official','admin') NOT NULL DEFAULT 'resident',
    committee VARCHAR(100) DEFAULT NULL,
    position VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    residency_verified TINYINT(1) DEFAULT 0,
    rejection_reason VARCHAR(255) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    identification VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS residents (
    resident_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resident_name (name),
    KEY idx_resident_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Official resident registry seed: 10 first names x 10 family names = 100 records.
INSERT IGNORE INTO residents (name, contact_number, age)
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
FROM numbered;

CREATE TABLE IF NOT EXISTS legislation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(250) NOT NULL,
    type ENUM('Ordinance', 'Resolution') NOT NULL DEFAULT 'Ordinance',
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    document_image_path VARCHAR(255) DEFAULT NULL,
    status VARCHAR(100) DEFAULT 'Active',
    published_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS civic_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(250) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    status ENUM('Open', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Open',
    assigned_committee VARCHAR(100) DEFAULT NULL,
    assigned_official_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_official_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_assigned_official (assigned_official_id)
);

CREATE TABLE IF NOT EXISTS issue_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('Open', 'In Progress', 'Resolved') NOT NULL,
    notes TEXT DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES civic_issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS certificate_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data can be inserted here for prototype testing.

-- Insert sample users
INSERT IGNORE INTO users (full_name, email, password_hash, role, committee, residency_verified, address, identification, created_at) VALUES
('Juan Dela Cruz', 'juan.delacruz@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 1, 'Purok 3, Sample Barangay', 'PhilSys ID', '2024-01-15 10:00:00'),
('Maria Santos', 'maria.santos@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'All committees', 1, NULL, NULL, '2023-12-01 09:00:00'),
('Pedro Bautista', 'pedro.bautista@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, NULL, NULL, '2023-11-01 08:00:00'),
('Ana Reyes', 'ana.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 1, 'Sitio 5, Sample Barangay', 'Voter\'s ID', '2024-02-10 11:00:00'),
('Roberto Cruz', 'roberto.cruz@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Infrastructure', 1, NULL, NULL, '2023-12-15 10:00:00'),
('Luz Peralta', 'luz.peralta@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Health', 1, NULL, NULL, '2024-01-01 09:00:00'),
('Carina Flores', 'carina.flores@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 0, 'Purok 5, Sample Barangay', 'PhilSys ID', '2025-04-12 14:00:00'),
('Ronald Tan', 'ronald.tan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 0, 'Sitio 2, Sample Barangay', 'Voter\'s ID', '2025-04-11 13:00:00'),
('Shiela Mendoza', 'shiela.mendoza@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 0, 'Purok 1, Sample Barangay', 'Barangay ID', '2025-04-10 12:00:00'),
('Rey Gomez', 'rey.gomez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', NULL, 1, 'Purok 5, Sample Barangay', 'PhilSys ID', '2024-03-20 15:00:00'),
('Carlos Mendoza', 'carlos.mendoza@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Peace & Order', 1, NULL, NULL, '2024-01-10 10:00:00'),
('Elena Fernandez', 'elena.fernandez@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Environment', 1, NULL, NULL, '2024-02-05 11:00:00'),
('David Santos', 'david.santos@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Livelihood', 1, NULL, NULL, '2024-01-20 09:30:00'),
('Susan Morales', 'susan.morales@barangay.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'official', 'Legislative', 1, NULL, NULL, '2023-12-10 08:00:00');

-- Insert sample legislation
INSERT IGNORE INTO legislation (title, type, category, description, status, published_date, created_at) VALUES
('Waste Segregation Compliance Ordinance', 'Ordinance', 'Environment', 'WHEREAS, the Barangay recognizes the importance of environmental protection, public health, and the efficient management of solid waste in the community; NOW, THEREFORE, be it ordained by the Sangguniang Barangay that all households, establishments, schools, and public facilities within the barangay shall segregate waste at source into biodegradable, recyclable, residual, and special waste categories. The barangay shall provide designated segregation bins, enforce regular collection schedules, and issue rules for the proper handling and disposal of hazardous materials, including used oil, batteries, electronic waste, and other non-biodegradable wastes. It shall likewise require residents and business operators to maintain clean surroundings and to cooperate with all sanitation and environmental compliance activities conducted by the barangay. Any person who violates this ordinance shall be subject to corrective notices, administrative fines, community service, or other sanctions as may be imposed by the barangay authorities, subject to applicable local regulations and due process. This ordinance shall take effect immediately upon approval and shall be enforced by the Barangay Environment and Sanitation Committee in coordination with relevant government offices.', 'Active', '2025-03-15', '2025-03-15 10:00:00'),
('Resolution on Community Health Programs', 'Resolution', 'Health', 'WHEREAS, the Barangay is committed to promoting accessible, affordable, and preventive health services for all residents; NOW, THEREFORE, be it resolved by the Barangay Council that the barangay shall continue and expand the implementation of community health programs, including but not limited to monthly free medical consultations, immunization drives, maternal and child health monitoring, wellness check-ups for senior citizens, and health education campaigns in every purok. The Barangay Health Worker Team, together with local health offices and partner agencies, shall conduct regular outreach activities and maintain a referral system for residents requiring further medical attention or specialized treatment. This resolution shall serve as the barangay''s policy basis for strengthening preventive care, improving public health awareness, and ensuring that indigent families have access to basic healthcare services and assistance through coordinated community-based interventions.', 'Active', '2025-02-28', '2025-02-28 11:00:00'),
('Curfew Hours for Minors Ordinance', 'Ordinance', 'Peace & Order', 'WHEREAS, the Barangay aims to protect minors from nighttime hazards, reduce criminal exposure, and maintain peace and order within the community; NOW, THEREFORE, be it ordained by the Sangguniang Barangay that minors below eighteen (18) years of age shall not be allowed outside their homes from 10:00 PM to 5:00 AM daily, unless accompanied by a parent, guardian, or an authorized adult relative. The barangay shall coordinate with local authorities and household leaders to enforce this ordinance and assist in the safe return of minors found outside during curfew hours without valid justification. Violations shall be reported to the parent or guardian for corrective action, and the barangay may impose administrative sanctions, intervention measures, or appropriate referrals in accordance with existing child protection and local government policies. This ordinance shall remain in force unless amended or repealed by a subsequent ordinance of the barangay.', 'Active', '2025-01-20', '2025-01-20 09:00:00'),
('Resolution Supporting Anti-Plastic Campaign', 'Resolution', 'Environment', 'WHEREAS, the excessive use of single-use plastics contributes to pollution, flooding, and environmental degradation that adversely affects public health and community welfare; NOW, THEREFORE, be it resolved by the Barangay Council that the barangay shall support the city-wide anti-plastic campaign and encourage residents, vendors, and business establishments to reduce the use of disposable plastic items. The barangay shall require public markets and barangay-managed facilities to promote reusable bags, refillable containers, and environmentally sustainable packaging alternatives. Barangay officials shall likewise conduct public awareness campaigns, monitor compliance, and coordinate with local businesses to reduce the volume of plastic waste entering waterways, public spaces, and drainage systems. This resolution shall guide barangay efforts toward cleaner surroundings, environmental conservation, and the promotion of sustainable practices within the community.', 'Active', '2024-11-05', '2024-11-05 14:00:00'),
('Barangay Livelihood Support Program', 'Ordinance', 'Livelihood', 'WHEREAS, livelihood development is essential to poverty reduction, economic resilience, and the improvement of household welfare; NOW, THEREFORE, be it ordained by the Sangguniang Barangay that a Livelihood Support Program shall be established to assist qualified residents in starting or expanding small-scale enterprises. Eligible beneficiaries may include low-income households, women, youth entrepreneurs, and other residents in need of financial and technical assistance for livelihood activities such as food processing, retail trading, home-based services, and micro-enterprises. The barangay shall prioritize deserving applicants, provide seed funding or support packages where feasible, and coordinate with relevant agencies for training, mentoring, and monitoring. This ordinance aims to promote self-employment, strengthen local economic activity, and ensure that assistance is delivered in a transparent, sustainable, and community-oriented manner.', 'Active', '2024-09-12', '2024-09-12 16:00:00'),
('Ordinance on Barangay Elections', 'Ordinance', 'Governance', 'WHEREAS, free, orderly, and honest barangay elections are essential to democratic governance, transparency, and public trust; NOW, THEREFORE, be it ordained by the Sangguniang Barangay that all election-related activities within the barangay shall be conducted in accordance with the rules governing voter registration, campaign conduct, polling, canvassing, and the proclamation of results. This ordinance prohibits vote-buying, unlawful campaigning, and the misuse of barangay facilities during the election period. The barangay shall ensure transparency by posting election results in public, promoting fair conduct among candidates, and coordinating with the appropriate authorities to maintain peace, order, and compliance with the law. Any violation of this ordinance shall be subject to the appropriate legal and administrative actions prescribed by the barangay and competent authorities.', 'Active', '2024-07-01', '2024-07-01 12:00:00');

UPDATE legislation SET document_image_path = CASE title
    WHEN 'Curfew Hours for Minors Ordinance' THEN 'assets/uploads/legislation/curfew.jpg'
    WHEN 'Barangay Livelihood Support Program' THEN 'assets/uploads/legislation/livelihood.png'
    WHEN 'Waste Segregation Compliance Ordinance' THEN 'assets/uploads/legislation/waste.jpg'
    ELSE document_image_path
END
WHERE title IN ('Curfew Hours for Minors Ordinance', 'Barangay Livelihood Support Program', 'Waste Segregation Compliance Ordinance');

-- Insert sample civic issues
INSERT IGNORE INTO civic_issues (user_id, title, issue_type, location, description, photo_path, status, assigned_committee, created_at, updated_at) VALUES
(1, 'Pothole on Purok 3 road', 'Infrastructure', 'Purok 3, near barangay road junction', 'Large pothole causing traffic hazards and potential vehicle damage.', NULL, 'In Progress', 'Infrastructure', '2025-04-08 10:00:00', '2025-04-12 14:00:00'),
(1, 'Broken streetlight near Sitio 5', 'Infrastructure', 'Sitio 5, Sample Barangay', 'Streetlight has been out for weeks, affecting safety at night.', NULL, 'Open', NULL, '2025-04-03 09:00:00', '2025-04-03 09:00:00'),
(1, 'Illegal dumping behind covered court', 'Waste Management', 'Behind covered court, Purok 2', 'Residents dumping waste illegally, causing odor and health issues.', NULL, 'Resolved', 'Environment', '2025-03-22 11:00:00', '2025-04-01 16:00:00'),
(1, 'Noise disturbance Purok 6 nightly', 'Peace and Order', 'Purok 6, Sample Barangay', 'Loud music from a nearby establishment disturbing residents.', NULL, 'Resolved', 'Peace & Order', '2025-03-10 20:00:00', '2025-03-18 10:00:00'),
(4, 'Illegal vendor blocking road', 'Peace and Order', 'Main road, Purok 4', 'Vendor setting up stall in the middle of the road, blocking traffic.', NULL, 'Open', NULL, '2025-04-05 08:00:00', '2025-04-05 08:00:00'),
(10, 'Clogged drainage Purok 5', 'Infrastructure', 'Purok 5, drainage canal', 'Drainage is clogged, causing flooding during rains.', NULL, 'Open', NULL, '2025-04-10 12:00:00', '2025-04-10 12:00:00');

-- Insert sample issue updates after the referenced issues exist
INSERT IGNORE INTO issue_updates (issue_id, user_id, status, notes, photo_path, created_at) VALUES
(1, 2, 'In Progress', 'The Infrastructure team inspected the damaged road and confirmed that the pothole requires immediate asphalt repair. A contractor has been scheduled for the patching works.', NULL, '2025-04-09 09:00:00'),
(3, 2, 'Resolved', 'The illegal dumping site was cleaned and the surrounding area was inspected to prevent further dumping. The site is now clear and monitored by the barangay sanitation crew.', NULL, '2025-04-01 16:30:00'),
(4, 5, 'Resolved', 'The reported noise complaint was verified and the nearby establishment was reminded to comply with barangay peace and order guidelines. Follow-up monitoring was conducted for two weeks.', NULL, '2025-03-18 10:00:00');
