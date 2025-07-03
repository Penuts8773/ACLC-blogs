<?php
  
// Start session
session_start();

// Include database connection
require_once __DIR__ . '/conn.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prepare and execute query
    $stmt = $conn->prepare('SELECT pass FROM user WHERE usn = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($db_password);
        $stmt->fetch();
        // Compare plain text password (not recommended for production)
        if ($password === $db_password) {
            $_SESSION['username'] = $username;
            header('Location: ../home.php');
            exit();
        } else {
            header('Location: ../index.php?error=1');
            exit();
        }
    } else {
        header('Location: ../index.php?error=1');
        exit();
    }
    $stmt->close();
}
?>