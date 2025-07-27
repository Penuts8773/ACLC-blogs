<?php
require_once '../backend/db.php';
session_start(); // Still needed to access the existing session
$_SESSION = array(); // Clear all session variables
session_destroy();
setcookie(session_name(), '', time() - 3600); // Destroy the session cookie
header("Location: login.php");
exit();
