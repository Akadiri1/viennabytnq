-- Add stock_quantity to Main Products Table
ALTER TABLE panel_products ADD COLUMN stock_quantity INT DEFAULT 0;

-- Add stock_quantity to Variants Table
ALTER TABLE product_price_variants ADD COLUMN stock_quantity INT DEFAULT 0;
