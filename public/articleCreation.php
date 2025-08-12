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
    $tags = $_POST['tags']; // Tags

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

    // Insert tags (assumes tags are stored as a comma-separated string or individual entries in a `tags` table)
    foreach ($tags as $tag) {
        $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_name) VALUES (?, ?)");
        $stmt->execute([$article_id, $tag]);
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
        <input type="text" name="title" placeholder="Title" required><br><br>

        <!-- Category Selection -->
        <label for="category">Category</label>
        <select name="category" id="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <!-- Tags Input (Comma-separated) -->
        <label for="tags">Tags (Comma-separated)</label>
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
<script>
document.querySelector('form').onsubmit = (e) => {
    e.preventDefault();
    showConfirmModal('Are you sure you want to publish this article?', () => {
        e.target.submit();
    });
};

function addBlock() {
    const block = document.createElement('div');
    block.className = 'block';
    
    // Create block header
    const header = document.createElement('div');
    header.className = 'block-header';
    
    // Create select element
    const select = document.createElement('select');
    select.name = 'types[]';
    select.innerHTML = `
        <option value="text">Text</option>
        <option value="image">Image</option>
    `;
    select.onchange = (e) => handleTypeChange(e.target);
    
    // Create remove button
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-block';
    removeBtn.textContent = 'Remove';
    removeBtn.onclick = () => block.remove();
    
    // Create content container
    const content = document.createElement('div');
    content.className = 'block-content';
    
    // Create textarea as default content
    const textarea = document.createElement('textarea');
    textarea.name = 'blocks[]';
    textarea.placeholder = 'Content';
    textarea.required = true;
    
    // Assemble the block
    header.appendChild(select);
    header.appendChild(removeBtn);
    content.appendChild(textarea);
    block.appendChild(header);
    block.appendChild(content);
    
    document.getElementById('blocks').appendChild(block);
}

function handleTypeChange(select) {
    const wrapper = select.closest('.block').querySelector('.block-content');
    wrapper.innerHTML = ''; // clear previous content

    if (select.value === 'image') {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'blocks[]';
        hiddenInput.required = true;

        const drop = document.createElement('div');
        drop.className = 'drop';
        drop.ondragover = e => e.preventDefault();
        drop.ondrop = e => handleDrop(e, drop);
        drop.onclick = () => handleClick(drop);
        drop.innerHTML = `<span>Drag & drop image or click</span>`;
        drop.appendChild(hiddenInput);

        wrapper.appendChild(drop);
    } else {
        wrapper.innerHTML = `<textarea name="blocks[]" placeholder="Content" required></textarea>`;
    }
}

function handleDrop(event, dropZone) {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    if (!file.type.startsWith('image/')) return alert('Only images allowed');

    const reader = new FileReader();
    reader.onload = function(e) {
        const input = dropZone.querySelector('input[type=hidden]');
        input.value = e.target.result;
        dropZone.innerHTML = `<img src="${e.target.result}" style="max-width: 200px">`;
        dropZone.appendChild(input);
    };
    reader.readAsDataURL(file);
}

function handleClick(dropZone) {
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = e => {
        const file = e.target.files[0];
        if (!file.type.startsWith('image/')) return alert('Only images allowed');
        
        const reader = new FileReader();
        reader.onload = evt => {
            const input = dropZone.querySelector('input[type=hidden]');
            input.value = evt.target.result;
            dropZone.innerHTML = `<img src="${evt.target.result}" style="max-width: 200px">`;
            dropZone.appendChild(input);
        };
        reader.readAsDataURL(file);
    };
    fileInput.click();
}
</script>
</body>
</html>
