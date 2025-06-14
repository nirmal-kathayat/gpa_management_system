-- Insert additional schools
INSERT INTO schools (name, address, phone, email, created_at, updated_at) VALUES
('Kathmandu Model School', 'Kathmandu, Nepal', '01-4567891', 'info@kms.edu.np', NOW(), NOW()),
('Pokhara International School', 'Pokhara, Nepal', '061-123457', 'contact@pis.edu.np', NOW(), NOW()),
('Chitwan Academy', 'Chitwan, Nepal', '056-123456', 'admin@chitwan.edu.np', NOW(), NOW());

-- Insert more sample students
INSERT INTO students (name, class, section, roll_number, date_of_birth, father_name, mother_name, address, phone, school_id, created_at, updated_at) VALUES
('Sita Gurung', 'EIGHT', 'A', 5, '2009-07-12', 'Bir Bahadur Gurung', 'Kamala Gurung', 'Pokhara, Nepal', '9851234568', 2, NOW(), NOW()),
('Ram Shrestha', 'SEVEN', 'B', 20, '2010-02-28', 'Hari Shrestha', 'Gita Shrestha', 'Lalitpur, Nepal', '9861234568', 1, NOW(), NOW()),
('Maya Tamang', 'NINE', 'A', 3, '2008-11-15', 'Lal Bahadur Tamang', 'Devi Tamang', 'Sindhupalchok, Nepal', '9841234569', 3, NOW(), NOW()),
('Arjun Magar', 'SEVEN', 'A', 25, '2010-04-20', 'Tek Bahadur Magar', 'Sunita Magar', 'Dharan, Nepal', '9851234569', 1, NOW(), NOW()),
('Priya Karki', 'EIGHT', 'B', 18, '2009-09-08', 'Gopal Karki', 'Radha Karki', 'Butwal, Nepal', '9861234569', 2, NOW(), NOW());

-- Insert sample marks for additional students
INSERT INTO student_marks (student_id, subject_id, exam_type, theory_marks, practical_marks, total_marks, letter_grade, grade_point, academic_year, created_at, updated_at) VALUES
-- Sita Gurung (student_id = 4) - English I
(4, 1, 'final_terminal', 85, 0, 85, 'A', 3.6, '2024', NOW(), NOW()),
-- Ram Shrestha (student_id = 5) - Mathematics  
(5, 4, 'final_terminal', 92, 0, 92, 'A+', 4.0, '2024', NOW(), NOW()),
-- Maya Tamang (student_id = 6) - Science
(6, 5, 'final_terminal', 78, 18, 96, 'A+', 4.0, '2024', NOW(), NOW()),
-- Arjun Magar (student_id = 7) - Nepali
(7, 3, 'final_terminal', 88, 0, 88, 'A', 3.6, '2024', NOW(), NOW()),
-- Priya Karki (student_id = 8) - English II
(8, 2, 'final_terminal', 81, 0, 81, 'A', 3.6, '2024', NOW(), NOW());

-- Insert sample reports for additional students
INSERT INTO student_reports (student_id, academic_year, final_gpa, final_grade, position, attendance_days, total_days, remarks, class_response, discipline, leadership, neatness, punctuality, regularity, social_conduct, sports_game, issue_date, created_at, updated_at) VALUES
(4, '2024', 3.8, 'A', 2, 235, 240, 'Excellent performance in all subjects.', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', '2024-12-15', NOW(), NOW()),
(5, '2024', 3.9, 'A', 1, 238, 240, 'Outstanding student with excellent mathematical skills.', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'B', '2024-12-15', NOW(), NOW()),
(6, '2024', 3.7, 'A', 3, 230, 240, 'Very good performance, shows great potential.', 'A', 'A', 'B', 'A', 'A', 'A', 'A', 'A', '2024-12-15', NOW(), NOW());
