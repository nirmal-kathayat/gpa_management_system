-- Insert sample marks for Prabinman Rai (student_id = 1)
INSERT INTO student_marks (student_id, subject_id, exam_type, theory_marks, practical_marks, total_marks, letter_grade, grade_point, academic_year, created_at, updated_at) VALUES
-- English I
(1, 1, 'first_terminal', 75, 0, 75, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 1, 'second_terminal', 78, 0, 78, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 1, 'final_terminal', 82, 0, 82, 'A', 3.6, '2024', NOW(), NOW()),
(1, 1, 'pre_board', 80, 0, 80, 'A', 3.6, '2024', NOW(), NOW()),

-- English II
(1, 2, 'first_terminal', 72, 0, 72, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 2, 'second_terminal', 76, 0, 76, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 2, 'final_terminal', 79, 0, 79, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 2, 'pre_board', 77, 0, 77, 'B+', 3.2, '2024', NOW(), NOW()),

-- Nepali
(1, 3, 'first_terminal', 85, 0, 85, 'A', 3.6, '2024', NOW(), NOW()),
(1, 3, 'second_terminal', 87, 0, 87, 'A', 3.6, '2024', NOW(), NOW()),
(1, 3, 'final_terminal', 90, 0, 90, 'A+', 4.0, '2024', NOW(), NOW()),
(1, 3, 'pre_board', 88, 0, 88, 'A', 3.6, '2024', NOW(), NOW()),

-- Mathematics
(1, 4, 'first_terminal', 68, 0, 68, 'B', 2.8, '2024', NOW(), NOW()),
(1, 4, 'second_terminal', 71, 0, 71, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 4, 'final_terminal', 74, 0, 74, 'B+', 3.2, '2024', NOW(), NOW()),
(1, 4, 'pre_board', 72, 0, 72, 'B+', 3.2, '2024', NOW(), NOW()),

-- Science
(1, 5, 'first_terminal', 80, 15, 95, 'A+', 4.0, '2024', NOW(), NOW()),
(1, 5, 'second_terminal', 78, 16, 94, 'A+', 4.0, '2024', NOW(), NOW()),
(1, 5, 'final_terminal', 82, 17, 99, 'A+', 4.0, '2024', NOW(), NOW()),
(1, 5, 'pre_board', 81, 16, 97, 'A+', 4.0, '2024', NOW(), NOW());

-- Insert sample report for Prabinman Rai
INSERT INTO student_reports (student_id, academic_year, final_gpa, final_grade, position, attendance_days, total_days, remarks, class_response, discipline, leadership, neatness, punctuality, regularity, social_conduct, sports_game, issue_date, created_at, updated_at) VALUES
(1, '2024', 3.52, 'A', 5, 220, 240, 'Good performance overall. Needs improvement in Mathematics.', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'B', '2024-12-15', NOW(), NOW());
