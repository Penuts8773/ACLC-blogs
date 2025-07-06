<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    // For AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        exit(json_encode(['redirect' => '/index.php?page=login']));
    }
    // For regular requests
    header('Location: /index.php?page=login');
    exit;
}
?>