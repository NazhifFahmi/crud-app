-- sql/init.sql
CREATE DATABASE IF NOT EXISTS crud_db;
USE crud_db;

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table (enhanced)
CREATE TABLE IF NOT EXISTS employees (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    position VARCHAR(100),
    department_id INT(11),
    salary DECIMAL(12,2),
    hire_date DATE,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    address TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('planning', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
    budget DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employee Projects junction table
CREATE TABLE IF NOT EXISTS employee_projects (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(11),
    project_id INT(11),
    role VARCHAR(100),
    assigned_date DATE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    employee_id INT(11),
    date DATE,
    check_in TIME,
    check_out TIME,
    break_time INT DEFAULT 0,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert sample departments
INSERT INTO departments (name, description) VALUES
('Human Resources', 'Manages employee relations and company policies'),
('Information Technology', 'Handles all technology and software development'),
('Finance', 'Manages company finances and accounting'),
('Marketing', 'Handles marketing and promotional activities'),
('Operations', 'Manages day-to-day business operations');

-- Insert sample employees
INSERT INTO employees (employee_id, first_name, last_name, email, phone, position, department_id, salary, hire_date, address) VALUES
('EMP001', 'John', 'Doe', 'john.doe@company.com', '081234567890', 'Software Engineer', 2, 8000000.00, '2023-01-15', 'Jl. Merdeka No. 123, Jakarta'),
('EMP002', 'Jane', 'Smith', 'jane.smith@company.com', '081234567891', 'HR Manager', 1, 12000000.00, '2022-06-01', 'Jl. Sudirman No. 456, Jakarta'),
('EMP003', 'Bob', 'Johnson', 'bob.johnson@company.com', '081234567892', 'Marketing Specialist', 4, 7000000.00, '2023-03-10', 'Jl. Thamrin No. 789, Jakarta'),
('EMP004', 'Alice', 'Wilson', 'alice.wilson@company.com', '081234567893', 'Finance Analyst', 3, 9000000.00, '2022-11-20', 'Jl. Gatot Subroto No. 321, Jakarta'),
('EMP005', 'Charlie', 'Brown', 'charlie.brown@company.com', '081234567894', 'Operations Manager', 5, 15000000.00, '2021-08-05', 'Jl. Rasuna Said No. 654, Jakarta');

-- Insert sample projects
INSERT INTO projects (name, description, start_date, end_date, status, budget) VALUES
('Website Redesign', 'Complete redesign of company website', '2024-01-01', '2024-06-30', 'in_progress', 50000000.00),
('Mobile App Development', 'Development of company mobile application', '2024-02-15', '2024-12-31', 'planning', 150000000.00),
('Marketing Campaign Q2', 'Second quarter marketing campaign', '2024-04-01', '2024-06-30', 'in_progress', 25000000.00);

-- Insert sample employee-project assignments
INSERT INTO employee_projects (employee_id, project_id, role, assigned_date) VALUES
(1, 1, 'Lead Developer', '2024-01-01'),
(1, 2, 'Senior Developer', '2024-02-15'),
(3, 3, 'Campaign Manager', '2024-04-01'),
(4, 1, 'Budget Analyst', '2024-01-01');

-- Insert sample attendance
INSERT INTO attendance (employee_id, date, check_in, check_out, status) VALUES
(1, '2024-06-10', '08:00:00', '17:00:00', 'present'),
(2, '2024-06-10', '08:15:00', '17:00:00', 'late'),
(3, '2024-06-10', '08:00:00', '17:00:00', 'present'),
(4, '2024-06-10', '08:00:00', '17:00:00', 'present'),
(5, '2024-06-10', '08:00:00', '17:00:00', 'present');