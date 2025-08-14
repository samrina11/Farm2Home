-- Create the farmers marketplace database
CREATE DATABASE IF NOT EXISTS farmers_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create a user for the application (optional)
-- CREATE USER 'farmers_user'@'localhost' IDENTIFIED BY 'farmers_password';
-- GRANT ALL PRIVILEGES ON farmers_marketplace.* TO 'farmers_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Use the database
USE farmers_marketplace;

-- Show success message
SELECT 'Database farmers_marketplace created successfully!' as message;
