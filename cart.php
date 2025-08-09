<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getDBConnection();

// Check if cart table exists and create it if it doesn't
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($stmt->rowCount() == 0) {
        // Cart table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ");
    }
} catch (PDOException $e) {
    // Log the error but continue
    error_log("Error checking/creating cart table: " . $e->getMessage());
}

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.description
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    // If there's an error, set cart items to empty array
    error_log("Error getting cart items: " . $e->getMessage());
    $cartItems = [];
    $total = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Breathe Luxe</title>
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
                    </ul>
                </nav>
                <div class="user-actions">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <h2 class="section-title">Shopping Cart</h2>
        
        <?php if (empty($cartItems)): ?>
            <div style="text-align: center; padding: 2rem;">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($item['description']); ?></small>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td>
                                    <div class="quantity-controls">
                                        <button onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">-</button>
                                        <input type="number" value="<?php echo $item['quantity']; ?>" readonly>
                                        <button onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">+</button>
                                    </div>
                                </td>
                                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                <td>
                                    <button onclick="removeFromCart(<?php echo $item['cart_id']; ?>)" 
                                            class="btn btn-secondary">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-total">
                <div class="total-amount">Total: <?php echo formatPrice($total); ?></div>
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>
</body>
</html>
