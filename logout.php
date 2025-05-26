<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    // Update session record
    $sql = "UPDATE user_sessions SET is_valid = 0 WHERE session_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
    
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        // Clear token in database
        $sql = "UPDATE users SET remember_token = NULL, remember_token_expiry = NULL WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to home page
header('Location: index.html');
exit;
?>
