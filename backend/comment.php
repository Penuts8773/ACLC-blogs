<?php
session_start();
require_once 'db.php';
require_once 'article.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET still unauthenticated
if ($method === 'GET') {
    handleGetAllComments($pdo);
    exit;
}

// Ensure user is authenticated for modifying actions
if (!isset($_SESSION['user']) || $_SESSION['user']['privilege'] == 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = null;
// parse JSON body for methods that send JSON (PUT, DELETE, PATCH)
if (in_array($method, ['PUT', 'DELETE', 'PATCH'])) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
}

try {
    $userId = $_SESSION['user']['usn'] ?? null;

    switch ($method) {
        case 'POST':
            handleCreateComment($pdo, $userId);
            break;
        case 'PUT':
            handleUpdateComment($pdo, $data, $userId);
            break;
        case 'DELETE':
            handleDeleteComment($pdo, $data, $userId);
            break;
        case 'PATCH':
            handleCommentVisibility($pdo, $data, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

/**
 * Handle getting all comments for an article
 */
function handleGetAllComments($pdo) {
    if (!isset($_GET['article_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Article ID is required']);
        return;
    }
    
    $articleId = (int)$_GET['article_id'];
    $currentUser = $_SESSION['user'] ?? null;
    
    // Get all comments without limit
    $comments = getArticleComments($pdo, $articleId);
    
    // Add ownership and permission flags and mask content for unauthorized viewers
    $formattedComments = array_map(function($comment) use ($currentUser) {
        $isOwner = $currentUser && isset($currentUser['usn']) && $currentUser['usn'] == $comment['user_id'];
        $isAdminOrMod = $currentUser && isset($currentUser['privilege']) && in_array($currentUser['privilege'], [1, 3]);
        
        // safe hidden flag (in case column not present yet)
        $hiddenFlag = !empty($comment['hidden']);
        
        // mask content for everyone except owner and admin/mod
        if ($hiddenFlag && !($isOwner || $isAdminOrMod)) {
            $content = '[This comment has been hidden by a moderator]';
        } else {
            $content = $comment['content'];
        }
        
        return [
            'id' => (int)$comment['id'],
            'name' => $comment['name'],
            'content' => $content,
            'created_at' => $comment['created_at'],
            'modified_at' => $comment['modified_at'] ?? null,
            'user_id' => $comment['user_id'],
            'user_privilege' => $comment['user_privilege'] ?? null,
            'is_owner' => (bool)$isOwner,
            'can_restrict' => (bool)$isAdminOrMod,
            'hidden' => (bool)$hiddenFlag
        ];
    }, $comments);
    
    echo json_encode([
        'success' => true,
        'comments' => $formattedComments
    ]);
}

/**
 * Verify comment ownership
 */
function verifyCommentOwnership($pdo, $commentId, $userId) {
    $stmt = $pdo->prepare("SELECT 1 FROM article_comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$commentId, $userId]);
    return $stmt->fetch() !== false;
}

/**
 * Handle comment creation with rate limiting
 */
function handleCreateComment($pdo, $userId) {
    if (!isset($_POST['article_id'], $_POST['comment'])) {
        throw new Exception('Article ID and comment content are required');
    }
    
    $articleId = (int)$_POST['article_id'];
    $content = trim($_POST['comment']);
    
    if (empty($content)) {
        throw new Exception('Comment cannot be empty');
    }

    // Rate limiting logic
    $commentLimit = 3;
    $timeoutDuration = 300; // 5 minutes in seconds

    // Check the user's comment history
    $stmt = $pdo->prepare("SELECT COUNT(*) as comment_count, MAX(created_at) as last_comment_time FROM article_comments WHERE user_id = ? AND created_at > NOW() - INTERVAL 5 MINUTE");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['comment_count'] >= $commentLimit) {
        $lastCommentTime = strtotime($result['last_comment_time']);
        $currentTime = time();

        if (($currentTime - $lastCommentTime) < $timeoutDuration) {
            // User is rate limited
            echo json_encode(['success' => false, 'error' => 'You have reached the comment limit. Please wait before commenting again.']);
            exit;
        }
    }

    // Proceed with comment insertion
    $stmt = $pdo->prepare("
        INSERT INTO article_comments (user_id, article_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $articleId, $content]);
    
    echo json_encode([
        'success' => true,
        'name' => $_SESSION['user']['name'],
        'content' => htmlspecialchars($content)
    ]);
}

/**
 * Handle comment update
 */
function handleUpdateComment($pdo, $data, $userId) {
    if (!isset($data['comment_id'], $data['content'])) {
        throw new Exception('Comment ID and content are required');
    }
    
    $commentId = (int)$data['comment_id'];
    $content = trim($data['content']);
    
    if (empty($content)) {
        throw new Exception('Comment cannot be empty');
    }
    
    if (!verifyCommentOwnership($pdo, $commentId, $userId)) {
        throw new Exception('Unauthorized to edit this comment');
    }
    
    $stmt = $pdo->prepare("
        UPDATE article_comments 
        SET content = ?, modified_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$content, $commentId]);
    
    echo json_encode(['success' => true]);
}

/**
 * Handle comment deletion
 */
function handleDeleteComment($pdo, $data, $userId) {
    if (!isset($data['comment_id'])) {
        throw new Exception('Comment ID is required');
    }
    
    $commentId = (int)$data['comment_id'];
    
    if (!verifyCommentOwnership($pdo, $commentId, $userId)) {
        throw new Exception('Unauthorized to delete this comment');
    }
    
    $stmt = $pdo->prepare("DELETE FROM article_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    echo json_encode(['success' => true]);
}

/**
 * Handle visibility toggle (hide/unhide) — only admins/mods allowed
 */
function handleCommentVisibility($pdo, $data, $userId) {
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['privilege'], [1, 3])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if (empty($data['comment_id']) || !isset($data['hidden'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        exit;
    }

    $commentId = (int)$data['comment_id'];
    // accept 1/0 or true/false
    $hidden = (int) $data['hidden'];

    $stmt = $pdo->prepare("UPDATE article_comments SET hidden = ? WHERE id = ?");
    if (!$stmt->execute([$hidden, $commentId])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update comment visibility']);
        exit;
    }

    echo json_encode(['success' => true, 'hidden' => (bool)$hidden]);
    exit;
}

/**
 * Example: make sure handleGetAllComments includes permission flags (can_restrict) and user_privilege & hidden
 * If your real handleGetAllComments already exists, ensure it includes similar mapping:
 *
 * 'user_privilege' => $comment['user_privilege'],
 * 'is_owner' => (bool)$isOwner,
 * 'can_restrict' => (bool)$isAdminOrMod,
 * 'hidden' => (bool)$hiddenFlag
 *
 * and that getArticleComments SELECTs c.hidden and u.privilege AS user_privilege.
 */