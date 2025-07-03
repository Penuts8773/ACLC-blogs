<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/login.css">
    <title>Login - ACLC Blogs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php if (isset($_GET['error'])): ?>
        <div class="login-error" style="color: red; text-align: center; margin-bottom: 10px;">Invalid username or password.</div>
    <?php endif; ?>
    <form class="login-container" method="post" action="backend/auth.php">
        <h2>Login</h2>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit">Login</button>
    </form>
</body>
</html>