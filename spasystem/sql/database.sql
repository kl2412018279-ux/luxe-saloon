CREATE DATABASE IF NOT EXISTS salon_booking;
USE salon_booking;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    duration INT,
    image_icon VARCHAR(50),
    is_active TINYINT DEFAULT 1
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_no VARCHAR(20) UNIQUE,
    user_id INT,
    service_id INT,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    booking_date DATE,
    booking_time TIME,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Backup logs
CREATE TABLE backup_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_file VARCHAR(255),
    record_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data (password = "password123")
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Admin', 'admin@salon.com', 'admin'),
('emma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Wilson', 'emma@email.com', 'customer'),
('lisa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Brown', 'lisa@email.com', 'customer');

-- Insert services
INSERT INTO services (name, description, price, duration, image_icon) VALUES
('Luxury Haircut', 'Professional haircut with styling and blow-dry', 45.00, 45, 'fa-cut'),
('Hair Coloring', 'Full color or highlights with premium products', 120.00, 90, 'fa-palette'),
('Spa Manicure', 'Nail shaping, cuticle care, massage and polish', 35.00, 45, 'fa-hand-peace'),
('Royal Facial', 'Deep cleansing, exfoliation, mask and massage', 85.00, 60, 'fa-smile'),
('Hot Stone Massage', 'Relaxing full body massage with hot stones', 110.00, 60, 'fa-spa'),
('Bridal Package', 'Complete bridal makeup + hair + nails', 350.00, 180, 'fa-crown');

-- Sample bookings
INSERT INTO bookings (booking_no, user_id, service_id, customer_name, customer_email, booking_date, booking_time, status) VALUES
('BK-1001', 2, 1, 'Emma Wilson', 'emma@email.com', CURDATE(), '10:00:00', 'confirmed'),
('BK-1002', 2, 3, 'Emma Wilson', 'emma@email.com', CURDATE(), '14:30:00', 'pending'),
('BK-1003', 3, 4, 'Lisa Brown', 'lisa@email.com', CURDATE(), '11:00:00', 'confirmed');