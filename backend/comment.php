<?php
require_once 'db.php';
require_once 'article.php';

header('Content-Type: application/json');

// Handle GET requests (fetch all comments) without authentication
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handleGetAllComments($pdo);
    exit;
}

// Ensure user is authenticated for POST, PUT, DELETE
if (!isset($_SESSION['user']) || $_SESSION['user']['privilege'] == 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$data = null;

if (in_array($method, ['PUT', 'DELETE'])) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
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
    
    // Add ownership and permission flags
    $formattedComments = array_map(function($comment) use ($currentUser) {
        $isOwner = $currentUser && $currentUser['usn'] == $comment['user_id'];
        $isAdminOrMod = $currentUser && in_array($currentUser['privilege'], [1, 3]);
        
        return [
            'id' => $comment['id'],
            'name' => $comment['name'],
            'content' => $comment['content'],
            'created_at' => $comment['created_at'],
            'modified_at' => $comment['modified_at'],
            'user_id' => $comment['user_id'],
            'user_privilege' => $comment['user_privilege'],
            'is_owner' => $isOwner,
            'can_restrict' => $isAdminOrMod
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

try {
    $userId = $_SESSION['user']['usn'];
    
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
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle comment creation
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