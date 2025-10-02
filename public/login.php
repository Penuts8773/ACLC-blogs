<?php require_once '../backend/config.php';
include 'components/modal.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - ACLC Blog</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">

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
            <a href="index.php">
                <button type="button">Log In as Guest</button>
            </a>
        </form>
    </div>
</body>
</html>
