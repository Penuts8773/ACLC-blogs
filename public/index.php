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

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Home - ACLC BLOGS</title>
    <link rel="icon" type="image/x-icon" href="public/assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body>  
    <?php include 'navbar.php'; ?>
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

        function showListArticle($article, $pdo){

            if (!$article) {
                echo "<p class='no-article'>No articles available.</p>";
                return;
            }

            $blocks = getArticleBlocks($pdo, $article['id']);
            $content = getArticleThumbnailAndPreview($blocks);

            echo "<div class='article-list-item'>";
            echo "<a href='article.php?id=". urlencode($article['id']) . "'>". htmlspecialchars($article['title']) . "</a>";
            echo "<small>By " . htmlspecialchars($article['name']) . " on " . $article['created_at'] . "</small>";
            echo "</div>";
        }
        ?>
        
        <div class="articleList slide-up">
            <h2>📝Article List</h2>
            <?php foreach ($articles as $article) {showListArticle($article, $pdo);}?>
        </div>
        <div class="article-section-latest slide-up">
            <?php showArticle($latest, "🆕 Latest Article", $pdo); ?>
        </div>
        <div class ="article-section-side slide-up">
            <div class="article-section-like">
                <?php showArticle($mostLiked, "👍 Most Liked Article", $pdo); ?>
            </div>
            <div class="article-section-comment">
                <?php showArticle($mostCommented, "💬 Most Commented Article", $pdo); ?>
            </div>
        </div>
    </div>
</body>
</html>
