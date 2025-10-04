-- Create database
CREATE DATABASE IF NOT EXISTS `e-veikalsDB`;
USE `e-veikalsDB`;

-- Create items table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    likes INT DEFAULT 0,
    views INT DEFAULT 0,
    price DECIMAL(10, 2),
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO items (title, description, image_url, likes, views, price, category) VALUES
('Premium Headphones', 'High-quality wireless headphones with noise cancellation', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400', 245, 1250, 199.99, 'Electronics'),
('Modern Laptop', 'Sleek and powerful laptop for professionals', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400', 189, 890, 1299.99, 'Electronics'),
('Stylish Watch', 'Classic timepiece with modern features', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400', 156, 675, 299.99, 'Accessories'),
('Coffee Maker', 'Professional grade espresso machine', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400', 98, 432, 599.99, 'Home & Kitchen'),
('Running Shoes', 'Comfortable athletic shoes for daily wear', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 267, 1456, 129.99, 'Fashion'),
('Gaming Chair', 'Ergonomic chair designed for long gaming sessions', 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=400', 134, 789, 349.99, 'Furniture');
