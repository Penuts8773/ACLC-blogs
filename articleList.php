<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - ACLC Blogs</title>
    <link rel="stylesheet" href="styles/articleList.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div>
        <a href="postBoard.php">Create Post</a>
    </div>
    <div class="article">
        <?php
        require_once __DIR__ . '/backend/conn.php';
        $sql = "SELECT * FROM articles ORDER BY created_at DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo '<ul>';
            while ($row = $result->fetch_assoc()) {
                echo '<li><a href="article.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a> - ' . htmlspecialchars($row['created_at']) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No articles found.</p>';
        }
        ?>
    </div>
    
</body>
</html>