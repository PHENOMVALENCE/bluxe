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
    // Get form data
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $terms_agree = isset($_POST['terms_agree']) ? true : false;
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }
    
    // Check if terms are agreed
    if (!$terms_agree) {
        $response['message'] = 'You must agree to the Terms and Conditions.';
        echo json_encode($response);
        exit;
    }
    
    // Check if email already exists
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['message'] = 'Email address is already registered. Please login or use a different email.';
        echo json_encode($response);
        exit;
    }
    
    // Generate username from email
    $username = explode('@', $email)[0] . rand(100, 999);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Create full name
    $full_name = $first_name . ' ' . $last_name;
    
    // Insert user into database
    $sql = "INSERT INTO users (username, password, email, phone, full_name, address, role, user_type, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'customer', 'customer', 1, NOW())";
    
    $stmt = $conn->prepare($sql);
    $role = 'customer';
    $stmt->bind_param("ssssss", $username, $hashed_password, $email, $phone, $full_name, $address);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role'] = 'customer';
        $_SESSION['user_type'] = 'customer';
        $_SESSION['logged_in'] = true;
        
        // Create session record
        $session_id = session_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $sql = "INSERT INTO user_sessions (session_id, user_id, login_time, last_activity, ip_address, user_agent) 
                VALUES (?, ?, NOW(), NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siss", $session_id, $user_id, $ip_address, $user_agent);
        $stmt->execute();
        
        // Send welcome email (in a real application)
        // sendWelcomeEmail($email, $full_name);
        
        $response['success'] = true;
        $response['message'] = 'Registration successful! You are now logged in.';
        $response['redirect'] = 'customer_dashboard.php';
    } else {
        $response['message'] = 'Error: ' . $stmt->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
