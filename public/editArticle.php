<?php
session_start();
require_once '../backend/db.php';
include 'components/modal.php';
require_once '../backend/controllers/ArticleController.php';
require_once '../backend/controllers/UserController.php';

$articleController = new ArticleController($pdo);
$userController = new UserController($pdo);

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$articleId = $_GET['id'] ?? null;
if (!$articleId) {
    header('Location: index.php');
    exit;
}

$article = $articleController->getArticle($articleId);
if (!$article) {
    header('Location: index.php');
    exit;
}

// Replace the permission check section with this
$isAdmin = $_SESSION['user']['privilege'] == 1;
$isTeacher = $_SESSION['user']['privilege'] == 2;
$isAuthor = $_SESSION['user']['usn'] == $article['user_id'];

// Check edit permissions
if ($isTeacher && !$isAuthor) {
    // Teachers can only edit their own articles
    $_SESSION['error'] = "You can only edit your own articles";
    header('Location: article.php?id=' . $articleId);
    exit;
} elseif (!$isAdmin && !$isTeacher) {
    // Other users (students, etc.) can't edit
    $_SESSION['error'] = "You don't have permission to edit articles";
    header('Location: article.php?id=' . $articleId);
    exit;
}

// Add this after the existing permission checks
if ($isTeacher && !$isAdmin) {
    // Check if teacher already has a pending draft
    if ($articleController->hasPendingDraft($articleId, $_SESSION['user']['usn'])) {
        $_SESSION['error'] = "You already have a pending edit request for this article";
        header('Location: article.php?id=' . $articleId);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $category_id = $_POST['category'] ?? null;
    $tags_input = $_POST['tags'] ?? '';
    $blocksData = json_decode($_POST['blocksData'] ?? '[]', true);

    // Validate thumbnail (first block must be image)
    if (empty($blocksData) || $blocksData[0]['type'] !== 'image') {
        $_SESSION['error'] = "First block must be an image (thumbnail)";
        header('Location: editArticle.php?id=' . $articleId);
        exit;
    }

    foreach ($blocksData as &$block) {
        if ($block['type'] === 'image' && str_starts_with($block['content'], 'data:image/')) {
            $data = explode(',', $block['content']);
            $imgData = base64_decode($data[1]);
            $imgName = uniqid('img_', true) . '.png';
            
            // Make sure 'uploads/' folder exists and is writable
            file_put_contents(__DIR__ . "/uploads/$imgName", $imgData);

            // Replace content with relative path
            $block['content'] = "uploads/$imgName";
        }
    }
    unset($block);
    
    if ($isAdmin) {
        // Direct edit for admins
        $success = $articleController->updateArticle($articleId, $title, $blocksData, $_SESSION['user']['usn'], $category_id, $tags_input);
        if ($success) {
            $_SESSION['success'] = "Article updated successfully";
            header('Location: article.php?id=' . $articleId);
            exit;
        }
    } else {
        // Create draft for teachers
        $draftId = $articleController->createEditDraft($articleId, $_SESSION['user']['usn'], $title, $blocksData, $category_id, $tags_input);
        if ($draftId) {
            $_SESSION['success'] = "Your edit has been submitted for approval";
            header('Location: article.php?id=' . $articleId);
            exit;
        }
    }
    
    $_SESSION['error'] = "Failed to save changes";
}

// Get article blocks
$blocks = $articleController->getArticleBlocks($articleId);

// Get categories
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Get current article tags
$stmt = $pdo->prepare("
    SELECT t.name 
    FROM tags t 
    JOIN article_tags at ON t.id = at.tag_id 
    WHERE at.article_id = ?
");
$stmt->execute([$articleId]);
$currentTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
$tagsString = implode(', ', $currentTags);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Article - <?= htmlspecialchars($article['title']) ?></title>
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">
    <link rel="stylesheet" href="assets/style/articleForm.css">

    <style>
        .drop {
            border: 2px dashed #ccc;
            padding: 10px;
            margin-top: 5px;
            cursor: pointer;
        }   
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container article-form">
    <h2>Edit Article</h2>
    
    <?php if ($isTeacher): ?>
        <div class="notice">Your changes will need admin approval before being published.</div>
    <?php endif; ?>

    <form method="POST" id="editForm">
        <input type="text" name="title" value="<?= htmlspecialchars($article['title']) ?>" required><br><br>
        
        <!-- Category Selection -->
        <label for="category">Category</label>
        <select name="category" id="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?= $category['id'] == $article['category_id'] ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <!-- Tags Input -->
        <label for="tags">Tags (Comma-separated)</label>
        <input type="text" name="tags" id="tags" value="<?= htmlspecialchars($tagsString) ?>" placeholder="Enter tags, separated by commas"><br><br>

        <div id="blocks">
            <?php foreach ($blocks as $index => $block): ?>
                <div class="block">
                    <div class="block-header">
                        <?php if ($index === 0): ?>
                            <label>Thumbnail (Required)</label>
                            <select name="types[]" onchange="handleTypeChange(this)" disabled>
                                <option value="image" selected>Image</option>
                            </select>
                        <?php else: ?>
                            <select name="types[]" onchange="handleTypeChange(this)">
                                <option value="text" <?= $block['block_type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                <option value="image" <?= $block['block_type'] === 'image' ? 'selected' : '' ?>>Image</option>
                            </select>
                            <button type="button" class="remove-block" onclick="this.closest('.block').remove()">Remove</button>
                        <?php endif; ?>
                    </div>
                    <div class="block-content">
                        <?php if ($block['block_type'] === 'text'): ?>
                            <textarea name="blocks[]" required><?= htmlspecialchars($block['content']) ?></textarea>
                        <?php else: ?>
                            <div class="drop" ondragover="event.preventDefault()" ondrop="handleDrop(event, this)" onclick="handleClick(this)">
                                <?php if ($block['content']): ?>
                                    <img src="<?= htmlspecialchars($block['content']) ?>" style="max-width: 200px">
                                <?php else: ?>
                                    <span>Drag & drop image or click</span>
                                <?php endif; ?>
                                <input type="hidden" name="blocks[]" value="<?= htmlspecialchars($block['content']) ?>" required>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" onclick="addBlock()" class="add-block-btn">Add Block</button>
        <div class="form-buttons">
            <button type="submit" class="save-btn action-btn">Save Changes</button>
            <button type="button" onclick="history.back()" class="cancel-btn action-btn">Cancel</button>
        </div>
    </form>
</div>

<?php include 'components/modal.php'; ?>
<script src="script/editArticleJs.js"></script>

</body>
</html>