<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<nav class="navbar">
    <div class="nav-logo">
        <img src="styles/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
        <strong>Blogs</strong>
    </div>
    <div>
        <a href="home.php">Home</a>
        <a href="articleList.php">Post Board</a>
        <p>
            <form method="POST" class="logout-form">
                <button type="submit" name="logout">Logout</button>
            </form>
            </p>
    </div>
</nav>