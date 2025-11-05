<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="assets/style/navBar.css">

</head>
<body>
  <div class="navbar">
  <div onclick="window.location.href='index.php'" class="nav-logo">
    <img src="assets/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
    <a>Blogs</a>
  </div>
  
  <div class="nav-user-menu">
    <div class="dropdown">
      <?php if (isset($_SESSION['user']) && $_SESSION['user']['privilege'] < 3): ?>
        <button id="create-button" onclick="confirmNavigation('articleCreation.php')">
            Create Article
        </button>
    <?php endif; ?>
      <button id="search-button" onclick="window.location.href='articleBoard.php'">
            <img id="search-icon" src="assets/images/search_icon.svg">
      </button>
      <button id= "burger-btn" class="burger-btn" aria-label="Toggle menu">
        &#9776; <!-- burger icon -->
      </button>
      <div id="dropdown-menu" class="dropdown-content slideDown">
        <div class="dropdown-header">
          <img src="assets/images/user-icon.png" class="user-icon">
          <span>
            <?php if (isset($_SESSION['user'])): ?>
              <?= htmlspecialchars($_SESSION['user']['name']) ?>
            <?php else: ?>
              <a href="login.php" style="color:white; text-decoration:none;">Sign In</a>
            <?php endif; ?>
          </span>
        </div>

        <a href="index.php">Home</a>
        <a href="articleBoard.php">Articles</a>

        <?php if(isset($_SESSION['user']) && $_SESSION['user']['privilege'] == 1): ?>
          <a href="adminPanel.php">Admin</a>
        <?php endif; ?>

        <?php if(isset($_SESSION['user'])): ?>
          <a href="#" onclick="confirmLogout()">Logout</a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php
require_once '../backend/db.php';
if (!isset($_SESSION)) session_start();

// Fetch categories from DB
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>
<div class="tags-area">
  <div class="tags-container" id="tags-container">
    <button class="tag" onclick="window.location.href='articleBoard.php'">#All</button>
    <?php foreach ($categories as $cat): ?>
        <button class="tag" onclick="window.location.href='articleBoard.php?category=<?= urlencode($cat['id']) ?>'">#<?= htmlspecialchars($cat['name']) ?></button>
    <?php endforeach; ?>
  </div>
</div>


<script src="script/navbarJs.js"></script>
</body>
</html>

