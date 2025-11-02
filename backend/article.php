<?php
require_once 'db.php';

/**
 * Get article with author and editor information
 */
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
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get article blocks ordered by sort order
 */
function getArticleBlocks($pdo, $articleId) {
    $stmt = $pdo->prepare("
        SELECT * FROM article_blocks 
        WHERE article_id = ? 
        ORDER BY sort_order
    ");
    $stmt->execute([$articleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Extract thumbnail and preview from article blocks
 */
function getArticleThumbnailAndPreview($blocks) {
    $thumbnail = '';
    $preview = '';
    
    foreach ($blocks as $block) {
        // Get first image as thumbnail
        if ($block['block_type'] === 'image' && empty($thumbnail)) {
            $thumbnail = $block['content'];
        }
        
        // Get first text block as preview
        if ($block['block_type'] === 'text' && empty($preview)) {
            $text = strip_tags($block['content']);
            $preview = strlen($text) > 150 
                ? substr($text, 0, strrpos(substr($text, 0, 150), ' ')) . '...' 
                : $text;
        }
        
        // Break early if we have both
        if (!empty($thumbnail) && !empty($preview)) {
            break;
        }
    }
    
    return ['thumbnail' => $thumbnail, 'preview' => $preview];
}

/**
 * Get all articles with author information
 */
function getAllArticles($pdo) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name AS author_name 
        FROM articles a 
        JOIN user u ON a.user_id = u.usn 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get comments for an article with user information
 */
function getArticleComments($pdo, $articleId) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name, u.usn AS user_id, u.privilege AS user_privilege
        FROM article_comments c
        JOIN user u ON c.user_id = u.usn
        WHERE c.article_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$articleId]);
    return $stmt->fetchAll();
}

/**
 * Check if user can edit article (admin or owner)
 */
function canEditArticle($article, $user) {
    if (!isset($user)) {
        return false;
    }
    
    return $user['privilege'] == 1 || $user['usn'] == $article['user_id'];
}

/**
 * Check if user can edit comment (owner only)
 */
function canEditComment($comment, $user) {
    if (!isset($user)) {
        return false;
    }
    
    return $user['usn'] == $comment['user_id'];
}

function getRelatedArticles(PDO $pdo, int $articleId, int $limit = 4): array
{
    // Get current article category (may be null)
    $stmt = $pdo->prepare("SELECT category_id FROM articles WHERE id = :id");
    $stmt->execute([':id' => $articleId]);
    $categoryId = $stmt->fetchColumn();
    $categoryId = $categoryId !== false ? (int)$categoryId : null;

    // First try tag-based relevance: count shared tags, add a small boost for same category
    $sql = "
        SELECT a.*, COUNT(at.tag_id) AS tag_matches,
               (a.category_id = :category_id) AS same_category,
               (COUNT(at.tag_id) + (a.category_id = :category_id)) AS score
        FROM article_tags at
        JOIN articles a ON a.id = at.article_id
        WHERE at.tag_id IN (
            SELECT tag_id FROM article_tags WHERE article_id = :article_id
        )
          AND a.id != :article_id
        GROUP BY a.id
        ORDER BY score DESC, tag_matches DESC, a.created_at DESC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        return $results;
    }

    // Fallback: articles from same category (if any)
    if ($categoryId) {
        $stmt = $pdo->prepare("
            SELECT * FROM articles
            WHERE category_id = :category_id AND id != :article_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            return $results;
        }
    }

    // Final fallback: most recent articles excluding current
    $stmt = $pdo->prepare("
        SELECT * FROM articles
        WHERE id != :article_id
        ORDER BY created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}