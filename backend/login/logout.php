<?php
session_start();

// Connect to MySQL database
require_once __DIR__ . '/database.php';

// Update user status to offline before destroying session
if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "UPDATE users SET status = 'offline',  last_activity = NULL WHERE id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if cookies are enabled
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy server session
session_destroy();

// Redirect to home page
header("Location: http://localhost/olfu-antipolo-lost-found/index.php");
exit();
?>
