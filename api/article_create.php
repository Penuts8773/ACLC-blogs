<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}
require_once __DIR__ . '/conn.php';

// Helper: Save uploaded image and return filename or null
function save_uploaded_image($file, $upload_dir = '../uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user id from session
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    if (!$user_id) {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;">You must be logged in to post.</div>';
    } else {
        $title = $_POST['title'] ?? '';

        // Insert article (no subtitle/header image)
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title) VALUES (?, ?)");
        $stmt->bind_param('is', $user_id, $title);
        $stmt->execute();
        $article_id = $stmt->insert_id;
        $stmt->close();

        // Handle blocks (fixed: separate arrays for text and image content)
        $block_types = $_POST['block_type'] ?? [];
        $block_order = 0;
        $text_idx = 0;
        $image_idx = 0;
        foreach ($block_types as $i => $type) {
            if ($type === 'text') {
                $content = $_POST['block_content'][$text_idx];
                $stmt = $conn->prepare("INSERT INTO article_blocks (article_id, block_type, content, sort_order) VALUES (?, 'text', ?, ?)");
                $stmt->bind_param('isi', $article_id, $content, $block_order);
                $stmt->execute();
                $stmt->close();
                $text_idx++;
            } elseif ($type === 'image') {
                $imgFile = [
                    'name' => $_FILES['block_content']['name'][$image_idx],
                    'type' => $_FILES['block_content']['type'][$image_idx],
                    'tmp_name' => $_FILES['block_content']['tmp_name'][$image_idx],
                    'error' => $_FILES['block_content']['error'][$image_idx],
                    'size' => $_FILES['block_content']['size'][$image_idx],
                ];
                $imgName = save_uploaded_image($imgFile);
                $stmt = $conn->prepare("INSERT INTO article_blocks (article_id, block_type, content, sort_order) VALUES (?, 'image', ?, ?)");
                $stmt->bind_param('isi', $article_id, $imgName, $block_order);
                $stmt->execute();
                $stmt->close();
                $image_idx++;
            }
            $block_order++;
        }

        echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;">Post saved successfully!</div>';
    }
}
?>