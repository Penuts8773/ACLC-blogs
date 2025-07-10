<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Article - ACLC Blogs</title>
    <link rel="stylesheet" href="styles/article.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">
</head>
<?php
require_once __DIR__ . '/backend/conn.php';
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$blocks = [];
if ($article_id > 0) {
    $sql = "SELECT a.title, a.created_at, u.name as author FROM articles a JOIN user u ON a.user_id = u.usn WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $article = $result->fetch_assoc();
        // Fetch blocks
        $blockSql = "SELECT block_type, content FROM article_blocks WHERE article_id = ? ORDER BY sort_order ASC";
        $blockStmt = $conn->prepare($blockSql);
        $blockStmt->bind_param('i', $article_id);
        $blockStmt->execute();
        $blockResult = $blockStmt->get_result();
        while ($block = $blockResult->fetch_assoc()) {
            $blocks[] = $block;
        }
        $blockStmt->close();
    }
    $stmt->close();
}
?>
<body class="article-body" >
    <?php include 'navbar.php'; ?>
    <article class="article">
        <?php if ($article): ?>
            <h1><?php echo htmlspecialchars($article['title']); ?></h1>
            <p><em>By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars(date('Y-m-d', strtotime($article['created_at']))); ?></em></p>
            <?php
            $firstImage = null;
            foreach ($blocks as $block) {
                if ($block['block_type'] === 'image' && !empty($block['content'])) {
                    $firstImage = $block['content'];
                    break;
                }
            }
            if ($firstImage): ?>
                <img src="uploads/<?php echo htmlspecialchars($firstImage); ?>" alt="Article Main Image" class="article-image">
            <?php endif; ?>
            <?php
            // Display all blocks in order
            foreach ($blocks as $block) {
                if ($block['block_type'] === 'text') {
                    echo '<div class="section"><p>' . nl2br(htmlspecialchars($block['content'])) . '</p></div>';
                } elseif ($block['block_type'] === 'image' && !empty($block['content'])) {
                    echo '<div class="section"><img src="uploads/' . htmlspecialchars($block['content']) . '" class="article-image" alt="Article Image"></div>';
                }
            }
            ?>
        <?php else: ?>
            <h1>Article Not Found</h1>
            <p>The article you are looking for does not exist.</p>
        <?php endif; ?>
    </article>
</body>
</html>