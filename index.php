<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="styles/animations.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">
    <title>Login - ACLC Blogs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-body">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'login_required'): ?>
        <div class="login-error" style="color: red; text-align: center; margin-bottom: 10px;">You must be logged in to post.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="login-error" style="color: red; text-align: center; margin-bottom: 10px;">Invalid username or password.</div>
    <?php endif; ?>
    <form class="login-container slide-up" method="post" action="backend/auth.php">
        <div>
            <img src="styles/images/aclc-logo.png" alt="ACLC Logo" style="max-width: 120px; height: auto;">
        </div>
        <h2>Login to ACLC Blogs</h2>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit">Login</button>
    </form>
</body>
</html>