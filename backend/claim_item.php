<?php
session_start();

// Check login status
$is_authenticated = isset($_SESSION['user_id'])
    || isset($_SESSION['user_email'])
    || !empty($_SESSION['admin_logged_in']);

if (!$is_authenticated) {
    header('Location: login/login.php');
    exit();
}

// Database connection
require_once __DIR__ . '/login/database.php';

$message = '';
$message_type = '';
$item_name = trim($_GET['item'] ?? ($_POST['item_name'] ?? ''));

// Load item data
$data_file = __DIR__ . '/admin/item_data.json';
$items_data = [];

if (is_file($data_file)) {
    $decoded = json_decode(file_get_contents($data_file), true);

    if (is_array($decoded)) {
        $items_data = $decoded;
    }
}

// Check if item is already claimed
$is_claimed = false;
$claim_date_display = '';

foreach ($items_data as $filename => $meta) {
    $clean_name = pathinfo((string)$filename, PATHINFO_FILENAME);
    $clean_name = preg_replace('/^\d+_/', '', $clean_name);
    $clean_name = ucwords(str_replace('_', ' ', $clean_name));

    if (
        strcasecmp($clean_name, $item_name) === 0
        && is_array($meta)
        && strtoupper((string)($meta['status'] ?? '')) === 'CLAIMED'
    ) {
        $is_claimed = true;
        break;
    }
}

// Get approval date
if ($is_claimed && $item_name !== '') {
    $sql = "SELECT submitted_at FROM claims
            WHERE LOWER(item_name) = LOWER(?)
            AND claims_status = 'APPROVED'
            ORDER BY submitted_at DESC LIMIT 1";

    $date_stmt = $conn->prepare($sql);

    if ($date_stmt) {
        $date_stmt->bind_param('s', $item_name);

        if ($date_stmt->execute()) {
            $result = $date_stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (!empty($row['submitted_at'])) {
                    $claim_date_display = date(
                        'F d, Y - h:i A',
                        strtotime($row['submitted_at'])
                    );
                }
            }
        }

        $date_stmt->close();
    }
}

