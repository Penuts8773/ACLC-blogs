<?php
require_once '../backend/db.php';
require_once '../backend/blog.php';
require_once '../backend/article.php';
include 'components/modal.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$articles = getAllArticles($pdo);
?>
<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Articles</title>
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body>  
    <div class="home-articles">
        <?php
        $latest = getArticleWithUser($pdo, "a.created_at");
        $mostLiked = getArticleWithUser($pdo, "a.likes");
        $mostCommented = getMostCommentedArticle($pdo);

        function showArticle($article, $title, $pdo) {
            echo "<h2>$title</h2>";

            if (!$article) {
                echo "<p class='no-article'>No articles available.</p>";
                return;
            }

            $blocks = getArticleBlocks($pdo, $article['id']);
            $content = getArticleThumbnailAndPreview($blocks);

            echo "<div class='article' style='background-image: url(\"" . htmlspecialchars($content['thumbnail']) . "\")'>";
            echo "<div class='article-content'>";
            echo "<h2>" . htmlspecialchars($article['title']) . "</h2>";
            echo "<small>By " . htmlspecialchars($article['name']) . " on " . $article['created_at'] . "</small>";
            echo "<p class='preview'>" . htmlspecialchars($content['preview']) . "</p>";
            echo "<button onclick='window.location.href=\"article.php?id={$article['id']}\"' class='read-more'>Read More</button>";
            echo "</div></div>";
        }
        ?>
        
        <div class="article-section">
            <?php showArticle($mostLiked, "👍 Most Liked Article", $pdo); ?>
        </div>
        
        <div class="article-section">
            <?php showArticle($latest, "🆕 Latest Article", $pdo); ?>
        </div>

        <div class="article-section">
            <?php 
            if ($mostCommented && count($mostCommented) > 0) {
                echo "<h2>💬 Most Commented Articles</h2>";
                foreach ($mostCommented as $article) {
                    showArticle($article, "", $pdo);
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
