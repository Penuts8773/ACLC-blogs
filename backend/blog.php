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
    return $stmt->fetchAll();
}
//ANDITO TANGNAMO
function getMostLikedArticles($pdo) {
    $sql = "
        SELECT 
            a.*, 
            u.name,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes
        FROM articles a
        JOIN user u ON a.user_id = u.usn
        WHERE a.approved = 1
        GROUP BY a.id, u.name
        ORDER BY likes DESC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
/**
 * Get most popular articles based on combined likes and comments
 * Uses weighted scoring: likes * 2 + comments * 1
 */
function getMostPopularArticles($pdo, $limit = 3) {
    // Convert limit to integer to avoid SQL injection and syntax errors
    $limit = (int)$limit;
    
    $sql = "
        SELECT a.*, u.name AS author_name,
               COALESCE(like_counts.like_count, 0) as like_count,
               COALESCE(comment_counts.comment_count, 0) as comment_count,
               (COALESCE(like_counts.like_count, 0) * 2 + COALESCE(comment_counts.comment_count, 0)+ COALESCE(a.views, 0) * 1) as popularity_score
        FROM articles a 
        JOIN user u ON a.user_id = u.usn 
        LEFT JOIN (
            SELECT article_id, COUNT(*) as like_count 
            FROM article_likes 
            GROUP BY article_id
        ) like_counts ON a.id = like_counts.article_id
        LEFT JOIN (
            SELECT article_id, COUNT(*) as comment_count 
            FROM article_comments 
            GROUP BY article_id
        ) comment_counts ON a.id = comment_counts.article_id
        WHERE a.approved = 1
        ORDER BY popularity_score DESC, a.created_at DESC
        LIMIT " . $limit;
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
