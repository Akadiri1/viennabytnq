-- ========================
-- DISCOUNTS TABLE SETUP
-- ========================
-- Run this SQL to create the discounts table for discount codes management

CREATE TABLE IF NOT EXISTS discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    max_uses INT DEFAULT 0,
    times_used INT DEFAULT 0,
    expiry_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add discount_code column to orders table to track which code was used
ALTER TABLE orders ADD COLUMN discount_code VARCHAR(50) NULL AFTER discount_amount;

-- Sample discount codes (optional - remove if not needed)
INSERT INTO discounts (code, discount_type, discount_value, min_order_amount, max_uses, is_active) VALUES
('WELCOME10', 'percentage', 10.00, 50000, 100, 1),
('FLAT5000', 'fixed', 5000.00, 30000, 50, 1);
