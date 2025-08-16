<?php
session_start();
require_once '../backend/db.php';

require_once '../backend/controllers/ArticleController.php';

require_once '../backend/controllers/UserController.php';
require_once 'components/admin.php';


$articleController = new ArticleController($pdo);
$userController = new UserController($pdo);

if (!isset($_SESSION['user']) || !$userController->isAdmin($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    $message = '';

    try {
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }
        switch($_POST['action']) {
            case 'approve':
                $success = $articleController->approveArticle($_POST['article_id'], true);
                $message = $success ? "Article approved successfully" : "Failed to approve article";
                break;
            case 'unapprove':
                $success = $articleController->approveArticle($_POST['article_id'], false);
                $message = $success ? "Article unapproved successfully" : "Failed to unapprove article";
                break;
            case 'delete':
                $success = $articleController->deleteArticle($_POST['article_id']);
                $message = $success ? "Article deleted successfully" : "Failed to delete article";
                break;
            case 'approve_draft':
                $success = $articleController->approveDraft($_POST['draft_id']);
                $message = $success ? "Edit request approved successfully" : "Failed to approve edit";
                break;
            case 'reject_draft':
                $success = $articleController->deleteDraft($_POST['draft_id']);
                $message = $success ? "Edit request rejected successfully" : "Failed to reject edit";
                break;
            case 'update_privilege':
                if (isset($_POST['target_usn'], $_POST['new_level'])) {
                    $success = $userController->updateUserPrivilege($_POST['target_usn'], $_POST['new_level']);
                    $message = $success ? "User privilege updated successfully" : "Failed to update user privilege";
                } else {
                    $message = "Missing user or privilege level.";
                }
                break;
            default:
                $message = "Invalid action";
        }
        
        if ($success) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = $message;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred while processing your request";
        error_log("Error in draft action: " . $e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$unapproved = $articleController->getUnapprovedArticles();
$approved = $articleController->getApprovedArticles();
$users = $userController->getAllUsers();
$pendingDrafts = $articleController->getPendingDrafts();

$articlesPerPage = [
    'pending' => 4,   // Show 4 articles for pending section
    'approved' => 3   // Keep 3 articles for approved section
];
$searchQuery = $_GET['search'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/style/index.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="admin-container">
        <!-- Pending Articles Section -->
        <div class="admin-section">
            <h3>Pending Article Approvals</h3>
            <input type="text" class="article-search" placeholder="Search pending articles..." 
                   data-section="pending" onkeyup="searchArticles(this)">
            <div class="articles-container" id="pending-articles">
                <?php 
                $count = 0;
                foreach ($unapproved as $article): 
                    $hidden = $count >= $articlesPerPage['pending'] ? 'style="display: none;"' : '';
                    echo "<div class='article-wrapper' $hidden>";
                    displayArticle($article, false, $articleController);
                    echo "</div>";
                    $count++;
                endforeach; 
                ?>
            </div>
            <?php if (count($unapproved) > $articlesPerPage['pending']): ?>
                <button class="show-more" data-section="pending">Show More</button>
                <button class="show-less" data-section="pending" style="display: none;">Show Less</button>
            <?php endif; ?>
        </div>

        <!-- User Management Section -->
        <div class="admin-section">
            <h3>User Privilege Management</h3>
            <input type="text" id="userSearch" class="user-search" placeholder="Search users..." onkeyup="searchUsers()">
            <div class="user-management" id="userList">
                <?php foreach ($users as $u): ?>
                    <form method="POST" class="user-form">
                        <span><?= htmlspecialchars($u['usn']) ?> - <?= htmlspecialchars($userController->getPrivilegeName($u['privilege'])) ?></span>
                        <div>
                            <select name="new_level">
                                <option value="1" <?= $u['privilege'] == 1 ? 'selected' : '' ?>>Admin</option>
                                <option value="2" <?= $u['privilege'] == 2 ? 'selected' : '' ?>>Teacher</option>
                                <option value="3" <?= $u['privilege'] == 3 ? 'selected' : '' ?>>Student</option>
                            </select>
                            <input type="hidden" name="target_usn" value="<?= $u['usn'] ?>">
                            <input type="hidden" name="action" value="update_privilege">
                            <button type="submit">Update</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Approved Articles Section -->
        <div class="admin-section half-width">
            <h3>Approved Articles</h3>
            <input type="text" class="article-search" placeholder="Search approved articles..." 
                   data-section="approved" onkeyup="searchArticles(this)">
            <div class="articles-container" id="approved-articles">
                <?php 
                $count = 0;
                foreach ($approved as $article): 
                    $hidden = $count >= 3 ? 'style="display: none;"' : '';
                    echo "<div class='article-wrapper' $hidden>";
                    displayArticle($article, true, $articleController);
                    echo "</div>";
                    $count++;
                endforeach; 
                ?>
            </div>
            <?php if (count($approved) > 3): ?>
                <button class="show-more" data-section="approved">Show More</button>
                <button class="show-less" data-section="approved" style="display: none;">Show Less</button>
            <?php endif; ?>
        </div>

        <!-- Pending Edit Requests Section -->
        <?php if ($_SESSION['user']['privilege'] == 1): ?>
            <div class="admin-section half-width">
                <h3>Pending Edit Requests</h3>
                <?php if (empty($pendingDrafts)): ?>
                    <p class="no-content">No pending edit requests</p>
                <?php else: ?>
                    <?php foreach ($pendingDrafts as $draft): ?>
                        <div class="draft-request">
                            <div class="draft-info">
                                <h3><?= htmlspecialchars($draft['title']) ?></h3>
                                <p>Original title: <?= htmlspecialchars($draft['original_title']) ?></p>
                                <p>Editor: <?= htmlspecialchars($draft['editor_name']) ?></p>
                                <p>Submitted: <?= date('M j, Y g:i A', strtotime($draft['created_at'])) ?></p>
                            </div>
                            <div class="draft-actions">
                                <form method="POST" onsubmit="return confirmAction(this, 'approve_draft')">
                                    <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                                    <input type="hidden" name="action" value="approve_draft">
                                    <button type="submit" class="action-btn approve-btn">
                                        Approve Edit
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirmAction(this, 'reject_draft')">
                                    <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                                    <input type="hidden" name="action" value="reject_draft">
                                    <button type="submit" class="action-btn reject-btn">
                                        Reject Edit
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<?php include 'components/modal.php'; ?>
<script src="components/adminComponents.js" type="text/javascript"></script>
</body>
</html>
