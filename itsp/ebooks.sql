-- Create ebooks table
CREATE TABLE IF NOT EXISTS ebooks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample ebooks
INSERT INTO ebooks (title, author, category, description, cover_image, file_path, price) VALUES
('The Power of Interactive Magazine', 'Jane Smith', 'Technology', 'A comprehensive guide to creating engaging digital magazines.', 'interactive-magazine.jpg', 'ebooks/interactive-magazine.pdf', 9.99),
('Understanding AI and Machine Learning', 'John Doe', 'Technology', 'An introduction to artificial intelligence and machine learning concepts.', 'ai-ml.jpg', 'ebooks/ai-ml.pdf', 14.99),
('The Future of Space Exploration', 'Neil Armstrong', 'Science', 'Exploring the possibilities of space travel and colonization.', 'space-exploration.jpg', 'ebooks/space-exploration.pdf', 12.99),
('Healthy Living Guide', 'Dr. Emily Taylor', 'Health', 'A complete guide to maintaining a healthy lifestyle.', 'healthy-living.jpg', 'ebooks/healthy-living.pdf', 8.99),
('Mastering Web Development', 'Robert Green', 'Programming', 'Learn modern web development techniques and best practices.', 'web-dev.jpg', 'ebooks/web-dev.pdf', 19.99); 