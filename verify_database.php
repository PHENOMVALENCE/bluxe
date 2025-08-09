<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Database Verification</h2>";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Current Database:</strong> " . $result['current_db'] . "</p>";
    
    // Check tables
    $tables = ['users', 'products', 'cart', 'orders'];
    
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        
        try {
            // Check if table exists and show structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Count records
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>Records:</strong> " . $count['count'] . "</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error with table $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test admin user
    echo "<h3>Admin User Test</h3>";
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin user exists: " . $admin['username'] . " (" . $admin['email'] . ")</p>";
    } else {
        echo "<p style='color: red;'>✗ Admin user not found</p>";
    }
    
    // Test products
    echo "<h3>Products Test</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Products in database: " . $productCount['count'] . "</p>";
    
    if ($productCount['count'] > 0) {
        $stmt = $pdo->query("SELECT name, price FROM products LIMIT 3");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li>" . $product['name'] . " - TZS " . number_format($product['price']) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Database Connection Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
