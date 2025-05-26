-- Modify the existing users table to support multiple user types
ALTER TABLE users 
ADD COLUMN user_type ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer' AFTER role,
ADD COLUMN phone VARCHAR(20) AFTER email,
ADD COLUMN address TEXT AFTER phone,
ADD COLUMN profile_image VARCHAR(255) AFTER address,
ADD COLUMN reset_token VARCHAR(255) AFTER is_active,
ADD COLUMN reset_token_expiry DATETIME AFTER reset_token;

-- Create a table for user sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    last_activity DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    is_valid BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create a table for customer bookings history
CREATE TABLE IF NOT EXISTS customer_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Add a user_id field to the bookings table to link bookings to registered users
ALTER TABLE bookings
ADD COLUMN user_id INT AFTER booking_id,
ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- Create a permissions table for fine-grained access control
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- Create a role_permissions junction table
CREATE TABLE IF NOT EXISTS role_permissions (
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- Insert default permissions
INSERT INTO permissions (permission_name, description) VALUES
('manage_bookings', 'Create, view, edit and delete bookings'),
('manage_packages', 'Create, view, edit and delete packages'),
('manage_users', 'Create, view, edit and delete users'),
('manage_inventory', 'Manage inventory items'),
('view_reports', 'View business reports and analytics'),
('manage_content', 'Edit website content'),
('process_payments', 'Process and manage payments');

-- Assign permissions to roles
INSERT INTO role_permissions (role, permission_id) VALUES
('admin', 1), ('admin', 2), ('admin', 3), ('admin', 4), ('admin', 5), ('admin', 6), ('admin', 7),
('staff', 1), ('staff', 4), ('staff', 5);

-- Insert an admin user (password: Admin@123)
INSERT INTO users (username, password, email, full_name, role, user_type, is_active) VALUES
('admin', '$2y$10$8KGQFGDEQj.rl4xM.1.Cre6Zj5qYBGM7OJ7Wt5DVLOucSYJROxQHe', 'admin@breatheluxe.com', 'System Administrator', 'admin', 'admin', 1);
