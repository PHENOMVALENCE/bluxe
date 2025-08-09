<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_id']) && isset($_POST['change'])) {
    $cartId = (int)$_POST['cart_id'];
    $change = (int)$_POST['change'];
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo = getDBConnection();
        
        // Verify cart item belongs to user
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }
        
        $newQuantity = $item['quantity'] + $change;
        
        if ($newQuantity <= 0) {
            // Remove item if quantity becomes 0 or less
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);
        } else {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $cartId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
