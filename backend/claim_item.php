<?php
// Start session to verify authentication status
session_start();

// Ensure either student or admin is logged in
$is_authenticated = isset($_SESSION['user_id']) || isset($_SESSION['user_email']) || !empty($_SESSION['admin_logged_in']);

if (!$is_authenticated) {
    header("Location: login/login.php");
    exit();
}

// Connect to MySQL database 
require_once __DIR__ . '/login/database.php';

$message = '';
$message_type = '';

// Retrieve target item name from URL or form POST
$item_name = trim($_GET['item'] ?? ($_POST['item_name'] ?? ''));

// Load persistent item database to check if item is already CLAIMED
$data_file  = __DIR__ . '/admin/item_data.json';
$items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

$is_claimed = false;
$claim_date_display = '';

// Check custody status from JSON
foreach ($items_data as $filename => $meta) {
    $clean_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
    $clean_name = ucwords(str_replace('_', ' ', $clean_name));

    if (strtolower($clean_name) === strtolower($item_name)) {
        if (($meta['status'] ?? '') === 'CLAIMED') {
            $is_claimed = true;
            break;
        }
    }
}

// Check if there is an approved claim record in MySQL database for the date timestamp
if ($is_claimed) {
    $stmt = $conn->prepare("SELECT submitted_at FROM claims WHERE LOWER(item_name) = LOWER(?) AND claim_status = 'APPROVED' ORDER BY submitted_at DESC LIMIT 1");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $claim_date_display = date('F d, Y - h:i A', strtotime($row['submitted_at']));
    }
    $stmt->close();
}

