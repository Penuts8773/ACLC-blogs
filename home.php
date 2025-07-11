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
        // Fetch articles using the blog.php helper
        require_once __DIR__ . '/backend/blog.php';
        $articles = [];
        $rawArticles = getAllArticlesWithContentAndImages();
        foreach ($rawArticles as $row) {
            if (empty($row['id'])) continue; // Skip articles with no ID
            echo '<!-- DEBUG: row id = ' . $row['id'] . ' -->';
            $summary = '';
            $image = '';
            if (!empty($row['blocks'])) {
                foreach ($row['blocks'] as $block) {
                    if ($block['type'] === 'text' && $summary === '') {
                        $summary = mb_substr(strip_tags($block['content']), 0, 120) . '...';
                    }
                    if ($block['type'] === 'image' && $image === '' && !empty($block['content'])) {
                        // Only use the filename, not the full path
                        $image = 'uploads/' . basename($block['content']);
                    }
                }
            }
            $articles[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'date' => !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '',
                'content' => $summary,
                'image' => $row['image'] ?? $image, // Use the image from the row or the processed one
                'views' => 0 // Placeholder, add views if you have them
            ];  
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
