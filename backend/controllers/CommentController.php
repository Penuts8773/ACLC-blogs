<?php
class CommentController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getComments($articleId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.name, c.user_id 
            FROM article_comments c 
            JOIN user u ON c.user_id = u.usn 
            WHERE article_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    public function addComment($articleId, $userId, $content) {
        $stmt = $this->pdo->prepare("
            INSERT INTO article_comments (article_id, user_id, content) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$articleId, $userId, $content]);
    }
}