<?php
require_once 'config/database.php';
require_once 'config/session.php';

$database = new Database();
$db = $database->getConnection();

// Get all categories with product count
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
          GROUP BY c.id 
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Farmers Marketplace</title>
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
        
        .page-title {
            font-size: 36px;
            color: #2d5a27;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-bottom: 50px;
        }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-align: center;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .category-card h3 {
            color: #2d5a27;
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .category-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .product-count {
            background: #e8f5e8;
            color: #2d5a27;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .category-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-small {
            padding: 10px 20px;
            font-size: 14px;
        }
        
        /* Stats Section */
        .stats-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 36px;
            color: #2d5a27;
            margin-bottom: 10px;
        }
        
        .stat-item p {
            color: #666;
            font-size: 16px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
        <h1 class="page-title">Product Categories</h1>
        <p class="page-subtitle">Explore our wide range of fresh produce organized by category</p>
        
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Categories Available</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo array_sum(array_column($categories, 'product_count')); ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-item">
                    <h3>100%</h3>
                    <p>Fresh & Organic</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Online Shopping</p>
                </div>
            </div>
        </div>

        <!-- Categories Grid -->
        <div class="categories-grid">
            <?php 
            // Category icons mapping
            $category_icons = [
                'Vegetables' => '🥬',
                'Fruits' => '🍎',
                'Grains' => '🌾',
                'Dairy' => '🥛',
                'Herbs' => '🌿',
                'Spices' => '🌶️',
                'Organic' => '🌱',
                'Seasonal' => '🍂'
            ];
            
            foreach ($categories as $category): 
                $icon = $category_icons[$category['name']] ?? '🥕';
            ?>
                <div class="category-card">
                    <div class="category-icon"><?php echo $icon; ?></div>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <div class="product-count">
                        <?php echo $category['product_count']; ?> Products Available
                    </div>
                    <div class="category-actions">
                        <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-primary btn-small">
                            Browse Products
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($categories)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #666;">
                <h3>No categories available</h3>
                <p>Categories will be displayed here once they are added to the system.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
