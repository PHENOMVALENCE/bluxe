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
    'booking_reference' => ''
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $package = $_POST['package'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $address = $_POST['address'];
    $specialRequests = isset($_POST['specialRequests']) ? $_POST['specialRequests'] : '';
    $paymentMethod = $_POST['paymentMethod'];
    $mpesaNumber = ($paymentMethod == 'mpesa' && isset($_POST['mpesaNumber'])) ? $_POST['mpesaNumber'] : '';
    
    // Get package ID
    $sql = "SELECT package_id FROM packages WHERE package_name = ? OR package_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $package, $package);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $package_row = $result->fetch_assoc();
        $package_id = $package_row['package_id'];
    } else {
        $response['message'] = 'Invalid package selected.';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (empty($fullName) || empty($email) || empty($phone) || empty($package) || 
        empty($date) || empty($time) || empty($address) || empty($paymentMethod)) {
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
    
    // Validate M-Pesa number if payment method is M-Pesa
    if ($paymentMethod == 'mpesa' && empty($mpesaNumber)) {
        $response['message'] = 'Please enter your M-Pesa number.';
        echo json_encode($response);
        exit;
    }
    
    // Generate booking reference
    $bookingReference = 'BL' . rand(1000, 9999);
    
    // Set payment status based on payment method
    $paymentStatus = ($paymentMethod == 'mpesa') ? 'pending' : 'awaiting_payment';
    
    // Process M-Pesa payment if selected
    $mpesa_transaction_id = null;
    if ($paymentMethod == 'mpesa') {
        // In a real application, this would call the M-Pesa API
        // For this example, we'll simulate a successful payment
        require_once 'mpesa_api.php';
        
        // Get package price
        $sql = "SELECT price FROM packages WHERE package_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $package_price = $result->fetch_assoc()['price'];
        
        $mpesaResponse = processMpesaPayment($mpesaNumber, $package_price);
        
        if ($mpesaResponse['success']) {
            $paymentStatus = 'paid';
            $mpesa_transaction_id = $mpesaResponse['transaction_id'];
        } else {
            $response['message'] = 'M-Pesa payment failed: ' . $mpesaResponse['message'];
            echo json_encode($response);
            exit;
        }
    }
    
    // Check if user is logged in
    $user_id = null;
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $user_id = $_SESSION['user_id'];
    } else {
        // Check if email exists in users table
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_id = $result->fetch_assoc()['user_id'];
        }
    }
    
    // Insert booking into database
    $sql = "INSERT INTO bookings (user_id, full_name, email, phone, package_id, event_date, event_time, address, 
            special_requests, payment_method, mpesa_number, mpesa_transaction_id, payment_status, booking_reference) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssssss", $user_id, $fullName, $email, $phone, $package_id, $date, $time, $address, 
                      $specialRequests, $paymentMethod, $mpesaNumber, $mpesa_transaction_id, $paymentStatus, $bookingReference);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // If user is logged in, add to customer_bookings
        if ($user_id !== null) {
            $sql = "INSERT INTO customer_bookings (user_id, booking_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $booking_id);
            $stmt->execute();
        }
        
        $response['success'] = true;
        $response['message'] = 'Booking successful!';
        $response['booking_reference'] = $bookingReference;
        
        // Log activity if user is logged in
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            logActivity($user_id, 'create_booking', 'Created booking #' . $bookingReference);
        }
        
        // Send confirmation email (in a real application)
        // sendConfirmationEmail($email, $fullName, $bookingReference, $package, $date, $time);
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
