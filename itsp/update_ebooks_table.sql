-- Add download_status column to ebooks table
ALTER TABLE ebooks ADD COLUMN download_status VARCHAR(20) DEFAULT 'Enabled';

-- Update existing records to have 'Enabled' download status
UPDATE ebooks SET download_status = 'Enabled' WHERE download_status IS NULL; 