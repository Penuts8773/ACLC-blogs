<?php
// Include the database connection
require_once 'conn.php';

function createArticle($title, $content, $author, $image) {

}

function modifyArticle($id, $title, $content, $author, $image) {


}

function deleteArticle($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

function getArticleById($id) {
    global $conn;
    $sql = "SELECT articles.*, article_blocks.*, u.name as author FROM articles 
            LEFT JOIN article_blocks ON articles.id = article_blocks.article_id 
            LEFT JOIN user u ON articles.user_id = u.usn 
            WHERE articles.id = ? 
            ORDER BY article_blocks.sort_order ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = null;
    $blocks = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!$article) {
                $article = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'created_at' => $row['created_at'],
                    'blocks' => []
                ];
            }
            if (isset($row['block_type']) && $row['block_type']) {
                $article['blocks'][] = [
                    'type' => $row['block_type'],
                    'content' => $row['block_content'] ?? $row['content'] ?? '',
                    'image_url' => $row['block_image_url'] ?? $row['content'] ?? null,
                    'position' => $row['block_position'] ?? $row['sort_order'] ?? null
                ];
            }
        }
    }
    $stmt->close();
    return $article;
}

function getAllArticlesWithSummaryAndImage() {
    global $conn;
    $articles = [];
    $sql = "SELECT a.id, a.title, a.created_at, u.name as author FROM articles a JOIN user u ON a.user_id = u.usn ORDER BY a.created_at DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $blockSql = "SELECT block_type, content, sort_order FROM article_blocks WHERE article_id = ? ORDER BY sort_order ASC";
            $blockStmt = $conn->prepare($blockSql);
            $blockStmt->bind_param('i', $row['id']);
            $blockStmt->execute();
            $blockResult = $blockStmt->get_result();
            $summary = '';
            $image = '';
            while ($block = $blockResult->fetch_assoc()) {
                if ($block['sort_order'] == 0) {
                    if ($block['block_type'] === 'text' && $summary === '') {
                        $summary = mb_substr(strip_tags($block['content']), 0, 120) . '...';
                    }
                    if ($block['block_type'] === 'image' && $image === '') {
                        $image = 'uploads/' . basename($block['content']);
                    }
                }
            }
            $blockStmt->close();
            $articles[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'date' => date('Y-m-d', strtotime($row['created_at'])),
                'content' => $summary,
                'image' => $image,
                'views' => 0 // Placeholder, add views if you have them
            ];
        }
    }
    return $articles;
}