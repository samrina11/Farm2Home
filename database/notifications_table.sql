-- Create notifications table for order status updates
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

-- Insert sample notifications
INSERT INTO notifications (user_id, order_id, message, type) VALUES
(2, 1, 'Your order #1 has been confirmed by our team. We will contact you soon for delivery arrangements.', 'order_confirmed'),
(2, 1, 'Your order #1 has been shipped and is on the way to your delivery address.', 'order_shipped');
