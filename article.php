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
require_once __DIR__ . '/backend/blog.php';
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo '<!-- DEBUG: article_id = ' . $article_id . ' -->';
$article = null;
if ($article_id > 0) {
    $article = getArticleWithContentAndImages($article_id);
    echo '<!-- DEBUG: article title = ' . ($article ? $article['title'] : 'not found') . ' -->';
}
?>
<body class="article-body" >
    <?php include 'navbar.php'; ?>
    <article class="article">
        <?php if ($article): ?>
            <h1><?php echo htmlspecialchars($article['title']); ?></h1>
            <p><em>By <?php echo htmlspecialchars($article['author']); ?> | <?php 
    if (!empty($article['created_at'])) {
        echo htmlspecialchars(date('Y-m-d', strtotime($article['created_at'])));
    } else {
        echo 'Unknown date';
    }
?></em></p>
            <?php
            $firstImage = null;
            foreach ($article['blocks'] as $block) {
                if ($block['type'] === 'image' && !empty($block['image_url'])) {
                    $firstImage = $block['image_url'];
                    break;
                }
            }
            if ($firstImage): ?>
                <img src="uploads/<?php echo htmlspecialchars($firstImage); ?>" alt="Article Main Image" class="article-image">
            <?php endif; ?>
            <?php
            // Display all blocks in order
            foreach ($article['blocks'] as $block) {
                if ($block['type'] === 'text') {
                    echo '<div class="section"><p>' . nl2br(htmlspecialchars($block['content'])) . '</p></div>';
                } elseif ($block['type'] === 'image' && !empty($block['image_url'])) {
                    echo '<div class="section"><img src="uploads/' . htmlspecialchars($block['image_url']) . '" class="article-image" alt="Article Image"></div>';
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