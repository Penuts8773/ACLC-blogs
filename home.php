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
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/animations.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">

    <title>Homepage - ACLC Blogs</title>
</head>
<body class="home-body">
    <?php include 'navbar.php'; ?>
    <div class="home-container slide-up">
        
        <?php
        // Fetch articles from the database
        require_once __DIR__ . '/backend/conn.php';
        $articles = [];
        $sql = "SELECT a.id, a.title, a.created_at, u.name as author FROM articles a JOIN user u ON a.user_id = u.usn ORDER BY a.created_at DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Fetch first block for summary and image
                $blockSql = "SELECT block_type, content FROM article_blocks WHERE article_id = ? ORDER BY sort_order ASC";
                $blockStmt = $conn->prepare($blockSql);
                $blockStmt->bind_param('i', $row['id']);
                $blockStmt->execute();
                $blockResult = $blockStmt->get_result();
                $summary = '';
                $image = 'styles/images/article-sample.png';
                while ($block = $blockResult->fetch_assoc()) {
                    if ($block['block_type'] === 'text' && $summary === '') {
                        $summary = mb_substr(strip_tags($block['content']), 0, 120) . '...';
                    }
                    if ($block['block_type'] === 'image' && $image === 'styles/images/article-sample.png' && !empty($block['content'])) {
                        $image = 'uploads/' . $block['content'];
                    }
                }
                $blockStmt->close();
                $articles[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'date' => date('Y-m-d', strtotime($row['created_at'])),
                    'content' => $summary,
                    'image' => $image,
                    'views' => 0 // Placeholder, add views if you have them
                ];
            }
        }
            ?>
            <div class="home-sections-container">
                <div class="home-section slide-up" id="recent-posts">
                <h2>Just In</h2>
                <?php
        // Show the 2 most recent articles
                    $recent = array_slice($articles, 0, 3);
                    foreach ($recent as $article) {
                        ?>
                        <a href="article.php?id=<?php echo urlencode($article['id']); ?>" style="text-decoration:none;color:inherit;">
                            <div class="home-article">
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Recent Post" class="home-article-image">
                                <div>
                                    <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                                    <div class="article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                                    <div class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>
                                </div>
                            </div>
                        </a>
                        <?php
                  }
                ?>
            </div>
            <div class="home-section slide-up" id="latest-post">
                <h2>Latest Post</h2>
                <?php
                usort($articles, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                $latest = $articles[0];
                ?>
                <div class="latest-article-highlight">
                    <img src="<?php echo htmlspecialchars($latest['image']); ?>" alt="Latest Post" class="latest-img-highlight">
                    <div>
                        <div class="article-title"><?php echo htmlspecialchars($latest['title']); ?></div>
                        <div class="article-meta">By <?php echo htmlspecialchars($latest['author']); ?> | <?php echo htmlspecialchars($latest['date']); ?></div>
                        <div class="article-content">
                            <?php echo htmlspecialchars($latest['content']); ?>
                        </div>
                        <a class="read-more-button" href="article.php?id=<?php echo urlencode($latest['id']); ?>">Read More</a>
                    </div>
                </div>
            </div>
            <div class="home-section slide-up" id="trending-posts">
                <h2>Trending Posts</h2>
                <?php
                usort($articles, function($a, $b) {
                    return $b['views'] - $a['views'];
                });
                $trending = array_slice($articles, 0, 3);
                foreach ($trending as $article) {
                    ?>
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" style="text-decoration:none;color:inherit;">
                        <div class="home-article">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Trending Post" class="home-article-image">
                            <div>
                                <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                                <div class="article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                                <div class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php
        
        ?>
</body>
</html>

</html>
