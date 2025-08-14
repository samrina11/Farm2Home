<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Function to authenticate user
function authenticateUser($email, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, password, full_name, user_type FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

// Function to register new user
function registerUser($userData) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if username or email already exists
    $query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$userData['username'], $userData['email']]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Insert new user
    $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, email, password, full_name, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, 'customer')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        $userData['username'],
        $userData['email'],
        $hashed_password,
        $userData['full_name'],
        $userData['phone'],
        $userData['address']
    ])) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

// Function to get user profile
function getUserProfile($userId) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, full_name, phone, address, user_type, created_at FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update user profile
function updateUserProfile($userId, $userData) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        $userData['full_name'],
        $userData['phone'],
        $userData['address'],
        $userId
    ]);
}
?>
