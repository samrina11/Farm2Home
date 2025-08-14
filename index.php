<?php
require_once 'config/database.php';
require_once 'config/session.php';

$database = new Database();
$db = $database->getConnection();

// Get featured products (latest 8 products)
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'active' 
          ORDER BY p.created_at DESC LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Marketplace - Fresh from Farm to Your Table</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        /* Header Styles */
        .header {
            background: #2d5a27;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-menu a:hover {
            color: #a8d5a3;
        }
        
        .user-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #2d5a27;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(45, 90, 39, 0.8), rgba(45, 90, 39, 0.8)), url('/placeholder.svg?height=500&width=1200');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 20px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Categories Section */
        .categories {
            padding: 60px 20px;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #2d5a27;
            font-weight: 700;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-card h3 {
            color: #2d5a27;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .category-card p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Featured Products */
        .featured-products {
            padding: 60px 20px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-info h3 {
            color: #2d5a27;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .product-farmer {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            flex: 1;
            text-align: center;
        }
        
        /* Footer */
        .footer {
            background: #2d5a27;
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer h3 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .footer p {
            margin-bottom: 10px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            margin: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .section-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">🌱 Farmers Market</div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo htmlspecialchars(getUserName()); ?>!</span>
                    <a href="cart.php" class="btn btn-outline">Cart</a>
                    <a href="profile.php" class="btn btn-primary">Profile</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($message === 'logged_out'): ?>
        <div class="alert alert-success">You have been successfully logged out.</div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Fresh from Farm to Your Table</h1>
        <p>Discover the finest vegetables and fruits directly from local farmers. Quality guaranteed, freshness delivered.</p>
        <a href="products.php" class="btn btn-primary" style="font-size: 18px; padding: 12px 30px;">Shop Now</a>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">Browse</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($product['image_url']); ?>')"></div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">₹<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></div>
                            <div class="product-farmer">By <?php echo htmlspecialchars($product['farmer_name']); ?> - <?php echo htmlspecialchars($product['farmer_location']); ?></div>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-small">View Details</a>
                                <?php if (isLoggedIn()): ?>
                                    <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-small">Add to Cart</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <h3>🌱 Farmers Market</h3>
            <p>Connecting farmers directly with consumers</p>
            <p>Fresh • Organic • Local</p>
            <p>&copy; 2024 Farmers Marketplace. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
