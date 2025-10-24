-- Database setup for country-specific shipping fees
-- Run this SQL to set up the shipping_fees table with country support

-- First, add country_code column to existing shipping_fees table (if it doesn't exist)
ALTER TABLE shipping_fees ADD COLUMN country_code VARCHAR(2) DEFAULT 'NG';
ALTER TABLE shipping_fees ADD COLUMN currency VARCHAR(3) DEFAULT 'NGN';
ALTER TABLE shipping_fees ADD COLUMN description TEXT;

-- Update existing records to be Nigeria-specific
UPDATE shipping_fees SET country_code = 'NG' WHERE country_code IS NULL;

-- Sample data for different countries
-- Nigeria (existing data - update with country_code)
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Lagos', 3500.00, 'NG', 'NGN', 1, 'Standard delivery within Lagos'),
('Abuja', 5000.00, 'NG', 'NGN', 1, 'Standard delivery to Abuja'),
('Port Harcourt', 4500.00, 'NG', 'NGN', 1, 'Standard delivery to Port Harcourt'),
('Kano', 6000.00, 'NG', 'NGN', 1, 'Standard delivery to Kano');

-- United States
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('New York', 25.00, 'US', 'USD', 1, 'Standard shipping to New York'),
('California', 30.00, 'US', 'USD', 1, 'Standard shipping to California'),
('Texas', 28.00, 'US', 'USD', 1, 'Standard shipping to Texas'),
('Florida', 26.00, 'US', 'USD', 1, 'Standard shipping to Florida');

-- United Kingdom
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('London', 20.00, 'GB', 'GBP', 1, 'Standard shipping to London'),
('Manchester', 22.00, 'GB', 'GBP', 1, 'Standard shipping to Manchester'),
('Birmingham', 21.00, 'GB', 'GBP', 1, 'Standard shipping to Birmingham');

-- Canada
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Toronto', 35.00, 'CA', 'CAD', 1, 'Standard shipping to Toronto'),
('Vancouver', 40.00, 'CA', 'CAD', 1, 'Standard shipping to Vancouver'),
('Montreal', 38.00, 'CA', 'CAD', 1, 'Standard shipping to Montreal');

-- Germany
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Berlin', 25.00, 'DE', 'EUR', 1, 'Standard shipping to Berlin'),
('Munich', 28.00, 'DE', 'EUR', 1, 'Standard shipping to Munich'),
('Hamburg', 26.00, 'DE', 'EUR', 1, 'Standard shipping to Hamburg');

-- Australia
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Sydney', 45.00, 'AU', 'AUD', 1, 'Standard shipping to Sydney'),
('Melbourne', 47.00, 'AU', 'AUD', 1, 'Standard shipping to Melbourne'),
('Brisbane', 46.00, 'AU', 'AUD', 1, 'Standard shipping to Brisbane');

-- France
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Paris', 22.00, 'FR', 'EUR', 1, 'Standard shipping to Paris'),
('Lyon', 24.00, 'FR', 'EUR', 1, 'Standard shipping to Lyon'),
('Marseille', 25.00, 'FR', 'EUR', 1, 'Standard shipping to Marseille');

-- South Africa
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Cape Town', 180.00, 'ZA', 'ZAR', 1, 'Standard shipping to Cape Town'),
('Johannesburg', 200.00, 'ZA', 'ZAR', 1, 'Standard shipping to Johannesburg'),
('Durban', 190.00, 'ZA', 'ZAR', 1, 'Standard shipping to Durban');

-- Kenya
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Nairobi', 1200.00, 'KE', 'KES', 1, 'Standard shipping to Nairobi'),
('Mombasa', 1400.00, 'KE', 'KES', 1, 'Standard shipping to Mombasa'),
('Kisumu', 1300.00, 'KE', 'KES', 1, 'Standard shipping to Kisumu');

-- Ghana
INSERT INTO shipping_fees (location_name, fee, country_code, currency, is_active, description) VALUES
('Accra', 45.00, 'GH', 'GHS', 1, 'Standard shipping to Accra'),
('Kumasi', 50.00, 'GH', 'GHS', 1, 'Standard shipping to Kumasi'),
('Tamale', 55.00, 'GH', 'GHS', 1, 'Standard shipping to Tamale');

-- Create an index for better performance
CREATE INDEX idx_shipping_fees_country ON shipping_fees(country_code, is_active);
