<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$order_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'update_status') {
    $new_status = $_POST['status'];
    $delivery_date = $_POST['delivery_date'] ?? null;
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    $query = "UPDATE orders SET status = ?, delivery_date = ?, admin_notes = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$new_status, $delivery_date, $admin_notes, $order_id])) {
        
        // Get order details for notification
        $query = "SELECT user_id FROM orders WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create notification message based on status
        $notification_messages = [
            'confirmed' => "Great news! Your order #$order_id has been confirmed. Our team will contact you soon to arrange delivery.",
            'shipped' => "Your order #$order_id has been shipped and is on the way to your delivery address.",
            'delivered' => "Your order #$order_id has been successfully delivered. Thank you for choosing us!",
            'cancelled' => "We're sorry, but your order #$order_id has been cancelled. Our team will contact you with more details."
        ];
        
        if (isset($notification_messages[$new_status])) {
            $query = "INSERT INTO notifications (user_id, order_id, message, type) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_user['user_id'], $order_id, $notification_messages[$new_status], 'order_' . $new_status]);
        }
        
        if ($new_status == 'confirmed') {
            $query = "SELECT oi.product_id, oi.quantity FROM order_items oi WHERE oi.order_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($order_items as $item) {
                $query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }
        
        $message = 'Order status updated successfully and customer notified!';
    } else {
        $error = 'Failed to update order status';
    }
    $action = 'list';
}

// Get orders list
if ($action == 'list') {
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(u.full_name LIKE ? OR o.id = ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = intval($search);
    }
    
    $where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT o.*, u.full_name, u.email FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.order_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get order details
if ($action == 'view' && $order_id) {
    $query = "SELECT o.*, u.full_name, u.email, u.phone FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $query = "SELECT oi.*, p.name, p.image_url FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $action = 'list';
        $error = 'Order not found';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin Panel</title>
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
        
        /* Orders Table */
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
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 8px 16px;
            background: #bdc3c7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        
        .filter-tab.active,
        .filter-tab:hover {
            background: #2c3e50;
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
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-id {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .customer-info {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .customer-email {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .order-amount {
            font-weight: 600;
            color: #e74c3c;
            font-size: 16px;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Order Details */
        .order-details {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .order-header {
            background: #ecf0f1;
            padding: 20px;
            border-bottom: 1px solid #bdc3c7;
        }
        
        .order-content {
            padding: 30px;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #7f8c8d;
        }
        
        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        /* Order Items */
        .items-section {
            margin-top: 30px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            background-size: cover;
            background-position: center;
            border-radius: 5px;
        }
        
        .item-total {
            font-weight: 600;
            color: #e74c3c;
        }
        
        /* Status Update Form */
        .status-update {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .no-orders {
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
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
            }
            
            .order-info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .orders-table {
                font-size: 14px;
            }
            
            .orders-table th,
            .orders-table td {
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
                <a href="products.php">Products</a>
                <a href="categories.php">Categories</a>
                <a href="orders.php" class="active">Orders</a>
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
                <h1 class="page-title">Orders Management</h1>
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
                        <strong><?php echo count($orders); ?> Orders</strong>
                        <div class="filter-tabs" style="margin-top: 10px;">
                            <a href="orders.php" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
                            <a href="orders.php?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                            <a href="orders.php?status=confirmed" class="filter-tab <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                            <a href="orders.php?status=shipped" class="filter-tab <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">Shipped</a>
                            <a href="orders.php?status=delivered" class="filter-tab <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">Delivered</a>
                        </div>
                    </div>
                    <form method="GET" action="" class="search-form">
                        <?php if ($status_filter): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="btn btn-primary btn-small">Search</button>
                    </form>
                </div>
                
                <?php if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Delivery Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="order-id">#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="customer-info"><?php echo htmlspecialchars($order['full_name']); ?></div>
                                        <div class="customer-email"><?php echo htmlspecialchars($order['email']); ?></div>
                                    </td>
                                    <td class="order-amount">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php if ($order['delivery_date']): ?>
                                            <?php echo date('M j, Y', strtotime($order['delivery_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: #7f8c8d;">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-primary btn-small">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-orders">
                        <h3>No orders found</h3>
                        <p>Orders will appear here once customers start purchasing.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($action == 'view'): ?>
            <div class="page-header">
                <h1 class="page-title">Order Details #<?php echo $order['id']; ?></h1>
                <a href="orders.php" class="btn btn-primary">← Back to Orders</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="order-details">
                <div class="order-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Order #<?php echo $order['id']; ?></h2>
                        <span class="order-status status-<?php echo $order['status']; ?>" style="font-size: 14px; padding: 8px 16px;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-content">
                    <div class="order-info-grid">
                        <div class="info-section">
                            <h3>Customer Information</h3>
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Delivery Address:</span>
                                <span class="info-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h3>Order Information</h3>
                            <div class="info-item">
                                <span class="info-label">Order Date:</span>
                                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Amount:</span>
                                <span class="info-value" style="color: #e74c3c;">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Delivery Date:</span>
                                <span class="info-value">
                                    <?php if ($order['delivery_date']): ?>
                                        <?php echo date('F j, Y', strtotime($order['delivery_date'])); ?>
                                    <?php else: ?>
                                        Not set
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="items-section">
                        <h3>Order Items</h3>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="item-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url']); ?>')"></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="item-total">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="status-update">
                        <h3>Update Order Status</h3>
                        <form method="POST" action="orders.php?action=update_status&id=<?php echo $order['id']; ?>">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="delivery_date">Delivery Date</label>
                                    <input type="date" id="delivery_date" name="delivery_date" value="<?php echo $order['delivery_date']; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_notes">Admin Notes (Internal)</label>
                                <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about customer contact, payment arrangements, etc."><?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">Update Status & Notify Customer</button>
                                <a href="orders.php" class="btn btn-primary">Back to Orders</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
