<?php
  
session_start();

require_once __DIR__ . '/conn.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT usn, pass FROM user WHERE usn = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $db_password);
        $stmt->fetch();

        if ($password === $db_password) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user'] = $username;
            $stmt->close();
            header('Location: /index.php?page=home');
            exit();
        } else {
            $stmt->close();
            header('Location: ../index.php?error=1');
            exit();
        }
    } else {
        $stmt->close();
        header('Location: ../index.php?error=1');
        exit();
    }
}
?>