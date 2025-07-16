<?php
session_start();
require_once 'functions.php';

if (isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (verifyAdminPassword($password)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Incorrect password';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mod Login</title>
    <link rel="stylesheet" href="/public/css/global.css">
</head>
<body>
    <div class="container">
        <div>
            <h3>Mod Login</h3>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
            
            <p style="margin-top: 20px; text-align: center;">
                <a href="index.php">Back</a>
            </p>
        </div>
    </div>
</body>
</html> 