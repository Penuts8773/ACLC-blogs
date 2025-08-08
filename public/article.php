<?php
require_once '../backend/db.php';
require_once '../backend/article.php';
include 'components/modal.php';

// Debug session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session data
error_log('Session data: ' . print_r($_SESSION, true));

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: articleBoard.php');
    exit;
}



$article = getArticleWithNames($pdo, $id);

if (!$article) {
    header('Location: articleBoard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($article['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <p>By <?= htmlspecialchars($article['author_name']) ?> | <?= $article['created_at'] ?></p>
        <?php if ($article['modified_at']): ?>
            <p class="edit-info">
                <?php if ($article['last_editor_name']): ?>
                    Last edited by <?= htmlspecialchars($article['last_editor_name']) ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        
        <?php 
        // Show edit button only to admins and article owners
        if (isset($_SESSION['user']) && 
            ($_SESSION['user']['privilege'] == 1 || $_SESSION['user']['usn'] == $article['user_id'])): 
        ?>
            <button onclick="window.location.href='editArticle.php?id=<?= $article['id'] ?>'" 
                    class="edit-btn action-btn">
                Edit Article
            </button>
        <?php endif; ?>
    </div>
    
    <?php
    $blocks = getArticleBlocks($pdo, $id);
    foreach ($blocks as $b) {
        if ($b['block_type'] === 'text') {
            echo "<p>" . nl2br(htmlspecialchars($b['content'])) . "</p>";
        } else {
            echo "<img src='" . htmlspecialchars($b['content']) . "'>";
        }
    }
    ?>
</div>
<!-- Display comments -->
<div class="comments-section" id="comments">
    <?php
    // Debug output
    echo "<!-- Current user: " . ($_SESSION['user']['usn'] ?? 'Not logged in') . " -->";
    
    // Get comments with user information
    $comments = getArticleComments($pdo, $id);
    foreach ($comments as $comment) {
        $isOwner = isset($_SESSION['user']) && $_SESSION['user']['usn'] == $comment['user_id'];
        // Debug output
        echo "<!-- Comment ID: {$comment['id']}, User ID: {$comment['user_id']}, Is Owner: " . ($isOwner ? 'true' : 'false') . " -->";
        ?>
        <div class='comment' id='comment-<?= $comment['id'] ?>'>
            <strong><?= htmlspecialchars($comment['name']) ?></strong>
            <small>
                <?= $comment['created_at'] ?>
                <?php if ($comment['modified_at']): ?>
                    <span class="edit-indicator">(edited)</span>
                <?php endif; ?>
            </small>
            <?php if ($isOwner): ?>
                <div class='comment-actions'>
                    <button class='edit-btn action-btn' onclick='editComment(<?= $comment['id'] ?>)'>Edit</button>
                    <button class='delete-btn action-btn' onclick='deleteComment(<?= $comment['id'] ?>)'>Delete</button>
                </div>
            <?php endif; ?>
            <p class='comment-content'><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
            <?php if ($isOwner): ?>
                <form class='edit-form' style='display:none;'>
                    <textarea required><?= htmlspecialchars($comment['content']) ?></textarea>
                    <div class="form-buttons">
                        <button type='submit' class="save-btn action-btn">Save</button>
                        <button type='button' class="cancel-btn action-btn" onclick='cancelEdit(<?= $comment['id'] ?>)'>Cancel</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    ?>
</div>

<!-- Comment form -->
<?php if (isset($_SESSION['user'])): ?>
    <form id="comment-form" class="comment-form">
        <textarea name="comment" rows="3" placeholder="Write a comment..." required></textarea>
        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
        <button type="submit">Post Comment</button>
    </form>
<?php else: ?>
    <p><a href="login.php">Login</a> to comment.</p>
<?php endif; ?>

<?php include 'components/modal.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Article edit button handler
    const editArticleBtn = document.getElementById('editArticleBtn');
    if (editArticleBtn) {
        editArticleBtn.addEventListener('click', function() {
            const articleId = this.dataset.articleId;
            const userPrivilege = parseInt(this.dataset.privilege);
            
            const message = userPrivilege === 1 ? 
                'Edit this article?' : 
                'Your changes will need admin approval. Continue?';
            
            showConfirmModal(message, () => {
                window.location.href = `editArticle.php?id=${articleId}`;
            });
        });
    }
    
    // Comment edit buttons handler
    document.querySelectorAll('.comment .edit-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.closest('.comment').id.split('-')[1];
            editComment(commentId);
        });
    });
});

document.getElementById('comment-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const data = new FormData(form);
    
    try {
        const response = await fetch('../backend/comment.php', {
            method: 'POST',
            body: data
        });
        
        if (!response.ok) throw new Error('Network response was not ok');
        
        const result = await response.json();
        
        if (result.success) {
            // Add new comment to the top of comments section
            const commentsDiv = document.getElementById('comments');
            const newComment = document.createElement('div');
            newComment.className = 'comment';
            newComment.innerHTML = `
                <strong>${result.name}</strong>
                <small>Just now</small>
                <p>${result.content.replace(/\n/g, '<br>')}</p>
            `;
            commentsDiv.insertBefore(newComment, commentsDiv.firstChild);
            
            // Clear the form
            form.reset();
        } else {
            alert('Error posting comment: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error posting comment. Please try again.');
    }
});

function editComment(id) {
    const commentDiv = document.getElementById(`comment-${id}`);
    if (!commentDiv) return;
    
    const content = commentDiv.querySelector('.comment-content');
    const form = commentDiv.querySelector('.edit-form');
    
    if (!content || !form) return;
    
    content.style.display = 'none';
    form.style.display = 'block';
    
    const textarea = form.querySelector('textarea');
    if (textarea) {
        textarea.focus();
        textarea.selectionStart = textarea.value.length;
    }
}

function cancelEdit(id) {
    const commentDiv = document.getElementById(`comment-${id}`);
    const content = commentDiv.querySelector('.comment-content');
    const form = commentDiv.querySelector('.edit-form');
    
    content.style.display = 'block';
    form.style.display = 'none';
}

document.querySelectorAll('.edit-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const commentDiv = this.closest('.comment');
        const id = commentDiv.id.split('-')[1];
        const content = this.querySelector('textarea').value;
        
        showConfirmModal('Save changes to this comment?', async () => {
            try {
                const response = await fetch('../backend/comment.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: id,
                        content: content
                    })
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const result = await response.json();
                if (result.success) {
                    const contentElement = commentDiv.querySelector('.comment-content');
                    contentElement.innerHTML = content.replace(/\n/g, '<br>');
                    cancelEdit(id);
                    
                    // Add edit indicator if not present
                    if (!commentDiv.querySelector('.edit-indicator')) {
                        const timeElement = commentDiv.querySelector('small');
                        timeElement.innerHTML += ' (edited)';
                    }
                } else {
                    alert('Error updating comment: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating comment. Please try again.');
            }
        });
    });
});

async function deleteComment(id) {
    showConfirmModal('Are you sure you want to delete this comment?', async () => {
        try {
            const response = await fetch('../backend/comment.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ comment_id: id })
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            if (result.success) {
                document.getElementById(`comment-${id}`).remove();
            } else {
                alert('Error deleting comment: ' + result.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting comment. Please try again.');
        }
    });
}
</script>

</body>
</html>