<?php
/**
 * Authentication functions for Breathe Luxe
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!function_exists('getUserById')) {
    require_once 'functions.php';
}

/**
 * Check if user is authenticated via remember me cookie
 */
function checkRememberMe() {
    global $conn;
    
    if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = $_COOKIE['user_id'];
        
        $sql = "SELECT * FROM users WHERE user_id = ? AND remember_token = ? AND remember_token_expiry > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['logged_in'] = true;
            
            if ($user['user_type'] === 'admin' || $user['user_type'] === 'staff') {
                $_SESSION['is_admin'] = true;
            }
            
            // Create session record
            $session_id = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            $sql = "INSERT INTO user_sessions (session_id, user_id, login_time, last_activity, ip_address, user_agent) 
                    VALUES (?, ?, NOW(), NOW(), ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siss", $session_id, $user['user_id'], $ip_address, $user_agent);
            $stmt->execute();
            
            // Renew token
            $new_token = generateToken();
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            $sql = "UPDATE users SET remember_token = ?, remember_token_expiry = FROM_UNIXTIME(?) WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $new_token, $expiry, $user['user_id']);
            $stmt->execute();
            
            // Set new cookie
            setcookie('remember_token', $new_token, $expiry, '/', '', false, true);
            
            return true;
        } else {
            // Invalid or expired token, clear cookies
            setcookie('remember_token', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
        }
    }
    
    return false;
}

/**
 * Update user's last activity
 */
function updateLastActivity() {
    global $conn;
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
        $user_id = $_SESSION['user_id'];
        $session_id = session_id();
        
        $sql = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
    }
}

/**
 * Check if session is valid
 */
function isSessionValid() {
    global $conn;
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
        $user_id = $_SESSION['user_id'];
        $session_id = session_id();
        
        $sql = "SELECT COUNT(*) as count FROM user_sessions WHERE session_id = ? AND user_id = ? AND is_valid = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    return false;
}

/**
 * Authenticate user
 * 
 * @param string $email_or_username Email or username
 * @param string $password Password
 * @param string $user_type User type (customer, admin, staff)
 * @return array Authentication result
 */
function authenticateUser($email_or_username, $password, $user_type = 'customer') {
    global $conn;
    
    $result = [
        'success' => false,
        'message' => '',
        'user' => null
    ];
    
    // Check if input is email or username
    $is_email = filter_var($email_or_username, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        // Login with email
        $sql = "SELECT * FROM users WHERE email = ?";
        if ($user_type !== 'all') {
            $sql .= " AND user_type = ?";
        }
    } else {
        // Login with username
        $sql = "SELECT * FROM users WHERE username = ?";
        if ($user_type !== 'all') {
            $sql .= " AND user_type = ?";
        }
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($user_type !== 'all') {
        $stmt->bind_param("ss", $email_or_username, $user_type);
    } else {
        $stmt->bind_param("s", $email_or_username);
    }
    
    $stmt->execute();
    $db_result = $stmt->get_result();
    
    if ($db_result->num_rows === 1) {
        $user = $db_result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if account is active
            if ($user['is_active'] == 1) {
                $result['success'] = true;
                $result['user'] = $user;
            } else {
                $result['message'] = 'Your account is inactive. Please contact support.';
            }
        } else {
            $result['message'] = 'Invalid credentials.';
        }
    } else {
        $result['message'] = 'Invalid credentials.';
    }
    
    return $result;
}

// Check remember me on page load
checkRememberMe();

// Update last activity
updateLastActivity();

// Check if session is valid
if (isset($_SESSION['logged_in']) && !isSessionValid()) {
    // Session is invalid, log out user
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: login.html');
    exit;
}
?>
