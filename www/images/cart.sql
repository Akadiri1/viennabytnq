DROP TABLE IF EXISTS cart_items;
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_token VARCHAR(255) NOT NULL, -- Replaced session_id
    user_id INT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    
    color_id INT NULL,
    custom_color_name VARCHAR(255) NULL,
    size_id INT NULL,
    custom_size_details TEXT NULL,
    
    price_at_time_of_add DECIMAL(10, 2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX cart_token_idx (cart_token), -- Add an index for faster lookups
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES panel_products(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES product_colors(id) ON DELETE SET NULL,
    FOREIGN KEY (size_id) REFERENCES product_sizes(id) ON DELETE SET NULL
);