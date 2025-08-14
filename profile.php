<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'auth/check_auth.php';

requireLogin();

$user_id = getUserId();
$user = getUserProfile($user_id);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        if (empty($full_name)) {
            $error = 'Full name is required';
        } else {
            $userData = [
                'full_name' => $full_name,
                'phone' => $phone,
                'address' => $address
            ];
            
            if (updateUserProfile($user_id, $userData)) {
                $success = 'Profile updated successfully!';
                $user = getUserProfile($user_id); // Refresh user data
                $_SESSION['full_name'] = $full_name; // Update session
            } else {
                $error = 'Failed to update profile';
            }
        }
    } elseif ($action == 'mark_notification_read') {
        $notification_id = $_POST['notification_id'];
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$notification_id, $user_id]);
    }
}

// Get user's recent orders and notifications
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Farmers Marketplace</title>
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
        
        /* Updated grid layout for three columns */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        
        .profile-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 24px;
            color: #2d5a27;
            margin-bottom: 20px;
            border-bottom: 2px solid #e8f5e8;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
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
        .form-group textarea:focus {
            outline: none;
            border-color: #2d5a27;
        }
        
        .form-group input[readonly] {
            background: #f8f9fa;
            color: #666;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #363;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
        
        .user-info {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #d4edda;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #2d5a27;
        }
        
        .info-value {
            color: #333;
        }
        
        .orders-list {
            list-style: none;
        }
        
        .order-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #2d5a27;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            font-weight: 600;
            color: #2d5a27;
        }
        
        .order-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-details {
            color: #666;
            font-size: 14px;
        }
        
        .order-amount {
            font-weight: 600;
            color: #e74c3c;
            font-size: 16px;
        }
        
        .no-orders {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        /* Added notification styles */
        .notifications-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #2d5a27;
            position: relative;
        }
        
        .notification-item.unread {
            background: #e8f5e8;
            border-left-color: #28a745;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .notification-type {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .type-order_placed { background: #fff3cd; color: #856404; }
        .type-order_confirmed { background: #d1ecf1; color: #0c5460; }
        .type-order_shipped { background: #d4edda; color: #155724; }
        .type-order_delivered { background: #d4edda; color: #155724; }
        .type-order_cancelled { background: #f8d7da; color: #721c24; }
        
        .notification-message {
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .notification-time {
            color: #666;
            font-size: 12px;
        }
        
        .mark-read-btn {
            background: none;
            border: none;
            color: #2d5a27;
            font-size: 12px;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .no-notifications {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .profile-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                <span>Welcome, <?php echo htmlspecialchars(getUserName()); ?>!</span>
                <a href="cart.php" class="btn btn-outline">Cart</a>
                <a href="profile.php" class="btn btn-primary">Profile</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <h1 class="page-title">My Profile</h1>
        
        <div class="profile-container">
            <!-- Profile Information -->
            <div class="profile-section">
                <h2 class="section-title">👤 Profile Information</h2>
                
                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Profile</button>
                </form>
            </div>
            
            <!-- Recent Orders -->
            <div class="profile-section">
                <h2 class="section-title">📦 Recent Orders</h2>
                
                <?php if (count($recent_orders) > 0): ?>
                    <ul class="orders-list">
                        <?php foreach ($recent_orders as $order): ?>
                            <li class="order-item">
                                <div class="order-header">
                                    <span class="order-id">Order #<?php echo $order['id']; ?></span>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-details">
                                    <div>Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
                                    <div class="order-amount">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                    <?php if ($order['delivery_date']): ?>
                                        <div>Delivery: <?php echo date('F j, Y', strtotime($order['delivery_date'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="orders.php" class="btn btn-outline" style="width: 100%; text-align: center;">View All Orders</a>
                <?php else: ?>
                    <div class="no-orders">
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here!</p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 15px;">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Added notifications section -->
            <div class="profile-section">
                <h2 class="section-title">
                    🔔 Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h2>
                
                <?php if (count($notifications) > 0): ?>
                    <ul class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                <div class="notification-header">
                                    <span class="notification-type type-<?php echo $notification['type']; ?>">
                                        <?php echo str_replace('_', ' ', $notification['type']); ?>
                                    </span>
                                    <?php if (!$notification['is_read']): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_notification_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="mark-read-btn">Mark as read</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-time">
                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-notifications">
                        <h3>No notifications yet</h3>
                        <p>Order updates and important messages will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
