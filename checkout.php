<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

$error = '';
$success = '';

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.unit, p.stock_quantity 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ? AND p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($cart_items) == 0) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_charge = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $delivery_charge;

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = trim($_POST['delivery_address']);
    $phone = trim($_POST['phone']);
    
    if (empty($delivery_address) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $db->beginTransaction();
            
            // Check stock availability for all items
            foreach ($cart_items as $item) {
                $query = "SELECT stock_quantity FROM products WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$item['product_id']]);
                $current_stock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];
                
                if ($current_stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for " . $item['name']);
                }
            }
            
            $query = "INSERT INTO orders (user_id, total_amount, delivery_address, phone, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $total, $delivery_address, $phone]);
            $order_id = $db->lastInsertId();
            
            // Add order items (don't update stock until admin confirms)
            foreach ($cart_items as $item) {
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            $query = "INSERT INTO notifications (user_id, order_id, message, type) VALUES (?, ?, ?, 'order_placed')";
            $notification_message = "Your order #$order_id has been placed successfully. Our team will contact you soon to confirm the order.";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $order_id, $notification_message]);
            
            // Clear cart
            $query = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            
            $db->commit();
            
            header('Location: order_success.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Farmers Marketplace</title>
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
        
        .breadcrumb {
            text-align: center;
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
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Checkout Layout */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .checkout-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #2d5a27;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .section-content {
            padding: 30px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d5a27;
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
        
        .required {
            color: #e74c3c;
        }
        
        /* Order Summary */
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #2d5a27;
            margin-bottom: 5px;
        }
        
        .item-details {
            font-size: 14px;
            color: #666;
        }
        
        .item-total {
            font-weight: 600;
            color: #e74c3c;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-label {
            color: #666;
            font-weight: 500;
        }
        
        .summary-value {
            font-weight: 600;
            color: #333;
        }
        
        .total-row {
            font-size: 20px;
            color: #2d5a27;
            font-weight: 700;
        }
        
        .place-order-btn {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 20px;
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
        <h1 class="page-title">Checkout</h1>
        
        <div class="breadcrumb">
            <a href="cart.php">Cart</a> > Checkout
        </div>
        
        <?php if ($error): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="checkout-container">
                <div class="checkout-section">
                    <div class="section-header">
                        Delivery Information
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly style="background: #f8f9fa;">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: #f8f9fa;">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_address">Delivery Address <span class="required">*</span></label>
                            <textarea id="delivery_address" name="delivery_address" required placeholder="Enter complete delivery address with landmarks"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-section">
                    <div class="section-header">
                        Order Summary
                    </div>
                    <div class="section-content">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-details">
                                        ₹<?php echo number_format($item['price'], 2); ?>/<?php echo htmlspecialchars($item['unit']); ?> × <?php echo $item['quantity']; ?>
                                    </div>
                                </div>
                                <div class="item-total">
                                    ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e1e5e9;">
                            <div class="summary-row">
                                <span class="summary-label">Subtotal:</span>
                                <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span class="summary-label">Delivery Charge:</span>
                                <span class="summary-value">
                                    <?php if ($delivery_charge > 0): ?>
                                        ₹<?php echo number_format($delivery_charge, 2); ?>
                                    <?php else: ?>
                                        <span style="color: #28a745;">FREE</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="summary-row total-row">
                                <span class="summary-label">Total:</span>
                                <span class="summary-value">₹<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success place-order-btn">Place Order (No Payment Required)</button>
                        
                        <div style="text-align: center; margin-top: 15px; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #2d5a27;">
                            <strong>📞 Contact-Based Ordering</strong><br>
                            <small>After placing your order, our team will contact you to confirm details and arrange delivery. No advance payment required!</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</body>
</html>
