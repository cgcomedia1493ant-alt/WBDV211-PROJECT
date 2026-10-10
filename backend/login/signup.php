<?php
// Start user session
session_start();

$error_message = '';
$success_message = '';

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['fullname'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm_password'] ?? '');

    // Form field verification
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error_message = 'Please fill out all required fields.';
    } elseif ($password !== $confirm) {
        $error_message = 'Passwords do not match. Please verify.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(student\.)?fatima\.edu\.ph$/', $email)) {
        // Enforce institutional domain constraint
        $error_message = 'Invalid institutional credentials. Please try again.';
    } else {
        // Authentication success
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $full_name;

        // Redirect back to portal feed
        header("Location: ../../index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account - OLFU Lost & Found</title>
    <!-- Base login stylesheet -->
    <link rel="stylesheet" href="../../frontend/css/login.css?v=4">

    <!-- Compact Vertical Layout Overrides -->
    <style>
        .signup-card {
            max-width: 410px !important;
            padding: 1.7rem 2rem 1.4rem !important; /* Compact vertical padding */
        }

        .signup-card .logo-badge {
            width: 52px !important;
            height: 52px !important;
            margin-bottom: 0.5rem !important;
        }

        .signup-card .logo-badge img {
            width: 38px !important;
            height: 38px !important;
        }

        .signup-card .login-title {
            font-size: 1.35rem !important;
            margin-bottom: 0.2rem !important;
        }

        .signup-card .login-sub {
            font-size: 0.78rem !important;
            margin-bottom: 1rem !important;
        }

        .signup-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem !important; /* Tight gaps between stacked inputs */
        }

        .signup-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem !important;
        }

        .signup-form .form-group label {
            font-size: 0.72rem !important;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signup-form .form-group input {
            padding: 0.65rem 0.9rem 0.65rem 2.45rem !important;
            font-size: 0.88rem !important;
            border-radius: 9px !important;
        }

        .signup-form .input-icon {
            left: 12px !important;
            font-size: 0.88rem !important;
        }

        .signup-card .btn-signin {
            padding: 0.75rem !important;
            font-size: 0.9rem !important;
            margin-top: 0.35rem !important;
            border-radius: 9px !important;
        }

        .signup-card .login-footer-links {
            margin-top: 1rem !important;
            font-size: 0.78rem !important;
        }

        .signup-card .error-banner {
            padding: 0.55rem 0.8rem !important;
            font-size: 0.78rem !important;
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <main class="login-card signup-card">
        <!-- University Medallion -->
        <div class="logo-badge">
            <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
        </div>
        <h1 class="login-title">Create Account</h1>
        <p class="login-sub">Our Lady of Fatima University • Antipolo Campus</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="signup-form" method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input 
                        type="text" 
                        name="fullname" 
                        placeholder="Full Name" 
                        required>
                </div>
            </div>

            <div class="form-group">
                <label>Institutional Account</label>
                <div class="input-wrapper">
                    <span class="input-icon">✉️</span>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Institutional Account" 
                        autocomplete="email" 
                        required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="••••••••" 
                        autocomplete="new-password" 
                        required>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🛡️</span>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        placeholder="••••••••" 
                        autocomplete="new-password" 
                        required>
                </div>
            </div>

            <button type="submit" class="btn-signin">Complete Registration</button>
        </form>

        <div class="login-footer-links">
            Already registered? <a href="login.php" style="color: var(--olfu-green); font-weight: 700;">Sign in here</a> • <a href="../../index.php">&larr; Return to Home Feed</a>
        </div>
    </main>

</body>
</html>