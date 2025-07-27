<?php require_once '../backend/config.php';
include 'components/modal.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - ACLC Blog</title>
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="post" action="../backend/auth.php">
            <h2>Welcome Back</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="login-error">Invalid credentials. Please try again.</div>
            <?php endif; ?>
            <input type="text" name="usn" placeholder="Enter your USN" required>
            <input type="password" name="pass" placeholder="Enter your password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
