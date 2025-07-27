<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usn = $_POST['usn'] ?? '';
    $pass = $_POST['pass'] ?? '';

    $stmt = $pdo->prepare("SELECT usn, name, pass, privilege FROM user WHERE usn = ?");
    $stmt->execute([$usn]);
    $user = $stmt->fetch();

if ($user && ($user['pass'] === $pass || password_verify($pass, $user['pass']))) {
        // Make sure the privilege level is being set in the session
        $_SESSION['user'] = [
            'usn' => $user['usn'],
            'name' => $user['name'],
            'privilege' => intval($user['privilege'])  // Ensure it's an integer
        ];
        header("Location: ../public/index.php");
    } else {
        header("Location: ../public/login.php?error=1");
    }
    exit;
}
