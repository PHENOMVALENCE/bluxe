<?php
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Check if cart table exists and create it if it doesn't
try {
    $pdo = getDBConnection();
    
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
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo = getDBConnection();
        
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingItem) {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
            $stmt->execute([$existingItem['id']]);
            $message = 'Product quantity updated in cart';
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$userId, $productId]);
            $message = 'Product added to cart successfully';
        }
        
        // Get updated cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $result['total'] ?? 0;
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'cart_count' => $cartCount,
            'product_name' => $product['name']
        ]);
        
    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Cart Error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage(),
            'error_code' => $e->getCode()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing product ID']);
}
?>
