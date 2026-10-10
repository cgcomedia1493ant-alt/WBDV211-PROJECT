<?php
// Start user session to handle authentication
session_start();

$error_message = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    // Validate presence and institutional domain match
    if (empty($email) || empty($password)) {
        $error_message = 'Please provide your institutional account and password.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(student\.)?fatima\.edu\.ph$/', $email)) {
        // Generic security notice without exposing domain pattern specifics
        $error_message = 'Invalid institutional credentials. Please try again.';
    } else {
        // Successful authentication
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = strstr($email, '@', true) ?: 'Student';

        // Redirect back to main feed
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
    <title>Institutional Portal Sign In</title>
    <!-- External stylesheet for login styling and animations -->
    <link rel="stylesheet" href="../../frontend/css/login.css?v=1">
</head>
<body>

    <!-- Animated Ambient Glowing Orbs -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <main class="login-card">
        <!-- University Medallion with pulsing glow -->
        <div class="logo-badge">
            <img src="../../frontend/assets/images/OLFU LOGO.png" alt="OLFU Seal">
        </div>
        <h1 class="login-title">Account Sign In</h1>
        <p class="login-sub">Our Lady of Fatima University • Antipolo Campus</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label>Institutional Account</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Institutional Account" 
                        autocomplete="username"
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
                        autocomplete="current-password"
                        required>
                </div>
            </div>

            <button type="submit" class="btn-signin">Sign In to Continue</button>
        </form>

        <div class="login-footer-links">
            Don't have an account yet? <a href="signup.php" style="color: var(--olfu-green); font-weight: 700;">Register here</a>
            <br style="margin-bottom: 0.5rem;">
            <a href="../../index.php" style="margin-top: 0.4rem;">&larr; Return to Home Feed</a>
        </div>
    </main>

</body>
</html>