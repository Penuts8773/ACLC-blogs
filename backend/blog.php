<?php
require_once 'blogModel.php';
require_once __DIR__ . '/conn.php';

// Get all articles with summary and image (moved to model)
function getAllArticlesWithContentAndImages() {
    return getAllArticlesWithSummaryAndImage();
}

// Get a single article by ID (controller)
function getArticleWithContentAndImages($id) {
    $article = getArticleById($id);
    if ($article) {
        usort($article['blocks'], function($a, $b) {
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });
    }
    return $article;
}

function deleteArticleController($id) {
    return deleteArticle($id);
}
