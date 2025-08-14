<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$order_id = $_GET['order_id'] ?? 0;
$user_id = getUserId();

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items
$query = "SELECT oi.*, p.name FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Farmers Marketplace</title>
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
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
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .success-header {
            background: #28a745;
            color: white;
            padding: 40px 20px;
        }
        
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .success-subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .success-content {
            padding: 40px;
        }
        
        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #2d5a27;
        }
        
        .order-items {
            margin-top: 20px;
        }
        
        .order-items h4 {
            color: #2d5a27;
            margin-bottom: 15px;
        }
        
        .item-list {
            list-style: none;
        }
        
        .item-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
        }
        
        .item-list li:last-child {
            border-bottom: none;
        }
        
        .next-steps {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .next-steps h3 {
            color: #2d5a27;
            margin-bottom: 15px;
        }
        
        .next-steps ul {
            color: #2d5a27;
            padding-left: 20px;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Added contact info section styling */
        .contact-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .contact-info h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-info p {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .contact-info strong {
            color: #2d5a27;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .success-header {
                padding: 30px 20px;
            }
            
            .success-icon {
                font-size: 48px;
            }
            
            .success-title {
                font-size: 24px;
            }
            
            .success-content {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
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
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">✅</div>
                <h1 class="success-title">Order Placed Successfully!</h1>
                <p class="success-subtitle">No payment required - We'll contact you soon</p>
            </div>
            
            <div class="success-content">
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">Order Number:</span>
                        <span class="detail-value">#<?php echo $order['id']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">Pending Confirmation</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Delivery Address:</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                    </div>
                    
                    <div class="order-items">
                        <h4>Items Ordered:</h4>
                        <ul class="item-list">
                            <?php foreach ($order_items as $item): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($item['name']); ?> (×<?php echo $item['quantity']; ?>)</span>
                                    <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Updated contact-based process information -->
                <div class="contact-info">
                    <h3>📞 What happens next?</h3>
                    <p><strong>Our team will contact you within 2-4 hours</strong> to:</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Confirm your order details and availability</li>
                        <li>Discuss payment options (Cash on Delivery available)</li>
                        <li>Schedule convenient delivery time</li>
                        <li>Answer any questions about your order</li>
                    </ul>
                    <p><strong>Contact Info:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
                
                <div class="next-steps">
                    <h3>Order Process</h3>
                    <ul>
                        <li><strong>Step 1:</strong> Order placed (Current)</li>
                        <li><strong>Step 2:</strong> Admin confirmation call</li>
                        <li><strong>Step 3:</strong> Fresh produce preparation</li>
                        <li><strong>Step 4:</strong> Delivery scheduling</li>
                        <li><strong>Step 5:</strong> Fresh delivery to your door</li>
                    </ul>
                </div>
                
                <div class="action-buttons">
                    <a href="products.php" class="btn btn-success">Continue Shopping</a>
                    <a href="profile.php" class="btn btn-primary">View Order Status</a>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
