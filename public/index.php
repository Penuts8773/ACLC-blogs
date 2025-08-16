<?php
require_once '../backend/blog.php';
require_once '../backend/article.php';
include 'components/modal.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user is not logged in
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Fetch articles
$articles       = getAllArticles($pdo);
$latest         = getArticleWithUser($pdo, "a.created_at");
$mostLiked      = getMostLikedArticles($pdo);
$mostCommented  = getMostCommentedArticle($pdo);

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

    echo "<div class='article' style='background-image: url(\"$thumb\")'>";
    echo "  <div class='article-content'>";
    echo "    <h2>" . htmlspecialchars($article['title']) . "</h2>";
    echo "    <small>By " . htmlspecialchars($article['name']) . " on " . date("F j, Y, g:i a", strtotime($article['created_at'])) . "</small>";
    echo "    <p class='preview'>$preview</p>";
    echo "    <button onclick='window.location.href=\"article.php?id=" . urlencode($article['id']) . "\"' class='read-more'>Read More</button>";
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
    echo "  <a href='article.php?id=" . urlencode($article['id']) . "'>" . htmlspecialchars($article['title']) . "</a>";
    echo "  <small>By " . htmlspecialchars($article['name']) . " on " . date("F j, Y, g:i a", strtotime($article['created_at'])) . "</small>";
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
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="home-articles">
        <!-- Article List -->
        <div class="articleList slide-up">
            <h2>📝 Article List</h2>
            <?php foreach ($articles as $article) {
                showListArticle($article, $pdo);
            } ?>
        </div>

        <!-- Latest Article -->
        <div class="article-section-latest slide-up">
            <?php showArticle($latest, "🆕 Latest Article", $pdo); ?>
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
</body>
</html>
