<?php
// Include database connection
require_once 'db_connect.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['contactName'];
    $email = $_POST['contactEmail'];
    $subject = $_POST['contactSubject'];
    $message = $_POST['contactMessage'];
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
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
    
    // Insert message into database
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Message sent successfully!';
        
        // Send email notification (in a real application)
        $to = "info@breatheluxe.com"; // Replace with your email
        $subject = "New Contact Form Submission: $subject";
        $emailBody = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        $headers = "From: $email";
        
        // Uncomment the line below in a real application
        // mail($to, $subject, $emailBody, $headers);
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
