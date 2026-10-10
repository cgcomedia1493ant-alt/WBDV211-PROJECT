<?php
// Start user session to verify authentication status
session_start();

// Determine whether student/staff or custodian admin is authenticated
$is_admin = !empty($_SESSION['admin_logged_in']);
$is_student = isset($_SESSION['user_id']) || isset($_SESSION['user_email']);
$is_logged_in = $is_admin || $is_student;

if ($is_admin) {
    $user_display = 'Custodian';
    $first_name = 'Custodian';
} elseif ($is_student) {
    $user_display = $_SESSION['user_name'] ?? 'Student';
    $first_name = explode(' ', trim($user_display))[0];
} else {
    $user_display = null;
    $first_name = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLFU Antipolo - Lost & Found Portal</title>

    <style>
        /* ==========================================================================
           1. ROOT VARIABLES & GLOBAL RESET
           ========================================================================== */
        :root {
            --olfu-green: #005a36;
            --olfu-hover: #004428;
            --olfu-light: #e8f5e9;
            --olfu-gold: #f4c430;
            --lost-color: #d9383a;
            --found-color: #15803d;
            --bg: #f4f6f8;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ==========================================================================
           2. GLASSMORPHISM STYLES
           ========================================================================== */
        .glass-panel {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.7) !important;
            box-shadow: 0 8px 30px rgba(0, 90, 54, 0.08) !important;
        }

        .glass-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
        }

        /* ==========================================================================
           3. NAVBAR & BRANDING
           ========================================================================== */
        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.96) 0%, rgba(0, 68, 40, 0.98) 100%);
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1140px;
            margin: auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
        }

        .logo-circle {
            width: 60px;
            height: 60px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            border: 2.5px solid var(--olfu-gold);
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
        }

        .brand-header-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-title {
            font-size: 2.1rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: #ffffff;
            line-height: 1;
        }

        .brand-campus {
            font-size: 0.88rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--olfu-gold);
            text-transform: uppercase;
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 10px;
        }

        .brand-portal {
            font-size: 0.92rem;
            font-weight: 500;
            color: var(--olfu-light);
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link-login {
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.5rem 0.8rem;
        }

        .btn-report {
            background: #ffffff;
            color: var(--olfu-green);
            padding: 0.65rem 1.4rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .btn-report:hover {
            background: #f0fdf4;
            transform: translateY(-1px);
        }

        /* ==========================================================================
           4. BURGER BUTTON & CLEAN SIDEBAR DRAWER
           ========================================================================== */
        .burger-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
            cursor: pointer;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .burger-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .burger-bar {
            width: 22px;
            height: 2.5px;
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
            width: 310px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            box-shadow: -6px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 2001;
            display: flex;
            flex-direction: column;
            transition: right 0.35s ease;
            padding: 1.6rem 1.4rem;
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
            color: var(--text-secondary);
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
            color: var(--text-primary);
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

        .sidebar-admin-link:hover {
            background: var(--olfu-green) !important;
            color: #ffffff !important;
        }

        /* ==========================================================================
           5. HERO BANNER
           ========================================================================== */
        .hero-section {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.94) 0%, rgba(0, 68, 40, 0.96) 100%);
            color: #ffffff;
            padding: 2.6rem 1.25rem 2.8rem;
            text-align: center;
            border-bottom: 3px solid var(--olfu-gold);
        }

        .hero-content {
            max-width: 720px;
            margin: auto;
        }

        .hero-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin-bottom: 0.8rem;
        }

        .hero-title {
            font-size: 1.95rem;
            font-weight: 800;
            margin-bottom: 0.6rem;
            letter-spacing: -0.3px;
            line-height: 1.25;
        }

        .hero-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            line-height: 1.55;
            max-width: 580px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: var(--olfu-gold);
            color: #004428;
            padding: 0.7rem 1.6rem;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            padding: 0.7rem 1.6rem;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }

        /* ==========================================================================
           6. FEED CONTAINER & GRID CARDS
           ========================================================================== */
        .container {
            max-width: 1140px;
            margin: 2rem auto;
            padding: 0 1.25rem;
        }

        .filter-card {
            padding: 1.5rem 1.8rem;
            border-radius: 14px;
            border-left: 5px solid var(--olfu-green);
            margin-bottom: 2rem;
        }

        .filter-card h2 {
            color: var(--olfu-green);
            font-size: 1.45rem;
            font-weight: 800;
        }

        .filter-card p {
            color: var(--text-secondary);
            font-size: 0.92rem;
            margin-top: 4px;
        }

        .items-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1.5rem !important;
        }

        @media (max-width: 900px) {
            .items-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 600px) {
            .items-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .item-card {
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(0, 90, 54, 0.12);
        }

        .badge {
            position: absolute;
            top: 14px;
            left: 14px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            z-index: 2;
        }

        .badge-lost {
            background: #fee2e2;
            color: var(--lost-color);
        }

        .badge-found {
            background: #dcfce7;
            color: var(--found-color);
            border: 1px solid #bbf7d0;
        }

        .badge-claimed {
            background: #dcfce7;
            color: var(--found-color);
            border: 1px solid #bbf7d0;
        }

        .card-img-wrapper {
            width: 100%;
            height: 220px;
            background: rgba(248, 250, 252, 0.7);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .card-img-wrapper img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: auto;
        }

        .card-content {
            padding: 1.3rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            flex-grow: 1;
        }

        .card-content h3 {
            font-size: 1.15rem;
            color: var(--text-primary);
            font-weight: 700;
            margin: 0;
            text-transform: capitalize;
        }

        .location-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--olfu-green);
            background-color: var(--olfu-light);
            padding: 5px 12px;
            border-radius: 20px;
            width: fit-content;
            margin: 4px 0 10px 0;
        }

        .btn-claim {
            margin-top: auto;
            padding: 0.75rem;
            background: var(--olfu-green);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-claim:hover {
            background: var(--olfu-hover);
        }

        /* Authentication barrier locked card */
        .auth-gate-box {
            text-align: center;
            padding: 3.5rem 2rem;
            border-radius: 16px;
            border: 2px dashed rgba(0, 90, 54, 0.35);
            max-width: 640px;
            margin: 1.5rem auto;
        }

        .auth-gate-title {
            color: var(--olfu-green);
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .auth-gate-text {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.55;
            margin-bottom: 1.8rem;
        }

        .auth-gate-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-auth-login {
            background: var(--olfu-green);
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 90, 54, 0.2);
            transition: background 0.2s;
        }

        .btn-auth-login:hover {
            background: var(--olfu-hover);
        }

        .btn-auth-register {
            background: #ffffff;
            color: var(--olfu-green);
            border: 2px solid var(--olfu-green);
            padding: 0.85rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-auth-register:hover {
            background: var(--olfu-light);
        }

        /* ==========================================================================
           7. GREEN UNIVERSITY FOOTER
           ========================================================================= */
        .footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 2.5rem 1.5rem;
            margin-top: 4rem;
            border-top: 4px solid var(--olfu-green);
            text-align: center;
        }

        /* ==========================================================================
           8. MODALS STYLING
           ========================================================================== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }

        .modal-content {
            padding: 2.2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 480px;
            position: relative;
            background: #ffffff;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
        }

        .close-btn {
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand-link">
                <div class="logo-circle">
                    <img src="frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal" class="brand-logo">
                </div>
                <div class="brand-text">
                    <div class="brand-header-row">
                        <span class="brand-title">OLFU</span>
                        <span class="brand-campus">ANTIPOLO CAMPUS</span>
                    </div>
                    <span class="brand-portal">Lost & Found Portal</span>
                </div>
            </a>

            <!-- Navigation Actions & Greeting -->
            <div class="nav-right">
                <?php if ($is_logged_in): ?>
                    <span style="color: #ffffff; font-size: 0.95rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        Hi, <?php echo htmlspecialchars($first_name); ?> <span style="font-size: 1.35rem; line-height: 1;">👋🏻</span>
                    </span>
                    <a href="backend/login/logout.php" class="nav-link-login" style="color: #fca5a5;">Logout</a>
                <?php else: ?>
                    <a href="backend/login/login.php" class="nav-link-login">Login</a>
                <?php endif; ?>
                <a href="<?php echo $is_logged_in ? 'report.php' : 'backend/login/login.php'; ?>" class="btn-report">+ Report Item</a>
                <button class="burger-btn" onclick="openSidebar()" aria-label="Open Navigation Settings">
                    <span class="burger-bar"></span>
                    <span class="burger-bar"></span>
                    <span class="burger-bar"></span>
                </button>
            </div>
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
            <div class="sidebar-section-title">Support & Help Desk</div>
            <a href="javascript:void(0)" onclick="openContactModal(); closeSidebar();">📞 Contact Support</a>
            <a href="javascript:void(0)" onclick="openGuidelinesModal(); closeSidebar();">📋 Claiming Guidelines</a>
            <a href="javascript:void(0)" onclick="openBuildingsModal(); closeSidebar();">🏢 Campus Buildings</a>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.6rem 0;">
            <div class="sidebar-section-title">Staff Administration</div>
            <a href="backend/admin/dashboard.php" class="sidebar-admin-link">🛡️ Admin Portal Login</a>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: #94a3b8; text-align: center;">
            OLFU Antipolo
        </div>
    </aside>

    <!-- HERO LANDING BANNER -->
    <section class="hero-section">
        <div class="hero-content">
            <span class="hero-badge glass-badge">OFFICIAL CAMPUS RECOVERY DESK</span>
            <h1 class="hero-title">Lost or Found Something at OLFU Antipolo?</h1>
            <p class="hero-subtitle">Report misplaced items or claim belongings recovered across campus facilities.</p>
            <div class="hero-actions">
                <a href="<?php echo $is_logged_in ? 'report.php' : 'backend/login/login.php'; ?>" class="btn-hero-primary">Report Item</a>
                <a href="#itemsContainer" class="btn-hero-secondary">Browse Belongings Feed</a>
            </div>
        </div>
    </section>

    <!-- 3-STEP PROCESS CARDS (HORIZONTAL ROW) -->
    <section style="max-width: 1140px; margin: -1.6rem auto 2.2rem; padding: 0 1.25rem; position: relative; z-index: 10;">
        <div style="display: flex; flex-direction: row; justify-content: space-between; gap: 1.2rem; width: 100%;">
            <div class="glass-panel" style="flex: 1; padding: 1.2rem 1.4rem; border-radius: 14px; border-top: 4px solid var(--olfu-green) !important;">
                <div style="font-size: 1.6rem; margin-bottom: 0.3rem;">🔎</div>
                <h3 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">1. Search the Feed</h3>
                <p style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.45;">Check live updates with facility location tags across campus grounds.</p>
            </div>
            <div class="glass-panel" style="flex: 1; padding: 1.2rem 1.4rem; border-radius: 14px; border-top: 4px solid var(--olfu-gold) !important;">
                <div style="font-size: 1.6rem; margin-bottom: 0.3rem;">📝</div>
                <h3 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">2. Submit Claim Proof</h3>
                <p style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.45;">Use the Claim button to submit identifying proof using your OLFU email.</p>
            </div>
            <div class="glass-panel" style="flex: 1; padding: 1.2rem 1.4rem; border-radius: 14px; border-top: 4px solid var(--olfu-green) !important;">
                <div style="font-size: 1.6rem; margin-bottom: 0.3rem;">🏛️</div>
                <h3 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">3. Claim at Guard House</h3>
                <p style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.45;">Present your valid OLFU Student ID at the Campus Security Desk for handover.</p>
            </div>
        </div>
    </section>

    <!-- MAIN FEED CONTAINER -->
    <main class="container" id="itemsContainer">
        <!-- HEADER / FILTER BANNER -->
        <section class="glass-panel filter-card">
            <h2>Campus Belongings Feed</h2>
            <p>Real-time lost and found updates across OLFU Antipolo.</p>
        </section>

        <?php if ($is_logged_in): ?>
            <!-- ITEMS GRID FEED (AUTHENTICATED ONLY) -->
            <section class="items-grid">
                <?php
                // Load persistent data from admin JSON
                $data_file = __DIR__ . '/backend/admin/item_data.json';
                $items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

                $upload_dir = 'uploads/';
                // Exclude GIF format
                $images = glob($upload_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

                if (!empty($images)):
                    rsort($images);
                    $sample_buildings = [
                        'Vicente M. Santos Bldg (VMS)',
                        'Juliet Santos Bldg (JSB)',
                        'San Juan Bautista Hall (SJBH)',
                        'St. Catherine Hall (SCH)',
                        'San Pedro Calungsod Bldg (SPCB)',
                        'Athletics & Gym Center',
                    ];

                    foreach ($images as $img_path):
                        $filename     = basename($img_path);
                        $display_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
                        $display_name = ucwords(str_replace('_', ' ', $display_name));

                        // Kunin ang eksaktong location mula sa JSON; kung wala pa, gamitin ang existing building array
                        $assigned_location = $items_data[$filename]['location'] ?? $sample_buildings[crc32($filename) % count($sample_buildings)];
                        $custody_status    = $items_data[$filename]['status'] ?? 'IN CUSTODY';
                        $report_type       = $items_data[$filename]['report_type'] ?? 'LOST';
                        $is_claimed        = ($custody_status === 'CLAIMED');

                        // Set correct badge text and style
                        if ($is_claimed) {
                            $badge_text  = 'CLAIMED';
                            $badge_class = 'badge-claimed';
                        } else {
                            $badge_text  = ($report_type === 'FOUND') ? 'FOUND' : 'LOST';
                            $badge_class = ($report_type === 'FOUND') ? 'badge-found' : 'badge-lost';
                        }
                ?>
                    <!-- SINGLE ITEM CARD -->
                    <article class="glass-panel item-card">
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo $badge_text; ?>
                        </span>
                        <div class="card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($display_name); ?>">
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($display_name); ?></h3>
                            <div class="location-badge">
                                <span class="pin-icon">📍</span>
                                <span class="building-name"><?php echo htmlspecialchars($assigned_location); ?></span>
                            </div>
                            <button class="btn-claim" onclick="openClaimModal('<?php echo htmlspecialchars($display_name, ENT_QUOTES); ?>')">
                                <?php echo $is_claimed ? 'Claimed (View Details)' : 'Claim Item'; ?>
                            </button>
                        </div>
                    </article>
                <?php 
                    endforeach;
                else: 
                ?>
                    <div class="glass-panel" style="grid-column: 1 / -1; text-align: center; padding: 4rem 1.5rem; border-radius: 14px; border: 2px dashed #cbd5e1;">
                        <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 0.5rem;">No lost or found items reported at the moment.</p>
                        <a href="report.php" style="color: var(--olfu-green); font-weight: 700; text-decoration: none;">Report an item now &rarr;</a>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <!-- AUTHENTICATION GATEWAY PROMPT (WHEN LOGGED OUT) -->
            <section class="glass-panel auth-gate-box">
                <div style="font-size: 3rem; margin-bottom: 0.8rem;">🔒</div>
                <h3 class="auth-gate-title">OLFU Account Required</h3>
                <p class="auth-gate-text">To view detailed photos of lost items, inspect campus locations, and file recovery claims, please sign in using your official Our Lady of Fatima University account.</p>
                <div class="auth-gate-actions">
                    <a href="backend/login/login.php" class="btn-auth-login">Sign In with OLFU Account</a>
                    <a href="backend/login/signup.php" class="btn-auth-register">Register Account</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- GREEN CAMPUS FOOTER -->
    <footer class="footer">
        <div style="max-width: 1140px; margin: auto;">
            <p style="font-weight: 700; color: #ffffff; margin-bottom: 0.4rem;">Our Lady of Fatima University - Antipolo Campus</p>
            <p style="font-size: 0.88rem; color: #94a3b8; margin-bottom: 1.2rem;">Km 26 Sumulong Hwy, Antipolo, Rizal • Campus Security & Lost and Found</p>
            <p style="font-size: 0.8rem; color: #64748b;">&copy; <?php echo date('Y'); ?> OLFU Antipolo Lost & Found Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- 1. CLAIM MODAL POPUP -->
    <div id="claimModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeClaimModal()">&times;</span>
            <h3 id="modalItemTitle" style="color: var(--olfu-green); font-size: 1.3rem;">Claim Item</h3>
            <p style="font-size: 0.88rem; color: #64748b; margin-top: 0.4rem;">Please provide verifiable identifying details to present to Campus Security / Finder:</p>
            <form id="claimForm" onsubmit="event.preventDefault(); alert('Claim submitted! Proceed to the Campus Guard House for verification.'); closeClaimModal();" style="display: flex; flex-direction: column; gap: 0.8rem; margin-top: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 700;">Student Email:</label>
                <input type="email" placeholder="student@fatima.edu.ph" required style="padding: 0.7rem; border: 1.5px solid #d1d5db; border-radius: 8px;">
                <label style="font-size: 0.82rem; font-weight: 700;">Proof / Distinct Details:</label>
                <textarea rows="3" placeholder="Specify marks, stickers, inner contents, or proof of ownership..." required style="padding: 0.7rem; border: 1.5px solid #d1d5db; border-radius: 8px;"></textarea>
                <button type="submit" style="padding: 0.8rem; background: var(--olfu-green); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 0.5rem;">Submit Claim Request</button>
            </form>
        </div>
    </div>

    <!-- 2. CONTACT US MODAL -->
    <div id="contactModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeContactModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.3rem; margin-bottom: 0.6rem;">📞 Campus Help Desk</h3>
            <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin-bottom: 1.2rem;">For immediate concerns regarding lost valuables (wallets, laptops, phones), reach out directly to campus units:</p>
            <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.88rem;">
                <div style="background: var(--olfu-light); padding: 0.8rem; border-radius: 8px;">
                    <strong>🛡️ Main Guard Desk (Sumulong Gate):</strong><br>
                    Mon - Sat: 7:00 AM – 7:00 PM<br>
                    Location: Main Gate 1 Security Counter
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.8rem; border-radius: 8px;">
                    <strong>🏛️ Office of Student Affairs (OSA):</strong><br>
                    Email: <span style="color: var(--olfu-green); font-weight: 600;">osa.antipolo@fatima.edu.ph</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. GUIDELINES MODAL -->
    <div id="guidelinesModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeGuidelinesModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.3rem; margin-bottom: 0.6rem;">📋 Claiming Guidelines</h3>
            <ul style="padding-left: 1.2rem; font-size: 0.88rem; color: #475569; display: flex; flex-direction: column; gap: 0.6rem; line-height: 1.5;">
                <li><strong>Valid ID Required:</strong> Always present your official OLFU Student ID or Faculty RFID card.</li>
                <li><strong>Proof of Ownership:</strong> Be ready to unlock electronic devices or identify unique marks (stickers, case scratches, inside items).</li>
                <li><strong>Turnover Period:</strong> Unclaimed items are securely logged and stored at the Security Desk for 60 days before institutional review.</li>
            </ul>
        </div>
    </div>

    <!-- 4. CAMPUS BUILDINGS MODAL -->
    <div id="buildingsModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeBuildingsModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.3rem; margin-bottom: 0.6rem;">🏢 Antipolo Campus Buildings</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; font-size: 0.85rem; color: #334155;">
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>VMS:</strong> Vicente M. Santos Bldg</div>
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>JSB:</strong> Juliet Santos Bldg</div>
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>SJBH:</strong> San Juan Bautista Hall</div>
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>SCH:</strong> St. Catherine Hall</div>
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>SPCB:</strong> San Pedro Calungsod Bldg</div>
                <div style="padding: 0.6rem; background: var(--olfu-light); border-radius: 6px;"><strong>ATH:</strong> Athletics & Gym Center</div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: DRAWER & MODAL TOGGLES -->
    <script>
        function openSidebar() {
            document.getElementById('sidebarDrawer').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }

        function closeSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        /* Claim Modal */
        function openClaimModal(itemName) {
            document.getElementById('modalItemTitle').innerText = 'Claim Item: ' + itemName;
            document.getElementById('claimModal').style.display = 'flex';
        }
        function closeClaimModal() {
            document.getElementById('claimModal').style.display = 'none';
        }

        /* Support / Contact Modal */
        function openContactModal() {
            document.getElementById('contactModal').style.display = 'flex';
        }
        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
        }

        /* Guidelines Modal */
        function openGuidelinesModal() {
            document.getElementById('guidelinesModal').style.display = 'flex';
        }
        function closeGuidelinesModal() {
            document.getElementById('guidelinesModal').style.display = 'none';
        }

        /* Buildings Modal */
        function openBuildingsModal() {
            document.getElementById('buildingsModal').style.display = 'flex';
        }
        function closeBuildingsModal() {
            document.getElementById('buildingsModal').style.display = 'none';
        }
    </script>
</body>
</html>