-- Add missing columns to book_schedules table
ALTER TABLE book_schedules
ADD COLUMN IF NOT EXISTS purpose VARCHAR(255) NOT NULL DEFAULT 'Study',
ADD COLUMN IF NOT EXISTS due_date DATE,
MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending';

-- Add missing columns to borrowed_books table
ALTER TABLE borrowed_books
ADD COLUMN IF NOT EXISTS return_date DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS admin_confirmed_return TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS purpose VARCHAR(255) NOT NULL DEFAULT 'Study';

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE
);

-- Update existing status values in book_schedules
UPDATE book_schedules SET status = 'confirmed' WHERE status = 'approved'; 