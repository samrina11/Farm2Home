<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total products
$query = "SELECT COUNT(*) as count FROM products WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total users
$query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total orders
$query = "SELECT COUNT(*) as count FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue
$query = "SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// Recent orders
$query = "SELECT o.*, u.full_name FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          ORDER BY o.order_date DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$query = "SELECT * FROM products WHERE stock_quantity < 10 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Farmers Marketplace</title>
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
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #2c3e50;
        }
        
        /* Main Content */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .page-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 16px;
            font-weight: 500;
        }
        
        .stat-products { border-left: 4px solid #27ae60; }
        .stat-users { border-left: 4px solid #3498db; }
        .stat-orders { border-left: 4px solid #f39c12; }
        .stat-revenue { border-left: 4px solid #e74c3c; }
        
        /* Dashboard Sections */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #ecf0f1;
            padding: 20px;
            border-bottom: 1px solid #bdc3c7;
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .section-content {
            padding: 20px;
        }
        
        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Low Stock List */
        .stock-list {
            list-style: none;
        }
        
        .stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .stock-item:last-child {
            border-bottom: none;
        }
        
        .stock-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .stock-quantity {
            background: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 40px 20px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-nav {
                display: none;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .quick-actions {
                flex-direction: column;
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="products.php">Products</a>
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
        <h1 class="page-title">Dashboard Overview</h1>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="products.php?action=add" class="btn btn-success">Add New Product</a>
            <a href="categories.php?action=add" class="btn btn-warning">Add Category</a>
            <a href="orders.php" class="btn btn-primary">Manage Orders</a>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-products">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            
            <div class="stat-card stat-users">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            
            <div class="stat-card stat-orders">
                <div class="stat-icon">🛒</div>
                <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card stat-revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-number">₹<?php echo number_format($stats['revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                </div>
                <div class="section-content">
                    <?php if (count($recent_orders) > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="orders.php" class="btn btn-primary">View All Orders</a>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <h3>No orders yet</h3>
                            <p>Orders will appear here once customers start purchasing.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Low Stock Alert</h2>
                </div>
                <div class="section-content">
                    <?php if (count($low_stock) > 0): ?>
                        <ul class="stock-list">
                            <?php foreach ($low_stock as $product): ?>
                                <li class="stock-item">
                                    <div>
                                        <div class="stock-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div style="font-size: 12px; color: #7f8c8d;">
                                            <?php echo htmlspecialchars($product['farmer_name']); ?>
                                        </div>
                                    </div>
                                    <div class="stock-quantity">
                                        <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="products.php?filter=low_stock" class="btn btn-warning">Manage Stock</a>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <h3>All products well stocked!</h3>
                            <p>No products are running low on stock.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
