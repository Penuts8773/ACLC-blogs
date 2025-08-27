<?php
require_once '../backend/db.php';
require_once '../backend/article.php';
require_once '../backend/blog.php';
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
$mostPopular = getMostPopularArticles($pdo, 4); // Get 4 for better grid layout
$blocks = getArticleBlocks($pdo, $articleId);
$comments = getArticleComments($pdo, $articleId);

/**
 * Render an article card
 */
function renderArticleCard($article, $pdo) {
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }
    
    $blocks = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $authorName = $article['author_name'] ?? $article['name'] ?? 'Unknown';
    ?>
    <div onclick='window.location.href="article.php?id=<?= $article['id'] ?>"' class='article' style='background-image: url("<?= htmlspecialchars($content['thumbnail']) ?>")'>
        <div class='article-content'>
            <h2><?= htmlspecialchars($article['title']) ?></h2>
            <p class='preview'><?= htmlspecialchars($content['preview']) ?></p>
            <?php if (isset($article['popularity_score'])): ?>
                <div class='popularity-stats'>
                    <small>👍 <?= $article['like_count'] ?? 0 ?> | 💬 <?= $article['comment_count'] ?? 0 ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render a single comment
 */
function renderComment($comment, $currentUser) {
    $isOwner = isset($currentUser) && $currentUser['usn'] == $comment['user_id'];
    ?>
    <div class='comment' id='comment-<?= $comment['id'] ?>'>
        <div class="comment-user">
            <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">
            <strong><?= htmlspecialchars($comment['name']) ?></strong>
        </div>
        <p id='comment-content'><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
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
        </div>
        
        
    </div>
    <?php
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
</head>
<body class="article-view">
    <?php include 'navbar.php'; ?>
    
    <div class="article-body">
        <div class="article-container">
            <!-- Article Header -->
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            
            <div class="article-meta">
                <p class="author-meta">By <?= htmlspecialchars($article['author_name']) ?> | <?= htmlspecialchars($article['created_at']) ?></p>
                
                <?php if ($article['modified_at'] && $article['last_editor_name']): ?>
                    <p class="edit-info">
                        Last edited by <?= htmlspecialchars($article['last_editor_name']) ?>
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
                        <img src="<?= htmlspecialchars($block['content']) ?>" alt="Article image">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Comments Section -->
            <div class="comment-section" id="comments">
                <h3>Comments</h3>
                <?php foreach ($comments as $comment): ?>
                    <?php renderComment($comment, $_SESSION['user'] ?? null); ?>
                <?php endforeach; ?>
                
                <?php if (empty($comments)): ?>
                    <p class="no-comments">No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <?php if (isset($_SESSION['user'])): ?>
                <form id="comment-form" class="comment-form">
                    <textarea name="comment" rows="3" placeholder="Write a comment..." required></textarea>
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">
                    <a href="login.php">Login</a> to comment.
                </p>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="article-section-side slide-up">
            <!-- Most Popular Articles -->
            <div class="article-section-popular">
                <h2>🔥 Most Popular Articles</h2>
                <?php if ($mostPopular && count($mostPopular) > 0): ?>
                    <?php foreach ($mostPopular as $popularArticle): ?>
                        <?php renderArticleCard($popularArticle, $pdo); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Most Liked Articles -->
            <div class="article-section-like">
                <h2>👍 Most Liked Articles</h2>
                <?php if ($mostLiked && count($mostLiked) > 0): ?>
                    <?php foreach ($mostLiked as $likedArticle): ?>
                        <?php renderArticleCard($likedArticle, $pdo); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Most Commented Articles -->
            <div class="article-section-comment">
                <h2>💬 Most Commented Articles</h2>
                <?php if ($mostCommented && count($mostCommented) > 0): ?>
                    <?php foreach ($mostCommented as $commentedArticle): ?>
                        <?php renderArticleCard($commentedArticle, $pdo); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Article List -->
            <div class="articleList slide-up">
                <h2>📄 Article List</h2>
                <ul>
                    <?php foreach ($articles as $listArticle): ?>
                        <li>
                            <a href="article.php?id=<?= urlencode($listArticle['id']) ?>">
                                <strong><?= htmlspecialchars($listArticle['title']) ?></strong>
                            </a>
                            <br>
                            <small>
                                By <?= htmlspecialchars($listArticle['author_name'] ?? 'Unknown') ?> 
                                | <?= htmlspecialchars($listArticle['created_at']) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'components/modal.php'; ?>

    <script src="script/articleJs.js"></script>
</body>
</html>