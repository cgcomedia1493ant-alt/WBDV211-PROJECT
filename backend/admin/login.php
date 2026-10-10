<?php
// Start staff session
session_start();

$error_message = '';

// Handle staff authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_account = strtolower(trim($_POST['username'] ?? ''));
    $staff_pass    = trim($_POST['password'] ?? '');

    if (!empty($staff_account) && !empty($staff_pass)) {
        // Accepted authorized institutional roles
        $valid_accounts = [
            'custodian', 
            'security', 
            'custodian@fatima.edu.ph', 
            'security@fatima.edu.ph',
            'desk@fatima.edu.ph'
        ];

        // Flexible credential authentication
        if (in_array($staff_account, $valid_accounts) && ($staff_pass === 'fatima123' || $staff_pass === 'admin123')) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = 'Campus Custodian Desk';
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = 'Invalid staff credentials. Access denied.';
        }
    } else {
        $error_message = 'Please provide both staff account and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Custodian Portal - OLFU</title>
    <!-- Use consistent styling -->
    <link rel="stylesheet" href="../../frontend/css/login.css?v=6">
    <style>
        .staff-badge-label {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.8px;
            margin-bottom: 0.5rem;
            border: 1px solid #fde68a;
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <main class="login-card" style="max-width: 420px;">
        <!-- University Medallion -->
        <div class="logo-badge">
            <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
        </div>

        <span class="staff-badge-label">CAMPUS SECURITY & RECOVERY DESK</span>
        <h1 class="login-title">Custodian Access</h1>
        <p class="login-sub">Facility Log & Belongings Management</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label>Staff Account</label>
                <div class="input-wrapper">
                    <span class="input-icon">🛡️</span>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="custodian@fatima.edu.ph" 
                        autocomplete="username"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label>Security Key</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔑</span>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="••••••••" 
                        autocomplete="current-password"
                        required>
                </div>
            </div>

            <button type="submit" class="btn-signin">Verify & Access Dashboard</button>
        </form>

        <div class="login-footer-links">
            <a href="../../index.php">&larr; Return to Public Feed</a>
        </div>
    </main>

</body>
</html>