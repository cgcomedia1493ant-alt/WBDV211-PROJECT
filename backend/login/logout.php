<?php
// Terminate session and return to home feed
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy session cookies if available
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

// Safely redirect back to index.php
header("Location: http://localhost/olfu-antipolo-lost-found/index.php");
exit();
?>