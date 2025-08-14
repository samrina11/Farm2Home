<?php
require_once 'config/database.php';
require_once 'config/session.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($search_query) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.farmer_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'name' => 'p.name ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC'
];

$order_clause = $sort_options[$sort_by] ?? 'p.name ASC';

$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE $where_clause 
          ORDER BY $order_clause";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Farmers Marketplace</title>
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
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #2d5a27;
        }
        
        /* Products Table */
        .products-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .products-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background: #2d5a27;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .products-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-name {
            font-weight: 600;
            color: #2d5a27;
            font-size: 16px;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .product-category {
            background: #e8f5e8;
            color: #2d5a27;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        
        .product-farmer {
            color: #666;
            font-size: 14px;
        }
        
        .product-stock {
            color: #28a745;
            font-weight: 500;
        }
        
        .product-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            text-align: center;
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-products h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .products-table {
                overflow-x: auto;
            }
            
            .products-table table {
                min-width: 800px;
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
        <h1 class="page-title">Our Fresh Products</h1>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Products</label>
                        <input type="text" id="search" name="search" placeholder="Search by name, farmer..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <?php if (count($products) > 0): ?>
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Farmer</th>
                            <th>Location</th>
                            <th>Stock</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                </td>
                                <td>
                                    <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                </td>
                                <td>
                                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></div>
                                </td>
                                <td>
                                    <div class="product-farmer"><?php echo htmlspecialchars($product['farmer_name']); ?></div>
                                </td>
                                <td>
                                    <div class="product-farmer"><?php echo htmlspecialchars($product['farmer_location']); ?></div>
                                </td>
                                <td>
                                    <div class="product-stock"><?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></div>
                                </td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($product['description']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-actions">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-small">View</a>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-small">Add to Cart</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-products">
                <h3>No products found</h3>
                <p>Try adjusting your search criteria or browse all categories.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 20px;">View All Products</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
