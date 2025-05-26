<?php
// Start session
session_start();

// Include required files
require_once 'db_connect.php';
require_once 'functions.php';

// Check if user is logged in
requireLogin();

// Check if user is customer
if ($_SESSION['user_type'] !== 'customer') {
    header('Location: index.html');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get user bookings
$sql = "SELECT b.*, p.package_name, p.price 
        FROM bookings b 
        JOIN packages p ON b.package_id = p.package_id 
        WHERE b.user_id = ? 
        ORDER BY b.event_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];

while ($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Breathe Luxe</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-body">
    <header class="dashboard-header">
        <div class="logo">
            <h1>Breathe Luxe</h1>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <span>Welcome, <?php echo sanitizeOutput($user['full_name']); ?></span>
            </div>
            <div class="dropdown">
                <button class="dropdown-toggle">
                    <span class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </span>
                    <span class="dropdown-icon">▼</span>
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php">My Profile</a>
                    <a href="change_password.php">Change Password</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="dashboard-sidebar">
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="customer_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="my_bookings.php"><span class="icon">📅</span> My Bookings</a></li>
                    <li><a href="new_booking.php"><span class="icon">➕</span> New Booking</a></li>
                    <li><a href="profile.php"><span class="icon">👤</span> My Profile</a></li>
                    <li><a href="index.html"><span class="icon">🏠</span> Back to Website</a></li>
                </ul>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="dashboard-header-bar">
                <h2>Customer Dashboard</h2>
                <a href="new_booking.php" class="btn">New Booking</a>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3>Total Bookings</h3>
                        <p class="stat-value"><?php echo count($bookings); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📆</div>
                    <div class="stat-content">
                        <h3>Upcoming Bookings</h3>
                        <?php
                        $upcoming = 0;
                        foreach ($bookings as $booking) {
                            if (strtotime($booking['event_date']) >= strtotime('today')) {
                                $upcoming++;
                            }
                        }
                        ?>
                        <p class="stat-value"><?php echo $upcoming; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>Total Spent</h3>
                        <?php
                        $total_spent = 0;
                        foreach ($bookings as $booking) {
                            if ($booking['payment_status'] === 'paid') {
                                $total_spent += $booking['price'];
                            }
                        }
                        ?>
                        <p class="stat-value"><?php echo formatCurrency($total_spent); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>Recent Bookings</h3>
                
                <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_bookings = array_slice($bookings, 0, 5);
                            foreach ($recent_bookings as $booking): 
                            ?>
                            <tr>
                                <td><?php echo sanitizeOutput($booking['booking_reference']); ?></td>
                                <td><?php echo sanitizeOutput($booking['package_name']); ?></td>
                                <td><?php echo formatDate($booking['event_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($booking['event_time'])); ?></td>
                                <td><?php echo formatCurrency($booking['price']); ?></td>
                                <td><?php echo getStatusLabel($booking['payment_status']); ?></td>
                                <td>
                                    <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn-sm">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($bookings) > 5): ?>
                <div class="view-all">
                    <a href="my_bookings.php" class="btn-link">View All Bookings</a>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h4>No Bookings Yet</h4>
                    <p>You haven't made any bookings yet. Start by creating your first booking!</p>
                    <a href="new_booking.php" class="btn">Create Booking</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <h3>Recommended Packages</h3>
                
                <div class="package-cards">
                    <?php
                    // Get featured packages
                    $sql = "SELECT * FROM packages WHERE is_featured = 1 LIMIT 3";
                    $result = $conn->query($sql);
                    
                    while ($package = $result->fetch_assoc()):
                    ?>
                    <div class="package-card">
                        <div class="package-image">
                            <?php if (!empty($package['image_path'])): ?>
                            <img src="<?php echo sanitizeOutput($package['image_path']); ?>" alt="<?php echo sanitizeOutput($package['package_name']); ?>">
                            <?php else: ?>
                            <img src="https://placeholder.pics/svg/300x200/DEDEDE/555555/<?php echo urlencode($package['package_name']); ?>" alt="<?php echo sanitizeOutput($package['package_name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="package-content">
                            <h4><?php echo sanitizeOutput($package['package_name']); ?></h4>
                            <p><?php echo sanitizeOutput($package['package_description']); ?></p>
                            <p class="price"><?php echo formatCurrency($package['price']); ?> per event</p>
                            <a href="new_booking.php?package=<?php echo $package['package_id']; ?>" class="btn">Book Now</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <footer class="dashboard-footer">
        <div class="container">
            <p>&copy; 2023 Breathe Luxe. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Dashboard specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown toggle
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownToggle && dropdownMenu) {
                dropdownToggle.addEventListener('click', function() {
                    dropdownMenu.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.dropdown')) {
                        dropdownMenu.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
