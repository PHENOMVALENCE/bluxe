<?php
/**
 * Helper functions for Breathe Luxe application
 */

/**
 * Generate a random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if user has permission
 * 
 * @param string $permission Permission to check
 * @param string $role User role
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permission, $role) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role = ? AND p.permission_name = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $role, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && 
           ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'staff');
}

/**
 * Redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.html');
        exit;
    }
}

/**
 * Redirect to home page if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.html');
        exit;
    }
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @return array|null User data or null if not found
 */
function getUserById($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Format date
 * 
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 * 
 * @param float $amount Amount
 * @param string $currency Currency symbol
 * @return string Formatted currency
 */
function formatCurrency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

/**
 * Get booking status label
 * 
 * @param string $status Status
 * @return string Status label with HTML
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return '<span class="status-badge pending">Pending</span>';
        case 'paid':
            return '<span class="status-badge success">Paid</span>';
        case 'awaiting_payment':
            return '<span class="status-badge warning">Awaiting Payment</span>';
        case 'cancelled':
            return '<span class="status-badge danger">Cancelled</span>';
        default:
            return '<span class="status-badge">' . ucfirst($status) . '</span>';
    }
}

/**
 * Log activity
 * 
 * @param int $user_id User ID
 * @param string $action Action
 * @param string $details Details
 */
function logActivity($user_id, $action, $details = '') {
    global $conn;
    
    $sql = "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
}

/**
 * Sanitize output
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
