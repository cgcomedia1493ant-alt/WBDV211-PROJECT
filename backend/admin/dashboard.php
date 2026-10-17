<?php
// Start admin session
session_start();

// Enforce custodian access
if (empty($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Handle secure logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header("Location: login.php");
    exit();
}

// Connect to database to read and manage claims
require_once __DIR__ . '/../login/database.php';

$upload_dir = realpath(__DIR__ . '/../../uploads');
$data_file  = __DIR__ . '/item_data.json';

// Load persistent item database (status and exact location)
$items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

// Handle Background AJAX Status Toggle for Belongings Inventory
if (isset($_POST['ajax_toggle'])) {
    $target_file = basename($_POST['filename'] ?? '');
    $current_status = $items_data[$target_file]['status'] ?? 'IN CUSTODY';
    $new_status = ($current_status === 'CLAIMED') ? 'IN CUSTODY' : 'CLAIMED';
    
    if (!isset($items_data[$target_file])) {
        $items_data[$target_file] = [];
    }
    $items_data[$target_file]['status'] = $new_status;
    file_put_contents($data_file, json_encode($items_data, JSON_PRETTY_PRINT));

    // Calculate updated metrics
    $total = count(glob($upload_dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE));
    $claimed = 0;
    foreach ($items_data as $record) {
        if (($record['status'] ?? '') === 'CLAIMED') $claimed++;
    }
    $in_custody = $total - $claimed;

    header('Content-Type: application/json');
    echo json_encode([
        'success'    => true,
        'new_status' => $new_status,
        'in_custody' => $in_custody,
        'claimed'    => $claimed,
        'badge_id'   => md5($target_file)
    ]);
    exit();
}

// Handle AJAX Status Toggle for Claims (PENDING <-> APPROVED) & Auto-sync Inventory
if (isset($_POST['ajax_claim_status'])) {
    $claim_id = intval($_POST['claim_id'] ?? 0);
    $status_stmt = $conn->prepare("SELECT item_name, claim_status FROM claims WHERE id = ?");
    $status_stmt->bind_param("i", $claim_id);
    $status_stmt->execute();
    $curr = $status_stmt->get_result()->fetch_assoc();
    $status_stmt->close();

    if ($curr) {
        $updated_status = ($curr['claim_status'] === 'APPROVED') ? 'PENDING' : 'APPROVED';
        $update_stmt = $conn->prepare("UPDATE claims SET claim_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $updated_status, $claim_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Sync with Inventory (item_data.json)
        $target_item_name = strtolower(trim($curr['item_name']));
        $inventory_status = ($updated_status === 'APPROVED') ? 'CLAIMED' : 'IN CUSTODY';

        foreach ($items_data as $filename => &$item_meta) {
            $clean_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
            $clean_name = strtolower(str_replace('_', ' ', $clean_name));
            if ($clean_name === $target_item_name) {
                $item_meta['status'] = $inventory_status;
            }
        }
        unset($item_meta);
        file_put_contents($data_file, json_encode($items_data, JSON_PRETTY_PRINT));

        // Recalculate metrics
        $total = count(glob($upload_dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE));
        $claimed = 0;
        foreach ($items_data as $record) {
            if (($record['status'] ?? '') === 'CLAIMED') $claimed++;
        }
        $in_custody = $total - $claimed;

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'new_status' => $updated_status,
            'in_custody' => $in_custody,
            'claimed'    => $claimed
        ]);
        exit();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit();
}

// Handle Claim Deletion
if (isset($_GET['delete_claim'])) {
    $del_id = intval($_GET['delete_claim']);
    $del_stmt = $conn->prepare("DELETE FROM claims WHERE id = ?");
    $del_stmt->bind_param("i", $del_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: dashboard.php?view=claims");
    exit();
}

// Handle Item Deletion
if (isset($_GET['delete'])) {
    $target_file = basename($_GET['delete']);
    $full_path = $upload_dir . DIRECTORY_SEPARATOR . $target_file;
    if (file_exists($full_path)) {
        unlink($full_path);
        unset($items_data[$target_file]);
        file_put_contents($data_file, json_encode($items_data, JSON_PRETTY_PRINT));
    }
    header("Location: dashboard.php");
    exit();
}

// Handle Direct Quick Register from Dashboard (Preserves Exact Location)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_report'])) {
    $item_title   = trim($_POST['item_name'] ?? '');
    $raw_facility = trim($_POST['building'] ?? '');
    
    // Exact location assignment
    if ($raw_facility === 'Others') {
        $exact_location = trim($_POST['custom_building'] ?? 'Other Campus Spot');
        if (empty($exact_location)) {
            $exact_location = 'Campus Grounds';
        }
    } else {
        $exact_location = $raw_facility;
    }

    if (!empty($item_title) && isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['item_image']['tmp_name'];
        $file_ext  = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $clean_title  = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $item_title));
            $new_filename = time() . '_' . $clean_title . '.' . $file_ext;
            
            if (move_uploaded_file($file_tmp, $upload_dir . DIRECTORY_SEPARATOR . $new_filename)) {
                $items_data[$new_filename] = [
                    'status'   => 'IN CUSTODY',
                    'location' => $exact_location
                ];
                file_put_contents($data_file, json_encode($items_data, JSON_PRETTY_PRINT));
                header("Location: dashboard.php");
                exit();
            }
        }
    }
}

