<?php
require_once '../backend/db.php';
require_once '../backend/article.php';
require_once '../backend/blog.php';
require_once '../backend/controllers/ArticleController.php';
include 'components/modal.php';

if (!isset($_SESSION)) {
    session_start();
}

$sort_by = $_GET['sort_by'] ?? 'latest';
$order = $_GET['order'] ?? 'desc';
$categoryId = $_GET['category'] ?? null;
$userId = $_SESSION['user']['usn'] ?? 0;

$articleController = new ArticleController($pdo);
$articles = $articleController->getApprovedArticlesByCategory($userId, $categoryId, $sort_by, $order);
$mostLiked = getMostLikedArticles($pdo);
$mostCommented = getMostCommentedArticle($pdo);
$mostPopular = getMostPopularArticles($pdo, 3); // Get 4 for better grid layout

function showListArticle($article, $pdo)
{
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }

    echo "<div class='article-list-item'>";
    echo "  <a href='article.php?id=" . urlencode($article['id']) . "'>" . htmlspecialchars($article['title']) . "</a>";
    echo "  <small>By " . htmlspecialchars($_SESSION['user']['name'] ?? '') . " on " . date("F j, Y, g:i a", strtotime($article['created_at'])) . "</small>";
    echo "</div>";
}

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

    echo "<div onclick='window.location.href=\"article.php?id=" . urlencode($article['id']) . "\"' class='article' style='background-image: url(\"$thumb\")'>";
    echo "  <div  class='article-content'>";
    echo "    <h2>" . htmlspecialchars($article['title']) . "</h2>";
    echo "    <p class='preview'>$preview</p>";
    echo "  </div>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Articles</title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">
    <link rel="stylesheet" href="assets/style/articleBoard.css">

</head>
<body>
<?php include 'navbar.php'; ?>
<div class="ab-container slide-up">
    <div class="ab-all-articles slide-up">
        <h2>All Articles</h2>
        
        <!-- Search Bar -->
        <input type="text" id="articleSearch" class="article-search" placeholder="Search articles by title, author, or tags..." onkeyup="searchBoardArticles()">
        
        <form method="get">
            <label>Sort by: </label>
            <select name="sort_by">
                <option value="latest" <?= $sort_by === 'latest' ? 'selected' : '' ?>>Latest</option>
                <option value="likes" <?= $sort_by === 'likes' ? 'selected' : '' ?>>Likes</option>
                <option value="comments" <?= $sort_by === 'comments' ? 'selected' : '' ?>>Comments</option>
            </select>
            <select name="order">
                <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>Descending</option>
                <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>Ascending</option>
            </select>
            <button type="submit">Apply</button>
        </form>
        <br>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert success" style="background: #d4edda; color: #155724; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
                ✅ Article published successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['pending'])): ?>
            <div class="alert pending" style="background: #fff3cd; color: #856404; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
                ⏳ Article submitted successfully and is awaiting admin approval.
            </div>
        <?php endif; ?>

        <?php foreach ($articles as $a): ?>
            <?php
                $blocks = $pdo->prepare("SELECT * FROM article_blocks WHERE article_id = ? ORDER BY sort_order");
                $blocks->execute([$a['id']]);
                $blocks = $blocks->fetchAll();
                
                // Get thumbnail and first text block
                $thumbnail = '';
                $preview = '';
                foreach ($blocks as $block) {
                    if ($block['block_type'] === 'image' && empty($thumbnail)) {
                        $thumbnail = $block['content'];
                    }
                    if ($block['block_type'] === 'text' && empty($preview)) {
                        $text = strip_tags($block['content']);
                        if (strlen($text) > 150) {
                            $preview = substr($text, 0, 150);
                            // Find the last space within the first 150 characters
                            $lastSpace = strrpos($preview, ' ');
                            // Cut at the last space to avoid cutting words in half
                            $preview = substr($text, 0, $lastSpace) . '...';
                        } else {
                            $preview = $text;
                        }
                    }
                }
                
                // Get tags for this article
                $tagsStmt = $pdo->prepare("
                    SELECT t.name 
                    FROM tags t
                    JOIN article_tags at ON t.id = at.tag_id
                    WHERE at.article_id = ?
                ");
                $tagsStmt->execute([$a['id']]);
                $tags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
                $tagsString = !empty($tags) ? implode(', ', $tags) : '';
            ?>
            <div class="ab-article" style="background-image: url('<?= htmlspecialchars($thumbnail) ?>');" data-tags="<?= htmlspecialchars($tagsString) ?>">
                <div class="ab-article-content">
                    <h2><?= htmlspecialchars($a['title']) ?></h2>
                    <p class="ab-preview"><?= htmlspecialchars($preview) ?></p>
                    <small>
                        By <?= htmlspecialchars($a['name']) ?> | <?= $a['created_at'] ?>
                        <button class="ab-like-btn <?= $a['liked_by_user'] ? 'liked' : '' ?>" data-id="<?= $a['id'] ?>">
                            <?= $a['liked_by_user'] ? '👍 Liked' : '👍' ?> 
                            <span class="ab-like-count" id="likes-<?= $a['id'] ?>"><?= $a['likes'] ?></span>
                        </button> |
                        💬 <?= $a['comment_count'] ?>
                    </small>
                    <button onclick="window.location.href='article.php?id=<?= $a['id'] ?>'" class="read-more">Read More</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Sidebar -->
     <div class="ab-sidebar">
        <div class="article-article-section-side slide-up">
            <!-- Most Popular Articles -->
            <div class="article.popular-articles-section">
                <h2 class="section-title">🔥 Most Popular Articles</h2>            
                    <?php if (!empty($mostPopular)): ?>
                        <div class="article-popular-articles-grid">
                            <?php foreach ($mostPopular as $article): ?>
                                <?php showArticle($article, "",$pdo); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class='no-article'>No popular articles available yet.</p>
                    <?php endif; ?>
            </div>

            <!-- Most Liked Articles -->
            <div class="article-article-section-like">
                <h2 class="section-title">👍 Most Liked Articles</h2>
                <?php if ($mostLiked && count($mostLiked) > 0): ?>
                    <div class="article.popular-articles-grid">
                        <?php foreach ($mostLiked as $likedArticle): ?>
                            <?php showArticle($likedArticle,    "", $pdo); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Most Commented Articles -->
            <div class="article-article-section-comment">
                <h2 class="section-title">💬 Most Commented Articles</h2>
                <?php if ($mostCommented && count($mostCommented) > 0): ?>
                    <div class="article.popular-articles-grid">
                        <?php foreach ($mostCommented as $commentedArticle): ?>
                            <?php showArticle($commentedArticle,"", $pdo); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<script src="script/articleBoardJs.js"></script>
</body>
</html>
