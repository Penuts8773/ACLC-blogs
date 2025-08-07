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
    $blocksData = json_decode($_POST['blocksData'] ?? '[]', true);

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
        $success = $articleController->updateArticle($articleId, $title, $blocksData, $_SESSION['user']['usn']);
        if ($success) {
            $_SESSION['success'] = "Article updated successfully";
            header('Location: article.php?id=' . $articleId);
            exit;
        }
    } else {
        // Create draft for teachers
        $draftId = $articleController->createEditDraft($articleId, $_SESSION['user']['usn'], $title, $blocksData);
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Article - <?= htmlspecialchars($article['title']) ?></title>
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
    <h2>Edit Article</h2>
    
    <?php if ($isTeacher): ?>
        <div class="notice">Your changes will need admin approval before being published.</div>
    <?php endif; ?>

    <form method="POST" id="editForm">
    <input type="text" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
    <div id="blocks">
        <?php foreach ($blocks as $index => $block): ?>
            <div class="block">
                <div class="block-header">
                    <select name="types[]" onchange="handleTypeChange(this)">
                        <option value="text" <?= $block['block_type'] === 'text' ? 'selected' : '' ?>>Text</option>
                        <option value="image" <?= $block['block_type'] === 'image' ? 'selected' : '' ?>>Image</option>
                    </select>
                    <?php if ($index > 0): ?>
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
<script>
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Collect blocks data
    const blocks = [];
    
    document.querySelectorAll('.block').forEach((block, index) => {
        const type = block.querySelector('select').value;
        let content;
        
        if (type === 'text') {
            content = block.querySelector('textarea').value;
        } else {
            content = block.querySelector('input[name="blocks[]"]').value;
        }
        
        blocks.push({
            type: type,
            content: content,
            sort_order: index
        });
    });
    
    // Validate thumbnail
    if (blocks.length === 0 || blocks[0].type !== 'image') {
        alert('First block must be an image (thumbnail).');
        return;
    }
    
    // Show confirmation modal
    showConfirmModal('Save changes to this article?', () => {
        // Remove any existing hidden input
        const oldInput = document.getElementById('blocksData');
        if (oldInput) oldInput.remove();
        
        // Add hidden input with blocks data
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'blocksData';
        hiddenInput.id = 'blocksData';
        hiddenInput.value = JSON.stringify(blocks);
        this.appendChild(hiddenInput);
        
        // Submit the form
        this.submit();
    });
});

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
    const block = select.closest('.block');
    const content = block.querySelector('.block-content');
    content.innerHTML = ''; // clear previous content

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

        content.appendChild(drop);
    } else {
        const textarea = document.createElement('textarea');
        textarea.name = 'blocks[]';
        textarea.placeholder = 'Content';
        textarea.required = true;
        content.appendChild(textarea);
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

// Remove the removeBlock function since we're handling it inline
</script>
</body>
</html>