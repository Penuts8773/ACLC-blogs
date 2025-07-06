<?php
session_start();
$error = $_GET['error'] ?? '';
?>
<link rel="stylesheet" href="/../assets/css/login.css">
<div class="login-body">
    <form class="login-container" method="post" action="api/auth.php">
        <h2>Login</h2>
        <?php if ($error === 'login_required'): ?>
            <div class="login-error" style="color: red; text-align: center; margin-bottom: 10px;">
                You must be logged in to post.
            </div>
        <?php elseif ($error): ?>
            <div class="login-error" style="color: red; text-align: center; margin-bottom: 10px;">
                Invalid username or password.
            </div>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit">Login</button>
    </form>
</div>

