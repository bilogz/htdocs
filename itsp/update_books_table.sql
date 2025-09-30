-- Add status column to books table
ALTER TABLE books ADD COLUMN status VARCHAR(20) DEFAULT 'Available';

-- Update existing records to have 'Available' status
UPDATE books SET status = 'Available' WHERE status IS NULL; 