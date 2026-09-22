<?php
session_start();

$username = $_SESSION['username'] ?? 'Guest';
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['claim'])) {

        $claim = trim($_POST['claim_item'] ?? '');

        if (empty($claim)) {
            $message = "Please enter something to claim.";
        } else {
            $message = "Congratulations, " . htmlspecialchars($username) .
                       "! You claimed: " . htmlspecialchars($claim);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./frontend/css/styles.css">

    <title>Claim Reward</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
        }

        .claim-box {
            width: 400px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .claim-box h2 {
            margin-bottom: 10px;
        }

        .claim-input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .claim-btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .claim-btn:hover {
            background: #0056b3;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="claim-box">

    <h2> Claim Box</h2>

    <p>
        Welcome,
        <strong><?php echo htmlspecialchars($username); ?></strong>!
    </p>

    <?php if (!empty($message)): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="claim.php">

        <label for="claim_item">
            What do you want to claim?
        </label>

        <input
            type="text"
            name="claim_item"
            id="claim_item"
            class="claim-input"
            placeholder="Type your claim here..."
            required
        >

        <button
            type="submit"
            name="claim"
            class="claim-btn"
        >
             Claim Now
        </button>

    </form>

</div>

</body>
</html>
