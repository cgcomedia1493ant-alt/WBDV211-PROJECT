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
            --found-color: #2e7d32;
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
           4. BURGER BUTTON & SLIDE-OUT SIDEBAR
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
            width: 300px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            box-shadow: -6px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 2001;
            display: flex;
            flex-direction: column;
            transition: right 0.35s ease;
            padding: 1.5rem;
        }

        .sidebar-drawer.active {
            right: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.2rem;
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

        .sidebar-links {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .sidebar-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.92rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .sidebar-links a:hover {
            background: var(--olfu-light);
            color: var(--olfu-green);
            transform: translateX(4px);
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
           6. FEED CONTAINER & GRID CARDS (FIXED HORIZONTAL GRID)
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

        /* Fixed multi-column horizontal display */
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

        /* ==========================================================================
           7. GREEN UNIVERSITY FOOTER
           ========================================================================== */
        .footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 2.5rem 1.5rem;
            margin-top: 4rem;
            border-top: 4px solid var(--olfu-green);
            text-align: center;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            padding: 2.2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 460px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
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

            <!-- Navigation Actions & Burger Button -->
            <div class="nav-right">
                <a href="backend/login/login.php" class="nav-link-login">Login</a>
                <a href="report.php" class="btn-report">+ Report Item</a>
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
            <h3>Portal Menu</h3>
            <span class="sidebar-close" onclick="closeSidebar()">&times;</span>
        </div>
        <nav class="sidebar-links">
            <a href="index.php">🏠 Home Feed</a>
            <a href="report.php">➕ Report Missing Item</a>
            <a href="backend/login/login.php">🔐 Student / Staff Login</a>
            <a href="backend/login/signup.html">📝 Register Account</a>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.6rem 0;">
            <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; padding: 0 0.5rem;">Campus Info</div>
            <a href="#itemsContainer" onclick="closeSidebar()">📍 Antipolo Buildings</a>
            <a href="javascript:void(0)" onclick="alert('Campus Guard House is open 7:00 AM - 7:00 PM (Monday-Saturday) at the Main Sumulong Gate.');">🛡️ Guard Desk Directory</a>
        </nav>
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: #94a3b8; text-align: center;">
            OLFU Antipolo Portal
        </div>
    </aside>

    <!-- HERO LANDING BANNER -->
    <section class="hero-section">
        <div class="hero-content">
            <span class="hero-badge glass-badge">OFFICIAL CAMPUS RECOVERY DESK</span>
            <h1 class="hero-title">Lost Something at OLFU Antipolo?</h1>
            <p class="hero-subtitle">A centralized digital registry for Fatima Antipolo students and staff. Report misplaced items or claim belongings recovered across campus facilities.</p>
            <div class="hero-actions">
                <a href="report.php" class="btn-hero-primary">Report Item</a>
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
                <p style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.45;">Check live updates with building tags (JSB, Vicente Santos, San Lorenzo Hall).</p>
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
    <main class="container">
        <!-- HEADER / FILTER BANNER -->
        <section class="glass-panel filter-card">
            <h2>Campus Belongings Feed</h2>
            <p>Real-time lost and found updates across OLFU Antipolo buildings.</p>
        </section>

        <!-- ITEMS GRID FEED (HORIZONTAL ROW DISPLAY) -->
        <section class="items-grid" id="itemsContainer">
            <?php
            $upload_dir = 'uploads/';
            $images = glob($upload_dir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);

            if (!empty($images)):
                rsort($images);
                foreach ($images as $img_path):
                    $filename = basename($img_path);
                    $display_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
                    $sample_buildings = [
                        'Vicente Santos Bldg',
                        'Juliet Santos Bldg (JSB)',
                        'St. Catherine Hall',
                        'San Lorenzo Hall'
                    ];
                    $assigned_building = $sample_buildings[crc32($filename) % count($sample_buildings)];
            ?>
                <!-- SINGLE ITEM CARD -->
                <article class="glass-panel item-card">
                    <span class="badge badge-lost">LOST</span>
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($display_name); ?>">
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($display_name); ?></h3>
                        <div class="location-badge">
                            <span class="pin-icon">📍</span>
                            <span class="building-name"><?php echo htmlspecialchars($assigned_building); ?></span>
                        </div>
                        <button class="btn-claim" onclick="openClaimModal('<?php echo htmlspecialchars($display_name); ?>')">Claim Item</button>
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
    </main>

    <!-- GREEN CAMPUS FOOTER -->
    <footer class="footer">
        <div style="max-width: 1140px; margin: auto;">
            <p style="font-weight: 700; color: #ffffff; margin-bottom: 0.4rem;">Our Lady of Fatima University - Antipolo Campus</p>
            <p style="font-size: 0.88rem; color: #94a3b8; margin-bottom: 1.2rem;">Km 26 Sumulong Hwy, Antipolo, Rizal • Campus Security & Lost and Found Unit</p>
            <p style="font-size: 0.8rem; color: #64748b;">&copy; <?php echo date('Y'); ?> OLFU Antipolo Lost & Found Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- CLAIM MODAL POPUP -->
    <div id="claimModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeClaimModal()">&times;</span>
            <h3 id="modalItemTitle">Claim Item</h3>
            <p style="font-size: 0.9rem; color: #64748b; margin-top: 0.4rem;">Please provide verifiable identifying details to present to Campus Security / Finder:</p>
            <form id="claimForm" onsubmit="event.preventDefault(); alert('Claim submitted! Proceed to the Campus Guard House for verification.'); closeClaimModal();" style="display: flex; flex-direction: column; gap: 0.8rem; margin-top: 1rem;">
                <label style="font-size: 0.85rem; font-weight: 600;">Student Email:</label>
                <input type="email" placeholder="student@fatima.edu.ph" required style="padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <label style="font-size: 0.85rem; font-weight: 600;">Proof / Distinct Details:</label>
                <textarea rows="3" placeholder="Specify marks, stickers, inner contents, or proof of ownership..." required style="padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px;"></textarea>
                <button type="submit" style="padding: 0.8rem; background: var(--olfu-green); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 0.5rem;">Submit Claim Request</button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT: SIDEBAR TOGGLE -->
    <script>
        function openSidebar() {
            document.getElementById('sidebarDrawer').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }

        function closeSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        function openClaimModal(itemName) {
            document.getElementById('modalItemTitle').innerText = 'Claim Item: ' + itemName;
            document.getElementById('claimModal').style.display = 'flex';
        }

        function closeClaimModal() {
            document.getElementById('claimModal').style.display = 'none';
        }
    </script>
</body>
</html>