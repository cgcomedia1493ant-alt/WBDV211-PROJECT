<?php
/**
 * OLFU Antipolo Lost & Found Portal - Main Landing Page
 * This file handles user sessions, authentication status verification, 
 * renders the responsive UI, and displays the real-time campus belongings feed.
 */

// Start user session to verify authentication status
session_start();

// Determine whether student/staff or custodian admin is authenticated
$is_admin = !empty($_SESSION['admin_logged_in']);
$is_student = isset($_SESSION['user_id']) || isset($_SESSION['user_email']);
$is_logged_in = $is_admin || $is_student;

// Generate user display name and initials for the top navigation greeting chip
if ($is_admin) {
    $user_display = 'Custodian';
    $first_name = 'Custodian';
    $user_initials = 'CA';
} elseif ($is_student) {
    $user_display = $_SESSION['user_name'] ?? 'Student';
    $name_parts = array_values(array_filter(explode(' ', trim($user_display))));
    $first_name = $name_parts[0] ?? 'Student';
    
    // Generate two-letter initials (First Name Initial + Last Name Initial)
    if (count($name_parts) >= 2) {
        $first_initial = substr($name_parts[0], 0, 1);
        $last_initial = substr(end($name_parts), 0, 1);
        $user_initials = strtoupper($first_initial . $last_initial);
    } else {
        $user_initials = strtoupper(substr($first_name, 0, 2));
    }
} else {
    $user_display = null;
    $first_name = '';
    $user_initials = '';
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
           Defines color themes (OLFU green, gold, status colors) and resets spacing.
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

        html, body {
            overflow-x: hidden;
            width: 100%;
            background-color: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ==========================================================================
           2. GLASSMORPHISM STYLES
           Provides modern frosted glass visual effects for panels and badges.
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
           3. FIXED TOP NAVBAR & BRANDING
           Sticky top navigation bar containing university logo, title, and actions.
           ========================================================================== */
        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.98) 0%, rgba(0, 68, 40, 0.99) 100%);
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
            transition: all 0.2s ease;
        }

        .nav-container {
            max-width: 1140px;
            margin: auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            min-width: 0;
        }

        .logo-circle {
            width: 48px;
            height: 48px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            border: 2px solid var(--olfu-gold);
            overflow: hidden;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
            transition: all 0.2s ease;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
            min-width: 0;
        }

        .brand-header-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #ffffff;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .brand-campus {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--olfu-gold);
            text-transform: uppercase;
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 8px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .brand-portal {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--olfu-light);
            opacity: 0.95;
            letter-spacing: 0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.2s ease;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-shrink: 0;
        }

        /* Single-Line Clean User Greeting Chip */
        .user-greeting-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 5px 14px 5px 6px;
            border-radius: 20px;
            color: #ffffff;
            transition: all 0.2s ease;
        }

        .user-greeting-chip:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: var(--olfu-gold);
        }

        .user-avatar-circle {
            width: 26px;
            height: 26px;
            background: var(--olfu-gold);
            color: #004428;
            font-weight: 800;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }

        .user-name-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            letter-spacing: 0.2px;
        }

        .nav-link-login {
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 0.4rem 0.75rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link-login:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        @media screen and (min-width: 992px) {
            .navbar { padding: 1.1rem 0; }
            .logo-circle { width: 56px; height: 56px; border-width: 2.5px; }
            .brand-logo { width: 44px; height: 44px; }
            .brand-title { font-size: 1.85rem; }
            .brand-campus { font-size: 0.85rem; padding-left: 10px; }
            .brand-portal { font-size: 0.88rem; }
            .burger-btn { padding: 8px 10px; }
        }

        /* ==========================================================================
           4. BURGER BUTTON & CLEAN SIDEBAR DRAWER
           Slide-out menu drawer for navigation links, helpdesk, and admin portal.
           ========================================================================== */
        .burger-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
            cursor: pointer;
            padding: 7px 9px;
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
            width: 20px;
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

        .sidebar-overlay.active { display: block; }

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

        .sidebar-drawer.active { right: 0; }

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

        .sidebar-logout-link {
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            color: #b91c1c !important;
            font-weight: 700 !important;
        }

        .sidebar-logout-link:hover {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        /* ==========================================================================
           5. HERO BANNER SECTION
           Hero landing header welcoming users with call-to-action buttons.
           ========================================================================== */
       .hero-section {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.82) 0%, rgba(0, 68, 40, 0.86) 100%),
                        url('frontend/assets/images/OLFU Front.png') center / 70% no-repeat;
            color: #ffffff;
            padding: 9rem 1rem 5.1rem;
            text-align: center;
            border-bottom: 3px solid var(--olfu-gold);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 720px;
            margin: auto;
        }

        .hero-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin-bottom: 0.8rem;
        }

        .hero-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.6rem;
            letter-spacing: -0.3px;
            line-height: 1.25;
            word-wrap: break-word;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 0.9rem;
            opacity: 0.95;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            max-width: 580px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 0.5rem;
            text-shadow: 0 1px 4px rgba(0,0,0,0.3);
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: var(--olfu-gold);
            color: #004428;
            padding: 0.7rem 1.4rem;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.88rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
            display: inline-block;
        }

        .btn-hero-primary:hover { transform: translateY(-2px); }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            padding: 0.7rem 1.4rem;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            backdrop-filter: blur(4px);
            display: inline-block;
        }

        /* 3-Step Process Cards Layout */
        .process-cards-section {
            max-width: 1140px;
            margin: -1.6rem auto 2.2rem;
            padding: 0 1rem;
            position: relative;
            z-index: 10;
        }

        .process-cards-wrapper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            width: 100%;
        }

        /* ==========================================================================
           6. FEED CONTAINER & ITEM CARDS GRID
           Displays reported lost and found items in a responsive grid layout.
           ========================================================================== */
        .container {
            max-width: 1140px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }

        .filter-card {
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            border-left: 5px solid var(--olfu-green);
            margin-bottom: 1.5rem;
        }

        .filter-card h2 {
            color: var(--olfu-green);
            font-size: 1.3rem;
            font-weight: 800;
        }

        .filter-card p {
            color: var(--text-secondary);
            font-size: 0.88rem;
            margin-top: 4px;
        }

        .items-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1.25rem !important;
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
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            z-index: 2;
        }

        .badge-lost { background: #fee2e2; color: var(--lost-color); }
        .badge-found { background: #dcfce7; color: var(--found-color); border: 1px solid #bbf7d0; }
        .badge-claimed { background: #dcfce7; color: var(--found-color); border: 1px solid #bbf7d0; }

        .card-img-wrapper {
            width: 100%;
            height: 200px;
            background: rgba(248, 250, 252, 0.7);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem;
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
            padding: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-grow: 1;
        }

        .card-content h3 {
            font-size: 1.05rem;
            color: var(--text-primary);
            font-weight: 700;
            margin: 0;
            text-transform: capitalize;
        }

        .location-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--olfu-green);
            background-color: var(--olfu-light);
            padding: 4px 10px;
            border-radius: 20px;
            width: fit-content;
            margin: 2px 0 8px 0;
        }

        .btn-claim {
            margin-top: auto;
            padding: 0.7rem;
            background: var(--olfu-green);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background 0.2s ease;
            width: 100%;
        }

        .btn-claim:hover { background: var(--olfu-hover); }

        /* Authentication barrier locked card prompt */
        .auth-gate-box {
            text-align: center;
            padding: 2.5rem 1.25rem;
            border-radius: 16px;
            border: 2px dashed rgba(0, 90, 54, 0.35);
            max-width: 640px;
            margin: 1.5rem auto;
        }

        .auth-gate-title {
            color: var(--olfu-green);
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .auth-gate-text {
            color: var(--text-secondary);
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .auth-gate-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-auth-login {
            background: var(--olfu-green);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.88rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 90, 54, 0.2);
            transition: background 0.2s;
        }

        .btn-auth-login:hover { background: var(--olfu-hover); }

        .btn-auth-register {
            background: #ffffff;
            color: var(--olfu-green);
            border: 2px solid var(--olfu-green);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.88rem;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-auth-register:hover { background: var(--olfu-light); }

        /* ==========================================================================
           7. FOOTER STYLES
           Multi-column university footer displaying contact info, hours, and navigation.
           ========================================================================== */
        .footer {
            background: #002b19;
            color: #cbd5e1;
            padding: 3rem 1rem 1.5rem;
            margin-top: 4rem;
            border-top: 4px solid var(--olfu-gold);
            width: 100%;
        }

        .footer-container {
            max-width: 1140px;
            margin: auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1.2fr;
            gap: 2.5rem;
            text-align: left;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .footer-brand-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 38px;
            height: 38px;
            background: #ffffff;
            border-radius: 50%;
            padding: 2px;
            border: 1.5px solid var(--olfu-gold);
            object-fit: contain;
        }

        .footer-brand h4 {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .footer-brand p {
            font-size: 0.82rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .footer-title {
            color: var(--olfu-gold);
            font-size: 0.88rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.9rem;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--olfu-gold);
            text-decoration: underline;
        }

        .footer-info-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.8rem;
            line-height: 1.5;
            color: #e2e8f0;
        }

        .footer-bottom {
            max-width: 1140px;
            margin: 2rem auto 0;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem;
            font-size: 0.78rem;
            color: #64748b;
        }

        /* ==========================================================================
           8. MODALS & INTERACTIVE CAMPUS MAP STYLES
           Modal popup styling and smooth interactive draggable/zoomable map container.
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
            padding: 1rem;
        }

        .modal-content {
            padding: 1.8rem 1.4rem;
            border-radius: 16px;
            width: 100%;
            max-width: 720px;
            position: relative;
            background: #ffffff;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
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

        .close-btn:hover { color: var(--text-primary); }

        /* Interactive Draggable & Zoomable Campus Map Container */
        .campus-map-wrapper {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.8rem;
            border: 1.5px solid #cbd5e1;
            background: #ffffff; /* Clean white background */
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: relative;
            cursor: zoom-in;
            height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .campus-map-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
            transition: transform 0.2s ease-out;
            transform-origin: center center;
            user-select: none;
            pointer-events: auto;
        }

        .campus-map-wrapper.zoomed { cursor: grab; }
        .campus-map-wrapper.zoomed:active { cursor: grabbing; }

        .zoom-instruction {
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        /* ==========================================================================
           9. MOBILE MEDIA QUERIES (RESPONSIVE BREAKPOINTS)
           Adjusts layout grids and typography for smaller mobile screen sizes.
           ========================================================================== */
        @media screen and (max-width: 850px) {
            .items-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }

        @media screen and (max-width: 768px) {
            .footer-container { grid-template-columns: 1fr; gap: 1.8rem; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }

        @media screen and (max-width: 650px) {
            .hero-section { padding-top: 5.5rem; }
            .brand-campus, .brand-portal { display: none; }
            .brand-title { font-size: 1.35rem; }
            .logo-circle { width: 42px; height: 42px; }
            .brand-logo { width: 34px; height: 34px; }
            .user-greeting-chip { padding: 4px 10px 4px 5px; }
            .user-avatar-circle { width: 24px; height: 24px; font-size: 0.68rem; }
            .user-name-title { font-size: 0.8rem; }
            .hero-title { font-size: 1.45rem; }
            .hero-subtitle { font-size: 0.85rem; }
            .hero-actions { flex-direction: column; width: 100%; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; text-align: center; }
            .process-cards-wrapper { grid-template-columns: 1fr; }
            .process-cards-section { margin-top: -1rem; }
            .items-grid { grid-template-columns: 1fr !important; }
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

            <!-- Clean Top Right Actions -->
            <div class="nav-right">
                <?php if ($is_logged_in): ?>
                    <!-- Single-Line Clean User Greeting Chip with First & Last Name Initials -->
                    <div class="user-greeting-chip">
                        <div class="user-avatar-circle">
                            <?php echo htmlspecialchars($user_initials); ?>
                        </div>
                        <span class="user-name-title">Hi, <?php echo htmlspecialchars($first_name); ?> 👋🏻</span>
                    </div>
                <?php else: ?>
                    <a href="backend/login/login.php" class="nav-link-login">Login</a>
                <?php endif; ?>

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
            <a href="javascript:void(0)" onclick="openBuildingsModal(); closeSidebar();">🏢 Campus Map & Buildings</a>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.6rem 0;">
            <div class="sidebar-section-title">Account & Administration</div>
            <a href="backend/admin/dashboard.php" class="sidebar-admin-link">🛡️ Admin Portal Login</a>
            
            <?php if ($is_logged_in): ?>
                <a href="backend/login/logout.php" class="sidebar-logout-link">🚪 Log Out Account</a>
            <?php endif; ?>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: #94a3b8; text-align: center;">
            OLFU Antipolo
        </div>
    </aside>

    <!-- HERO LANDING BANNER SECTION -->
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

    <!-- 3-STEP PROCESS CARDS -->
    <section class="process-cards-section">
        <div class="process-cards-wrapper">
            <div class="glass-panel" style="padding: 1.1rem 1.25rem; border-radius: 14px; border-top: 4px solid var(--olfu-green) !important;">
                <div style="font-size: 1.5rem; margin-bottom: 0.3rem;">🔎</div>
                <h3 style="font-size: 0.95rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">1. Search the Feed</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.45;">Check updates with facility location tags across campus grounds.</p>
            </div>
            <div class="glass-panel" style="padding: 1.1rem 1.25rem; border-radius: 14px; border-top: 4px solid var(--olfu-gold) !important;">
                <div style="font-size: 1.5rem; margin-bottom: 0.3rem;">📝</div>
                <h3 style="font-size: 0.95rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">2. Submit Claim Proof</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.45;">Use the Claim button to submit identifying proof using your OLFU email.</p>
            </div>
            <div class="glass-panel" style="padding: 1.1rem 1.25rem; border-radius: 14px; border-top: 4px solid var(--olfu-green) !important;">
                <div style="font-size: 1.5rem; margin-bottom: 0.3rem;">🏛️</div>
                <h3 style="font-size: 0.95rem; color: var(--text-primary); margin-bottom: 0.2rem; font-weight: 700;">3. Claim at Guard House</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.45;">Present your valid OLFU Student ID at the Campus Security Desk for handover.</p>
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
                // Load persistent item metadata from admin JSON storage
                $data_file = __DIR__ . '/backend/admin/item_data.json';
                $items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

                $upload_dir = 'uploads/';
                // Scan uploaded images excluding GIF formats
                $images = glob($upload_dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

                if (!empty($images)):
                    rsort($images);
                    $sample_buildings = [
                        'Vicente M. Santos Bldg (VMS)',
                        'Juliet Santos Bldg (JSB)',
                        'St. John the Baptist Hall (SJBH)',
                        'St. Catherine Hall (SCH)',
                        'San Pedro Calungsod Bldg (SPCB)',
                        'Athletics & Gym Center',
                    ];

                    foreach ($images as $img_path):
                        $filename     = basename($img_path);
                        $display_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
                        $display_name = ucwords(str_replace('_', ' ', $display_name));

                        $assigned_location = $items_data[$filename]['location'] ?? $sample_buildings[crc32($filename) % count($sample_buildings)];
                        $custody_status    = $items_data[$filename]['status'] ?? 'IN CUSTODY';
                        $report_type       = $items_data[$filename]['report_type'] ?? 'LOST';
                        $is_claimed        = ($custody_status === 'CLAIMED');

                        // Set appropriate badge status text and styling class
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
                            <button class="btn-claim" onclick="window.location.href='backend/claim_item.php?item=<?php echo urlencode($display_name); ?>'">
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
                <div style="font-size: 2.5rem; margin-bottom: 0.6rem;">🔒</div>
                <h3 class="auth-gate-title">OLFU Account Required</h3>
                <p class="auth-gate-text">To view detailed photos of lost items, inspect campus locations, and file recovery claims, please sign in using your official Our Lady of Fatima University account.</p>
                <div class="auth-gate-actions">
                    <a href="backend/login/login.php" class="btn-auth-login">Sign In with OLFU Account</a>
                    <a href="backend/login/signup.php" class="btn-auth-register">Register Account</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- ENHANCED MULTI-COLUMN GREEN UNIVERSITY FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Column 1: Brand Info -->
            <div class="footer-brand">
                <div class="footer-brand-header">
                    <img src="frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal" class="footer-logo">
                    <div>
                        <h4>OLFU Antipolo</h4>
                        <span style="font-size: 0.75rem; color: var(--olfu-gold); font-weight: 700;">Lost & Found Portal</span>
                    </div>
                </div>
                <p>The official campus recovery platform designed to log, track, and return misplaced belongings to students and faculty across OLFU Antipolo facilities.</p>
            </div>

            <!-- Column 2: Quick Links (Triggers Modals) -->
            <div>
                <h5 class="footer-title">Portal Navigation</h5>
                <ul class="footer-links">
                    <li><a href="javascript:void(0)" onclick="openContactModal()">📞 Campus Help Desk</a></li>
                    <li><a href="javascript:void(0)" onclick="openGuidelinesModal()">📋 Claiming Guidelines</a></li>
                    <li><a href="javascript:void(0)" onclick="openBuildingsModal()">🗺️ Campus Map & Directory</a></li>
                    <li><a href="backend/admin/dashboard.php">🛡️ Custodian Admin Portal</a></li>
                </ul>
            </div>

            <!-- Column 3: Security Hours & Location -->
            <div>
                <h5 class="footer-title">Turnover & Claiming Desk</h5>
                <div class="footer-info-box">
                    <strong style="color: #ffffff;">📍 Main Security Desk (Gate 1)</strong><br>
                    Km 26 Sumulong Hwy, Antipolo, Rizal<br><br>
                    <strong style="color: #ffffff;">⏰ Operating Hours:</strong><br>
                    Monday – Saturday: 7:00 AM – 7:00 PM
                </div>
            </div>
        </div>

        <!-- Footer Bottom Legal / Copyright -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Our Lady of Fatima University - Antipolo Campus. All rights reserved.</p>
            <p>Designed for OLFU Student Recovery Services</p>
        </div>
    </footer>

    <!-- MODAL POPUPS: CONTACT US -->
    <div id="contactModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeContactModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.2rem; margin-bottom: 0.6rem;">📞 Campus Help Desk</h3>
            <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-bottom: 1rem;">For immediate concerns regarding lost valuables (wallets, laptops, phones), reach out directly to campus units:</p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.85rem;">
                <div style="background: var(--olfu-light); padding: 0.75rem; border-radius: 8px;">
                    <strong>🛡️ Main Guard Desk (Sumulong Gate):</strong><br>
                    Mon - Sat: 7:00 AM – 7:00 PM<br>
                    Location: Main Gate 1 Security Counter
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 8px;">
                    <strong>🏛️ Office of Student Affairs (OSA):</strong><br>
                    Email: <span style="color: var(--olfu-green); font-weight: 600;">osa.antipolo@fatima.edu.ph</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL POPUPS: CLAIMING GUIDELINES -->
    <div id="guidelinesModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeGuidelinesModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.2rem; margin-bottom: 0.6rem;">📋 Claiming Guidelines</h3>
            <ul style="padding-left: 1.2rem; font-size: 0.85rem; color: #475569; display: flex; flex-direction: column; gap: 0.5rem; line-height: 1.45;">
                <li><strong>Valid ID Required:</strong> Always present your official OLFU Student ID or Faculty RFID card.</li>
                <li><strong>Proof of Ownership:</strong> Be ready to unlock electronic devices or identify unique marks (stickers, case scratches, inside items).</li>
                <li><strong>Turnover Period:</strong> Unclaimed items are securely logged and stored at the Security Desk for 60 days before institutional review.</li>
            </ul>
        </div>
    </div>

    <!-- MODAL POPUPS: CAMPUS MAP & DIRECTORY -->
    <div id="buildingsModal" class="modal">
        <div class="glass-panel modal-content">
            <span class="close-btn" onclick="closeBuildingsModal()">&times;</span>
            <h3 style="color: var(--olfu-green); font-size: 1.2rem; margin-bottom: 0.4rem;">🗺️ Antipolo Campus Map & Directory</h3>
            <p class="zoom-instruction">🔍 Click the map to zoom in, then click and drag to pan across different areas.</p>
            
            <!-- INTERACTIVE DRAGGABLE & ZOOMABLE MAP CONTAINER -->
            <div class="campus-map-wrapper" id="mapWrapper">
                <img src="frontend/assets/images/OLFU Antipolo Campus Map.jpg" alt="OLFU Antipolo Campus Map" class="campus-map-img" id="mapImage">
            </div>

            <!-- HELPFUL CAMPUS NOTE -->
            <div style="background: var(--olfu-light); border: 1px solid #bbf7d0; padding: 0.85rem 1rem; border-radius: 10px; font-size: 0.82rem; color: #004428; line-height: 1.45;">
                💡 <strong>Tip:</strong> All items recovered from any building listed above are logged and secured at the <strong>Main Security Desk (Gate 1)</strong>.
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: UI CONTROLLERS & DRAGGABLE MAP ZOOM LOGIC -->
    <script>
        /**
         * Opens the mobile slide-out navigation sidebar drawer.
         */
        function openSidebar() {
            document.getElementById('sidebarDrawer').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }

        /**
         * Closes the mobile slide-out navigation sidebar drawer.
         */
        function closeSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        /**
         * Opens the Contact Support modal dialog box.
         */
        function openContactModal() {
            document.getElementById('contactModal').style.display = 'flex';
        }

        /**
         * Closes the Contact Support modal dialog box.
         */
        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
        }

        /**
         * Opens the Claiming Guidelines modal dialog box.
         */
        function openGuidelinesModal() {
            document.getElementById('guidelinesModal').style.display = 'flex';
        }

        /**
         * Closes the Claiming Guidelines modal dialog box.
         */
        function closeGuidelinesModal() {
            document.getElementById('guidelinesModal').style.display = 'none';
        }

        /**
         * Opens the Campus Map & Buildings modal dialog box and resets view.
         */
        function openBuildingsModal() {
            document.getElementById('buildingsModal').style.display = 'flex';
            resetMapTransform();
        }

        /**
         * Closes the Campus Map & Buildings modal dialog box and resets map zoom/pan.
         */
        function closeBuildingsModal() {
            document.getElementById('buildingsModal').style.display = 'none';
            resetMapTransform();
        }

        // ======================================================================
        // INTERACTIVE CAMPUS MAP ZOOM & DRAG (PANNING) CONTROLLER
        // ======================================================================
        const mapWrapper = document.getElementById('mapWrapper');
        const mapImage = document.getElementById('mapImage');

        let isZoomed = false;
        let scale = 1;
        let pointX = 0;
        let pointY = 0;
        let startX = 0;
        let startY = 0;
        let isDragging = false;

        /**
         * Applies translation (pan coordinates) and scale (zoom level) to the map image.
         */
        function setTransform() {
            mapImage.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`;
        }

        /**
         * Resets the map image back to its default unzoomed state and centers position.
         */
        function resetMapTransform() {
            isZoomed = false;
            scale = 1;
            pointX = 0;
            pointY = 0;
            mapWrapper.classList.remove('zoomed');
            setTransform();
        }

        // Event Listener: Click to toggle zoom in or reset zoom
        mapWrapper.addEventListener('click', function(e) {
            // Prevent toggling zoom if the user was actively dragging/panning
            if (isDragging) return;

            if (!isZoomed) {
                scale = 2.5; // Set zoom magnification level (2.5x)
                isZoomed = true;
                mapWrapper.classList.add('zoomed');
            } else {
                resetMapTransform();
            }
        });

        // Event Listener: Mouse Dragging (Panning) across the map image when zoomed in
        mapImage.addEventListener('mousedown', function(e) {
            if (!isZoomed) return;
            e.preventDefault();
            isDragging = false;
            startX = e.clientX - pointX;
            startY = e.clientY - pointY;

            /**
             * Updates image position coordinates as the mouse moves.
             */
            function onMouseMove(e) {
                isDragging = true;
                pointX = e.clientX - startX;
                pointY = e.clientY - startY;
                setTransform();
            }

            /**
             * Cleans up mouse movement listeners when mouse button is released.
             */
            function onMouseUp() {
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
                // Slight delay to prevent immediate click toggle conflict after dragging
                setTimeout(() => { isDragging = false; }, 50);
            }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        });
    </script>
</body>
</html>