// Handle Claim Form Submission (Only if NOT claimed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_claimed) {
    $item_name     = trim($_POST['item_name'] ?? '');
    $student_email = strtolower(trim($_POST['student_email'] ?? ''));
    $proof_details = trim($_POST['proof_details'] ?? '');

    if (empty($item_name) || empty($student_email) || empty($proof_details)) {
        $message = 'Please provide all required claim details and identification proof.';
        $message_type = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(student\.)?fatima\.edu\.ph$/', $student_email)) {
        $message = 'Please use a valid institutional OLFU email address.';
        $message_type = 'error';
    } else {
        // Insert claim request into claims table
        $stmt = $conn->prepare("INSERT INTO claims (item_name, student_email, proof_details, claim_status) VALUES (?, ?, ?, 'PENDING')");
        $stmt->bind_param("sss", $item_name, $student_email, $proof_details);

        if ($stmt->execute()) {
            $message = 'Claim request submitted successfully! Please proceed to the Campus Security Guard House with your valid OLFU Student ID.';
            $message_type = 'success';
        } else {
            $message = 'Failed to submit claim request. Please check your connection and try again.';
            $message_type = 'error';
        }
        $stmt->close();
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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

        /* HEADER NAVBAR */
        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.96) 0%, rgba(0, 68, 40, 0.98) 100%);
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
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
            color: #ffffff;
        }

        .logo-circle {
            width: 44px;
            height: 44px;
            background: #ffffff;
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
            letter-spacing: 0.5px;
            white-space: nowrap;
            line-height: 1;
        }

        .brand-campus {
            font-size: 0.72rem;
            color: var(--olfu-gold);
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
            margin-top: 2px;
        }

        /* Burger Drawer Settings */
        .burger-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
            cursor: pointer;
            padding: 6px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .burger-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .burger-bar {
            width: 18px;
            height: 2px;
            background-color: #ffffff;
            border-radius: 2px;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
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
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            box-shadow: -6px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 2001;
            display: flex;
            flex-direction: column;
            transition: right 0.35s ease;
            padding: 1.5rem 1.25rem;
        }

        .sidebar-drawer.active {
            right: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.9rem;
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
            font-size: 0.72rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding: 0 0.5rem;
            margin-top: 0.6rem;
            margin-bottom: 0.35rem;
        }

        .sidebar-links {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .sidebar-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0.95rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .sidebar-links a:hover {
            background: var(--olfu-light);
            color: var(--olfu-green);
            transform: translateX(4px);
        }

        .sidebar-admin-link {
            background: rgba(0, 90, 54, 0.08) !important;
            border: 1px solid rgba(0, 90, 54, 0.2) !important;
            color: var(--olfu-green) !important;
            font-weight: 700 !important;
        }

        .sidebar-logout-link {
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            color: #b91c1c !important;
            font-weight: 700 !important;
        }

        /* CONTAINER & CARD STYLING */
        .page-container {
            max-width: 680px;
            width: 100%;
            margin: 2.2rem auto;
            padding: 0 1rem;
            flex-grow: 1;
        }

        .claim-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.2rem 1.8rem;
            box-shadow: 0 12px 35px rgba(0, 90, 54, 0.08);
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
            background: linear-gradient(90deg, var(--olfu-green), var(--olfu-gold));
            border-radius: 20px 20px 0 0;
        }

        .claim-header {
            margin-bottom: 1.5rem;
        }

        .claim-header h1 {
            color: var(--olfu-green);
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
        }

        .claim-header p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* CLAIMED ITEM STATUS BANNER */
        .claimed-banner-box {
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            padding: 1.8rem 1.2rem;
            border-radius: 14px;
            text-align: center;
            margin-top: 0.8rem;
        }

        .claimed-banner-box h2 {
            color: #047857;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 0.4rem;
        }

        .claimed-banner-box p {
            color: #065f46;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .claimed-date-badge {
            display: inline-block;
            background: #ffffff;
            color: #047857;
            padding: 7px 14px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.8rem;
            margin-top: 0.85rem;
            border: 1px solid #a7f3d0;
            box-shadow: 0 2px 6px rgba(4, 120, 87, 0.08);
        }

        .alert-box {
            padding: 0.85rem 1.1rem;
            border-radius: 10px;
            font-size: 0.88rem;
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
            gap: 0.45rem;
            margin-bottom: 1.2rem;
        }

        .form-group label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group textarea {
            padding: 0.78rem 0.9rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.88rem;
            color: var(--text-main);
            outline: none;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--olfu-green);
            box-shadow: 0 0 0 3.5px rgba(0, 90, 54, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--olfu-green) 0%, var(--olfu-hover) 100%);
            color: #ffffff;
            padding: 0.9rem;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            cursor: pointer;
            width: 100%;
            margin-top: 0.4rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 6px 18px rgba(0, 90, 54, 0.25);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0, 90, 54, 0.35);
        }

        /* RIGHT-ALIGNED GHOST RETURN LINK CONTAINER */
        .form-footer-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
            padding-top: 0.4rem;
            border-top: 1px solid #f1f5f9;
        }

        /* OUTLINE / GHOST STYLE RETURN BUTTON */
        .btn-return-ghost {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            background: transparent;
            border: 1.5px solid #cbd5e1;
            padding: 0.6rem 1.1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-return-ghost:hover {
            background: var(--olfu-light);
            border-color: var(--olfu-green);
            color: var(--olfu-green);
        }

        /* COMPACT RESPONSIVE FOOTER */
        .page-footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 1.5rem 1rem;
            margin-top: 2.5rem;
            border-top: 4px solid var(--olfu-green);
            text-align: center;
            width: 100%;
        }

        /* MOBILE BREAKPOINTS OVERRIDES */
        @media screen and (max-width: 600px) {
            .nav-container {
                padding: 0 0.8rem;
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
                padding: 1.2rem 0.8rem;
            }

            .footer-title {
                font-size: 0.85rem !important;
            }

            .footer-sub {
                font-size: 0.75rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand-link">
                <div class="logo-circle">
                    <img src="../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
                </div>
                <div>
                    <div class="brand-title">OLFU ANTIPOLO</div>
                    <div class="brand-campus">Lost & Found Portal</div>
                </div>
            </a>
            
            <button class="burger-btn" onclick="openSidebar()" aria-label="Open Navigation Settings">
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
                <span class="burger-bar"></span>
            </button>
        </div>
    </header>

    <!-- SIDEBAR DRAWER MENU -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>
    <aside id="sidebarDrawer" class="sidebar-drawer">
        <div class="sidebar-header">
            <h3>MENU</h3>
            <span class="sidebar-close" onclick="closeSidebar()">&times;</span>
        </div>
        
        <nav class="sidebar-links">
            <div class="sidebar-section-title">Support & Navigation</div>
            <a href="../index.php">🏠 Return to Home Feed</a>
            <a href="../report.php">➕ Report Misplaced Item</a>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.6rem 0;">
            <div class="sidebar-section-title">Account & Administration</div>
            <a href="admin/dashboard.php" class="sidebar-admin-link">🛡️ Admin Portal Login</a>
            
            <?php if ($is_authenticated): ?>
                <a href="login/logout.php" class="sidebar-logout-link">🚪 Log Out Account</a>
            <?php endif; ?>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: #94a3b8; text-align: center;">
            OLFU Antipolo
        </div>
    </aside>

    <!-- Main Claim Form -->
    <main class="page-container">
        <section class="claim-card">
            <div class="claim-header">
                <h1>Claim Belonging</h1>
                <p>Provide verifiable identifying details and ownership proof before claiming at the Security Desk.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($is_claimed): ?>
                <!-- SHOW THIS BANNER IF ITEM IS ALREADY CLAIMED -->
                <div class="claimed-banner-box">
                    <div style="font-size: 2.5rem; margin-bottom: 0.4rem;">✅</div>
                    <h2>Item Has Been Claimed</h2>
                    <p>This belonging was successfully verified and handed over to its owner at the OLFU Antipolo Campus Security Desk.</p>

                    <?php if (!empty($claim_date_display)): ?>
                        <div class="claimed-date-badge">
                            📅 Date Claimed: <?php echo htmlspecialchars($claim_date_display); ?>
                        </div>
                    <?php else: ?>
                        <div class="claimed-date-badge">
                            📅 Status: Claimed & Returned
                        </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT-ALIGNED GHOST RETURN LINK (WHEN CLAIMED) -->
                <div class="form-footer-actions" style="margin-top: 1.5rem;">
                    <a href="../index.php" class="btn-return-ghost">&larr; Return to Home Feed</a>
                </div>
            <?php else: ?>
                <!-- SHOW FORM ONLY IF ITEM IS IN CUSTODY -->
                <form method="POST" action="">
                    <!-- Target Item Name -->
                    <div class="form-group">
                        <label for="item_name">Item Name</label>
                        <input 
                            type="text" 
                            id="item_name" 
                            name="item_name" 
                            value="<?php echo htmlspecialchars($item_name); ?>" 
                            placeholder="e.g. Wallet, Tumbler, Glasses..." 
                            required>
                    </div>

                    <!-- Student Email -->
                    <div class="form-group">
                        <label for="student_email">Student Email</label>
                        <input 
                            type="email" 
                            id="student_email" 
                            name="student_email" 
                            value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" 
                            placeholder="student@fatima.edu.ph" 
                            required>
                    </div>

                    <!-- Proof / Distinct Ownership Details -->
                    <div class="form-group">
                        <label for="proof_details">Proof / Distinct Details</label>
                        <textarea 
                            id="proof_details" 
                            name="proof_details" 
                            rows="4" 
                            placeholder="Specify marks, stickers, inner contents, color shades, or serial numbers..." 
                            required></textarea>
                    </div>

                    <!-- Primary Action Button -->
                    <button type="submit" class="btn-submit">Submit Claim Request</button>

                    <!-- RIGHT-ALIGNED GHOST RETURN BUTTON BELOW SUBMIT -->
                    <div class="form-footer-actions">
                        <a href="../index.php" class="btn-return-ghost">&larr; Return to Home Feed</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <!-- GREEN CAMPUS FOOTER -->
    <footer class="page-footer">
        <div style="max-width: 1040px; margin: auto;">
            <p class="footer-title" style="font-weight: 700; color: #ffffff; margin-bottom: 0.3rem; font-size: 0.9rem;">Our Lady of Fatima University - Antipolo Campus</p>
            <p class="footer-sub" style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 0.6rem;">Km 26 Sumulong Hwy, Antipolo, Rizal • Campus Security & Lost and Found</p>
            <p style="font-size: 0.72rem; color: #64748b;">&copy; <?php echo date('Y'); ?> OLFU Antipolo. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function openSidebar() {
            document.getElementById('sidebarDrawer').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }

        function closeSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    </script>
</body>
</html>