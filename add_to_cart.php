<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$product_id = $_POST['product_id'] ?? $_GET['id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;
$user_id = getUserId();

if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Get product details
$query = "SELECT * FROM products WHERE id = ? AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit();
}

// Check if product already in cart
$query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id, $product_id]);
$existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_item) {
    // Update quantity
    $new_quantity = $existing_item['quantity'] + $quantity;
    
    // Check stock availability
    if ($new_quantity > $product['stock_quantity']) {
        header('Location: product.php?id=' . $product_id . '&error=insufficient_stock');
        exit();
    }
    
    $query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$new_quantity, $user_id, $product_id]);
} else {
    // Check stock availability
    if ($quantity > $product['stock_quantity']) {
        header('Location: product.php?id=' . $product_id . '&error=insufficient_stock');
        exit();
    }
    
    // Add new item to cart
    $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $product_id, $quantity]);
}

header('Location: cart.php?success=item_added');
exit();
?>
