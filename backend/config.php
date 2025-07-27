<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog');
define('DB_USER', 'blog');
define('DB_PASS', 'blog');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
