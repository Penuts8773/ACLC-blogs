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

$slug = $_GET['a'] ?? null;

if (!$slug) {
    header("Location: articleBoard.php");
    exit;
}

$articleId = getArticleIdBySlug($pdo, $slug);
if (!$articleId || !is_numeric($articleId)) {
    header('Location: articleBoard.php');
    exit;
}

// Get article data
$article = getArticleWithNames($pdo, (int)$articleId);
$blocks  = getArticleBlocks($pdo, $article['id']);
$content = getArticleThumbnailAndPreview($blocks);
$thumb   = htmlspecialchars($content['thumbnail']);
if (!str_starts_with($image, 'http')) {
    $image = $urlBase . $image;
}

if (!$article) {
    header('Location: articleBoard.php');
    exit;
}
$title       = htmlspecialchars($article['title']);
$description = htmlspecialchars($content['preview'] ?? substr(strip_tags($article['body']), 0, 150));
$image       = $thumb; // From your content extractor
$url         = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$cookieKey = "viewed_article_" . $articleId;

// Only increment view if cookie does NOT exist
if (!isset($_COOKIE[$cookieKey])) {

    // Increment view count in DB
    incrementArticleViews($pdo, $articleId);

    // Set cookie that expires in 1 hour (you can adjust to your needs)
    setcookie($cookieKey, '1', time() + 3600, "/");
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
$totalViews = getArticleViewCount($pdo, $articleId);
if($totalViews > 1){
    $totalViews .= " views";
} else {
    $totalViews .= " view";
}
// Related articles by tags/categories
$related = getRelatedArticles($pdo, (int)$articleId, 4);
?>


<?php
function renderComment($comment, $currentUser = null) {
    if (!$currentUser && session_status() === PHP_SESSION_ACTIVE) {
        $currentUser = $_SESSION['user'] ?? null;
    }

    $isOwner = $currentUser && isset($currentUser['usn']) && $currentUser['usn'] == $comment['user_id'];
    $isAdminOrMod = $currentUser && isset($currentUser['privilege']) && in_array($currentUser['privilege'], [1, 3]);
    $commentUserIsAdminOrMod = in_array($comment['user_privilege'] ?? 4, [1, 3]);
    $isHidden = !empty($comment['hidden']);

    $showContent = !$isHidden || $isOwner || $isAdminOrMod;
    $displayContent = $showContent ? $comment['content'] : '[This comment has been hidden by a moderator]';
    $pClass = 'comment-content' . ($showContent ? '' : ' hidden-content');
    ?>
    <div class='comment <?= $isHidden ? 'hidden-comment' : '' ?>' id='comment-<?= (int)$comment['id'] ?>'>
        <div class="comment-user">
            <div class="user-icon-container">
                <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">
                <?php if ($commentUserIsAdminOrMod): ?>
                    <img src="assets/images/verified.gif" alt="Admin/Mod Badge" class="badge-overlay">
                <?php endif; ?>
            </div>
            <strong><?= htmlspecialchars($comment['name']) ?></strong>
        </div>

        <p class='<?= $pClass ?>'><?= $showContent ? nl2br(htmlspecialchars($displayContent)) : htmlspecialchars($displayContent) ?></p>

        <?php if ($isOwner): ?>
            <form class='edit-form' style='display:none;' data-comment-id='<?= (int)$comment['id'] ?>'>
                <textarea name="edit_comment" class="edit-textarea" rows="4" required><?= htmlspecialchars($comment['content']) ?></textarea>
                <div class="form-buttons">
                    <button type='submit' class="save-btn action-btn">Save</button>
                    <button type='button' class="cancel-btn action-btn" onclick='cancelEdit(<?= (int)$comment['id'] ?>)'>Cancel</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="comment-meta">
            <small>
                <?= htmlspecialchars($comment['created_at']) ?>
                <?php if (!empty($comment['modified_at'])): ?>
                    <span class="edit-indicator">(edited)</span>
                <?php endif; ?>
                <?php if ($isHidden): ?>
                    <span class="hidden-indicator">(hidden)</span>
                <?php endif; ?>
            </small>

            <?php if ($isOwner): ?>
                <div class='comment-actions'>
                    <a class='comment-edit' onclick='editComment(<?= (int)$comment['id'] ?>)'>Edit</a>
                    <a class='comment-edit' onclick='deleteComment(<?= (int)$comment['id'] ?>)'>Delete</a>
                </div>
            <?php endif; ?>

            <?php if ($isAdminOrMod && !in_array($comment['user_privilege'] ?? 0, [1, 2])): ?>
                <div class="admin-actions">
                    <form method="post" action="restrictUser.php" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($comment['user_id']) ?>">
                        <button type="submit" class="restrict-btn" onclick="return confirm('Restrict this user from commenting?');">Restrict User</button>
                    </form>
                    <button class="hide-btn" onclick="toggleCommentVisibility(<?= (int)$comment['id'] ?>, <?= $isHidden ? 'false' : 'true' ?>)">
                        <?= $isHidden ? 'Unhide' : 'Hide' ?> Comment
                    </button>
                </div>
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
    echo "  <a href='article/" . urlencode($article['title']) . "'>" . htmlspecialchars($article['title']) . "</a>";
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

    echo "<div onclick='window.location.href=\"article/" . urlencode($article['title']) . "\"' class='article' style='background-image: url(\"$thumb\")'>";
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
    <meta property="og:type" content="article" />
<meta property="og:title" content="<?= $title ?>" />
<meta property="og:description" content="<?= $description ?>" />
<meta property="og:image" content="<?= $image ?>" />
<meta property="og:url" content="<?= $url ?>" />
<meta property="og:site_name" content="ACLC College of Taytay Blogs" />

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $title ?>">
<meta name="twitter:description" content="<?= $description ?>">
<meta name="twitter:image" content="<?= $image ?>">
    <base href="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
               . '://' . $_SERVER['HTTP_HOST'] 
               . dirname($_SERVER['SCRIPT_NAME']) . '/' ?>">

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
                <p class="author-meta">By <?= htmlspecialchars($article['author_name']) ?> | <?= date("F j, Y, g:i a", strtotime($article['created_at'])) ?> | <?= $totalViews ?></p>
                
                <?php if ($article['modified_at'] && $article['last_editor_name']): ?>
                    <p class="edit-info">
                        Last updated <?= date("F j, Y, g:i a", strtotime($article['modified_at'])) ?>
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
                <div id="comments-container" data-article-id="<?= (int)$articleId ?>">
                    <input type="hidden" name="article_id" value="<?= (int)$articleId ?>">
                    <?php foreach ($comments as $comment): ?>
                        <?php renderComment($comment, $_SESSION['user'] ?? null); ?>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
                
                <button id="show-all-comments-btn" class="show-all-btn" data-article-id="<?= (int)$articleId ?>">Show all comments</button>
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