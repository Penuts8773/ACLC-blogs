<div class="navbar">
    <div class="nav-logo">
        <img src="assets/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
        <strong>Blogs</strong>
    </div>
    <div>
        <span>Hi, <?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>!</span>
        <a href="index.php">Home</a>
        <a href="articleBoard.php">Articles</a>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['privilege'] == 1): ?>
            <a href="adminPanel.php">Admin</a>
        <?php endif; ?>
        <button onclick="confirmLogout()">Logout</button>
    </div>
</div>

<script>
function confirmLogout() {
    showConfirmModal('Are you sure you want to logout?', () => {
        window.location.href = 'logout.php';
    });
}
</script>
