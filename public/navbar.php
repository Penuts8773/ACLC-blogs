<div class="navbar">
  <div class="nav-logo">
    <img src="assets/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
    <strong>Blogs</strong>
  </div>
  
  <div class="nav-user-menu">
    <div class="dropdown">
      <button id="burger-btn" class="burger-btn" aria-label="Toggle menu">
        &#9776; <!-- burger icon -->
      </button>
      <div id="dropdown-menu" class="dropdown-content">
        <span>Hi, <?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>!</span>
        <a href="index.php">Home</a>
        <a href="articleBoard.php">Articles</a>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['privilege'] == 1): ?>
          <a href="adminPanel.php">Admin</a>
        <?php endif; ?>
        <a href="#" onclick="confirmLogout()">Logout</a>
      </div>
    </div>
  </div>
</div>



<script>
function confirmLogout() {
    showConfirmModal('Are you sure you want to logout?', () => {
        window.location.href = 'logout.php';
    });
}
const burgerBtn = document.getElementById('burger-btn');
const dropdownMenu = document.getElementById('dropdown-menu');

burgerBtn.addEventListener('click', () => {
  dropdownMenu.classList.toggle('show');
});

// Close dropdown if clicking outside of it
window.addEventListener('click', (event) => {
  if (!burgerBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
    dropdownMenu.classList.remove('show');
  }
});
</script>
