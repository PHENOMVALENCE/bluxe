-- Create database
CREATE DATABASE IF NOT EXISTS breathe_luxe;
USE breathe_luxe;

-- Drop tables if they exist (to start fresh)
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table (corrected with all required columns)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_data TEXT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample products with images
INSERT INTO products (name, description, price, image) VALUES
('Starter Kit', 'Perfect for beginners - includes basic hookah setup with premium tobacco', 50000.00, 'h1.jpg'),
('Free Sampling', 'Try our premium flavors with this sampling package', 35000.00, 'h2.jpg'),
('VIP Lounge', 'Ultimate luxury experience with premium hookah and exclusive flavors', 120000.00, 'h3.jpg'),
('Holiday Kit', 'Special holiday package with festive flavors and decorative setup', 80000.00, 'h4.jpg'),
('Premium Collection', 'Curated selection of our finest tobacco blends and accessories', 95000.00, 'h5.jpg'),
('Executive Package', 'Professional-grade hookah setup for the discerning enthusiast', 150000.00, 'h6.jpg'),
('Exotic Flavors', 'Rare and unique flavor combinations from around the world', 65000.00, 'h7.jpg'),
('Deluxe Experience', 'Complete luxury hookah experience with premium service', 200000.00, 'h8.jpg');

-- Create admin user (password: admin123)
INSERT INTO users (username, email, password) VALUES
('admin', 'admin@breatheluxe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Verify table structure
DESCRIBE orders;
