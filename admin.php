<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$pdo = getDBConnection();

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
        
        // Get all orders with a more robust query
        $stmt = $pdo->query("
            SELECT o.id, o.order_date, 
                   IFNULL(o.total_price, 0) as total_price, 
                   IFNULL(o.payment_method, 'Unknown') as payment_method, 
                   IFNULL(o.status, 'pending') as status,
                   u.username, u.email
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.order_date DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // If there's an error with the query, just show an empty orders list
    error_log("Admin Error: " . $e->getMessage());
    $orders = [];
}

// Get all users
$stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        // Redirect to refresh the page
        header("Location: admin.php?updated=1");
        exit;
    } catch (PDOException $e) {
        error_log("Status Update Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Breathe Luxe</title>
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
                        <li><a href="admin.php">Admin</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <span>Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container dashboard">
        <h2 class="section-title">Admin Panel</h2>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="message success">Order status updated successfully!</div>
        <?php endif; ?>
        
        <div class="dashboard-card">
            <h3>All Orders</h3>
            <?php if (empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td><?php echo formatPrice($order['total_price']); ?></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $order['status'] == 'completed' ? 'green' : 'orange'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_order" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-card" style="margin-top: 2rem;">
            <h3>All Users</h3>
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
