-- Create database if not exists
CREATE DATABASE IF NOT EXISTS payroll_system;
USE payroll_system;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    job_title VARCHAR(100) NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payroll table
CREATE TABLE IF NOT EXISTS payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    pay_date DATE NOT NULL,
    gross_salary DECIMAL(10,2) NOT NULL,
    deductions DECIMAL(10,2) NOT NULL DEFAULT 0,
    bonuses DECIMAL(10,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$8i5Oo58LJqVN0qQzS5YwWuHY.pMfF8.38zXWGT.bGNxo/kqprSAfi', 'admin');

-- Insert sample employees
INSERT INTO employees (first_name, last_name, email, phone, job_title, base_salary) VALUES
('John', 'Doe', 'john.doe@example.com', '1234567890', 'Software Engineer', 75000.00),
('Jane', 'Smith', 'jane.smith@example.com', '0987654321', 'Project Manager', 85000.00),
('Mike', 'Johnson', 'mike.johnson@example.com', '5555555555', 'Designer', 65000.00);

-- Insert sample payroll records
INSERT INTO payroll (employee_id, pay_date, gross_salary, deductions, bonuses, net_salary) VALUES
(1, '2024-01-01', 6250.00, 1000.00, 500.00, 5750.00),
(2, '2024-01-01', 7083.33, 1200.00, 0.00, 5883.33),
(3, '2024-01-01', 5416.67, 800.00, 300.00, 4916.67);
