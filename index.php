<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get all products
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() == 0) {
        // Products table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert sample products with images
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
    }
    
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If there's an error, set products to empty array
    error_log("Error getting products: " . $e->getMessage());
    $products = [];
}

// Get cart count for logged in user
$cartCount = 0;
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = $result['total'] ?? 0;
    } catch (PDOException $e) {
        // If there's an error, set cart count to 0
        error_log("Error getting cart count: " . $e->getMessage());
        $cartCount = 0;
    }
}

// Function to check if image exists and return appropriate background
function getProductImageStyle($imageName) {
    if (!empty($imageName) && file_exists($imageName)) {
        return "background-image: url('" . htmlspecialchars($imageName) . "');";
    } else {
        // Return a gradient background if image doesn't exist
        $gradients = [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
            'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'
        ];
        $index = crc32($imageName) % count($gradients);
        return "background: " . $gradients[$index] . ";";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breathe Luxe - Premium Hookah Experience</title>
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
                        <?php if (isLoggedIn()): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="cart.php">Cart <span class="cart-badge"><?php echo $cartCount; ?></span></a></li>
                            <?php if (isAdmin()): ?>
                                <li><a href="admin.php">Admin</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="user-actions">
                    <?php if (isLoggedIn()): ?>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section with Background Image Slider -->
    <section class="hero-slider">
        <div class="hero-slide active" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Premium Hookah Experience</h1>
                    <p class="hero-subtitle">Discover the finest shisha products and accessories for the ultimate relaxation</p>
                    <div class="hero-buttons">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-primary btn-large">Get Started</a>
                            <a href="#products" class="btn btn-secondary btn-large">View Products</a>
                        <?php else: ?>
                            <a href="#products" class="btn btn-primary btn-large">Shop Now</a>
                            <a href="cart.php" class="btn btn-secondary btn-large">View Cart</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Luxury Shisha Collection</h1>
                    <p class="hero-subtitle">Experience the art of traditional hookah with modern sophistication</p>
                    <div class="hero-buttons">
                        <a href="#products" class="btn btn-primary btn-large">Explore Collection</a>
                        <a href="#about" class="btn btn-secondary btn-large">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Authentic Flavors</h1>
                    <p class="hero-subtitle">From traditional to exotic blends, taste the difference quality makes</p>
                    <div class="hero-buttons">
                        <a href="#products" class="btn btn-primary btn-large">Taste Now</a>
                        <a href="#flavors" class="btn btn-secondary btn-large">View Flavors</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">VIP Lounge Experience</h1>
                    <p class="hero-subtitle">Elevate your moments with our exclusive premium packages</p>
                    <div class="hero-buttons">
                        <a href="#products" class="btn btn-primary btn-large">Book VIP</a>
                        <a href="#contact" class="btn btn-secondary btn-large">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Dots -->
        <div class="hero-dots">
            <span class="dot active" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
            <span class="dot" onclick="currentSlide(4)"></span>
        </div>
        
        <!-- Navigation Arrows -->
        <div class="hero-nav">
            <button class="hero-prev" onclick="changeSlide(-1)">❮</button>
            <button class="hero-next" onclick="changeSlide(1)">❯</button>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌟</div>
                    <h3>Premium Quality</h3>
                    <p>Only the finest tobacco and accessories for an exceptional experience</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚚</div>
                    <h3>Fast Delivery</h3>
                    <p>Quick and reliable delivery across Tanzania within 24-48 hours</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💎</div>
                    <h3>Luxury Experience</h3>
                    <p>Transform your space into a premium lounge with our exclusive packages</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Expert Curation</h3>
                    <p>Carefully selected products by hookah enthusiasts for enthusiasts</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="container">
            <h2 class="section-title">Our Premium Packages</h2>
            <p class="section-subtitle">Choose from our carefully curated selection of hookah experiences</p>
            
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 2rem;">
                    <p>No products available at the moment. Please check back later.</p>
                    <p>If you're the administrator, please run the <a href="repair_database.php">database repair tool</a>.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image" style="<?php echo getProductImageStyle($product['image']); ?>">
                                <div class="product-badge">Premium</div>
                                <div class="product-overlay">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                </div>
                                <?php if (!file_exists($product['image'])): ?>
                                    <div class="image-placeholder">
                                        <div class="placeholder-icon">📦</div>
                                        <div class="placeholder-text"><?php echo htmlspecialchars($product['name']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                <div class="product-actions">
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn btn-success btn-full" onclick="addToCart(<?php echo $product['id']; ?>)">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary btn-full">Login to Purchase</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Image Upload Instructions for Admin -->
                <?php if (isLoggedIn() && isAdmin()): ?>
                    <div class="admin-notice">
                        <h3>📁 Admin Notice: Add Product Images</h3>
                        <p>To display product images, upload the following files to your root directory:</p>
                        <ul>
                            <li><strong>h1.jpg</strong> - Starter Kit image</li>
                            <li><strong>h2.jpg</strong> - Free Sampling image</li>
                            <li><strong>h3.jpg</strong> - VIP Lounge image</li>
                            <li><strong>h4.jpg</strong> - Holiday Kit image</li>
                            <li><strong>h5.jpg</strong> - Premium Collection image</li>
                            <li><strong>h6.jpg</strong> - Executive Package image</li>
                            <li><strong>h7.jpg</strong> - Exotic Flavors image</li>
                            <li><strong>h8.jpg</strong> - Deluxe Experience image</li>
                        </ul>
                        <p><small>Images should be at least 400x300px for best quality. JPEG format recommended.</small></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Breathe Luxe</h2>
                    <p>We are passionate about bringing you the finest hookah experience in Tanzania. Our carefully curated selection of premium tobacco, authentic accessories, and luxury packages ensures that every moment you spend with our products is extraordinary.</p>
                    <p>From traditional flavors to modern blends, from beginner-friendly starter kits to VIP lounge experiences, we cater to every level of hookah enthusiast.</p>
                    <div class="about-stats">
                        <div class="stat">
                            <h3>500+</h3>
                            <p>Happy Customers</p>
                        </div>
                        <div class="stat">
                            <h3>50+</h3>
                            <p>Premium Flavors</p>
                        </div>
                        <div class="stat">
                            <h3>24/7</h3>
                            <p>Customer Support</p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="about-placeholder">
                        Premium Hookah Setup
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Experience Luxury?</h2>
                <p>Join thousands of satisfied customers who have elevated their hookah experience with Breathe Luxe</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary btn-large">Start Your Journey</a>
                <?php else: ?>
                    <a href="#products" class="btn btn-primary btn-large">Shop Now</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Breathe Luxe</h3>
                    <p>Premium hookah experience delivered to your doorstep across Tanzania.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#products">Products</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="cart.php">Cart</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>📧 info@breatheluxe.com</p>
                    <p>📱 +255 123 456 789</p>
                    <p>📍 Dar es Salaam, Tanzania</p>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#">Facebook</a>
                        <a href="#">Instagram</a>
                        <a href="#">WhatsApp</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Breathe Luxe. All rights reserved. | Premium Hookah Experience</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
