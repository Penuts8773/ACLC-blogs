<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
        <a href="postApprove.php">Post Approval</a>
        <a href="articleReq.php">Article Request</a>
        <a href="postApprovalPage.php">Post Approval</a>
        <a href="postApprovalPage.php">Post Approval</a>
</body>
</html>