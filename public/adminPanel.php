<?php
session_start();
require_once '../backend/db.php';

require_once '../backend/controllers/ArticleController.php';
require_once '../backend/controllers/UserController.php';

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

function displayArticle($article, $isApproved, $articleController) {
    $blocks = $articleController->getArticleBlocks($article['id']);
    $content = $articleController->processArticleContent($blocks);
    ?>
    <div class="article" data-article-id="<?= $article['id'] ?>" style="background-image: url('<?= htmlspecialchars($content['thumbnail']) ?>');">
        <div class="article-content">
            <h2><?= htmlspecialchars($article['title']) ?></h2>
            <small>
                By <?= htmlspecialchars($article['name']) ?> | <?= $article['created_at'] ?> |
                👍 <?= $article['likes'] ?> | 💬 <?= $article['comment_count'] ?>
            </small>
            <p class="preview"><?= htmlspecialchars($content['preview']) ?></p>
            
            <div class="article-actions">
                <?php if ($_SESSION['user']['privilege'] == 1): ?>
                    <!-- Admin-only actions -->
                    <form method="POST" onsubmit="return confirmAction(this, '<?= $isApproved ? 'unapprove' : 'approve' ?>')">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <input type="hidden" name="action" value="<?= $isApproved ? 'unapprove' : 'approve' ?>">
                        <button type="submit" class="action-btn <?= $isApproved ? 'unapprove-btn' : 'approve-btn' ?>">
                            <?= $isApproved ? 'Unapprove' : 'Approve' ?>
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- View button with confirmation -->
                <button onclick="confirmView(<?= $article['id'] ?>)" class="action-btn view-btn">View</button>
                
                <?php if ($_SESSION['user']['privilege'] == 1 || $_SESSION['user']['usn'] == $article['user_id']): ?>
                    <!-- Edit button with confirmation -->
                    <button onclick="confirmEdit(<?= $article['id'] ?>)" class="action-btn edit-btn">Edit</button>
                    
                    <!-- Delete button -->
                    <form method="POST" onsubmit="return confirmAction(this, 'delete')">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="action-btn delete-btn">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
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
    <script>
        // Add these functions first
        function confirmView(articleId) {
            showConfirmModal('View this article?', () => {
                window.location.href = `article.php?id=${articleId}`;
            });
        }

        function confirmEdit(articleId) {
            showConfirmModal('Edit this article?', () => {
                window.location.href = `editArticle.php?id=${articleId}`;
            });
        }

        function showConfirmModal(message, callback) {
            const modal = document.getElementById('confirmModal');
            const messageEl = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            
            messageEl.textContent = message;
            modal.classList.add('show');
            document.querySelector('.modal').classList.add('active');
            
            confirmBtn.onclick = () => {
                closeModal();
                callback();
            };
        }

        function closeModal() {
            const modal = document.getElementById('confirmModal');
            document.querySelector('.modal').classList.remove('active');
            setTimeout(() => {
                modal.classList.remove('show');
            }, 300);
        }

        function searchUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toLowerCase();
            const userList = document.getElementById('userList');
            const forms = userList.getElementsByTagName('form');

            for (let form of forms) {
                const usn = form.querySelector('span').textContent;
                if (usn.toLowerCase().includes(filter)) {
                    form.style.display = "";
                } else {
                    form.style.display = "none";
                }
            }
        }

        function searchArticles(input) {
            const section = input.dataset.section;
            const filter = input.value.toLowerCase();
            const container = document.getElementById(section + '-articles');
            const articles = container.getElementsByClassName('article-wrapper');
            let visibleCount = 0;
            let totalMatches = 0;

            // Count total matches first
            for (let wrapper of articles) {
                const article = wrapper.querySelector('.article');
                const title = article.querySelector('h2').textContent;
                const author = article.querySelector('small').textContent;
                
                if (title.toLowerCase().includes(filter) || 
                    author.toLowerCase().includes(filter)) {
                    totalMatches++;
                }
            }

            // Now handle visibility
            for (let wrapper of articles) {
                const article = wrapper.querySelector('.article');
                const title = article.querySelector('h2').textContent;
                const author = article.querySelector('small').textContent;
                
                if (filter.length > 0) {
                    // When searching, show/hide based on match
                    if (title.toLowerCase().includes(filter) || 
                        author.toLowerCase().includes(filter)) {
                        wrapper.style.display = "";
                        visibleCount++;
                    } else {
                        wrapper.style.display = "none";
                    }
                } else {
                    // When not searching, respect the initial limit
                    wrapper.style.display = visibleCount < 3 ? "" : "none";
                    visibleCount++;
                }
            }

            // Update buttons visibility
            const showMoreBtn = container.parentElement.querySelector('.show-more');
            const showLessBtn = container.parentElement.querySelector('.show-less');
            
            if (showMoreBtn && showLessBtn) {
                if (filter.length > 0) {
                    // Hide both buttons during search
                    showMoreBtn.style.display = "none";
                    showLessBtn.style.display = "none";
                } else {
                    // Show appropriate buttons when not searching
                    showMoreBtn.style.display = visibleCount < totalMatches ? "" : "none";
                    showLessBtn.style.display = visibleCount > 3 ? "" : "none";
                }
            }
        }

        // Show more/less functionality
        document.querySelectorAll('.show-more').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.dataset.section;
                const container = document.getElementById(section + '-articles');
                const hidden = container.querySelectorAll('.article-wrapper[style="display: none;"]');
                
                hidden.forEach(article => {
                    article.style.display = "";
                });

                // Hide show more button and show the show less button
                this.style.display = "none";
                const showLessBtn = container.parentElement.querySelector('.show-less');
                if (showLessBtn) {
                    showLessBtn.style.display = "";
                }
            });
        });

        document.querySelectorAll('.show-less').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.dataset.section;
                const container = document.getElementById(section + '-articles');
                const articles = container.getElementsByClassName('article-wrapper');
                
                // Show only first 3 articles
                Array.from(articles).forEach((article, index) => {
                    article.style.display = index < 3 ? "" : "none";
                });

                // Show show more button and hide show less button
                this.style.display = "none";
                const showMoreBtn = container.parentElement.querySelector('.show-more');
                if (showMoreBtn) {
                    showMoreBtn.style.display = "";
                }
            });
        });

        // Update your form submissions to use the new modal
        document.querySelectorAll('.user-form').forEach(form => {
            form.onsubmit = (e) => {
                e.preventDefault();
                showConfirmModal('Are you sure you want to change this user\'s privilege level?', () => {
                    form.submit();
                });
                return false;
            };
        });

        // Replace the confirmAction function and remove the redundant event handlers
        function confirmAction(form, action) {
            event.preventDefault();
            
            let message = '';
            switch(action) {
                case 'approve':
                    message = 'Do you want to approve this article?';
                    break;
                case 'unapprove':
                    message = 'Do you want to unapprove this article?';
                    break;
                case 'delete':
                    message = 'Do you want to delete this article? This cannot be undone.';
                    break;
                case 'approve_draft':
                    message = 'Do you want to approve this edit request?';
                    break;
                case 'reject_draft':
                    message = 'Do you want to reject this edit request?';
                    break;
                default:
                    message = 'Do you want to proceed with this action?';
            }
            
            showConfirmModal(message, () => {
                form.submit();
            });
            
            return false;
        }

        // Update logout button
        function logout() {
            showConfirmModal('Are you sure you want to logout?', () => {
                fetch('logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        window.location.href = 'login.php';
                    }
                });
            });
        }
    </script>
</body>
</html>
