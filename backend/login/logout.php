<?php
// Terminate session and return to home page
session_start();
session_unset();
session_destroy();
header("Location: http://localhost/olfu-antipolo-lost-found/index.php");
exit();
?>