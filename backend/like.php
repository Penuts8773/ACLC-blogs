<?php
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'])) {
    $articleId = (int) $_POST['article_id'];
    $userId = $_SESSION['user']['usn'];  // Use the actual session user ID

    // Check if user already liked the article
    $stmt = $pdo->prepare("SELECT id FROM article_likes WHERE user_id = ? AND article_id = ?");
    $stmt->execute([$userId, $articleId]);
    $like = $stmt->fetch();

    if ($like) {
        // Unlike
        $pdo->prepare("DELETE FROM article_likes WHERE id = ?")->execute([$like['id']]);
        $liked = false;
    } else {
        // Like
        $pdo->prepare("INSERT INTO article_likes (user_id, article_id) VALUES (?, ?)")->execute([$userId, $articleId]);
        $liked = true;
    }

    // Get new like count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id = ?");
    $stmt->execute([$articleId]);
    $totalLikes = $stmt->fetchColumn();

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'liked' => $liked,
        'likes' => $totalLikes
    ]);
    exit;
}
