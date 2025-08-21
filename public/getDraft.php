<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/controllers/ArticleController.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['privilege'] != 1) {
    http_response_code(403);
    exit('Access denied');
}

// Get draft ID
$draftId = $_GET['id'] ?? null;
if (!$draftId || !is_numeric($draftId)) {
    http_response_code(400);
    exit('Invalid draft ID');
}

$articleController = new ArticleController($pdo);

// Get draft data
$draft = $articleController->getDraft($draftId);
if (!$draft) {
    http_response_code(404);
    exit('Draft not found');
}

// Get draft blocks
$blocks = $articleController->getDraftBlocks($draftId);
?>

<div class="draft-viewer">
    <div class="draft-header">
        <h2><?= htmlspecialchars($draft['title']) ?></h2>
        <div class="draft-meta">
            <div class="meta-row">
                <strong>Original Article:</strong> <?= htmlspecialchars($draft['original_title']) ?>
            </div>
            <div class="meta-row">
                <strong>Original Author:</strong> <?= htmlspecialchars($draft['original_author_name']) ?>
            </div>
            <div class="meta-row">
                <strong>Editor:</strong> <?= htmlspecialchars($draft['editor_name']) ?>
            </div>
            <div class="meta-row">
                <strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($draft['created_at'])) ?>
            </div>
            <?php if (!empty($draft['tags'])): ?>
                <div class="meta-row">
                    <strong>Tags:</strong> <?= htmlspecialchars($draft['tags']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="draft-content">
        <h3>Proposed Changes:</h3>
        <?php if (empty($blocks)): ?>
            <p class="no-content">No content blocks found in this draft.</p>
        <?php else: ?>
            <div class="content-blocks">
                <?php foreach ($blocks as $block): ?>
                    <?php if ($block['block_type'] === 'text'): ?>
                        <div class="text-block">
                            <p><?= nl2br(htmlspecialchars($block['content'])) ?></p>
                        </div>
                    <?php elseif ($block['block_type'] === 'image'): ?>
                        <div class="image-block">
                            <img src="<?= htmlspecialchars($block['content']) ?>" 
                                 alt="Draft image" 
                                 style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="draft-actions">
        <form method="POST" action="adminPanel.php" style="display: inline-block; margin-right: 10px;">
            <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
            <input type="hidden" name="action" value="approve_draft">
            <button type="submit" class="action-btn approve-btn" onclick="return confirm('Are you sure you want to approve this edit request?')">
                ✓ Approve Changes
            </button>
        </form>
        
        <form method="POST" action="adminPanel.php" style="display: inline-block;">
            <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
            <input type="hidden" name="action" value="reject_draft">
            <button type="submit" class="action-btn reject-btn" onclick="return confirm('Are you sure you want to reject this edit request?')">
                ✗ Reject Changes
            </button>
        </form>
        
        <button type="button" class="action-btn" onclick="window.open('article.php?id=<?= $draft['original_article_id'] ?>', '_blank')" style="margin-left: 10px;">
            📄 View Original Article
        </button>
    </div>
</div>

<style>
.draft-viewer {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.draft-header {
    border-bottom: 2px solid #ffa500;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.draft-header h2 {
    color: #ffa500;
    margin: 0 0 10px 0;
}

.draft-meta {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #ffa500;
}

.meta-row {
    margin-bottom: 8px;
    font-size: 14px;
}

.meta-row:last-child {
    margin-bottom: 0;
}

.draft-content {
    margin-bottom: 30px;
}

.draft-content h3 {
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.content-blocks {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
}

.text-block {
    margin-bottom: 15px;
    line-height: 1.6;
}

.text-block:last-child {
    margin-bottom: 0;
}

.image-block {
    margin-bottom: 15px;
    text-align: center;
}

.image-block:last-child {
    margin-bottom: 0;
}

.draft-actions {
    border-top: 1px solid #ddd;
    padding-top: 20px;
    text-align: center;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.approve-btn {
    background: #28a745;
    color: white;
}

.approve-btn:hover {
    background: #218838;
}

.reject-btn {
    background: #dc3545;
    color: white;
}

.reject-btn:hover {
    background: #c82333;
}

.action-btn:not(.approve-btn):not(.reject-btn) {
    background: #6c757d;
    color: white;
}

.action-btn:not(.approve-btn):not(.reject-btn):hover {
    background: #5a6268;
}

.no-content {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 20px;
}

/* Large modal styles */
.large-modal {
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}
</style>