// Read current uploaded belongings
$images = [];
if ($upload_dir && is_dir($upload_dir)) {
    $scanned = glob($upload_dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
    if ($scanned) {
        $images = $scanned;
    }
}

$total_items = count($images);
$claimed_count = 0;
foreach ($images as $img) {
    $fn = basename($img);
    if (($items_data[$fn]['status'] ?? 'IN CUSTODY') === 'CLAIMED') {
        $claimed_count++;
    }
}
$in_custody_count = $total_items - $claimed_count;

// Fetch all claims from MySQL database
$claims_list = [];
$claims_result = $conn->query("SELECT * FROM claims ORDER BY submitted_at DESC");
if ($claims_result) {
    while ($row = $claims_result->fetch_assoc()) {
        $claims_list[] = $row;
    }
}
$total_claims = count($claims_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custodian Console - OLFU Antipolo</title>
    <style>
        /* ==========================================================================
           1. CSS VARIABLES & THEME CONFIGURATION
           ========================================================================== */
        :root {
            --olfu-green: #005a36;
            --olfu-hover: #004428;
            --olfu-dark: #003620;
            --olfu-gold: #f4c430;
            --olfu-light: #e8f5e9;
            --bg: #f4f6f8;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        /* ==========================================================================
           2. GLOBAL RESETS
           ========================================================================== */
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
            display: flex;
            flex-direction: column;
        }

        /* ==========================================================================
           3. FIXED TOP NAVIGATION BAR
           Uses position: fixed so header stays pinned at top during scroll
           ========================================================================== */
        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.98) 0%, rgba(0, 68, 40, 0.99) 100%);
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }

        .nav-container {
            max-width: 1200px;
            margin: auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            min-width: 0;
        }

        .logo-circle {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            border: 2px solid var(--olfu-gold);
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
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
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: 1px;
            color: #ffffff;
            line-height: 1;
            white-space: nowrap;
        }

        .brand-campus {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--olfu-gold);
            text-transform: uppercase;
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 8px;
            white-space: nowrap;
        }

        .brand-portal {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--olfu-light);
            opacity: 0.95;
            letter-spacing: 0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ==========================================================================
           4. SIDEBAR DRAWER & OVERLAY
           ========================================================================== */
        .nav-right-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-shrink: 0;
        }

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

        .sidebar-links a, .sidebar-links button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0.95rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
        }

        .sidebar-links a:hover, .sidebar-links button:hover {
            background: var(--olfu-light);
            color: var(--olfu-green);
            transform: translateX(4px);
        }

        .sidebar-claims-btn {
            background: rgba(244, 196, 48, 0.15) !important;
            border: 1px solid var(--olfu-gold) !important;
            color: #004428 !important;
            font-weight: 700 !important;
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

        .counter-badge {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 12px;
            font-weight: 800;
        }

        /* ==========================================================================
           5. MAIN DASHBOARD WORKSPACE CONTAINER
           Includes top margin to clear space below fixed navbar
           ========================================================================== */
        .dash-container {
            max-width: 1200px;
            margin: 5.5rem auto 1.5rem;
            padding: 0 1rem;
            width: 100%;
            flex-grow: 1;
        }

        .welcome-box {
            background: #ffffff;
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            border-left: 5px solid var(--olfu-gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .welcome-text h1 {
            font-size: 1.25rem;
            color: var(--olfu-green);
            font-weight: 800;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .btn-open-modal {
            background: var(--olfu-green);
            color: #ffffff;
            padding: 0.65rem 1.2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-open-modal:hover {
            background: var(--olfu-hover);
        }

        /* ==========================================================================
           6. METRIC CARDS GRID
           ========================================================================== */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: #ffffff;
            padding: 1.2rem;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            border-top: 4px solid var(--olfu-green);
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--olfu-green);
            margin-top: 0.3rem;
        }

        /* ==========================================================================
           7. DATA TABLES
           ========================================================================== */
        .table-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .table-header h2 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.85rem;
            min-width: 600px;
        }

        .data-table th {
            background: #f8fafc;
            padding: 0.8rem 1rem;
            font-weight: 700;
            color: var(--text-secondary);
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-primary);
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .item-thumb {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
            outline: none;
            transition: transform 0.15s ease;
            white-space: nowrap;
        }

        .badge-custody {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-claimed {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-pending {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* ==========================================================================
           8. PUBLIC STUDENT FEED PREVIEW
           ========================================================================== */
        .preview-section {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            display: none;
        }

        .preview-header {
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.8rem;
        }

        .preview-header h2 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--olfu-green);
        }

        .preview-header p {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        @media (max-width: 900px) {
            .items-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .items-grid {
                grid-template-columns: 1fr;
            }
        }

        .preview-card {
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .preview-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 800;
            z-index: 2;
        }

        .badge-lost-tag {
            background: #fee2e2;
            color: #d9383a;
        }

        .badge-claimed-tag {
            background: #dcfce7;
            color: #15803d;
        }

        .preview-img-wrapper {
            width: 100%;
            height: 180px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .preview-img-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .preview-card-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .preview-card-body h3 {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 700;
            text-transform: capitalize;
        }

        .preview-loc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--olfu-green);
            background: var(--olfu-light);
            padding: 4px 9px;
            border-radius: 20px;
            width: fit-content;
        }

        /* ==========================================================================
           9. MODAL POPUP DIALOG
           ========================================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(3px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.8rem 1.4rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            box-shadow: 0 20px 45px rgba(0,0,0,0.2);
        }

        .modal-close {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1rem;
        }

        .form-group label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select {
            padding: 0.7rem 0.85rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.88rem;
            outline: none;
            background: #ffffff;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--olfu-green);
        }

        /* ==========================================================================
           10. DASHBOARD FOOTER
           ========================================================================== */
        .dash-footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 1.5rem 1rem;
            text-align: center;
            font-size: 0.78rem;
            border-top: 4px solid var(--olfu-green);
            margin-top: auto;
            width: 100%;
        }

        /* ==========================================================================
           11. MOBILE BREAKPOINTS
           ========================================================================== */
        @media screen and (max-width: 650px) {
            .dash-container {
                margin-top: 4.8rem;
            }

            .brand-campus, .brand-portal {
                display: none;
            }

            .brand-title {
                font-size: 1.15rem;
            }

            .logo-circle {
                width: 38px;
                height: 38px;
            }

            .brand-logo {
                width: 28px;
                height: 28px;
            }

            .welcome-box {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.1rem;
            }

            .btn-open-modal {
                width: 100%;
                text-align: center;
            }

            .dash-footer {
                padding: 1.2rem 0.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR WITH FIXED TOP HEADER -->
    <header class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-link">
                <div class="logo-circle">
                    <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal" class="brand-logo">
                </div>
                <div class="brand-text">
                    <div class="brand-header-row">
                        <span class="brand-title">OLFU CUSTODIAN</span>
                        <span class="brand-campus">ANTIPOLO</span>
                    </div>
                    <span class="brand-portal">Security Console</span>
                </div>
            </a>

            <div class="nav-right-actions">
                <button class="burger-btn" onclick="openSidebar()" aria-label="Open Navigation Menu">
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
            <h3>CUSTODIAN MENU</h3>
            <span class="sidebar-close" onclick="closeSidebar()">&times;</span>
        </div>
        
        <nav class="sidebar-links">
            <div class="sidebar-section-title">Console Navigation</div>
            <button type="button" class="sidebar-claims-btn" onclick="showClaimsView(); closeSidebar();">
                <span>📩 Claim Requests</span>
                <span class="counter-badge"><?php echo $total_claims; ?></span>
            </button>
            <button type="button" id="btnViewToggle" onclick="toggleDashboardView(); closeSidebar();">
                <span>👁️ Student Feed View</span>
            </button>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.6rem 0;">
            <div class="sidebar-section-title">Account</div>
            <a href="dashboard.php?action=logout" class="sidebar-logout-link">
                <span>🚪 Log Out Console</span>
            </a>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: #94a3b8; text-align: center;">
            OLFU Antipolo Security Office
        </div>
    </aside>

    <main class="dash-container">
        <!-- Welcome Banner -->
        <section class="welcome-box">
            <div class="welcome-text">
                <h1 id="welcomeTitle">Campus Custodian & Security Console</h1>
                <p id="welcomeDesc">Manage inventory records, review incoming claim requests, and toggle handovers.</p>
            </div>
            <button class="btn-open-modal" onclick="openRegisterModal()">+ Register New Item</button>
        </section>

        <!-- Metric Statistics -->
        <section class="metrics-grid">
            <div class="metric-card">
                <span class="metric-label">Total Logged Items</span>
                <div class="metric-value"><?php echo $total_items; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: var(--olfu-gold);">
                <span class="metric-label">In Custody</span>
                <div class="metric-value" id="metricInCustody"><?php echo $in_custody_count; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: #10b981;">
                <span class="metric-label">Claimed & Returned</span>
                <div class="metric-value" style="color: #10b981;" id="metricClaimed"><?php echo $claimed_count; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: #ef4444;">
                <span class="metric-label">Student Claim Requests</span>
                <div class="metric-value" style="color: #dc2626;"><?php echo $total_claims; ?></div>
            </div>
        </section>

        <!-- 1. Inventory Management Table View (DEFAULT VIEW) -->
        <section class="table-card" id="viewTable">
            <div class="table-header">
                <h2>Current Campus Belongings (Inventory Table)</h2>
            </div>

            <?php if (!empty($images)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Item Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        rsort($images);
                        $default_spots = [
                            'Vicente M. Santos Bldg (VMS)',
                            'Juliet Santos Bldg (JSB)',
                            'San Juan Bautista Hall (SJBH)',
                            'St. Catherine Hall (SCH)'
                        ];

                        foreach ($images as $img):
                            $fname = basename($img);
                            $clean_title = preg_replace('/^\d+_/', '', pathinfo($fname, PATHINFO_FILENAME));
                            
                            $display_location = $items_data[$fname]['location'] ?? $default_spots[crc32($fname) % count($default_spots)];
                            $current_status   = $items_data[$fname]['status'] ?? 'IN CUSTODY';
                            $is_claimed       = ($current_status === 'CLAIMED');
                        ?>
                            <tr>
                                <td>
                                    <img src="../../uploads/<?php echo htmlspecialchars($fname); ?>" alt="" class="item-thumb">
                                </td>
                                <td style="font-weight: 700;">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $clean_title))); ?>
                                </td>
                                <td style="color: var(--text-secondary);">
                                    📍 <?php echo htmlspecialchars($display_location); ?>
                                </td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="status-badge <?php echo $is_claimed ? 'badge-claimed' : 'badge-custody'; ?>"
                                        onclick="toggleItemStatus('<?php echo htmlspecialchars($fname, ENT_QUOTES); ?>', this)">
                                        <?php echo $is_claimed ? '✓ CLAIMED' : '⏳ IN CUSTODY'; ?>
                                    </button>
                                </td>
                                <td style="text-align: right;">
                                    <a href="dashboard.php?delete=<?php echo urlencode($fname); ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this item?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 3.5rem; text-align: center; color: var(--text-secondary);">
                    <p>No items currently logged in campus storage.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- 2. Student Claim Requests Table View -->
        <section class="table-card" id="viewClaims" style="display: none; border-top: 4px solid var(--olfu-gold);">
            <div class="table-header">
                <h2>Student Claim Requests (Database Feed)</h2>
                <button type="button" class="btn-open-modal" style="padding: 0.45rem 1rem; font-size: 0.82rem;" onclick="showInventoryView()">
                    &larr; Back to Inventory
                </button>
            </div>

            <?php if (!empty($claims_list)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Student Email</th>
                            <th>Proof / Distinct Details</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims_list as $claim): 
                            $is_approved = ($claim['claim_status'] === 'APPROVED');
                        ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--olfu-green);">
                                    📦 <?php echo htmlspecialchars($claim['item_name']); ?>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($claim['student_email']); ?>" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                                        <?php echo htmlspecialchars($claim['student_email']); ?>
                                    </a>
                                </td>
                                <td style="max-width: 320px; line-height: 1.4; color: #334155;">
                                    <?php echo nl2br(htmlspecialchars($claim['proof_details'])); ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    <?php echo date('M d, Y - h:i A', strtotime($claim['submitted_at'])); ?>
                                </td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="status-badge <?php echo $is_approved ? 'badge-claimed' : 'badge-pending'; ?>"
                                        onclick="toggleClaimStatus(<?php echo $claim['id']; ?>, this)">
                                        <?php echo $is_approved ? '✓ APPROVED' : '⏳ PENDING'; ?>
                                    </button>
                                </td>
                                <td style="text-align: right;">
                                    <a href="dashboard.php?delete_claim=<?php echo $claim['id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Are you sure you want to remove this claim record?');">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 3.5rem; text-align: center; color: var(--text-secondary);">
                    <p>No student claim requests submitted yet.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- 3. Public Student Feed Cards View -->
        <section class="preview-section" id="viewPreview">
            <div class="preview-header">
                <h2>Public Student Feed View</h2>
                <p>Real-time visual display matching how students view belongings on the live campus feed.</p>
            </div>

            <?php if (!empty($images)): ?>
                <div class="items-grid">
                    <?php 
                    foreach ($images as $img):
                        $fname = basename($img);
                        $clean_title = preg_replace('/^\d+_/', '', pathinfo($fname, PATHINFO_FILENAME));
                        $display_location = $items_data[$fname]['location'] ?? $default_spots[crc32($fname) % count($default_spots)];
                        $current_status   = $items_data[$fname]['status'] ?? 'IN CUSTODY';
                        $is_claimed       = ($current_status === 'CLAIMED');
                    ?>
                        <article class="preview-card">
                            <span class="preview-badge <?php echo $is_claimed ? 'badge-claimed-tag' : 'badge-lost-tag'; ?>" id="tag-<?php echo md5($fname); ?>">
                                <?php echo $is_claimed ? 'CLAIMED' : 'LOST'; ?>
                            </span>
                            <div class="preview-img-wrapper">
                                <img src="../../uploads/<?php echo htmlspecialchars($fname); ?>" alt="">
                            </div>
                            <div class="preview-card-body">
                                <h3><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $clean_title))); ?></h3>
                                <div class="preview-loc">
                                    <span>📍</span>
                                    <span><?php echo htmlspecialchars($display_location); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No items currently available in the public feed.</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- Custodian Register Item Modal -->
    <div id="registerModal" class="modal-overlay">
        <div class="modal-card">
            <span class="modal-close" onclick="closeRegisterModal()">&times;</span>
            <h2 style="color: var(--olfu-green); font-size: 1.2rem; margin-bottom: 0.3rem;">Log New Belonging</h2>
            <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1.2rem;">Add an item directly to the active inventory feed.</p>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="quick_report" value="1">

                <div class="form-group">
                    <label>Item Name / Title</label>
                    <input type="text" name="item_name" placeholder="e.g. Tumbler, Wallet, Laptop..." required>
                </div>

                <div class="form-group">
                    <label>Campus Facility / Location</label>
                    <select name="building" onchange="toggleAdminCustomBuilding(this.value)" required>
                        <option value="Vicente M. Santos Bldg (VMS)">Vicente M. Santos Bldg (VMS)</option>
                        <option value="Juliet Santos Bldg (JSB)">Juliet Santos Bldg (JSB)</option>
                        <option value="San Juan Bautista Hall (SJBH)">San Juan Bautista Hall (SJBH)</option>
                        <option value="St. Catherine Hall (SCH)">St. Catherine Hall (SCH)</option>
                        <option value="San Pedro Calungsod Bldg (SPCB)">San Pedro Calungsod Bldg (SPCB)</option>
                        <option value="Athletics & Gym Center">Athletics & Gym Center</option>
                        <option value="University Canteen">University Canteen / Food Court</option>
                        <option value="Main Gate Guard House">Main Gate Guard House</option>
                        <option value="Others">Others (Please specify)</option>
                    </select>
                </div>

                <div class="form-group" id="adminCustomBuildingGroup" style="display: none;">
                    <label>Specify Facility / Area</label>
                    <input type="text" id="admin_custom_building" name="custom_building" placeholder="e.g. Student Lounge, Parking Lot, Oval...">
                </div>

                <div class="form-group">
                    <label>Item Photo</label>
                    <input type="file" name="item_image" accept="image/*" required>
                </div>

                <button type="submit" class="btn-open-modal" style="width: 100%; margin-top: 0.5rem;">Upload & Log Item</button>
            </form>
        </div>
    </div>

    <footer class="dash-footer">
        Our Lady of Fatima University &bull; Antipolo Campus Security & Facilities Custody Management
    </footer>

    <!-- View Switchers, Sidebar Toggles & Instant AJAX Toggling -->
    <script>
        let currentView = 'table';

        // Open slide-out navigation menu
        function openSidebar() {
            document.getElementById('sidebarDrawer').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }

        // Close slide-out navigation menu
        function closeSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        // Toggle between table view and visual student feed
        function toggleDashboardView() {
            const tableView = document.getElementById('viewTable');
            const previewView = document.getElementById('viewPreview');
            const claimsView = document.getElementById('viewClaims');

            if (currentView === 'table' || currentView === 'claims') {
                tableView.style.display = 'none';
                claimsView.style.display = 'none';
                previewView.style.display = 'block';
                currentView = 'preview';
            } else {
                previewView.style.display = 'none';
                claimsView.style.display = 'none';
                tableView.style.display = 'block';
                currentView = 'table';
            }
        }

        // Switch workspace to Claim Requests table
        function showClaimsView() {
            document.getElementById('viewTable').style.display = 'none';
            document.getElementById('viewPreview').style.display = 'none';
            document.getElementById('viewClaims').style.display = 'block';
            currentView = 'claims';
        }

        // Switch workspace to Inventory table
        function showInventoryView() {
            document.getElementById('viewClaims').style.display = 'none';
            document.getElementById('viewPreview').style.display = 'none';
            document.getElementById('viewTable').style.display = 'block';
            currentView = 'table';
        }

        // Show/hide custom building text input field
        function toggleAdminCustomBuilding(val) {
            const group = document.getElementById('adminCustomBuildingGroup');
            const input = document.getElementById('admin_custom_building');
            if (val === 'Others') {
                group.style.display = 'flex';
                input.required = true;
                input.focus();
            } else {
                group.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }

        // AJAX handler for updating inventory item status
        function toggleItemStatus(filename, btnElement) {
            const formData = new FormData();
            formData.append('ajax_toggle', '1');
            formData.append('filename', filename);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 'CLAIMED') {
                        btnElement.className = 'status-badge badge-claimed';
                        btnElement.innerHTML = '✓ CLAIMED';
                    } else {
                        btnElement.className = 'status-badge badge-custody';
                        btnElement.innerHTML = '⏳ IN CUSTODY';
                    }

                    const previewBadge = document.getElementById('tag-' + data.badge_id);
                    if (previewBadge) {
                        if (data.new_status === 'CLAIMED') {
                            previewBadge.className = 'preview-badge badge-claimed-tag';
                            previewBadge.innerText = 'CLAIMED';
                        } else {
                            previewBadge.className = 'preview-badge badge-lost-tag';
                            previewBadge.innerText = 'LOST';
                        }
                    }

                    document.getElementById('metricInCustody').innerText = data.in_custody;
                    document.getElementById('metricClaimed').innerText = data.claimed;
                }
            })
            .catch(err => console.error('Status toggle failed:', err));
        }

        // AJAX handler for approving/reverting claim requests
        function toggleClaimStatus(claimId, btnElement) {
            const formData = new FormData();
            formData.append('ajax_claim_status', '1');
            formData.append('claim_id', claimId);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.new_status === 'APPROVED') {
                        btnElement.className = 'status-badge badge-claimed';
                        btnElement.innerHTML = '✓ APPROVED';
                    } else {
                        btnElement.className = 'status-badge badge-pending';
                        btnElement.innerHTML = '⏳ PENDING';
                    }

                    if (data.in_custody !== undefined) {
                        document.getElementById('metricInCustody').innerText = data.in_custody;
                    }
                    if (data.claimed !== undefined) {
                        document.getElementById('metricClaimed').innerText = data.claimed;
                    }
                }
            })
            .catch(err => console.error('Claim toggle failed:', err));
        }

        // Open modal dialog for logging new items
        function openRegisterModal() {
            document.getElementById('registerModal').style.display = 'flex';
        }

        // Close modal dialog for logging new items
        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        // Automatically display claim requests if view parameter is set in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('view') === 'claims') {
            showClaimsView();
        }
    </script>
</body>
</html>