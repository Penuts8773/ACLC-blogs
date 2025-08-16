<?php
require_once '../backend/db.php';
require_once '../backend/article.php';
require_once '../backend/blog.php';
include 'components/modal.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate article ID
$articleId = $_GET['id'] ?? null;
if (!$articleId || !is_numeric($articleId)) {
    header('Location: articleBoard.php');
    exit;
}

// Get article data
$article = getArticleWithNames($pdo, (int)$articleId);
if (!$article) {
    header('Location: articleBoard.php');
    exit;
}

// Get related data
$articles = getAllArticles($pdo);
$mostLiked = getMostLikedArticles($pdo);
$mostCommented = getMostCommentedArticle($pdo);
$blocks = getArticleBlocks($pdo, $articleId);
$comments = getArticleComments($pdo, $articleId);

/**
 * Render an article card
 */
function renderArticleCard($article, $pdo) {
    if (!$article) {
        echo "<p class='no-article'>No articles available.</p>";
        return;
    }
    
    $blocks = getArticleBlocks($pdo, $article['id']);
    $content = getArticleThumbnailAndPreview($blocks);
    $authorName = $article['author_name'] ?? $article['name'] ?? 'Unknown';
    ?>
    <div class='article' style='background-image: url("<?= htmlspecialchars($content['thumbnail']) ?>")'>
        <div class='article-content'>
            <h2><?= htmlspecialchars($article['title']) ?></h2>
            <small>By <?= htmlspecialchars($authorName) ?> on <?= htmlspecialchars($article['created_at']) ?></small>
            <p class='preview'><?= htmlspecialchars($content['preview']) ?></p>
            <button onclick='window.location.href="article.php?id=<?= $article['id'] ?>"' class='read-more'>
                Read More
            </button>
        </div>
    </div>
    <?php
}

/**
 * Render a single comment
 */
