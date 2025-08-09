<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id'])) {
    $cartId = (int)$_POST['cart_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo = getDBConnection();
        
        // Verify cart item belongs to user and delete
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
