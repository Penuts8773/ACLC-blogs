<?php
require_once '../backend/db.php';
include 'components/modal.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$sort_by = $_GET['sort_by'] ?? 'latest';
$order = $_GET['order'] ?? 'desc';

$column = match($sort_by) {
    'likes' => 'likes',
    'comments' => 'comment_count',
    default => 'a.created_at'
};

$userId = $_SESSION['user']['usn'] ?? 0;

$sql = "
    SELECT 
        a.id, a.user_id, a.title, a.created_at,
        u.name,
        (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes,
        COUNT(c.id) AS comment_count,
        EXISTS (
            SELECT 1 FROM article_likes 
            WHERE article_id = a.id AND user_id = :user_id
        ) AS liked_by_user
    FROM articles a
    JOIN user u ON a.user_id = u.usn
    LEFT JOIN article_comments c ON a.id = c.article_id
    WHERE a.approved = 1
    GROUP BY a.id, u.name
    ORDER BY $column " . strtoupper($order);

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $userId]);
$articles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>All Articles</title>
    <link rel="stylesheet" href="assets/style/index.css">
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
    
    <?php if ($_SESSION['user']['privilege'] != 3): ?>
        <button class="create-button" onclick="confirmNavigation('articleCreation.php')">
            Create Article
        </button>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');

            fetch('../backend/like.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'article_id=' + encodeURIComponent(id),
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.likes !== undefined) {
                    document.getElementById('likes-' + id).textContent = data.likes;

                    const btn = document.querySelector(`.like-btn[data-id="${id}"]`);
                    if (data.liked) {
                        btn.classList.add('liked');
                        btn.textContent = '👍 Liked';
                    } else {
                        btn.classList.remove('liked');
                        btn.textContent = '👍';
                    }
                }
            })

            .catch(error => console.error('Error:', error));
        });
    });
});

function confirmNavigation(url) {
    showConfirmModal('Do you want to create a new article?', () => {
        window.location.href = url;
    });
}
</script>
</body>
</html>
