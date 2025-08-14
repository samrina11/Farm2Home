-- Insert sample data
USE farmers_marketplace;

-- Insert admin user and sample customers
INSERT INTO users (username, email, password, full_name, phone, address, user_type) VALUES
('admin', 'admin@farmersmarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '9876543210', 'Admin Office, Delhi', 'admin'),
('rajesh_farmer', 'rajesh@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Kumar', '9876543211', 'Village Rampur, Punjab', 'customer'),
('priya_customer', 'priya@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Sharma', '9876543212', 'Sector 15, Gurgaon', 'customer'),
('amit_buyer', 'amit@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Singh', '9876543213', 'Connaught Place, Delhi', 'customer');

-- Insert categories
INSERT INTO categories (name, description) VALUES
('Vegetables', 'Fresh vegetables directly from farms'),
('Fruits', 'Seasonal fresh fruits'),
('Leafy Greens', 'Fresh leafy vegetables and herbs'),
('Root Vegetables', 'Potatoes, carrots, radish and other root vegetables'),
('Seasonal Fruits', 'Seasonal and exotic fruits');

-- Insert sample products
INSERT INTO products (name, description, category_id, price, unit, stock_quantity, farmer_name, farmer_location, image_url) VALUES
-- Vegetables
('Fresh Tomatoes', 'Red ripe tomatoes, perfect for cooking', 1, 40.00, 'kg', 100, 'Ram Singh', 'Haryana', '/placeholder.svg?height=200&width=200'),
('Green Onions', 'Fresh green onions with leaves', 1, 30.00, 'kg', 50, 'Suresh Kumar', 'Punjab', '/placeholder.svg?height=200&width=200'),
('Bell Peppers', 'Colorful bell peppers - red, yellow, green', 1, 80.00, 'kg', 75, 'Mohan Lal', 'Himachal Pradesh', '/placeholder.svg?height=200&width=200'),
('Cauliflower', 'Fresh white cauliflower heads', 1, 35.00, 'piece', 60, 'Ravi Sharma', 'Punjab', '/placeholder.svg?height=200&width=200'),
('Brinjal (Eggplant)', 'Purple fresh brinjals', 1, 45.00, 'kg', 80, 'Vikram Singh', 'Haryana', '/placeholder.svg?height=200&width=200'),

-- Leafy Greens
('Spinach', 'Fresh green spinach leaves', 3, 25.00, 'kg', 40, 'Geeta Devi', 'Punjab', '/placeholder.svg?height=200&width=200'),
('Coriander', 'Fresh coriander leaves', 3, 20.00, 'bunch', 100, 'Sunita Sharma', 'Delhi', '/placeholder.svg?height=200&width=200'),
('Mint Leaves', 'Fresh mint leaves', 3, 15.00, 'bunch', 80, 'Kamala Devi', 'Uttar Pradesh', '/placeholder.svg?height=200&width=200'),
('Lettuce', 'Crispy lettuce leaves', 3, 50.00, 'kg', 30, 'Rajesh Kumar', 'Himachal Pradesh', '/placeholder.svg?height=200&width=200'),

-- Root Vegetables
('Potatoes', 'Fresh potatoes from hills', 4, 25.00, 'kg', 200, 'Hari Singh', 'Himachal Pradesh', '/placeholder.svg?height=200&width=200'),
('Carrots', 'Orange carrots, sweet and crunchy', 4, 55.00, 'kg', 90, 'Deepak Kumar', 'Punjab', '/placeholder.svg?height=200&width=200'),
('Radish', 'White radish with leaves', 4, 30.00, 'kg', 70, 'Mukesh Yadav', 'Haryana', '/placeholder.svg?height=200&width=200'),
('Sweet Potatoes', 'Sweet potatoes from organic farms', 4, 60.00, 'kg', 50, 'Anita Sharma', 'Punjab', '/placeholder.svg?height=200&width=200'),

-- Fruits
('Apples', 'Red delicious apples from Kashmir', 2, 120.00, 'kg', 150, 'Abdul Rahman', 'Kashmir', '/placeholder.svg?height=200&width=200'),
('Bananas', 'Yellow ripe bananas', 2, 50.00, 'dozen', 200, 'Raman Nair', 'Kerala', '/placeholder.svg?height=200&width=200'),
('Oranges', 'Juicy oranges from Nagpur', 2, 70.00, 'kg', 120, 'Sunil Patil', 'Maharashtra', '/placeholder.svg?height=200&width=200'),
('Mangoes', 'Alphonso mangoes - king of fruits', 5, 200.00, 'kg', 80, 'Krishna Murthy', 'Karnataka', '/placeholder.svg?height=200&width=200'),
('Grapes', 'Green seedless grapes', 2, 90.00, 'kg', 60, 'Vinod Sharma', 'Maharashtra', '/placeholder.svg?height=200&width=200'),
('Pomegranates', 'Red juicy pomegranates', 2, 150.00, 'kg', 40, 'Ashok Kumar', 'Rajasthan', '/placeholder.svg?height=200&width=200'),
('Lemons', 'Fresh lemons for cooking', 2, 60.00, 'kg', 100, 'Lakshmi Devi', 'Andhra Pradesh', '/placeholder.svg?height=200&width=200'),
('Guavas', 'Sweet guavas', 5, 80.00, 'kg', 70, 'Ramesh Gupta', 'Uttar Pradesh', '/placeholder.svg?height=200&width=200');

-- Insert sample orders
INSERT INTO orders (user_id, total_amount, status, delivery_address, phone, delivery_date) VALUES
(2, 285.00, 'delivered', 'Village Rampur, Punjab', '9876543211', '2024-01-15'),
(3, 420.00, 'shipped', 'Sector 15, Gurgaon', '9876543212', '2024-01-18'),
(4, 195.00, 'pending', 'Connaught Place, Delhi', '9876543213', '2024-01-20');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 2, 40.00),  -- 2kg Tomatoes
(1, 10, 3, 25.00), -- 3kg Potatoes
(1, 15, 1, 120.00), -- 1kg Apples
(1, 17, 1, 70.00),  -- 1kg Oranges
(2, 18, 2, 200.00), -- 2kg Mangoes
(2, 5, 1, 45.00),   -- 1kg Brinjal
(2, 11, 2, 55.00),  -- 2kg Carrots
(2, 19, 1, 90.00),  -- 1kg Grapes
(3, 6, 2, 25.00),   -- 2kg Spinach
(3, 16, 3, 50.00),  -- 3 dozen Bananas
(3, 21, 2, 60.00);  -- 2kg Lemons
