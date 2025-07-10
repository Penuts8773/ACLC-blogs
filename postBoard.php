<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}
require_once __DIR__ . '/backend/conn.php';

// Helper: Save uploaded image and return filename or null
function save_uploaded_image($file, $upload_dir = 'uploads/') {
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
        $subtitle = $_POST['subtitle'] ?? '';
        

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/postBoard.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">
    <title>Create Post - ACLC Blogs</title>
    
</head>
<body class="postBoard-body">
    <?php include 'navbar.php'; ?>
    <div class="postBoard-container">
        <form method="post" id="postForm" enctype="multipart/form-data">
            <h2>Post to Board</h2>
            <div>
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
                <label for="subtitle">Subtitle</label>
                <input type="text" id="subtitle" name="subtitle" required>
                <!-- Subtitle removed to match DB schema -->
                
                <div id="headerZone"
                    style="margin-top:10px; border:2px dashed #ccc; padding:10px; text-align:center; cursor:pointer;"
                    ondrop="handleHeaderDrop(event)" 
                    ondragover="allowDrop(event)">                
                    <div id="dropzone">
                        <p style="font-size:0.9em; color:#555;">Drag and drop a header image here 📷</p>
                        <input type="file" name="headerfile" accept="image/*" style="display:none;">
                    </div>
                </div>
                
                <div id="postPreviewContainer" style="margin-top:15px;"></div>

                <!-- Dynamic Content Blocks -->
                <div id="blocksContainer" style="margin-top:10px;"></div>

                <!-- Add Block Buttons -->
                <div style="margin-top:10px;">
                    <button class="postButton" type="button" onclick="addTextBlock()">Add Text Block</button>
                    <button class="postButton" type="button" onclick="addImageBlock()">Add Image Block</button>
                </div>

                <button type="submit" class="postButton" style="margin-top:15px;">Submit Post</button>
            </div>
        </form>
    </div>
    
    <script src="script/postBoard.js">
    </script>
</body>
</html>