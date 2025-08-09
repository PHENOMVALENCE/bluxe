<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = getDBConnection();
    
    echo "<h1>Database Repair Tool</h1>";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Current Database:</strong> " . $result['current_db'] . "</p>";
    
    // Define all required tables and their structures
    $tables = [
        'users' => "
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'products' => "
            CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'cart' => "
            CREATE TABLE cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ",
        'orders' => "
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
        "
    ];
    
    // Check and create each table
    foreach ($tables as $tableName => $createStatement) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "<p style='color: red;'>Table '$tableName' does not exist. Creating it now...</p>";
            $pdo->exec($createStatement);
            echo "<p style='color: green;'>Table '$tableName' created successfully!</p>";
            
            // Insert sample data for products table
            if ($tableName === 'products') {
                echo "<p>Adding sample products...</p>";
                $pdo->exec("
    INSERT INTO products (name, description, price, image) VALUES
    ('Starter Kit', 'Perfect for beginners - includes basic hookah setup with premium tobacco', 50000.00, 'h1.jpg'),
    ('Free Sampling', 'Try our premium flavors with this sampling package', 35000.00, 'h2.jpg'),
    ('VIP Lounge', 'Ultimate luxury experience with premium hookah and exclusive flavors', 120000.00, 'h3.jpg'),
    ('Holiday Kit', 'Special holiday package with festive flavors and decorative setup', 80000.00, 'h4.jpg'),
    ('Premium Collection', 'Curated selection of our finest tobacco blends and accessories', 95000.00, 'h5.jpg'),
    ('Executive Package', 'Professional-grade hookah setup for the discerning enthusiast', 150000.00, 'h6.jpg'),
    ('Exotic Flavors', 'Rare and unique flavor combinations from around the world', 65000.00, 'h7.jpg'),
    ('Deluxe Experience', 'Complete luxury hookah experience with premium service', 200000.00, 'h8.jpg')
");
                echo "<p style='color: green;'>Sample products with images added!</p>";
            }
            
            // Create admin user if users table was just created
            if ($tableName === 'users') {
                echo "<p>Adding admin user...</p>";
                // Check if admin user already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
                $stmt->execute();
                if ($stmt->rowCount() === 0) {
                    // Admin password: admin123
                    $pdo->exec("
                        INSERT INTO users (username, email, password) VALUES
                        ('admin', 'admin@breatheluxe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
                    ");
                    echo "<p style='color: green;'>Admin user added! (Username: admin, Password: admin123)</p>";
                } else {
                    echo "<p style='color: green;'>Admin user already exists.</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>✓ Table '$tableName' exists</p>";
            
            // Check columns for orders table
            if ($tableName === 'orders') {
                // Check if total_price column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'total_price'");
                $totalPriceExists = $stmt->rowCount() > 0;
                
                if (!$totalPriceExists) {
                    echo "<p style='color: red;'>Missing column: total_price. Adding it now...</p>";
                    $pdo->exec("ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER order_data");
                    echo "<p style='color: green;'>Added total_price column!</p>";
                }
                
                // Check if payment_method column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
                $paymentMethodExists = $stmt->rowCount() > 0;
                
                if (!$paymentMethodExists) {
                    echo "<p style='color: red;'>Missing column: payment_method. Adding it now...</p>";
                    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Unknown' AFTER total_price");
                    echo "<p style='color: green;'>Added payment_method column!</p>";
                }
                
                // Check if status column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
                $statusExists = $stmt->rowCount() > 0;
                
                if (!$statusExists) {
                    echo "<p style='color: red;'>Missing column: status. Adding it now...</p>";
                    $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER payment_method");
                    echo "<p style='color: green;'>Added status column!</p>";
                }
            }
        }
    }
    
    // Show current table structures
    echo "<h2>Current Database Structure</h2>";
    
    foreach ($tables as $tableName => $createStatement) {
        echo "<h3>Table: $tableName</h3>";
        
        $stmt = $pdo->query("DESCRIBE $tableName");
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
        
        // Show row count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $tableName");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Records:</strong> " . $count['count'] . "</p>";
    }
    
    echo "<p style='color: green;'>Database repair completed successfully!</p>";
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='index.php' style='background-color: #e74c3c; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Homepage</a>";
    echo "<a href='dashboard.php' style='background-color: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h1, h2, h3 { color: #333; }
p { margin: 10px 0; }
</style>
