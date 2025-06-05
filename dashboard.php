<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getDBConnection();

// Get user info
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if orders table has the required columns
try {
    // First, check if the orders table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create orders table if it doesn't exist
        $pdo->exec("
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                order_data TEXT NOT NULL,
                total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'Unknown',
                status VARCHAR(20) DEFAULT 'pending',
                order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $orders = [];
    } else {
        // Check if total_price column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'total_price'");
        $totalPriceExists = $stmt->rowCount() > 0;
        
        if (!$totalPriceExists) {
            // Add total_price column if it doesn't exist
            $pdo->exec("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER order_data");
        }
        
        // Check if payment_method column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        $paymentMethodExists = $stmt->rowCount() > 0;
        
        if (!$paymentMethodExists) {
            // Add payment_method column if it doesn't exist
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Unknown' AFTER total_price");
        }
        
        // Check if status column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
        $statusExists = $stmt->rowCount() > 0;
        
        if (!$statusExists) {
            // Add status column if it doesn't exist
            $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER payment_method");
        }
        
        // Get order history with a more robust query that adapts to available columns
        $stmt = $pdo->prepare("
            SELECT id, user_id, order_date, 
                   IFNULL(total_price, 0) as total_price, 
                   IFNULL(payment_method, 'Unknown') as payment_method, 
                   IFNULL(status, 'pending') as status 
            FROM orders 
            WHERE user_id = ? 
            ORDER BY order_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // If there's an error with the query, just show an empty orders list
    error_log("Dashboard Error: " . $e->getMessage());
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Breathe Luxe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Breathe Luxe</div>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="cart.php">Cart</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin.php">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="user-actions">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container dashboard">
        <h2 class="section-title">User Dashboard</h2>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Profile Information</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <a href="index.php" class="btn btn-primary" style="margin-bottom: 0.5rem; display: block;">Browse Products</a>
                <a href="cart.php" class="btn btn-secondary" style="display: block;">View Cart</a>
            </div>
        </div>
        
        <div class="dashboard-card" style="margin-top: 2rem;">
            <h3>Order History</h3>
            <?php if (empty($orders)): ?>
                <p>No orders found. <a href="index.php">Start shopping!</a></p>
            <?php else: ?>
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $order['status'] == 'completed' ? 'green' : 'orange'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
