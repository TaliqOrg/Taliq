-- Taleeq Database Seed Data
-- Sample data for testing

USE taleeq_db;

-- Insert sample users (password for all: password123)
INSERT INTO User (FirstName, LastName, Email, PasswordHash, PhoneNumber, Role, CreatedAt, IsActive) VALUES
('Admin', 'User', 'admin@taleeq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 'admin', NOW(), 1),
('ahmed', 'ali', 'ahmed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567891', 'user', NOW(), 1),
('khaled', 'abdullah', 'khaled@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567892', 'user', NOW(), 1),
('mohamed', 'ahmed', 'mohamed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567893', 'user', NOW(), 1);

-- Insert sample categories
INSERT INTO Category (Name, Description, CreatedAt) VALUES
('AI and Machine Learning', 'Courses related to artificial intelligence and machine learning', NOW()),
('Software Architecture', 'Learn about software design and architecture patterns', NOW()),
('Data Analysis', 'Master data analysis and visualization techniques', NOW()),
('Digital Marketing', 'Digital marketing strategies and tools', NOW()),
('Leadership', 'Leadership and management skills', NOW()),
('Design', 'User experience and interface design', NOW()),
('Finance', 'Financial analysis and planning', NOW());

-- Insert sample courses
INSERT INTO Course (CategoryId, Title, Description, Price, DurationHours, Level, Language, ThumbnailUrl, IsPublished, CreatedAt) VALUES
(1, 'Introduction to Machine Learning', 'Learn the fundamentals of machine learning and AI', 99.99, 40.5, 'beginner', 'English', '/images/ai_and_machine_learning.png', 1, NOW()),
(2, 'Microservices Architecture', 'Build scalable applications with microservices', 149.99, 30.0, 'intermediate', 'English', '/images/software_architecture.png', 1, NOW()),
(3, 'Data Visualization with Python', 'Create stunning visualizations with Python libraries', 79.99, 25.0, 'beginner', 'English', '/images/data_mastery_and_analysis.png', 1, NOW()),
(4, 'Digital Marketing Masterclass', 'Complete guide to digital marketing', 129.99, 35.0, 'intermediate', 'English', '/images/digital_marketing.png', 1, NOW()),
(5, 'Leadership in Practice', 'Develop your leadership skills', 89.99, 20.0, 'beginner', 'English', '/images/leadership_in_practice.png', 1, NOW());

-- Insert sample lessons for first course
INSERT INTO Lesson (CourseId, Title, Description, ContentType, ContentUrl, OrderNumber, DurationMinutes, CreatedAt) VALUES
(1, 'Introduction to AI', 'Overview of artificial intelligence', 'video', '/content/ml-intro.mp4', 1, 45, NOW()),
(1, 'Machine Learning Basics', 'Understanding ML fundamentals', 'video', '/content/ml-basics.mp4', 2, 60, NOW()),
(1, 'Supervised Learning', 'Learn about supervised learning algorithms', 'video', '/content/supervised.mp4', 3, 75, NOW()),
(1, 'Quiz: ML Fundamentals', 'Test your knowledge', 'quiz', '/content/quiz-1.json', 4, 15, NOW());

-- Insert sample workshops
INSERT INTO Workshop (CategoryId, Title, Description, Price, Location, Capacity, Language, IsPublished, CreatedAt) VALUES
(1, 'AI Workshop: Hands-on Deep Learning', 'Practical deep learning workshop', 299.99, 'Online', 50, 'English', 1, NOW()),
(5, 'Leadership Bootcamp', 'Intensive leadership training', 499.99, 'New York, NY', 30, 'English', 1, NOW());

-- Insert workshop sessions
INSERT INTO WorkshopSession (WorkshopId, SessionDate, StartTime, EndTime, Location, AvailableSeats, CreatedAt) VALUES
(1, '2026-06-15', '09:00:00', '17:00:00', 'Online', 50, NOW()),
(2, '2026-07-20', '09:00:00', '18:00:00', 'Riyadh, Saudi Arabia', 30, NOW());

-- Insert payment methods
INSERT INTO PaymentMethod (MethodName, Description, IsActive) VALUES
('Credit Card', 'Pay with credit or debit card', 1),
('PayPal', 'Pay with PayPal account', 1),
('Bank Transfer', 'Direct bank transfer', 1);

-- Insert sample discounts
INSERT INTO Discount (Code, Description, DiscountType, DiscountValue, StartDate, EndDate, IsActive) VALUES
('WELCOME10', '10% off for new users', 'percentage', 10.00, '2026-01-01', '2026-12-31', 1),
('SUMMER50', '$50 off summer courses', 'fixed', 50.00, '2026-06-01', '2026-08-31', 1);

-- Note: Password for all test users is 'password123'
-- Admin credentials: admin@taleeq.com / password123
