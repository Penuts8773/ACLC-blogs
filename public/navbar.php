<div class="navbar">
  <div class="nav-logo">
    <img src="assets/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
    <a href="index.php">Blogs</a>
  </div>
  
  <div class="nav-user-menu">
    <div class="dropdown">
      <?php if ($_SESSION['user']['privilege'] != 3): ?>
        <button id="create-button" onclick="confirmNavigation('articleCreation.php')">
            Create Article
        </button>
    <?php endif; ?>
      <button id="burger-btn" class="burger-btn" aria-label="Toggle menu">
        &#9776; <!-- burger icon -->
      </button>
      <div id="dropdown-menu" class="dropdown-content slideDown">
        <div class="dropdown-header">
          <img src="assets/images/user-icon.png" class="user-icon">
          <span><?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?></span>
        </div>
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



<script src="script/navbarJs.js"></script>