// Handle claim submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_claimed) {
    $item_name = trim($_POST['item_name'] ?? '');
    $student_email = strtolower(trim($_POST['student_email'] ?? ''));
    $proof_details = trim($_POST['proof_details'] ?? '');

    if (
        $item_name === ''
        || $student_email === ''
        || $proof_details === ''
    ) {
        $message = 'Please provide all required claim details and identification proof.';
        $message_type = 'error';

    } elseif (
        !filter_var($student_email, FILTER_VALIDATE_EMAIL)
        || !preg_match(
            '/^[a-zA-Z0-9._%+\-]+@(student\.)?fatima\.edu\.ph$/i',
            $student_email
        )
    ) {
        $message = 'Please use a valid institutional OLFU email address.';
        $message_type = 'error';

    } else {
        // Save claim to database
        $sql = "INSERT INTO claims
                (item_name, student_email, proof_details, claims_status)
                VALUES (?, ?, ?, 'PENDING')";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log('Claim prepare failed: ' . $conn->error);

            $message = 'Unable to submit the claim. Please contact the administrator.';
            $message_type = 'error';

        } else {
            $stmt->bind_param(
                'sss',
                $item_name,
                $student_email,
                $proof_details
            );

            if ($stmt->execute()) {
                $claim_id = $stmt->insert_id;
                $stmt->close();

                
$mail_sent = false;

try {
    // Load Brevo configuration
    $brevo_config_path = __DIR__ . '/login/brevo_config.php';

    if (!is_file($brevo_config_path)) {
        throw new RuntimeException('brevo_config.php was not found.');
    }

    $brevoConfig = require $brevo_config_path;

    if (
        !is_array($brevoConfig) ||
        empty($brevoConfig['api_key']) ||
        $brevoConfig['api_key'] === 'PASTE_YOUR_BREVO_API_KEY_HERE' ||
        empty($brevoConfig['from_email'])
    ) {
        throw new RuntimeException('Brevo configuration is incomplete.');
    }

    // Escape user-provided values for HTML email
    $safeItem = htmlspecialchars(
        $item_name,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeEmail = htmlspecialchars(
        $student_email,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeProof = nl2br(
        htmlspecialchars(
            $proof_details,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        )
    );

    // HTML email content
    $htmlContent = "
        <div style='font-family:Arial,sans-serif;line-height:1.6;
                    color:#1f2937;max-width:600px;margin:auto'>
            <h2 style='color:#005a36'>
                OLFU Antipolo Lost &amp; Found Portal
            </h2>

            <p>Hello,</p>

            <p>
                Your claim request has been received and is currently
                <strong>UNDER INVESTIGATION</strong>.
                Campus Security will review the details you submitted.
            </p>

            <p>
                <strong>Claim reference:</strong> #{$claim_id}<br>
                <strong>Item:</strong> {$safeItem}<br>
                <strong>Student email:</strong> {$safeEmail}<br>
                <strong>Ownership details submitted:</strong><br>
                {$safeProof}
            </p>

            <h3 style='color:#005a36'>Investigation notes</h3>
            <p>
                Your ownership details will be checked by Campus Security.
                Please bring your valid OLFU Student ID when instructed.
                This email is not approval to collect the item.
            </p>

            <h3 style='color:#005a36'>When to pick up the item</h3>
            <p>
                Please collect the item only after receiving separate
                confirmation that your claim is approved. After approval,
                proceed to the OLFU Antipolo Campus Security Desk during
                operating hours. Contact Campus Security to confirm the
                exact pickup date and time.
            </p>

            <p>
                Thank you,<br>
                <strong>OLFU Antipolo Campus Security &amp; Lost and Found</strong>
            </p>
        </div>
    ";

    $textContent =
        "Your claim request has been received and is UNDER INVESTIGATION.\n\n" .
        "Claim reference: #{$claim_id}\n" .
        "Item: {$item_name}\n" .
        "Student email: {$student_email}\n" .
        "Ownership details: {$proof_details}\n\n" .
        "Wait for separate approval before collecting the item. " .
        "Contact Campus Security to confirm the pickup date and time.";

    // Prepare Brevo API request
    $payload = [
        'sender' => [
            'name' => $brevoConfig['from_name']
                ?? 'OLFU Antipolo Lost and Found Portal',
            'email' => $brevoConfig['from_email'],
        ],
        'to' => [
            [
                'email' => $student_email,
            ],
        ],
        'subject' => 'Claim Request Received - OLFU Lost and Found Portal',
        'htmlContent' => $htmlContent,
        'textContent' => $textContent,
    ];

    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($jsonPayload === false) {
        throw new RuntimeException('Could not prepare the email content.');
    }

    // Send email using Brevo
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' =>
                "accept: application/json\r\n" .
                "api-key: " . $brevoConfig['api_key'] . "\r\n" .
                "content-type: application/json\r\n",
            'content' => $jsonPayload,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents(
        'https://api.brevo.com/v3/smtp/email',
        false,
        $context
    );

    // Read HTTP status without exposing the API key
    $httpStatus = 0;

    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                $httpStatus = (int) $matches[1];
            }
        }
    }

    if ($response === false) {
        throw new RuntimeException(
            'Could not connect to Brevo. Check PHP HTTPS stream support.'
        );
    }

    if ($httpStatus >= 200 && $httpStatus < 300) {
        $mail_sent = true;
    } else {
        error_log(
            'Brevo email request failed. HTTP status: ' . $httpStatus
        );
    }

} catch (Throwable $e) {
    error_log('Claim email error: ' . $e->getMessage());
}
if ($mail_sent) {
    $message = 'Claim request submitted successfully! A confirmation email with investigation notes and pickup instructions has been sent to your institutional email.';
} else {
    $message = 'Claim request submitted successfully, but the email notification could not be sent. Please contact Campus Security or the administrator for your claim status.';
}

$message_type = 'success';

            } else {
                error_log('Claim insert failed: ' . $stmt->error);
                $stmt->close();

                $message = 'Failed to submit claim request. Please check your database configuration and try again.';
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Claim Request - OLFU Lost & Found</title>

    <style>
        :root {
            --olfu-green: #005a36;
            --olfu-hover: #004428;
            --olfu-gold: #f4c430;
            --olfu-light: #e8f5e9;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont,
                "Segoe UI", Roboto, sans-serif;
        }

        html, body {
            overflow-x: hidden;
            width: 100%;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: linear-gradient(
                135deg,
                rgba(0, 90, 54, .96),
                rgba(0, 68, 40, .98)
            );
            padding: .85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,.12);
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1040px;
            margin: auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
        }

        .logo-circle {
            width: 44px;
            height: 44px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--olfu-gold);
            overflow: hidden;
            flex-shrink: 0;
        }

        .logo-circle img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: .5px;
            white-space: nowrap;
            line-height: 1;
        }

        .brand-campus {
            font-size: .72rem;
            color: var(--olfu-gold);
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            white-space: nowrap;
            margin-top: 2px;
        }

        .burger-btn {
            background: rgba(255,255,255,.15);
            border: 1.5px solid rgba(255,255,255,.35);
            border-radius: 8px;
            cursor: pointer;
            padding: 6px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: all .2s ease;
            flex-shrink: 0;
        }

        .burger-btn:hover {
            background: rgba(255,255,255,.25);
            transform: scale(1.05);
        }

        .burger-bar {
            width: 18px;
            height: 2px;
            background-color: #fff;
            border-radius: 2px;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-drawer {
            position: fixed;
            top: 0;
            right: -320px;
            width: 290px;
            max-width: 85vw;
            height: 100vh;
            background: rgba(255,255,255,.98);
            backdrop-filter: blur(16px);
            box-shadow: -6px 0 25px rgba(0,0,0,.15);
            z-index: 2001;
            display: flex;
            flex-direction: column;
            transition: right .35s ease;
            padding: 1.5rem 1.25rem;
        }

        .sidebar-drawer.active {
            right: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: .9rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.1rem;
        }

        .sidebar-header h3 {
            color: var(--olfu-green);
            font-size: 1.15rem;
            font-weight: 800;
        }

        .sidebar-close {
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--text-muted);
            line-height: 1;
        }

        .sidebar-section-title {
            font-size: .72rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 800;
            letter-spacing: .8px;
            padding: 0 .5rem;
            margin: .6rem 0 .35rem;
        }

        .sidebar-links {
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }

        .sidebar-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: .75rem .95rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: .9rem;
            border-radius: 8px;
            transition: all .2s ease;
        }

        .sidebar-links a:hover {
            background: var(--olfu-light);
            color: var(--olfu-green);
            transform: translateX(4px);
        }

        .sidebar-admin-link {
            background: rgba(0,90,54,.08) !important;
            border: 1px solid rgba(0,90,54,.2) !important;
            color: var(--olfu-green) !important;
            font-weight: 700 !important;
        }

        .sidebar-logout-link {
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            color: #b91c1c !important;
            font-weight: 700 !important;
        }

        .page-container {
            max-width: 680px;
            width: 100%;
            margin: 2.2rem auto;
            padding: 0 1rem;
            flex-grow: 1;
        }

        .claim-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.2rem 1.8rem;
            box-shadow: 0 12px 35px rgba(0,90,54,.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .claim-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(
                90deg,
                var(--olfu-green),
                var(--olfu-gold)
            );
            border-radius: 20px 20px 0 0;
        }

        .claim-header {
            margin-bottom: 1.5rem;
        }

        .claim-header h1 {
            color: var(--olfu-green);
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: .3rem;
        }

        .claim-header p {
            color: var(--text-muted);
            font-size: .88rem;
            line-height: 1.5;
        }

        .claimed-banner-box {
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            padding: 1.8rem 1.2rem;
            border-radius: 14px;
            text-align: center;
            margin-top: .8rem;
        }

        .claimed-banner-box h2 {
            color: #047857;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: .4rem;
        }

        .claimed-banner-box p {
            color: #065f46;
            font-size: .88rem;
            line-height: 1.5;
        }

        .claimed-date-badge {
            display: inline-block;
            background: #fff;
            color: #047857;
            padding: 7px 14px;
            border-radius: 20px;
            font-weight: 800;
            font-size: .8rem;
            margin-top: .85rem;
            border: 1px solid #a7f3d0;
            box-shadow: 0 2px 6px rgba(4,120,87,.08);
        }

        .alert-box {
            padding: .85rem 1.1rem;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            line-height: 1.5;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            margin-bottom: 1.2rem;
        }

        .form-group label {
            font-size: .78rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .form-group input,
        .form-group textarea {
            padding: .78rem .9rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: .88rem;
            color: var(--text-main);
            outline: none;
            background: #fff;
            transition: all .2s ease;
            width: 100%;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--olfu-green);
            box-shadow: 0 0 0 3.5px rgba(0,90,54,.1);
        }

        .btn-submit {
            background: linear-gradient(
                135deg,
                var(--olfu-green),
                var(--olfu-hover)
            );
            color: #fff;
            padding: .9rem;
            border: none;
            border-radius: 10px;
            font-size: .95rem;
            font-weight: 800;
            letter-spacing: .4px;
            cursor: pointer;
            width: 100%;
            margin-top: .4rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 6px 18px rgba(0,90,54,.25);
            transition: all .2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0,90,54,.35);
        }

        .form-footer-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
            padding-top: .4rem;
            border-top: 1px solid #f1f5f9;
        }

        .btn-return-ghost {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            background: transparent;
            border: 1.5px solid #cbd5e1;
            padding: .6rem 1.1rem;
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all .2s ease;
        }

        .btn-return-ghost:hover {
            background: var(--olfu-light);
            border-color: var(--olfu-green);
            color: var(--olfu-green);
        }

        .page-footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 1.5rem 1rem;
            margin-top: 2.5rem;
            border-top: 4px solid var(--olfu-green);
            text-align: center;
            width: 100%;
        }

        @media screen and (max-width: 600px) {
            .nav-container {
                padding: 0 .8rem;
            }

            .brand-campus {
                display: none;
            }

            .brand-title {
                font-size: 1.1rem;
            }

            .logo-circle {
                width: 38px;
                height: 38px;
            }

            .logo-circle img {
                width: 28px;
                height: 28px;
            }

            .claim-card {
                padding: 1.5rem 1.1rem;
            }

            .page-footer {
                padding: 1.2rem .8rem;
            }

            .footer-title {
                font-size: .85rem !important;
            }

            .footer-sub {
                font-size: .75rem !important;
            }
        }
    </style>