function renderComment($comment, $currentUser) {
    $isOwner = isset($currentUser) && $currentUser['usn'] == $comment['user_id'];
    ?>
    <div class='comment' id='comment-<?= $comment['id'] ?>'>
        <strong><?= htmlspecialchars($comment['name']) ?></strong>
        <small>
            <?= htmlspecialchars($comment['created_at']) ?>
            <?php if ($comment['modified_at']): ?>
                <span class="edit-indicator">(edited)</span>
            <?php endif; ?>
        </small>
        <p class='comment-content'><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
        
        <?php if ($isOwner): ?>
            <div class='comment-actions'>
                <button class='edit-btn action-btn' onclick='editComment(<?= $comment['id'] ?>)'>Edit</button>
                <button class='delete-btn action-btn' onclick='deleteComment(<?= $comment['id'] ?>)'>Delete</button>
            </div>
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body class="article-view">
    <?php include 'navbar.php'; ?>
    
    <div class="article-body">
        <div class="article-container">
            <!-- Article Header -->
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            
            <div class="article-meta">
                <p>By <?= htmlspecialchars($article['author_name']) ?> | <?= htmlspecialchars($article['created_at']) ?></p>
                
                <?php if ($article['modified_at'] && $article['last_editor_name']): ?>
                    <p class="edit-info">
                        Last edited by <?= htmlspecialchars($article['last_editor_name']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if (canEditArticle($article, $_SESSION['user'] ?? null)): ?>
                    <button onclick="window.location.href='editArticle.php?id=<?= $article['id'] ?>'" 
                            class="edit-btn">
                        Edit Article
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Article Content -->
            <div>
                <?php foreach ($blocks as $block): ?>
                    <?php if ($block['block_type'] === 'text'): ?>
                        <p><?= nl2br(htmlspecialchars($block['content'])) ?></p>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($block['content']) ?>" alt="Article image">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Comments Section -->
            <div class="comment-section" id="comments">
                <h3>Comments</h3>
                <?php foreach ($comments as $comment): ?>
                    <?php renderComment($comment, $_SESSION['user'] ?? null); ?>
                <?php endforeach; ?>
                
                <?php if (empty($comments)): ?>
                    <p class="no-comments">No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <?php if (isset($_SESSION['user'])): ?>
                <form id="comment-form" class="comment-form">
                    <textarea name="comment" rows="3" placeholder="Write a comment..." required></textarea>
                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">
                    <a href="login.php">Login</a> to comment.
                </p>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="article-section-side slide-up">
            <!-- Most Liked Articles -->
            <div class="article-section-like">
                <h2>👍 Most Liked Articles</h2>
                <?php if ($mostLiked && count($mostLiked) > 0): ?>
                    <?php foreach ($mostLiked as $likedArticle): ?>
                        <?php renderArticleCard($likedArticle, $pdo); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Most Commented Articles -->
            <div class="article-section-comment">
                <h2>💬 Most Commented Articles</h2>
                <?php if ($mostCommented && count($mostCommented) > 0): ?>
                    <?php foreach ($mostCommented as $commentedArticle): ?>
                        <?php renderArticleCard($commentedArticle, $pdo); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class='no-article'>No articles available.</p>
                <?php endif; ?>
            </div>

            <!-- Article List -->
            <div class="articleList slide-up">
                <h2>📄 Article List</h2>
                <ul>
                    <?php foreach ($articles as $listArticle): ?>
                        <li>
                            <a href="article.php?id=<?= urlencode($listArticle['id']) ?>">
                                <strong><?= htmlspecialchars($listArticle['title']) ?></strong>
                            </a>
                            <br>
                            <small>
                                By <?= htmlspecialchars($listArticle['author_name'] ?? 'Unknown') ?> 
                                | <?= htmlspecialchars($listArticle['created_at']) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'components/modal.php'; ?>

    <script>
        // Comment form submission
        document.getElementById('comment-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('../backend/comment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Add new comment to the DOM
                    const commentsDiv = document.getElementById('comments');
                    const noComments = commentsDiv.querySelector('.no-comments');
                    
                    if (noComments) {
                        noComments.remove();
                    }
                    
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.innerHTML = `
                        <strong>${result.name}</strong>
                        <small>Just now</small>
                        <p class="comment-content">${result.content.replace(/\n/g, '<br>')}</p>
                    `;
                    
                    // Insert after the "Comments" heading
                    const heading = commentsDiv.querySelector('h3');
                    heading.insertAdjacentElement('afterend', newComment);
                    
                    // Clear form
                    e.target.reset();
                } else {
                    alert('Error posting comment: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error posting comment. Please try again.');
            }
        });

        // Comment editing functions
        function editComment(id) {
            const commentDiv = document.getElementById(`comment-${id}`);
            const content = commentDiv.querySelector('.comment-content');
            const form = commentDiv.querySelector('.edit-form');
            const actions = commentDiv.querySelector('.comment-actions');
            
            content.style.display = 'none';
            actions.style.display = 'none';
            form.style.display = 'block';
            
            const textarea = form.querySelector('textarea');
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        function cancelEdit(id) {
            const commentDiv = document.getElementById(`comment-${id}`);
            const content = commentDiv.querySelector('.comment-content');
            const form = commentDiv.querySelector('.edit-form');
            const actions = commentDiv.querySelector('.comment-actions');
            
            content.style.display = 'block';
            actions.style.display = 'block';
            form.style.display = 'none';
        }

        // Handle edit form submissions
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
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                comment_id: parseInt(id),
                                content: content
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const contentElement = commentDiv.querySelector('.comment-content');
                            contentElement.innerHTML = content.replace(/\n/g, '<br>');
                            cancelEdit(id);
                            
                            // Add edit indicator if not present
                            if (!commentDiv.querySelector('.edit-indicator')) {
                                const timeElement = commentDiv.querySelector('small');
                                timeElement.innerHTML += ' <span class="edit-indicator">(edited)</span>';
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

        // Delete comment function
        async function deleteComment(id) {
            showConfirmModal('Are you sure you want to delete this comment?', async () => {
                try {
                    const response = await fetch('../backend/comment.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ comment_id: parseInt(id) })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        document.getElementById(`comment-${id}`).remove();
                        
                        // Show "no comments" message if all comments are deleted
                        const commentsDiv = document.getElementById('comments');
                        const remainingComments = commentsDiv.querySelectorAll('.comment');
                        
                        if (remainingComments.length === 0) {
                            const heading = commentsDiv.querySelector('h3');
                            heading.insertAdjacentHTML('afterend', '<p class="no-comments">No comments yet. Be the first to comment!</p>');
                        }
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