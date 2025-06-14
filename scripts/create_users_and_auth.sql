-- Run the user seeder after migrations
-- This will create default users for testing

-- Default users created:
-- Admin: admin@gpa-system.com / admin123
-- Teacher: teacher@school.com / teacher123  
-- Staff: staff@school.com / staff123

-- You can also create additional users manually:
INSERT INTO users (name, email, password, role, school_id, phone, address, is_active, email_verified_at, created_at, updated_at) VALUES
('Principal Smith', 'principal@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '9841111111', 'Kathmandu, Nepal', 1, NOW(), NOW(), NOW()),
('Math Teacher', 'math@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '9842222222', 'Lalitpur, Nepal', 1, NOW(), NOW(), NOW());

-- Note: The password hash above is for 'password' - change it in production