</head>

<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand-link">
                <div class="logo-circle">
                    <img src="../frontend/assets/images/OLFU LOGO.png"
                         alt="OLFU Seal">
                </div>

                <div>
                    <div class="brand-title">OLFU ANTIPOLO</div>
                    <div class="brand-campus">Lost &amp; Found Portal</div>
                </div>
            </a>

            <button class="burger-btn"
                    onclick="openSidebar()"
                    aria-label="Open Navigation Settings">
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
            </button>
        </div>
    </header>

    <div id="sidebarOverlay"
         class="sidebar-overlay"
         onclick="closeSidebar()"></div>

    <aside id="sidebarDrawer" class="sidebar-drawer">
        <div class="sidebar-header">
            <h3>MENU</h3>
            <span class="sidebar-close"
                  onclick="closeSidebar()">&times;</span>
        </div>

        <nav class="sidebar-links">
            <div class="sidebar-section-title">
                Support &amp; Navigation
            </div>

            <a href="../index.php">🏠 Return to Home Feed</a>
            <a href="../report.php">➕ Report Misplaced Item</a>

            <hr style="border:0;border-top:1px solid #e2e8f0;margin:.6rem 0;">

            <div class="sidebar-section-title">
                Account &amp; Administration
            </div>

            <a href="admin/dashboard.php" class="sidebar-admin-link">
                🛡️ Admin Portal Login
            </a>

            <?php if ($is_authenticated): ?>
                <a href="login/logout.php" class="sidebar-logout-link">
                    🚪 Log Out Account
                </a>
            <?php endif; ?>
        </nav>

        <div style="margin-top:auto;padding-top:1rem;border-top:1px solid #e2e8f0;font-size:.8rem;color:#94a3b8;text-align:center;">
            OLFU Antipolo
        </div>
    </aside>

    <main class="page-container">
        <section class="claim-card">
            <div class="claim-header">
                <h1>Claim Belonging</h1>
                <p>
                    Provide verifiable identifying details and ownership
                    proof before claiming at the Security Desk.
                </p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($is_claimed): ?>
                <div class="claimed-banner-box">
                    <div style="font-size:2.5rem;margin-bottom:.4rem;">✅</div>
                    <h2>Item Has Been Claimed</h2>
                    <p>
                        This belonging was successfully verified and
                        handed over to its owner at the OLFU Antipolo
                        Campus Security Desk.
                    </p>

                    <?php if (!empty($claim_date_display)): ?>
                        <div class="claimed-date-badge">
                            📅 Date Claimed:
                            <?php echo htmlspecialchars($claim_date_display, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php else: ?>
                        <div class="claimed-date-badge">
                            📅 Status: Claimed &amp; Returned
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-footer-actions" style="margin-top:1.5rem;">
                    <a href="../index.php" class="btn-return-ghost">
                        &larr; Return to Home Feed
                    </a>
                </div>

            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="item_name">Item Name</label>
                        <input
                            type="text"
                            id="item_name"
                            name="item_name"
                            value="<?php echo htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="e.g. Wallet, Tumbler, Glasses..."
                            required>
                    </div>

                    <div class="form-group">
                        <label for="student_email">Student Email</label>
                        <input
                            type="email"
                            id="student_email"
                            name="student_email"
                            value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="student@fatima.edu.ph"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="proof_details">Proof / Distinct Details</label>
                        <textarea
                            id="proof_details"
                            name="proof_details"
                            rows="4"
                            placeholder="Specify marks, stickers, inner contents, color shades, or serial numbers..."
                            required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        Submit Claim Request
                    </button>

                    <div class="form-footer-actions">
                        <a href="../index.php" class="btn-return-ghost">
                            &larr; Return to Home Feed
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer class="page-footer">
        <div style="max-width:1040px;margin:auto;">
            <p class="footer-title" style="font-weight:700;color:#fff;margin-bottom:.3rem;font-size:.9rem;">
                Our Lady of Fatima University - Antipolo Campus
            </p>

            <p class="footer-sub" style="font-size:.8rem;color:#94a3b8;margin-bottom:.6rem;">
                Km 26 Sumulong Hwy, Antipolo, Rizal • Campus Security &amp; Lost and Found
            </p>

            <p style="font-size:.72rem;color:#64748b;">
                &copy; <?php echo date('Y'); ?> OLFU Antipolo. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        function openSidebar() {
            document.getElementById('sidebarDrawer')
                .classList.add('active');

            document.getElementById('sidebarOverlay')
                .classList.add('active');
        }

        function closeSidebar() {
            document.getElementById('sidebarDrawer')
                .classList.remove('active');

            document.getElementById('sidebarOverlay')
                .classList.remove('active');
        }
    </script>
</body>
</html>
