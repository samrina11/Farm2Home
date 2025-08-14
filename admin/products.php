<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category_id = $_POST['category_id'];
        $price = floatval($_POST['price']);
        $unit = trim($_POST['unit']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $farmer_name = trim($_POST['farmer_name']);
        $farmer_location = trim($_POST['farmer_location']);
        $status = $_POST['status'];
        
        if (empty($name) || empty($price) || empty($farmer_name)) {
            $error = 'Please fill in all required fields';
        } else {
            if ($action == 'add') {
                $query = "INSERT INTO products (name, description, category_id, price, unit, stock_quantity, farmer_name, farmer_location, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $image_url = "/placeholder.svg?height=200&width=200&query=" . urlencode($name);
                $stmt = $db->prepare($query);
                if ($stmt->execute([$name, $description, $category_id, $price, $unit, $stock_quantity, $farmer_name, $farmer_location, $status, $image_url])) {
                    $message = 'Product added successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to add product';
                }
            } else {
                $query = "UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, unit = ?, stock_quantity = ?, farmer_name = ?, farmer_location = ?, status = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$name, $description, $category_id, $price, $unit, $stock_quantity, $farmer_name, $farmer_location, $status, $product_id])) {
                    $message = 'Product updated successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to update product';
                }
            }
        }
    }
}

// Handle delete
if ($action == 'delete' && $product_id) {
    $query = "UPDATE products SET status = 'inactive' WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$product_id])) {
        $message = 'Product deleted successfully!';
    } else {
        $error = 'Failed to delete product';
    }
    $action = 'list';
}

// Get categories for dropdown
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$product = null;
if ($action == 'edit' && $product_id) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $action = 'list';
        $error = 'Product not found';
    }
}

// Get products list
if ($action == 'list') {
    $filter = $_GET['filter'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($filter == 'low_stock') {
        $where_conditions[] = "p.stock_quantity < 10";
    }
    
    if ($search) {
        $where_conditions[] = "(p.name LIKE ? OR p.farmer_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT p.*, c.name as category_name FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              $where_clause 
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        /* Admin Header */
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-logo {
            font-size: 24px;
            font-weight: 700;
        }
        
        .admin-nav {
            display: flex;
            gap: 30px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: #34495e;
        }
        
        .admin-user {
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
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #2c3e50;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Main Content */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            color: #2c3e50;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .required {
            color: #e74c3c;
        }
        
        /* Products Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #ecf0f1;
            padding: 20px;
            border-bottom: 1px solid #bdc3c7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-form input {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-farmer {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .product-price {
            font-weight: 600;
            color: #e74c3c;
            font-size: 16px;
        }
        
        .product-stock {
            font-weight: 500;
        }
        
        .stock-low {
            color: #e74c3c;
        }
        
        .stock-good {
            color: #27ae60;
        }
        
        .product-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-nav {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .products-table {
                font-size: 14px;
            }
            
            .products-table th,
            .products-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="header-container">
            <div class="admin-logo">🛠️ Admin Panel</div>
            <nav class="admin-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php" class="active">Products</a>
                <a href="categories.php">Categories</a>
                <a href="orders.php">Orders</a>
                <a href="users.php">Users</a>
            </nav>
            <div class="admin-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
                <a href="../index.php" class="btn btn-primary">View Site</a>
                <a href="../logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </header>

    <main class="admin-container">
        <?php if ($action == 'list'): ?>
            <div class="page-header">
                <h1 class="page-title">Products Management</h1>
                <a href="products.php?action=add" class="btn btn-success">Add New Product</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <div class="table-header">
                    <div>
                        <strong><?php echo count($products); ?> Products</strong>
                        <?php if ($filter == 'low_stock'): ?>
                            <span style="color: #e74c3c; margin-left: 10px;">(Low Stock)</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <form method="GET" action="" class="search-form">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit" class="btn btn-primary btn-small">Search</button>
                        </form>
                        <a href="products.php?filter=low_stock" class="btn btn-warning btn-small">Low Stock</a>
                        <a href="products.php" class="btn btn-primary btn-small">All Products</a>
                    </div>
                </div>
                
                <?php if (count($products) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($product['image_url']); ?>')"></div>
                                    </td>
                                    <td>
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="product-farmer">By <?php echo htmlspecialchars($product['farmer_name']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td class="product-price">₹<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></td>
                                    <td>
                                        <span class="product-stock <?php echo $product['stock_quantity'] < 10 ? 'stock-low' : 'stock-good'; ?>">
                                            <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="product-status status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                            <?php if ($product['status'] == 'active'): ?>
                                                <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-products">
                        <h3>No products found</h3>
                        <p>Start by adding your first product!</p>
                        <a href="products.php?action=add" class="btn btn-success" style="margin-top: 15px;">Add Product</a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="page-header">
                <h1 class="page-title"><?php echo $action == 'add' ? 'Add New Product' : 'Edit Product'; ?></h1>
                <a href="products.php" class="btn btn-primary">← Back to Products</a>
            </div>
            
            <?php if ($error): ?>
                <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Product Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe the product..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₹) <span class="required">*</span></label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo $product['price'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="unit">Unit</label>
                            <select id="unit" name="unit">
                                <option value="kg" <?php echo ($product['unit'] ?? 'kg') == 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                <option value="piece" <?php echo ($product['unit'] ?? '') == 'piece' ? 'selected' : ''; ?>>Piece</option>
                                <option value="bunch" <?php echo ($product['unit'] ?? '') == 'bunch' ? 'selected' : ''; ?>>Bunch</option>
                                <option value="dozen" <?php echo ($product['unit'] ?? '') == 'dozen' ? 'selected' : ''; ?>>Dozen</option>
                                <option value="liter" <?php echo ($product['unit'] ?? '') == 'liter' ? 'selected' : ''; ?>>Liter</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $product['stock_quantity'] ?? '0'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo ($product['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($product['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="farmer_name">Farmer Name <span class="required">*</span></label>
                            <input type="text" id="farmer_name" name="farmer_name" required value="<?php echo htmlspecialchars($product['farmer_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="farmer_location">Farmer Location</label>
                            <input type="text" id="farmer_location" name="farmer_location" value="<?php echo htmlspecialchars($product['farmer_location'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-success" style="flex: 1;">
                            <?php echo $action == 'add' ? 'Add Product' : 'Update Product'; ?>
                        </button>
                        <a href="products.php" class="btn btn-primary" style="flex: 1; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
