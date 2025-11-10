<?php
require_once '../backend/blog.php';
require_once '../backend/article.php';
require_once '../backend/controllers/ArticleController.php';
include 'components/modal.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use ArticleController for approved articles
$articleController = new ArticleController($pdo);
$articles = $articleController->getApprovedArticles();
$latestArticles = array_slice($articles, 0, 3);
$latestMain = $latestArticles[0] ?? null;
$latestRest = array_slice($latestArticles, 1, 2);
$mostLiked = getMostLikedArticles($pdo);
$mostCommented = getMostCommentedArticle($pdo);
$mostPopular = getMostPopularArticles($pdo, 6);

/**
 * Displays a single main article block
 */
function showArticle($article, $title, $pdo)
{
    if ($title) {
        echo "<h2>" . htmlspecialchars($title) . "</h2>";
    }

    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    $blocks  = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $thumb   = htmlspecialchars($content['thumbnail']);
    $preview = htmlspecialchars($content['preview']);
    $preview = !empty($content['preview']) 
    ? htmlspecialchars($content['preview']) 
    : "No description available.";

    echo "<div onclick='window.location.href=\"article.php?a=" . urlencode($article['title']) . "\"' class='article' style='background-image: url(\"$thumb\")'>";
    echo "  <div  class='article-content'>";
    echo "    <h2>" . htmlspecialchars($article['title']) . "</h2>";
    echo "    <p class='preview'>$preview</p>";
    if (isset($article['popularity_score'])) {
        echo "    <div class='popularity-stats'>";
        echo "      <small>👍 " . ($article['like_count'] ?? 0) . " | 💬 " . ($article['comment_count'] ?? 0) . "</small>";
        echo "    </div>";
    }
    echo "  </div>";
    echo "</div>";
}

/**
 * Displays a latest article card (different style)
 */
function showLatestArticle($article, $pdo, $isMain = false)
{
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    $blocks  = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $thumb   = htmlspecialchars($content['thumbnail']);
    $preview = htmlspecialchars($content['preview']);

    $class = $isMain ? "latest-article-main" : "latest-article-small";

    echo "<div onclick='window.location.href=\"article.php?a=" . urlencode($article['title']) . "\"' class='$class' style='background-image: url(\"$thumb\")'>";
    echo "  <div class='latest-article-content'>";
    echo "    <h2>" . htmlspecialchars($article['title']) . "</h2>";
    echo "    <small>" . date("F d, Y", strtotime($article['created_at'])) . "</small>";
    echo "    <p class='latest-preview'>$preview</p>";
    echo "  </div>";
    echo "</div>";
}

function showDetailedArticle($article, $pdo)
{
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    $blocks  = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $thumb   = htmlspecialchars($content['thumbnail']);
    $preview = htmlspecialchars($content['preview']);

    $class = "latest-article-detailed";

    echo "<div onclick='window.location.href=\"article.php?a=" . urlencode($article['title']) . "\"' class='$class' style='background-image: url(\"$thumb\")'>";
    echo "  <div class='latest-article-content'>";
    echo "    <h2>" . htmlspecialchars($article['title']) . "</h2>";
    echo "    <small>" . date("F d, Y", strtotime($article['created_at'])) . "</small>";
    echo "    <p class='latest-preview'>$preview</p>";
    echo "    <button onclick='window.location.href=\"article.php?a=" . urlencode($article['title']) . "\"' class='latest-read-more'>Read More</button>";
    echo "  </div>";
    echo "</div>";
}

/**
 * Displays a smaller list-style article
 */
function showListArticle($article, $pdo)
{
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    echo "<div class='article-list-item'>";
    echo "  <a href='article.php?a=" . urlencode($article['title']) . "'>" . htmlspecialchars($article['title']) . "</a>";
    echo "  <small>By " . htmlspecialchars($article['name'] ?? 'Unknown') . " on " . date("F j, Y", strtotime($article['created_at'])) . "</small>";
    echo "</div>";
}

/**
 * Displays a popular article in grid format
 */
function showPopularArticle($article, $pdo)
{
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    $blocks  = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $thumb   = htmlspecialchars($content['thumbnail']);
    $preview = htmlspecialchars($content['preview']);
    $preview = !empty($content['preview']) 
    ? htmlspecialchars($content['preview']) 
    : "No description available.";

    echo "<div onclick='window.location.href=\"article.php?a=" . urlencode($article['title']) . "\"' class='popular-article' style='background-image: url(\"$thumb\")'>";
    echo "  <div class='popular-article-content'>";
    echo "    <h3>" . htmlspecialchars($article['title']) . "</h3>";
    echo "    <p class='popular-preview'>$preview</p>";
    echo "    <div class='popularity-stats'>";
    echo "      <small>👍 " . ($article['like_count'] ?? 0) . " | 💬 " . ($article['comment_count'] ?? 0)  . " | 👁️ " . ($article['views'] ?? 0)."</small>";
    echo "    </div>";
    echo "  </div>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - ACLC BLOGS</title>
    <link rel="icon" type="image/x-icon" href="public/assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">


</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="home-articles">
        <!-- Article List -->
        
        <div class="articleList slide-up">
            <h2 style="text-align: center;">📄 Article List</h2>
            <?php foreach ($articles as $article) {
                showListArticle($article, $pdo);
            } ?>
        </div>

        <!-- Latest Articles -->
        <div class="article-section-latest slide-up">
            <h2>🆕 Latest Articles</h2>
            <div class="latest-content">
                <?php if ($latestMain): ?>
                    <!-- Featured big card -->
                    <div class="latest-featured">
                        <?php showLatestArticle($latestMain, $pdo, true); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($latestRest)): ?>
                    <!-- Two smaller cards -->
                    <div class="latest-grid">
                        <?php foreach ($latestRest as $article): ?>
                            <?php showLatestArticle($article, $pdo, false); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Side Section -->
        <div class="article-section-side slide-up">
            <!-- Most Liked -->
            <div class="article-section-like">
                <h2>👍 Most Liked Articles</h2>
                <?php
                if (!empty($mostLiked)) {
                    foreach ($mostLiked as $article) {
                        showArticle($article, "", $pdo);
                    }
                } else {
                    echo "<p class='no-article'>No articles available.</p>";
                }
                ?>
            </div>

            <!-- Most Commented -->
            <div class="article-section-comment">
                <h2>💬 Most Commented Articles</h2>
                <?php
                if (!empty($mostCommented)) {
                    foreach ($mostCommented as $article) {
                        showArticle($article, "", $pdo);
                    }
                } else {
                    echo "<p class='no-article'>No articles available.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Most Popular Articles Section at the Bottom -->
    <div class="popular-articles-section slide-up">
        <div class="container">
            <h2 class="section-title">🔥 Most Popular Articles</h2>            
            <?php if (!empty($mostPopular)): ?>
                <div class="popular-articles-grid">
                    <?php foreach ($mostPopular as $article): ?>
                        <?php showPopularArticle($article, $pdo); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class='no-article'>No popular articles available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>