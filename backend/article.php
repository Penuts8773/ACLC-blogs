<?php
require_once 'db.php';

// Get article with author and last editor names
function getArticleWithNames($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               u1.name AS author_name, 
               u2.name AS last_editor_name 
        FROM articles a 
        JOIN user u1 ON a.user_id = u1.usn 
        LEFT JOIN user u2 ON a.last_editor_id = u2.usn 
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

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

// Get comments for an article with user info
function getArticleComments($pdo, $articleId) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name, c.user_id 
        FROM article_comments c 
        JOIN user u ON c.user_id = u.usn 
        WHERE article_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$articleId]);
    return $stmt->fetchAll();
}