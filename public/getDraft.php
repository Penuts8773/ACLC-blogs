<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/controllers/ArticleController.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['privilege'] != 1) {
    header("Location: login.php");
    exit;
}

// Get draft ID
$draftId = $_GET['id'] ?? null;
if (!$draftId || !is_numeric($draftId)) {
    header("Location: adminPanel.php");
    exit;
}

$articleController = new ArticleController($pdo);

// Get draft data
$draft = $articleController->getDraft($draftId);
if (!$draft) {
    header("Location: adminPanel.php");
    exit;
}

// Get draft blocks
$blocks = $articleController->getDraftBlocks($draftId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft: <?= htmlspecialchars($draft['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/aclcEmblem.ico">
    <link rel="stylesheet" href="assets/style/index.css">
    <link rel="stylesheet" href="assets/style/article.css">
    <link rel="stylesheet" href="assets/style/articleForm.css">
    <link rel="stylesheet" href="assets/style/draft.css">

    <style>
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .draft-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ffa500;
        }
        .draft-status {
            background: linear-gradient(135deg, #ffa500, #ff8c00);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .draft-actions-top {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .content-section {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            margin: 1.5rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #ffa500;
        }
        .section-header {
            color: #ff8c00;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .highlight-box {
            background: linear-gradient(145deg, #fff9e6, #ffffff);
            border: 2px solid #ffa500;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="draft-page">
            <div class="draft-header">
                <div>
                    <a href="adminPanel.php" class="action-btn back-btn">← Back to Admin Panel</a>
                </div>
                <div class="draft-status">📝 EDIT REQUEST</div>
            </div>
            
            <h1 class="draft-title"><?= htmlspecialchars($draft['title']) ?></h1>
            
            <div class="content-section">
                <h3 class="section-header">📋 Request Details</h3>
                <div class="highlight-box">
                    <div class="draft-meta">
                        <div class="meta-row">
                            <strong>Original Article:</strong> 
                            <span><?= htmlspecialchars($draft['original_title']) ?></span>
                        </div>
                        <div class="meta-row">
                            <strong>Original Author:</strong> 
                            <span><?= htmlspecialchars($draft['original_author_name']) ?></span>
                        </div>
                        <div class="meta-row">
                            <strong>Editor:</strong> 
                            <span><?= htmlspecialchars($draft['editor_name']) ?></span>
                        </div>
                        <div class="meta-row">
                            <strong>Submitted:</strong> 
                            <span><?= date('F j, Y g:i A', strtotime($draft['created_at'])) ?></span>
                        </div>
                        <?php if (!empty($draft['tags'])): ?>
                            <div class="meta-row">
                                <strong>Tags:</strong> 
                                <span><?= htmlspecialchars($draft['tags']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h3 class="section-header">✏️ Proposed Changes</h3>
                <?php if (empty($blocks)): ?>
                    <div class="content-blocks">
                        <div class="highlight-box">
                            <p class="no-content">⚠️ No content blocks found in this draft.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-blocks">
                        <?php foreach ($blocks as $index => $block): ?>
                            <div class="highlight-box" style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span style="font-weight: bold; color: #ff8c00;">
                                        Block <?= $index + 1 ?>: <?= ucfirst($block['block_type']) ?>
                                    </span>
                                </div>
                                <?php if ($block['block_type'] === 'text'): ?>
                                    <div class="text-block">
                                        <p style="line-height: 1.8; text-indent: 2rem; font-size: 1.1rem;">
                                            <?= nl2br(htmlspecialchars($block['content'])) ?>
                                        </p>
                                    </div>
                                <?php elseif ($block['block_type'] === 'image'): ?>
                                    <div class="image-block">
                                        <img src="<?= htmlspecialchars($block['content']) ?>" 
                                             alt="Draft image" 
                                             style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="draft-actions">
                <div style="display: flex; justify-content: center; gap: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 12px; border: 2px dashed #ddd;">
                    
                    <a href="article.php?id=<?= $draft['original_article_id'] ?>" target="_blank" class="action-btn view-original-btn">
                        📄 View Original Article
                    </a>
                    
                    <form method="POST" action="adminPanel.php" style="display: inline-block;">
                        <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                        <input type="hidden" name="action" value="approve_draft">
                        <button type="submit" class="action-btn approve-btn" 
                                onclick="return confirm('Are you sure you want to approve this edit request? The original article will be updated with these changes.')">
                            ✅ Approve Changes
                        </button>
                    </form>
                    
                    <form method="POST" action="adminPanel.php" style="display: inline-block;">
                        <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                        <input type="hidden" name="action" value="reject_draft">
                        <button type="submit" class="action-btn delete-btn" 
                                onclick="return confirm('Are you sure you want to reject this edit request? This action cannot be undone.')">
                            ❌ Reject Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const contentSections = document.querySelectorAll('.content-section');
            contentSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Highlight blocks on hover
            const highlightBoxes = document.querySelectorAll('.highlight-box');
            highlightBoxes.forEach(box => {
                box.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 15px rgba(255, 165, 0, 0.2)';
                    this.style.transition = 'all 0.3s ease';
                });
                
                box.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>