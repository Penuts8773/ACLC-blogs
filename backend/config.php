<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog');
define('DB_USER', 'root');
define('DB_PASS', 'root');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
