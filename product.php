<?php
require_once 'config/database.php';
require_once 'config/session.php';

$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get product details
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ? AND p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get related products (same category)
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
          ORDER BY RAND() LIMIT 4";
$stmt = $db->prepare($query);
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Farmers Marketplace</title>
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
            background: #f8f9fa;
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
            display: inline-block;
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
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .breadcrumb {
            margin-bottom: 30px;
            color: #666;
        }
        
        .breadcrumb a {
            color: #2d5a27;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Product Details */
        .product-details {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px;
        }
        
        .product-image {
            width: 100%;
            height: 400px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
        }
        
        .product-info h1 {
            font-size: 32px;
            color: #2d5a27;
            margin-bottom: 15px;
        }
        
        .product-category {
            background: #e8f5e8;
            color: #2d5a27;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .product-price {
            font-size: 36px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .product-description {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 25px;
            color: #555;
        }
        
        .product-meta {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .meta-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .meta-label {
            font-weight: 600;
            color: #333;
        }
        
        .meta-value {
            color: #666;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .quantity-selector label {
            font-weight: 600;
        }
        
        .quantity-input {
            width: 80px;
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: #2d5a27;
        }
        
        .add-to-cart-btn {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        /* Related Products */
        .related-products {
            margin-top: 60px;
        }
        
        .section-title {
            font-size: 28px;
            color: #2d5a27;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .product-card .product-image {
            height: 180px;
        }
        
        .product-card .product-info {
            padding: 20px;
        }
        
        .product-card h3 {
            color: #2d5a27;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .product-card .product-price {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .product-card .product-farmer {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            width: 100%;
            text-align: center;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .product-main {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }
            
            .product-info h1 {
                font-size: 24px;
            }
            
            .product-price {
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

    <main class="main-content">
        <div class="breadcrumb">
            <a href="index.php">Home</a> > 
            <a href="products.php">Products</a> > 
            <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> > 
            <?php echo htmlspecialchars($product['name']); ?>
        </div>

        <div class="product-details">
            <div class="product-main">
                <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($product['image_url']); ?>')"></div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">Farmer:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($product['farmer_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Location:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($product['farmer_location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Stock Available:</span>
                            <span class="meta-value"><?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Unit:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($product['unit']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="quantity-selector">
                                <label for="quantity">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="quantity-input">
                                <span><?php echo htmlspecialchars($product['unit']); ?></span>
                            </div>
                            <button type="submit" class="btn btn-primary add-to-cart-btn">Add to Cart</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary add-to-cart-btn">Login to Purchase</a>
                    <?php endif; ?>
                    
                    <a href="products.php" class="btn btn-outline" style="width: 100%; text-align: center;">← Continue Shopping</a>
                </div>
            </div>
        </div>

        <?php if (count($related_products) > 0): ?>
            <div class="related-products">
                <h2 class="section-title">Related Products</h2>
                <div class="products-grid">
                    <?php foreach ($related_products as $related_product): ?>
                        <div class="product-card">
                            <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($related_product['image_url']); ?>')"></div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($related_product['name']); ?></h3>
                                <div class="product-price">₹<?php echo number_format($related_product['price'], 2); ?>/<?php echo htmlspecialchars($related_product['unit']); ?></div>
                                <div class="product-farmer">By <?php echo htmlspecialchars($related_product['farmer_name']); ?></div>
                                <a href="product.php?id=<?php echo $related_product['id']; ?>" class="btn btn-primary btn-small">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
