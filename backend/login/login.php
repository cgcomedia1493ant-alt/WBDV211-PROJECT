<?php
session_start();

// Connect to MySQL database
require_once __DIR__ . '/database.php';

$error_message = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password)) {

        $error_message = 'Please provide your institutional account and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_message = 'Invalid institutional credentials. Please try again.';

    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(student\.)?fatima\.edu\.ph$/', $email)) {

        $error_message = 'Invalid institutional credentials. Please try again.';

    } else {

        // Get user account from database
        $stmt = $conn->prepare(
            "SELECT id, fullname, email, password, status, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {

            // Check if account is approved
            // Remove this check if you do not use account approval.
            if ($user['status'] === 'pending' ||
                $user['status'] === 'rejected') {

                $error_message = 'Your account is not approved yet.';

            } else {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store session information
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];

                $_SESSION['user_name'] = !empty($user['fullname'])
                    ? $user['fullname']
                    : (strstr($user['email'], '@', true) ?: 'Student');

                // Save role, defaulting to user if unavailable
                $_SESSION['role'] = $user['role'] ?? 'user';

                // Set user status to active
                $update = $conn->prepare(
                    "UPDATE users SET status = 'active' WHERE id = ?"
                );

                $update->bind_param("i", $user['id']);
                $update->execute();

                if ($update->affected_rows < 0) {
                    $error_message = 'Unable to update account status.';
                    $update->close();
                } else {
                    $update->close();

                    // Redirect admin and regular users
                    if ($_SESSION['role'] === 'admin') {
                        header("Location: ../../admin_dashboard.php");
                    } else {
                        header("Location: ../../index.php");
                    }

                    exit();
                }
            }

        } else {
            $error_message = 'Invalid email or password. Please try again.';
        }
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
                <label>FATIMA Account</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="FATIMA Email" 
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
