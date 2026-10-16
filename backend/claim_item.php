<?php
// Start session to verify authentication status
session_start();

// Ensure either student or admin is logged in
$is_authenticated = isset($_SESSION['user_id']) || isset($_SESSION['user_email']) || !empty($_SESSION['admin_logged_in']);

if (!$is_authenticated) {
    header("Location: login/login.php");
    exit();
}

// Connect to MySQL database (katabi lang ang login/database.php)
require_once __DIR__ . '/login/database.php';

$message = '';
$message_type = '';

// Retrieve target item name from URL or form POST
$item_name = trim($_GET['item'] ?? ($_POST['item_name'] ?? ''));

// Handle Claim Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            max-width: 680px;
            width: 100%;
            margin: 3rem auto;
            padding: 0 1.25rem;
            flex-grow: 1;
        }

        .claim-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2.2rem;
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
            margin-bottom: 1.8rem;
        }

        .claim-header h1 {
            color: var(--olfu-green);
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 0.4rem;
        }

        .claim-header p {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .item-highlight-box {
            background: var(--olfu-light);
            border: 1px solid #c8e6c9;
            padding: 0.9rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
            color: #004d2c;
            font-weight: 600;
        }

        .alert-box {
            padding: 0.9rem 1.2rem;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
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
            margin-bottom: 1.25rem;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group textarea {
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
        .form-group textarea:focus {
            border-color: var(--olfu-green);
            box-shadow: 0 0 0 3.5px rgba(0, 90, 54, 0.1);
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
            width: 100%;
            margin-top: 0.5rem;
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

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="../../index.php" class="brand-link">
                <div class="logo-circle">
                    <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
                </div>
                <div>
                    <div class="brand-title">OLFU ANTIPOLO</div>
                    <div class="brand-campus">Lost & Found Portal</div>
                </div>
            </a>
            <a href="../../index.php" class="btn-back">&larr; Return to Home Feed</a>
        </div>
    </header>

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

            <?php if (!empty($item_name)): ?>
                <div class="item-highlight-box">
                    <span>📦</span>
                    <span>Item to Claim: <strong><?php echo htmlspecialchars($item_name); ?></strong></span>
                </div>
            <?php endif; ?>

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

                <button type="submit" class="btn-submit">Submit Claim Request</button>
            </form>
        </section>
    </main>

    <footer class="page-footer">
        Our Lady of Fatima University &bull; Antipolo Campus Security & Lost and Found Desk
    </footer>

</body>
</html>