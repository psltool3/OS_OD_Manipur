<?php
session_start();

// Check if CSRF token exists in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
}

echo $_SESSION['csrf_token']; // Output token
?>
