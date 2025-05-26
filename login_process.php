<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';
require_once 'functions.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'redirect' => ''
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get login type
    $login_type = $_POST['login_type'];
    
    // Process based on login type
    if ($login_type == 'customer') {
        // Customer login
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']) ? true : false;
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address.';
            echo json_encode($response);
            exit;
        }
        
        // Check if email exists
        $sql = "SELECT user_id, username, email, password, full_name, role, user_type, is_active FROM users WHERE email = ? AND user_type = 'customer'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['is_active'] == 1) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'] ?? $user['email'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['logged_in'] = true;
                    
                    // Create session record
                    $session_id = session_id();
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    
                    $sql = "INSERT INTO user_sessions (session_id, user_id, login_time, last_activity, ip_address, user_agent) 
                            VALUES (?, ?, NOW(), NOW(), ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("siss", $session_id, $user['user_id'], $ip_address, $user_agent);
                    $stmt->execute();
                    
                    // Set remember me cookie if requested
                    if ($remember_me) {
                        $token = generateToken();
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // Store token in database
                        $sql = "UPDATE users SET remember_token = ?, remember_token_expiry = FROM_UNIXTIME(?) WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sii", $token, $expiry, $user['user_id']);
                        $stmt->execute();
                        
                        // Set cookie
                        setcookie('remember_token', $token, $expiry, '/', '', false, true);
                        setcookie('user_id', $user['user_id'], $expiry, '/', '', false, true);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirect'] = 'customer_dashboard.php';
                } else {
                    $response['message'] = 'Your account is inactive. Please contact support.';
                }
            } else {
                $response['message'] = 'Invalid email or password.';
            }
        } else {
            $response['message'] = 'Invalid email or password.';
        }
    } else if ($login_type == 'admin') {
        // Admin login
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
        
        // Check if username exists
        $sql = "SELECT user_id, username, email, password, full_name, role, user_type, is_active FROM users 
                WHERE username = ? AND (user_type = 'admin' OR user_type = 'staff')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['is_active'] == 1) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['is_admin'] = true;
                    
                    // Create session record
                    $session_id = session_id();
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    
                    $sql = "INSERT INTO user_sessions (session_id, user_id, login_time, last_activity, ip_address, user_agent) 
                            VALUES (?, ?, NOW(), NOW(), ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("siss", $session_id, $user['user_id'], $ip_address, $user_agent);
                    $stmt->execute();
                    
                    // Update last login time
                    $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user['user_id']);
                    $stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirect'] = 'admin/dashboard.php';
                } else {
                    $response['message'] = 'Your account is inactive. Please contact the system administrator.';
                }
            } else {
                $response['message'] = 'Invalid username or password.';
            }
        } else {
            $response['message'] = 'Invalid username or password.';
        }
    } else {
        $response['message'] = 'Invalid login type.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
