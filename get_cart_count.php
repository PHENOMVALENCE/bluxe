<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if cart table exists
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
    
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $result['total'] ?? 0]);
} catch (PDOException $e) {
    error_log("Cart Count Error: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}
?>
