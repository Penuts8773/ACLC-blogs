<?php

class CommentController {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all comments for an article with user information
     */
    public function getComments($articleId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.name 
            FROM article_comments c 
            JOIN user u ON c.user_id = u.usn 
            WHERE c.article_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([(int)$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a new comment
     */
    public function addComment($articleId, $userId, $content) {
        $content = trim($content);
        
        if (empty($content)) {
            throw new InvalidArgumentException('Comment content cannot be empty');
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO article_comments (article_id, user_id, content) 
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([(int)$articleId, (int)$userId, $content]);
    }

    /**
     * Update an existing comment
     */
    public function updateComment($commentId, $userId, $content) {
        $content = trim($content);
        
        if (empty($content)) {
            throw new InvalidArgumentException('Comment content cannot be empty');
        }
        
        // Verify ownership
        if (!$this->verifyOwnership($commentId, $userId)) {
            throw new UnauthorizedAccessException('Unauthorized to edit this comment');
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE article_comments 
            SET content = ?, modified_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$content, (int)$commentId]);
    }

    /**
     * Delete a comment
     */
    public function deleteComment($commentId, $userId) {
        // Verify ownership
        if (!$this->verifyOwnership($commentId, $userId)) {
            throw new UnauthorizedAccessException('Unauthorized to delete this comment');
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM article_comments WHERE id = ?");
        return $stmt->execute([(int)$commentId]);
    }

    /**
     * Verify comment ownership
     */
    private function verifyOwnership($commentId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM article_comments 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([(int)$commentId, (int)$userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get comment count for an article
     */
    public function getCommentCount($articleId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM article_comments 
            WHERE article_id = ?
        ");
        $stmt->execute([(int)$articleId]);
        return (int)$stmt->fetchColumn();
    }
}

// Custom exceptions for better error handling
class UnauthorizedAccessException extends Exception {}
class InvalidArgumentException extends Exception {}