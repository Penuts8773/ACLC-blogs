<?php
// Include the database connection
require_once 'conn.php';

function getAllArticles() {
    global $conn; // Use the connection from conn.php
    $sql = "SELECT * FROM articles LEFT JOIN article_blocks ON articles.id = article_blocks.article_id ORDER BY articles.created_at DESC";
    $result = $conn->query($sql);
    $articles = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
    }
    return $articles;
}
function createArticle($title, $content, $author, $image) {

}

function modifyArticle($id, $title, $content, $author, $image) {


}

function deleteArticle($id) {


}

function getAllArticlesWithContentAndImages() {
    global $conn;
    $sql = "SELECT a.id as article_id, a.title, a.author, a.created_at, 
                   c.id as content_id, c.content, c.position as content_position, 
                   i.id as image_id, i.image_url, i.position as image_position
            FROM articles a
            LEFT JOIN content c ON a.id = c.article_id
            LEFT JOIN image i ON a.id = i.article_id
            ORDER BY a.created_at DESC, c.position ASC, i.position ASC";
    $result = $conn->query($sql);
    $articles = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['article_id'];
            if (!isset($articles[$id])) {
                $articles[$id] = [
                    'id' => $id,
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'created_at' => $row['created_at'],
                    'content' => [],
                    'images' => []
                ];
            }
            if ($row['content_id']) {
                $articles[$id]['content'][$row['content_position']] = [
                    'id' => $row['content_id'],
                    'content' => $row['content']
                ];
            }
            if ($row['image_id']) {
                $articles[$id]['images'][$row['image_position']] = [
                    'id' => $row['image_id'],
                    'image_url' => $row['image_url']
                ];
            }
        }
        // Sort content and images by their position
        foreach ($articles as &$article) {
            ksort($article['content']);
            ksort($article['images']);
        }
    }
    return array_values($articles);
}