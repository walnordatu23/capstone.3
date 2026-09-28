<?php
require_once 'config.php';
requireRole(['official', 'admin', 'resident']);
$user = currentUser();
$stmt = $db->prepare('SELECT c.*, u.email AS resident_email FROM certificate_requests c JOIN users u ON u.id = c.resident_id WHERE c.request_id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$request || ($user['role'] === 'resident' && (int) $request['resident_id'] !== (int) $user['id'])) {
    http_response_code(404);
    exit('Certificate request not found.');
}
if ($request['status'] !== 'Ready for Pick-up' && $request['status'] !== 'Released' && $user['role'] === 'resident') {
    http_response_code(403);
    exit('This certificate is not ready for release.');
}

$fullName = trim((string) ($request['full_name'] ?? '')) ?: '________________';
$purpose = trim((string) ($request['purpose'] ?? '')) ?: '________________';
$address = trim((string) ($request['address'] ?? '')) ?: 'Barangay 76-A, Davao City';
$issuedDate = !empty($request['release_date']) ? date('F d, Y', strtotime($request['release_date'])) : date('F d, Y');

$captainStmt = $db->prepare("SELECT full_name FROM users WHERE role = 'official' AND (position LIKE '%Captain%' OR email = 'maria.santos@barangay.gov') ORDER BY CASE WHEN email = 'maria.santos@barangay.gov' THEN 0 ELSE 1 END, full_name LIMIT 1");
$captainStmt->execute();
$punongBarangayName = trim((string) ($captainStmt->fetchColumn() ?: 'Maria Santos'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Barangay Clearance</title>
    <style>
        :root {
            --paper: #ffffff;
            --line: #1a1a1a;
            --muted: #212121;
        }

        @page { size: letter portrait; margin: 0; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #ffffff;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page {
            width: 816px;
            min-height: 1056px;
            margin: 20px auto;
            padding: 22px 92px 42px;
            background: var(--paper);
            border: 1px solid #d9d9d9;
            position: relative;
            overflow: hidden;
        }

        .page::before {
            content: "";
            position: absolute;
            left: 50%;
            top: 185px;
            width: 680px;
            height: 680px;
            transform: translateX(-50%);
            background: url("../davao%20logo/davao.logo.webp") center / contain no-repeat;
            opacity: 0.1;
            pointer-events: none;
            z-index: 0;
        }

        .header,
        .title,
        .date-line,
        .statement,
        .signature-row,
        .footer {
            position: relative;
            z-index: 1;
        }

        .header {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            min-height: 130px;
        }

        .seal { display:flex; align-items:center; justify-content:center; }
        .seal img {
            display:block;
            width: 82px;
            height: 82px;
            object-fit: contain;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
        }

        .head-center {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .head-center .small {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            line-height: 1.4;
        }

        .head-center .barangay {
            font-size: 16px;
            font-weight: 700;
            margin-top: 4px;
            letter-spacing: 0.08em;
        }

        .title {
            margin-top: 14px;
            text-align: center;
            text-transform: uppercase;
        }

        .title .office-name {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .title h1 {
            margin: 0;
            font-size: 32px;
            letter-spacing: 0.04em;
            line-height: 1.1;
            font-weight: 700;
            text-decoration: underline;
        }

        .date-line {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 68px;
            margin-right: 0;
            font-size: 14px;
            font-weight: 600;
        }

        .date-line .field {
            display: inline-block;
            min-width: 220px;
            border-bottom: 1px solid var(--line);
            text-align: center;
            padding-bottom: 1px;
        }

        .statement {
            margin-top: 20px;
            padding-left: 0;
            position: relative;
            z-index: 1;
        }

        .statement p {
            margin: 0 0 18px;
            font-size: 16px;
            line-height: 1.8;
        }

        .statement p:last-child { padding-left: 50px; }

        .statement .label {
            display: inline-block;
            margin-top: 6px;
            margin-left: 6px;
            font-size: 18px;
        }

        .blank {
            display: inline-block;
            min-width: 230px;
            border-bottom: 1px solid var(--line);
            padding: 0 6px 0;
            line-height: 1.15;
            vertical-align: baseline;
            text-align: center;
            margin: 0 2px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 70px;
            padding: 0 22px 0 52px;
            gap: 20px;
        }

        .sig-block {
            width: 180px;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid var(--line);
            margin-top: 40px;
            width: 100%;
        }

        .sig-label {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .footer {
            margin-top: 55px;
            padding-left: 4px;
            font-size: 14px;
            line-height: 1.55;
        }

        .footer-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin: 0;
        }

        .footer-row .lhs {
            min-width: 172px;
            line-height: 1.15;
        }

        .footer-row .rhs {
            display: inline-block;
            min-width: 220px;
            border-bottom: 1px solid var(--line);
            text-align: center;
            padding-bottom: 1px;
            line-height: 1.15;
        }

        @media print {
            body { background: #fff; }
            .page {
                width: 100%;
                max-width: none;
                margin: 0;
                border: 0;
                min-height: auto;
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="header">
            <div class="seal">
                <img src="../davao logo/davao.logo.webp" alt="Davao City logo">
            </div>
            <div class="head-center">
                <div class="small">Republic of the Philippines</div>
                <div class="small">Province of Davao del Sur</div>
                <div class="small">City of Davao</div>
                <div class="barangay">Barangay 76-A</div>
            </div>
            <div class="seal">
                <img src="assets/images/barangay-76a-logo.png" alt="Barangay 76-A logo">
            </div>
        </header>

        <div class="title">
            <div class="office-name">Office of the Punong Barangay</div>
            <h1>Barangay Clearance</h1>
        </div>

        <div class="date-line">
            <span>Date:</span>
            <span class="field"><?= htmlspecialchars($issuedDate) ?></span>
        </div>

        <div class="statement">
            <p>To Whom It May Concern:</p>
            <p>
                This is to certify that <span class="blank"><?= htmlspecialchars($fullName) ?></span>, a resident of this Barangay, whose signature and community tax certificate appear below is well known to be a man/woman of good moral character, law abiding and peace loving citizen. He / she has never prosecuted of any crime whatsoever according to the records of this office.
            </p>
            <p>
                This certification is issued to <span class="blank"><?= htmlspecialchars($purpose) ?></span> for whatever legal purpose it may serve.
            </p>
        </div>

        <div class="signature-row">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Signature</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label"><?= htmlspecialchars(strtoupper($punongBarangayName)) ?></div>
                <div style="font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 700; color: #1f1f1f; margin-top: 4px;">Punong Barangay</div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-row">
                <span class="lhs">Community Tax Cert. No.</span>
                <span class="rhs"></span>
            </div>
            <div class="footer-row">
                <span class="lhs">Issued at</span>
                <span class="rhs"><?= htmlspecialchars($address) ?></span>
            </div>
            <div class="footer-row">
                <span class="lhs">On</span>
                <span class="rhs"><?= htmlspecialchars($issuedDate) ?></span>
            </div>
        </div>
    </div>
</body>
</html>
