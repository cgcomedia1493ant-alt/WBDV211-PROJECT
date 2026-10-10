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

$upload_dir = realpath(__DIR__ . '/../../uploads');
$data_file  = __DIR__ . '/item_data.json';

// Load persistent item database (status and exact location)
$items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

// Handle Background AJAX Status Toggle (Prevents Page Scroll/Jump)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custodian Console - OLFU Antipolo</title>
    <style>
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

        /* Top Navigation */
        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.96) 0%, rgba(0, 68, 40, 0.98) 100%);
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
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
            width: 58px;
            height: 58px;
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
            width: 48px;
            height: 48px;
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
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: #ffffff;
            line-height: 1;
        }

        .brand-campus {
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--olfu-gold);
            text-transform: uppercase;
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 10px;
        }

        .brand-portal {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--olfu-light);
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-view-toggle {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.35);
            padding: 0.55rem 1.05rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-view-toggle:hover {
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-1px);
        }

        .btn-logout {
            color: #fee2e2;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            background: rgba(239, 68, 68, 0.2);
            padding: 0.55rem 1.1rem;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.4);
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Main Workspace Container */
        .dash-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            width: 100%;
            flex-grow: 1;
        }

        .welcome-box {
            background: #ffffff;
            padding: 1.6rem 2rem;
            border-radius: 14px;
            border-left: 5px solid var(--olfu-gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .welcome-text h1 {
            font-size: 1.45rem;
            color: var(--olfu-green);
            font-weight: 800;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .btn-open-modal {
            background: var(--olfu-green);
            color: #ffffff;
            padding: 0.75rem 1.4rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.92rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .btn-open-modal:hover {
            background: var(--olfu-hover);
            transform: translateY(-1px);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            border-top: 4px solid var(--olfu-green);
        }

        .metric-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 2.1rem;
            font-weight: 900;
            color: var(--olfu-green);
            margin-top: 0.4rem;
        }

        /* 1. Inventory Table View */
        .table-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .table-header {
            padding: 1.25rem 1.8rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .data-table th {
            background: #f8fafc;
            padding: 0.9rem 1.5rem;
            font-weight: 700;
            color: var(--text-secondary);
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-primary);
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .item-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
            outline: none;
            transition: transform 0.15s ease, opacity 0.2s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
            opacity: 0.9;
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

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* 2. Public Student Feed View */
        .preview-section {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.8rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            display: none;
        }

        .preview-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }

        .preview-header h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--olfu-green);
        }

        .preview-header p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
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
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
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
            height: 190px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .preview-img-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .preview-card-body {
            padding: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .preview-card-body h3 {
            font-size: 1.05rem;
            color: var(--text-primary);
            font-weight: 700;
            text-transform: capitalize;
        }

        .preview-loc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--olfu-green);
            background: var(--olfu-light);
            padding: 4px 10px;
            border-radius: 20px;
            width: fit-content;
        }

        /* Modal Dialog */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(3px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2.2rem;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 20px 45px rgba(0,0,0,0.2);
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1.1rem;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select {
            padding: 0.75rem 1rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.92rem;
            outline: none;
            background: #ffffff;
            transition: border-color 0.2s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--olfu-green);
        }

        .dash-footer {
            background: #00331e;
            color: #cbd5e1;
            padding: 1.8rem;
            text-align: center;
            font-size: 0.82rem;
            border-top: 4px solid var(--olfu-green);
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- NAVBAR WITH VIEW SWITCHER BUTTON BEFORE LOGOUT -->
    <header class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-link">
                <div class="logo-circle">
                    <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal" class="brand-logo">
                </div>
                <div class="brand-text">
                    <div class="brand-header-row">
                        <span class="brand-title">OLFU</span>
                        <span class="brand-campus">ANTIPOLO CAMPUS</span>
                    </div>
                    <span class="brand-portal">Custodian & Security Console</span>
                </div>
            </a>
            <div class="nav-right">
                <button type="button" class="btn-view-toggle" id="btnViewToggle" onclick="toggleDashboardView()">
                    👁️ Student Feed View
                </button>
                <a href="dashboard.php?action=logout" class="btn-logout">Log Out</a>
            </div>
        </div>
    </header>

    <main class="dash-container">
        <!-- Welcome Banner -->
        <section class="welcome-box">
            <div class="welcome-text">
                <h1 id="welcomeTitle">Campus Custodian & Security Console</h1>
                <p id="welcomeDesc">Manage inventory records, toggle claim handovers, and delete unneeded items.</p>
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
        </section>

        <!-- 1. Inventory Management Table View -->
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
                            
                            // Retrieve saved location from JSON, or fallback safely
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

        <!-- 2. Public Student Feed Cards View -->
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

    <!-- Custodian Register Item Modal with Persistent Custom Location -->
    <div id="registerModal" class="modal-overlay">
        <div class="modal-card">
            <span class="modal-close" onclick="closeRegisterModal()">&times;</span>
            <h2 style="color: var(--olfu-green); font-size: 1.3rem; margin-bottom: 0.3rem;">Log New Belonging</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.4rem;">Add an item directly to the active inventory feed.</p>

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

                <!-- Custom Location Field (Appears only when "Others" is selected) -->
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

    <!-- View Switcher, Dynamic Custom Location, & Instant Status Toggling -->
    <script>
        let isFeedView = false;

        function toggleDashboardView() {
            const tableView = document.getElementById('viewTable');
            const previewView = document.getElementById('viewPreview');
            const toggleBtn = document.getElementById('btnViewToggle');

            isFeedView = !isFeedView;

            if (isFeedView) {
                tableView.style.display = 'none';
                previewView.style.display = 'block';
                toggleBtn.innerHTML = '📋 Inventory Table';
            } else {
                tableView.style.display = 'block';
                previewView.style.display = 'none';
                toggleBtn.innerHTML = '👁️ Student Feed View';
            }
        }

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

                    // Update live feed badge below in real-time
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

                    // Update metrics numbers
                    document.getElementById('metricInCustody').innerText = data.in_custody;
                    document.getElementById('metricClaimed').innerText = data.claimed;
                }
            })
            .catch(err => console.error('Status toggle failed:', err));
        }

        function openRegisterModal() {
            document.getElementById('registerModal').style.display = 'flex';
        }
        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }
    </script>
</body>
</html>