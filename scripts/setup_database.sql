-- Create database
CREATE DATABASE IF NOT EXISTS gpa_system;
USE gpa_system;

-- Insert sample school data
INSERT INTO schools (name, address, phone, email, created_at, updated_at) VALUES
('Seo Jang English Boarding Model School', 'Kathmandu, Nepal', '01-4567890', 'info@seojang.edu.np', NOW(), NOW()),
('Himalayan International School', 'Pokhara, Nepal', '061-123456', 'contact@himalayan.edu.np', NOW(), NOW());

-- Insert sample student data
INSERT INTO students (name, class, section, roll_number, date_of_birth, father_name, mother_name, address, phone, school_id, created_at, updated_at) VALUES
('Prabinman Rai', 'SEVEN', 'B', 15, '2010-05-15', 'Ram Bahadur Rai', 'Sita Rai', 'Dharan, Nepal', '9841234567', 1, NOW(), NOW()),
('Anita Sharma', 'EIGHT', 'A', 12, '2009-08-20', 'Krishna Sharma', 'Maya Sharma', 'Kathmandu, Nepal', '9851234567', 1, NOW(), NOW()),
('Bikash Thapa', 'SEVEN', 'A', 8, '2010-03-10', 'Gopal Thapa', 'Kamala Thapa', 'Lalitpur, Nepal', '9861234567', 1, NOW(), NOW());
