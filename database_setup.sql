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
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    isAdmin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO items (title, description, image_url, likes, views, category) VALUES
('Premium Headphones', 'High-quality wireless headphones with noise cancellation', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400', 245, 1250, 'Electronics'),
('Modern Laptop', 'Sleek and powerful laptop for professionals', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400', 189, 890, 'Electronics'),
('Stylish Watch', 'Classic timepiece with modern features', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400', 156, 675, 'Accessories');

INSERT INTO users (username, email, password_hash, isAdmin) VALUES
('admin', 'admin@example.com', '$2y$10$EIXZQ1jQ1jQ1jQ1jQ1jQ1u', TRUE),
('user1', 'user1@example.com', '$2y$10$EIXZQ1jQ1jQ1jQ1jQ1jQ1u', FALSE);