<?php
session_start();
require_once '../backend/db.php';
include 'components/modal.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Check user privilege
if ($user['privilege'] == 3) { // Student
    die("Students are not allowed to create articles.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $types = $_POST['types'];
    $blocks = $_POST['blocks'];
    $category_id = $_POST['category']; // Category selection
    $tags_input = $_POST['tags']; // Tags as comma-separated string

    if ($types[0] !== 'image') {
        die("First block must be an image (thumbnail).");
    }

    // Set approval status based on privilege level
    $isApproved = ($user['privilege'] == 1) ? 1 : 0;

    // Insert article
    $stmt = $pdo->prepare("INSERT INTO articles (user_id, title, approved, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['usn'], $title, $isApproved, $category_id]);
    $article_id = $pdo->lastInsertId();

    // Insert article blocks (content)
    foreach ($blocks as $i => $content) {
        $type = $types[$i];

        if ($type === 'image' && str_starts_with($content, 'data:image/')) {
            $data = explode(',', $content);
            $imgData = base64_decode($data[1]);
            $imgName = uniqid('img_', true) . '.png';
            file_put_contents("uploads/$imgName", $imgData);
            $content = "uploads/$imgName";
        }

        $stmt = $pdo->prepare("INSERT INTO article_blocks (article_id, block_type, content, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$article_id, $type, $content, $i]);
    }

    // Process and insert tags
    if (!empty($tags_input)) {
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array); // Remove empty values
        
        foreach ($tags_array as $tag_name) {
            // Check if tag exists, if not create it
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tag_name]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                // Create new tag
                $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$tag_name]);
                $tag_id = $pdo->lastInsertId();
            } else {
                $tag_id = $tag['id'];
            }
            
            // Link article to tag
            $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$article_id, $tag_id]);
        }
    }

    // Redirect with appropriate message
    if ($isApproved) {
        header("Location: articleBoard.php?success=1");
    } else {
        header("Location: articleBoard.php?pending=1");
    }
    exit;
}

// Fetch categories from the database
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Article</title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/articleForm.css">
    <link rel="stylesheet" href="assets/style/article.css">
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
    <h2>Create Article</h2>
    
    <?php if ($user['privilege'] == 2): ?>
    <div class="notice" style="background: #fff3cd; color: #856404; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
        ⚠️ Note: As a teacher, your article will need admin approval before being published.
    </div>
    <?php endif; ?>

    <form method="POST" id="articleForm">
        <input type="text" name="title" placeholder="Title" required><br>

        <!-- Category Selection -->
        <div class="select-wrapper">
            <select name="category" id="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>
        

        <!-- Tags Input (Comma-separated) -->
        
        <input type="text" name="tags" id="tags" placeholder="Enter tags, separated by commas"><br><br>

        <div id="blocks">
            <!-- First block (Thumbnail) -->
            <div class="block">
                <label>Thumbnail (First Block - Required)</label><br>
                <input type="hidden" name="types[]" value="image">
                <div class="drop" ondragover="event.preventDefault()" ondrop="handleDrop(event, this)" onclick="handleClick(this)">
                    <input type="hidden" name="blocks[]" required>
                    <span>Drag & drop image here or click to upload</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addBlock()" class="add-block-btn">Add Block</button>
        <div class="form-buttons">
            <button type="submit">Create Article</button>
            <button type="button" onclick="history.back()">Cancel</button>
        </div>
    </form>
</div>

<?php include 'components/modal.php'; ?>
<script src="script/articleCreationJs.js"></script>
</body>
</html>