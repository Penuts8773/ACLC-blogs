<?php
require_once '../backend/db.php';
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Articles</title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">

</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h2>All Articles</h2>
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
        ?>
        <div class="article" style="background-image: url('<?= htmlspecialchars($thumbnail) ?>');">
            <div class="article-content">
                <h2><?= htmlspecialchars($a['title']) ?></h2>
                <small>
                    By <?= htmlspecialchars($a['name']) ?> | <?= $a['created_at'] ?> |
                    <button class="like-btn <?= $a['liked_by_user'] ? 'liked' : '' ?>" data-id="<?= $a['id'] ?>">
                        <?= $a['liked_by_user'] ? '👍 Liked' : '👍' ?>
                    </button>

                    <span class="like-count" id="likes-<?= $a['id'] ?>"><?= $a['likes'] ?></span> |
                    💬 <?= $a['comment_count'] ?>
                </small>
                <p class="preview"><?= htmlspecialchars($preview) ?></p>
                <button onclick="window.location.href='article.php?id=<?= $a['id'] ?>'" class="read-more">Read More</button>
            </div>
        </div>
    <?php endforeach; ?>
    
    
</div>

<script src="script/articleBoardJs.js"></script>
</body>
</html>
