<?php
require_once 'db.php';

function getArticleWithUser($pdo, $orderBy) {
    $sql = "
        SELECT 
            a.*, 
            u.name,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes,
            COUNT(c.id) AS comment_count
        FROM articles a
        JOIN user u ON a.user_id = u.usn
        LEFT JOIN article_comments c ON a.id = c.article_id
        WHERE a.approved = 1
        GROUP BY a.id, u.name
        ORDER BY " . ($orderBy === "a.likes" ? "likes" : $orderBy) . " DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
}

function getMostCommentedArticle($pdo) {
    $sql = "SELECT a.*, u.name, COUNT(c.id) AS comment_count
            FROM articles a
            JOIN user u ON a.user_id = u.usn
            LEFT JOIN article_comments c ON a.id = c.article_id
            WHERE a.approved = 1
            GROUP BY a.id, a.title, a.created_at, u.name
            ORDER BY comment_count DESC
            LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
}