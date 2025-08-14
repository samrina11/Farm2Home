-- Add admin_notes column to orders table
ALTER TABLE orders ADD COLUMN admin_notes TEXT AFTER delivery_date;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT,
    message TEXT NOT NULL,
    type ENUM('order_placed', 'order_confirmed', 'order_shipped', 'order_delivered', 'order_cancelled') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert sample notifications for existing orders
INSERT INTO notifications (user_id, order_id, message, type) VALUES
(2, 1, 'Your order #1 has been placed successfully. Our team will contact you soon to confirm the order.', 'order_placed'),
(3, 2, 'Your order #2 has been confirmed by our team. We will contact you soon for delivery arrangements.', 'order_confirmed');
