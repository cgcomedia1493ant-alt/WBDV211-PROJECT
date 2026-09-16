<?php
// backend/add_item.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = htmlspecialchars($_POST['item_name'] ?? 'Item');
    $upload_dir = '../uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === 0) {
        $filename = time() . '_' . basename($_FILES['item_image']['name']);
        move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_dir . $filename);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Received</title>
    <link rel="stylesheet" href="../frontend/css/styles.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh; font-family:sans-serif;">
    <div style="background:white; padding:2rem; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); text-align:center;">
        <h2 style="color:#005a36;">✓ Successfully Reported!</h2>
        <p style="margin:1rem 0;">Na-upload na ang post para sa: <strong><?php echo $item_name; ?></strong></p>
        <a href="../report.php" style="display:inline-block; padding:0.6rem 1.2rem; background:#005a36; color:white; text-decoration:none; border-radius:5px;">Mag-post Ulit</a>
    </div>
</body>
</html>
<?php
} else {
    header('Location: ../report.php');
    exit;
}
?>