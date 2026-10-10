<?php
// Start user session to verify authentication status
session_start();

// Ensure either student/staff OR custodian admin is authenticated
$is_authenticated = isset($_SESSION['user_id']) || isset($_SESSION['user_email']) || !empty($_SESSION['admin_logged_in']);

if (!$is_authenticated) {
    header("Location: backend/login/login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle item report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_status   = trim($_POST['status'] ?? 'Lost');
    $report_type  = (strtoupper($raw_status) === 'FOUND') ? 'FOUND' : 'LOST';
    $item_title   = trim($_POST['item_name'] ?? '');
    $raw_building = trim($_POST['building'] ?? '');

    // Check if custom building was provided via 'Others'
    if ($raw_building === 'Others') {
        $building = trim($_POST['custom_building'] ?? '');
    } else {
        $building = $raw_building;
    }

    $location_detail = trim($_POST['location_detail'] ?? '');
    $full_location = !empty($location_detail) ? ($building . ' (' . $location_detail . ')') : $building;

    if (empty($item_title) || empty($building)) {
        $message = 'Please provide the item name and designated campus building or area.';
        $message_type = 'error';
    } elseif (!isset($_FILES['item_image']) || $_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please attach a clear photo of the item for verification.';
        $message_type = 'error';
    } else {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp  = $_FILES['item_image']['tmp_name'];
        $file_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['item_image']['name']));
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Allowed formats without GIF
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_exts)) {
            $message = 'Invalid image format. Allowed formats: JPG, PNG, WEBP.';
            $message_type = 'error';
        } else {
            // Standardize saved name with timestamp prefix
            $clean_title  = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $item_title));
            $new_filename = time() . '_' . $clean_title . '.' . $file_ext;
            $destination  = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                // Persist item metadata to JSON so it reflects immediately on the feed and dashboard
                $data_file = __DIR__ . '/backend/admin/item_data.json';
                $items_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];

                $items_data[$new_filename] = [
                    'status'      => 'IN CUSTODY',
                    'report_type' => $report_type,
                    'location'    => $full_location
                ];
                file_put_contents($data_file, json_encode($items_data, JSON_PRETTY_PRINT));

                $message = 'Item successfully logged to the campus recovery feed!';
                $message_type = 'success';
            } else {
                $message = 'File upload failed. Please verify folder permissions.';
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
    <title>Report Item - OLFU Lost & Found Portal</title>
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: linear-gradient(135deg, rgba(0, 90, 54, 0.96) 0%, rgba(0, 68, 40, 0.98) 100%);
            padding: 0.85rem 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        .nav-container {
            max-width: 1040px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }

        .logo-circle {
            width: 48px;
            height: 48px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--olfu-gold);
            overflow: hidden;
        }

        .logo-circle img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .brand-campus {
            font-size: 0.78rem;
            color: var(--olfu-gold);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-back {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .page-container {
            max-width: 780px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 0 1.25rem;
            flex-grow: 1;
        }

        .form-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2.2rem;
            box-shadow: 0 12px 35px rgba(0, 90, 54, 0.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .form-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--olfu-green), var(--olfu-gold));
            border-radius: 20px 20px 0 0;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h1 {
            color: var(--olfu-green);
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .alert-box {
            padding: 0.85rem 1.1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
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

        .report-form {
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            padding: 0.8rem 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.92rem;
            color: var(--text-main);
            outline: none;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--olfu-green);
            box-shadow: 0 0 0 3.5px rgba(0, 90, 54, 0.1);
        }

        .file-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1.8rem 1.5rem;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .file-dropzone:hover {
            border-color: var(--olfu-green);
            background: var(--olfu-light);
        }

        .file-dropzone input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .dropzone-icon {
            font-size: 2.2rem;
            margin-bottom: 0.4rem;
        }

        .dropzone-text {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .dropzone-sub {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .file-selected-name {
            margin-top: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--olfu-green);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--olfu-green) 0%, var(--olfu-hover) 100%);
            color: #ffffff;
            padding: 0.95rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.4px;
            cursor: pointer;
            margin-top: 0.6rem;
            box-shadow: 0 6px 18px rgba(0, 90, 54, 0.25);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0, 90, 54, 0.35);
        }

        .page-footer {
            background: #ffffff;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- Header Bar -->
    <header class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand-link">
                <div class="logo-circle">
                    <img src="frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
                </div>
                <div>
                    <div class="brand-title">OLFU ANTIPOLO</div>
                    <div class="brand-campus">Lost & Found Portal</div>
                </div>
            </a>
            <a href="index.php" class="btn-back">&larr; Return to Home Feed</a>
        </div>
    </header>

    <!-- Main Content Form -->
    <main class="page-container">
        <section class="form-card">
            <div class="form-header">
                <h1>Report a Belonging</h1>
                <p>Submit item details and an image preview to log an entry into the campus lost and found registry.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form class="report-form" method="POST" action="" enctype="multipart/form-data">
                <!-- Status & Title Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Report Status</label>
                        <select id="status" name="status" required>
                            <option value="Lost">Lost Item (Missing Belonging)</option>
                            <option value="Found">Found Item (Recovered Belonging)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="item_name">Item Identification</label>
                        <input 
                            type="text" 
                            id="item_name" 
                            name="item_name" 
                            placeholder="e.g. Phone, Wallet, Tumbler..." 
                            required>
                    </div>
                </div>

                <!-- Building & Spot Location Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="building">Antipolo Campus Building</label>
                        <select id="building" name="building" onchange="toggleCustomBuilding(this.value)" required>
                            <option value="" disabled selected>Select Building / Area</option>
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

                    <div class="form-group">
                        <label for="location_detail">Specific Facility / Floor</label>
                        <input 
                            type="text" 
                            id="location_detail" 
                            name="location_detail" 
                            placeholder="e.g. 3rd Floor Hallway, Room 304, Sa gilid ng upuan..." 
                            required>
                    </div>
                </div>

                <!-- Custom Building Input (Appears only when "Others" is selected) -->
                <div class="form-group" id="customBuildingGroup" style="display: none;">
                    <label for="custom_building">Specify Campus Area / Location</label>
                    <input 
                        type="text" 
                        id="custom_building" 
                        name="custom_building" 
                        placeholder="e.g. Student Pavilion, Parking Lot, Oval...">
                </div>

                <!-- File Dropzone (GIF format removed) -->
                <div class="form-group">
                    <label>Photographic Evidence</label>
                    <div class="file-dropzone">
                        <input 
                            type="file" 
                            id="item_image" 
                            name="item_image" 
                            accept="image/png, image/jpeg, image/jpg, image/webp" 
                            onchange="previewFilename(this)" 
                            required>
                        <div class="dropzone-icon">📷</div>
                        <div class="dropzone-text">Click to browse or drag image here</div>
                        <div class="dropzone-sub">Supported formats: JPG, PNG, WEBP (Max: 5MB)</div>
                        <div id="fileDisplay" class="file-selected-name"></div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">Publish Item to Campus Feed</button>
            </form>
        </section>
    </main>

    <footer class="page-footer">
        Our Lady of Fatima University &bull; Antipolo Campus Security & Lost and Found Desk
    </footer>

    <script>
        function previewFilename(input) {
            const display = document.getElementById('fileDisplay');
            if (input.files && input.files.length > 0) {
                display.innerText = 'Selected file: ' + input.files[0].name;
            } else {
                display.innerText = '';
            }
        }

        function toggleCustomBuilding(val) {
            const customGroup = document.getElementById('customBuildingGroup');
            const customInput = document.getElementById('custom_building');
            if (val === 'Others') {
                customGroup.style.display = 'flex';
                customInput.required = true;
                customInput.focus();
            } else {
                customGroup.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
    </script>
</body>
</html>