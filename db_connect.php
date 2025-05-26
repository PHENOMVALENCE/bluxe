<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "breatheluxe";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    echo "Error creating database: " . $conn->error;
}

// Select the database
$conn->select_db($database);

// Create bookings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    package VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    address TEXT NOT NULL,
    special_requests TEXT,
    payment_method VARCHAR(20) NOT NULL,
    mpesa_number VARCHAR(20),
    payment_status VARCHAR(20) NOT NULL,
    booking_reference VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    // Table created successfully or already exists
} else {
    echo "Error creating table: " . $conn->error;
}

// Create contact_messages table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    // Table created successfully or already exists
} else {
    echo "Error creating table: " . $conn->error;
}
?>
