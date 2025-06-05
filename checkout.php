<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pdo = getDBConnection();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    redirect('cart.php');
}

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $paymentCompleted = isset($_POST['payment_completed']);
    
    if (empty($paymentMethod)) {
        $error = 'Please select a payment method';
    } elseif (!$paymentCompleted) {
        $error = 'Please confirm that you have completed the payment';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate order reference
            $orderReference = 'BREATHE' . date('Ymd') . rand(1000, 9999);
            
            // Create order
            $orderData = json_encode([
                'items' => $cartItems,
                'reference' => $orderReference,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_data, total_price, payment_method, status) VALUES (?, ?, ?, ?, 'completed')");
            $stmt->execute([$_SESSION['user_id'], $orderData, $total, $paymentMethod]);
            
            $orderId = $pdo->lastInsertId();
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            $success = "Order #$orderId placed successfully! Reference: $orderReference";
            $cartItems = []; // Clear items for display
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Order processing failed. Please try again. Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Breathe Luxe</title>
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
        <h2 class="section-title">Checkout</h2>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align: center; padding: 2rem;">
                <a href="dashboard.php" class="btn btn-primary">View Orders</a>
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        <?php elseif (!empty($cartItems)): ?>
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Order Summary</h3>
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                            <span><strong><?php echo htmlspecialchars($item['name']); ?></strong> (x<?php echo $item['quantity']; ?>)</span>
                            <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2rem; color: #e74c3c;">
                        <span>Total:</span>
                        <span id="total-amount"><?php echo formatPrice($total); ?></span>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3>Payment Method</h3>
                    <form method="POST" id="checkout-form">
                        <input type="hidden" id="selected_payment_method" name="payment_method" value="">
                        
                        <div class="payment-methods">
                            <div class="payment-method" data-method="M-Pesa" onclick="selectPaymentMethod('M-Pesa')">
                                <h4>📱 M-Pesa</h4>
                                <p>Pay using your M-Pesa mobile wallet</p>
                            </div>
                            
                            <div class="payment-method" data-method="Airtel Money" onclick="selectPaymentMethod('Airtel Money')">
                                <h4>📱 Airtel Money</h4>
                                <p>Pay using your Airtel Money mobile wallet</p>
                            </div>
                            
                            <div class="payment-method" data-method="Bank Transfer" onclick="selectPaymentMethod('Bank Transfer')">
                                <h4>🏦 Bank Transfer</h4>
                                <p>Direct bank transfer payment</p>
                            </div>
                        </div>
                        
                        <div id="payment-instructions" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                            <!-- Payment instructions will be inserted here -->
                        </div>
                        
                        <div style="margin: 2rem 0;">
                            <button type="button" onclick="simulatePayment()" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                💳 Simulate Payment
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="payment_completed" name="payment_completed" required>
                                <span>I have completed the payment using the selected method</span>
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-success" style="flex: 1;">Complete Order</button>
                            <a href="cart.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Back to Cart</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>
</body>
</html>
