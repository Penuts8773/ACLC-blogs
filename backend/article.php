<?php
require_once 'db.php';

function getArticleBlocks($pdo, $articleId) {
    $blocks = $pdo->prepare("SELECT * FROM article_blocks WHERE article_id = ? ORDER BY sort_order");
    $blocks->execute([$articleId]);
    return $blocks->fetchAll();
}

function getArticleThumbnailAndPreview($blocks) {
    $thumbnail = '';
    $preview = '';
    foreach ($blocks as $block) {
        if ($block['block_type'] === 'image' && empty($thumbnail)) {
            $thumbnail = $block['content'];
        }
        if ($block['block_type'] === 'text' && empty($preview)) {
            $text = strip_tags($block['content']);
            $preview = strlen($text) > 150 ? substr($text, 0, strrpos(substr($text, 0, 150), ' ')) . '...' : $text;
        }
    }
    return ['thumbnail' => $thumbnail, 'preview' => $preview];
}

function getAllArticles($pdo) {
    $stmt = $pdo->query("SELECT a.*, u.name FROM articles a JOIN user u ON a.user_id = u.usn ORDER BY a.created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}