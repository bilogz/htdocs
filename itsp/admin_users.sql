-- Create admin_users table
CREATE TABLE admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create admin_roles table for role-based access control
CREATE TABLE admin_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Create admin_user_roles table for many-to-many relationship
CREATE TABLE admin_user_roles (
    admin_id INT,
    role_id INT,
    PRIMARY KEY (admin_id, role_id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES admin_roles(role_id) ON DELETE CASCADE
);

-- Insert default admin roles
INSERT INTO admin_roles (role_name, description) VALUES
('super_admin', 'Full access to all features'),
('book_manager', 'Can manage books and their status'),
('user_manager', 'Can manage user accounts'),
('reports_viewer', 'Can view reports and statistics');

-- Insert default super admin user (password: Admin@123)
INSERT INTO admin_users (username, password, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@library.com');

-- Insert new admin user (username: admin, password: admin)
INSERT INTO admin_users (username, password, full_name, email) VALUES
('admin2', '$2y$10$8K1p/a0dR1b5Y1J1p5Y1J1p5Y1J1p5Y1J1p5Y1J1p5Y1J1p5Y1J1p5Y', 'Library Administrator', 'admin2@library.com');

-- Assign super admin role to both admin users
INSERT INTO admin_user_roles (admin_id, role_id)
SELECT 
    (SELECT admin_id FROM admin_users WHERE username = 'admin'),
    (SELECT role_id FROM admin_roles WHERE role_name = 'super_admin');

INSERT INTO admin_user_roles (admin_id, role_id)
SELECT 
    (SELECT admin_id FROM admin_users WHERE username = 'admin2'),
    (SELECT role_id FROM admin_roles WHERE role_name = 'super_admin');

-- Create admin_activity_log table for tracking admin actions
CREATE TABLE admin_activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL
);

-- Create admin_settings table for system settings
CREATE TABLE admin_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO admin_settings (setting_key, setting_value, description) VALUES
('max_books_per_user', '5', 'Maximum number of books a user can borrow at once'),
('borrow_duration_days', '14', 'Default number of days a book can be borrowed'),
('overdue_fee_per_day', '1.00', 'Fee charged per day for overdue books'),
('maintenance_mode', 'false', 'System maintenance mode status');

SHOW CREATE TABLE admin_users; 