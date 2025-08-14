<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

$message = '';
$error = '';

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_quantity') {
        $cart_id = $_POST['cart_id'];
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            // Remove item
            $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$cart_id, $user_id]);
            $message = 'Item removed from cart';
        } else {
            // Check stock availability
            $query = "SELECT p.stock_quantity FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.id = ? AND c.user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$cart_id, $user_id]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stock && $quantity <= $stock['stock_quantity']) {
                $query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$quantity, $cart_id, $user_id]);
                $message = 'Cart updated successfully';
            } else {
                $error = 'Insufficient stock available';
            }
        }
    } elseif ($action == 'remove_item') {
        $cart_id = $_POST['cart_id'];
        $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$cart_id, $user_id]);
        $message = 'Item removed from cart';
    } elseif ($action == 'clear_cart') {
        $query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $message = 'Cart cleared successfully';
    }
}

// Handle success messages from URL
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'item_added') {
        $message = 'Item added to cart successfully!';
    }
}

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.unit, p.image_url, p.stock_quantity, p.farmer_name 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ? AND p.status = 'active'
          ORDER BY c.added_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_charge = $subtotal > 500 ? 0 : 50; // Free delivery above ₹500
$total = $subtotal + $delivery_charge;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Farmers Marketplace</title>
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
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
        
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
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
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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
        
        /* Cart Layout */
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .cart-header {
            background: #2d5a27;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .cart-content {
            padding: 0;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #e1e5e9;
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
        }
        
        .item-details h3 {
            color: #2d5a27;
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .item-farmer {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: 600;
            font-size: 16px;
        }
        
        .item-stock {
            color: #28a745;
            font-size: 12px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 2px solid #2d5a27;
            background: white;
            color: #2d5a27;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #2d5a27;
            color: white;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-weight: 600;
        }
        
        .item-total {
            font-size: 18px;
            font-weight: 700;
            color: #2d5a27;
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .remove-btn:hover {
            background: #f8d7da;
        }
        
        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-header {
            background: #2d5a27;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
            border-radius: 15px 15px 0 0;
        }
        
        .summary-content {
            padding: 20px;
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
        
        .delivery-note {
            background: #e8f5e8;
            color: #2d5a27;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin: 15px 0;
            text-align: center;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .empty-cart h2 {
            color: #2d5a27;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .cart-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
                padding: 15px;
            }
            
            .item-image {
                width: 80px;
                height: 80px;
            }
            
            .quantity-controls,
            .item-total,
            .remove-btn {
                grid-column: 1 / -1;
                justify-self: start;
                margin-top: 10px;
            }
            
            .quantity-controls {
                justify-self: center;
            }
            
            .item-total {
                justify-self: end;
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
                <a href="cart.php" class="btn btn-outline">Cart (<?php echo count($cart_items); ?>)</a>
                <a href="profile.php" class="btn btn-primary">Profile</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <h1 class="page-title">Shopping Cart</h1>
        
        <?php if ($message): ?>
            <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (count($cart_items) > 0): ?>
            <div class="cart-container">
                <div class="cart-items">
                    <div class="cart-header">
                        Your Cart Items (<?php echo count($cart_items); ?> items)
                    </div>
                    <div class="cart-content">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="item-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url']); ?>')"></div>
                                
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-farmer">By <?php echo htmlspecialchars($item['farmer_name']); ?></div>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?>/<?php echo htmlspecialchars($item['unit']); ?></div>
                                    <div class="item-stock">Stock: <?php echo $item['stock_quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                                </div>
                                
                                <div class="quantity-controls">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                                        <button type="submit" class="quantity-btn">-</button>
                                    </form>
                                    
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input" onchange="this.form.submit()">
                                    </form>
                                    
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo min($item['stock_quantity'], $item['quantity'] + 1); ?>">
                                        <button type="submit" class="quantity-btn" <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>+</button>
                                    </form>
                                </div>
                                
                                <div class="item-total">
                                    ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                                
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="remove-btn" onclick="return confirm('Remove this item from cart?')" title="Remove item">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-header">
                        Order Summary
                    </div>
                    <div class="summary-content">
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
                        
                        <?php if ($delivery_charge > 0): ?>
                            <div class="delivery-note">
                                Add ₹<?php echo number_format(500 - $subtotal, 2); ?> more for FREE delivery!
                            </div>
                        <?php else: ?>
                            <div class="delivery-note">
                                🎉 You qualify for FREE delivery!
                            </div>
                        <?php endif; ?>
                        
                        <a href="checkout.php" class="btn btn-success checkout-btn">Proceed to Checkout</a>
                        
                        <div class="cart-actions">
                            <a href="products.php" class="btn btn-primary" style="flex: 1; text-align: center;">Continue Shopping</a>
                            <form method="POST" action="" style="flex: 1;">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-warning" style="width: 100%;" onclick="return confirm('Clear entire cart?')">Clear Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" class="btn btn-success" style="font-size: 18px; padding: 12px 30px;">Start Shopping</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
