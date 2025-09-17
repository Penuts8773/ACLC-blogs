<?php
require_once '../backend/db.php';
require_once '../backend/controllers/UserController.php';
session_start();

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['privilege'], [1, 3])) {
    header("Location: articleBoard.php");
    exit;
}

$userId = $_POST['user_id'] ?? null;
if ($userId && is_numeric($userId)) {
    $userController = new UserController($pdo);
    // Prevent banning admins/teachers
    $stmt = $pdo->prepare("SELECT privilege FROM user WHERE usn = ?");
    $stmt->execute([$userId]);
    $targetPrivilege = $stmt->fetchColumn();
    if (!in_array($targetPrivilege, [1, 2])) {
        $userController->updateUserPrivilege($userId, 5); // Demote to banned
    }
}
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;