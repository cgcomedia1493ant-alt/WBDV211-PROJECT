<?php
session_start();
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'offline']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "UPDATE users
     SET status = 'active',
         last_activity = NOW()
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'active']);
?>
