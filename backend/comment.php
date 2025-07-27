<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get request method and body data
$method = $_SERVER['REQUEST_METHOD'];
$data = null;
if ($method === 'PUT' || $method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
}

try {
    switch ($method) {
        case 'DELETE':
            if (!isset($data['comment_id'])) {
                throw new Exception('Comment ID is required');
            }
            
            // Verify comment ownership
            $stmt = $pdo->prepare("SELECT 1 FROM article_comments WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['comment_id'], $_SESSION['user']['usn']]);
            if (!$stmt->fetch()) {
                throw new Exception('Unauthorized to delete this comment');
            }
            
            // Delete comment
            $stmt = $pdo->prepare("DELETE FROM article_comments WHERE id = ?");
            $stmt->execute([$data['comment_id']]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'PUT':
            if (!isset($data['comment_id'], $data['content'])) {
                throw new Exception('Comment ID and content are required');
            }
            
            // Verify comment ownership
            $stmt = $pdo->prepare("SELECT 1 FROM article_comments WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['comment_id'], $_SESSION['user']['usn']]);
            if (!$stmt->fetch()) {
                throw new Exception('Unauthorized to edit this comment');
            }
            
            // Update comment
            $stmt = $pdo->prepare("UPDATE article_comments SET content = ? WHERE id = ?");
            $stmt->execute([trim($data['content']), $data['comment_id']]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'POST':
            if (!isset($_POST['article_id'], $_POST['comment'])) {
                throw new Exception('Article ID and comment are required');
            }
            
            $stmt = $pdo->prepare("INSERT INTO article_comments (user_id, article_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user']['usn'], $_POST['article_id'], trim($_POST['comment'])]);
            
            echo json_encode([
                'success' => true,
                'name' => $_SESSION['user']['name'],
                'content' => htmlspecialchars(trim($_POST['comment']))
            ]);
            break;
            
        default:
            throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
