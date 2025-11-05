<?php
require_once '../backend/db.php';
require_once '../backend/article.php';
require_once '../backend/blog.php';
require_once '../backend/controllers/ArticleController.php';
include 'components/modal.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate article ID
$articleId = $_GET['id'] ?? null;
if (!$articleId || !is_numeric($articleId)) {
    header('Location: articleBoard.php');
    exit;
}

// Get article data
$article = getArticleWithNames($pdo, (int)$articleId);
if (!$article) {
    header('Location: articleBoard.php');
    exit;
}

// Get related data
$articles = getAllArticles($pdo);
$mostLiked = getMostLikedArticles($pdo);
$mostCommented = getMostCommentedArticle($pdo);
$mostPopular = getMostPopularArticles($pdo, 3); // Get 4 for better grid layout
$blocks = getArticleBlocks($pdo, $articleId);

// Get comments with limit
$commentLimit = 5;
$comments = getArticleComments($pdo, $articleId, $commentLimit);
$totalComments = getArticleCommentCount($pdo, $articleId);

// Related articles by tags/categories
$related = getRelatedArticles($pdo, (int)$articleId, 4);
?>


<?php
function renderComment($comment, $currentUser) {
    $isOwner = isset($currentUser) && $currentUser['usn'] == $comment['user_id'];
    $isAdminOrMod = isset($currentUser) && in_array($currentUser['privilege'], [1, 3]);
    ?>
    <div class='comment' id='comment-<?= $comment['id'] ?>'>
        <div class="comment-user">
            <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">
            <strong><?= htmlspecialchars($comment['name']) ?></strong>
        </div>
        <p class='comment-content'><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
        <div class="comment-meta">
            <small>
                <?= htmlspecialchars($comment['created_at']) ?>
                <?php if ($comment['modified_at']): ?>
                    <span class="edit-indicator">(edited)</span>
                <?php endif; ?>
            </small>
            <?php if ($isOwner): ?>
                <div class='comment-actions'>
                    <a class='comment-edit' onclick='editComment(<?= $comment['id'] ?>)'>Edit</a>
                    <a class='comment-edit' onclick='deleteComment(<?= $comment['id'] ?>)'>Delete</a>
                </div>
                <form class='edit-form' style='display:none;'>
                    <textarea required><?= htmlspecialchars($comment['content']) ?></textarea>
                    <div class="form-buttons">
                        <button type='submit' class="save-btn action-btn">Save</button>
                        <button type='button' class="cancel-btn action-btn" onclick='cancelEdit(<?= $comment['id'] ?>)'>Cancel</button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Restrict User Button for Admins/Moderators -->
            <?php
            // Show for admins/mods, except if commenter is already banned or is admin/teacher
            if ($isAdminOrMod && !in_array($comment['user_privilege'], [1, 2, 5])):
            ?>
                <form method="post" action="restrictUser.php" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($comment['user_id']) ?>">
                    <button type="submit" class="restrict-btn" onclick="return confirm('Restrict this user from commenting?');">
                        Restrict User
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">
    <link rel="stylesheet" href="assets/style/navBar.css">
    <link rel="stylesheet" href="assets/style/comment.css">
</head>
<body class="article-view">
    <?php include 'navbar.php'; ?>
    
    <div class="article-body">
        <div class="article-container slide-up">
            <!-- Article Header -->
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            
            <div class="article-meta">
                <p class="author-meta">By <?= htmlspecialchars($article['author_name']) ?> | <?= date("F j, Y, g:i a", strtotime($article['created_at'])) ?></p>
                
                <?php if ($article['modified_at'] && $article['last_editor_name']): ?>
                    <p class="edit-info">
                        Last edited by <?= htmlspecialchars($article['last_editor_name']) ?> | <?= date("F j, Y, g:i a", strtotime($article['modified_at'])) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (canEditArticle($article, $_SESSION['user'] ?? null)): ?>
                    <button onclick="window.location.href='editArticle.php?id=<?= $article['id'] ?>'" 
                            class="edit-btn">
                        Edit Article
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Article Content -->
            <div>
                <?php foreach ($blocks as $block): ?>
                    <?php if ($block['block_type'] === 'text'): ?>
                        <p class="article-desc"><?= nl2br(htmlspecialchars($block['content'])) ?></p>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($block['content']) ?>" class="article-image" alt="article-image">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Comments Section -->
            <div class="comment-section" id="comments">
                <h3>Comments (<?= $totalComments ?>)</h3>
                <div id="comments-container">
                    <?php foreach ($comments as $comment): ?>
                        <?php renderComment($comment, $_SESSION['user'] ?? null); ?>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($totalComments > $commentLimit): ?>
                    <button id="show-all-comments-btn" class="show-all-btn" onclick="loadAllComments()">
                        Show All Comments (<?= $totalComments ?>)
                    </button>
                <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['privilege'] != 5): ?>
            <form id="comment-form" class="comment-form">
                <textarea class="comment-txtarea" name="comment" rows="3" placeholder="Write a comment..." required></textarea>
                <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                <button class="comment-btn" type="submit">Post Comment</button>
            </form>
            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['privilege'] == 5): ?>
                <p class="login-prompt">
                    You are banned from commenting.
                </p>
            <?php else: ?>
                <p class="login-prompt">
                    <a href="login.php">Login</a> to comment.
                </p>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="article-article-section-side slide-up">
            <!-- Related Articles -->
            <div class="articleRelated">
                <h2 class="section-title">🔗 Related Articles</h2>
                <?php if (!empty($related)): ?>
                    <div class="article-popular-articles-grid">
                        <?php foreach ($related as $relArticle): ?>
                            <?php showArticle($relArticle, "", $pdo); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class='no-article'>No related articles found.</p>
                <?php endif; ?>
            </div>
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

            <!-- Article List -->
                <div class="articleList slide-up">
                    <h2>📄 Article List</h2>
                    <?php foreach ($articles as $article) {
                        showListArticle($article, $pdo);
                    } ?>
                </div>

            

        </div>
    </div>

    <?php include 'components/modal.php'; ?>

    <script src="script/articleJs.js"></script>
</body>
</html>