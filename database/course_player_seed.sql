-- Course Player Sample Data
-- Additional lessons for testing the course player feature

USE taleeq_db;

-- Add more lessons to Course 1 (Machine Learning) for better testing
INSERT INTO Lesson (CourseId, SectionTitle, Title, Description, ContentType, ContentUrl, SortOrder, Duration, CreatedAt) VALUES
-- Getting Started Section (already has 2 lessons, adding more)
(1, 'Getting Started', 'Setting Up Your Environment', 'Learn how to set up Python and required libraries for machine learning', 'video', '/content/ml-setup.mp4', 5, 30, NOW()),

-- Core Concepts Section
(1, 'Core Concepts', 'Unsupervised Learning', 'Introduction to unsupervised learning algorithms', 'video', '/content/unsupervised.mp4', 6, 65, NOW()),
(1, 'Core Concepts', 'Neural Networks Basics', 'Understanding the fundamentals of neural networks', 'video', '/content/neural-basics.mp4', 7, 80, NOW()),

-- Practical Applications Section
(1, 'Practical Applications', 'Building Your First Model', 'Step-by-step guide to building a simple ML model', 'video', '/content/first-model.mp4', 8, 90, NOW()),
(1, 'Practical Applications', 'Model Evaluation', 'Learn how to evaluate and improve your models', 'video', '/content/evaluation.mp4', 9, 55, NOW()),
(1, 'Practical Applications', 'Real-World Project', 'Apply your knowledge to a real-world dataset', 'video', '/content/project.mp4', 10, 120, NOW()),

-- Advanced Topics Section
(1, 'Advanced Topics', 'Deep Learning Introduction', 'Getting started with deep learning', 'video', '/content/deep-learning.mp4', 11, 75, NOW()),
(1, 'Advanced Topics', 'Model Optimization', 'Techniques for optimizing ML models', 'video', '/content/optimization.mp4', 12, 60, NOW()),
(1, 'Advanced Topics', 'Final Project', 'Capstone project for the course', 'video', '/content/final-project.mp4', 13, 150, NOW());

-- Add lessons to Course 2 (Microservices Architecture)
INSERT INTO Lesson (CourseId, SectionTitle, Title, Description, ContentType, ContentUrl, SortOrder, Duration, CreatedAt) VALUES
-- Introduction Section
(2, 'Introduction', 'What are Microservices?', 'Understanding microservices architecture', 'video', '/content/microservices-intro.mp4', 1, 40, NOW()),
(2, 'Introduction', 'Monolith vs Microservices', 'Comparing architectural patterns', 'video', '/content/monolith-vs-micro.mp4', 2, 35, NOW()),

-- Design Patterns Section
(2, 'Design Patterns', 'Service Discovery', 'Implementing service discovery patterns', 'video', '/content/service-discovery.mp4', 3, 50, NOW()),
(2, 'Design Patterns', 'API Gateway Pattern', 'Building an API gateway', 'video', '/content/api-gateway.mp4', 4, 55, NOW()),
(2, 'Design Patterns', 'Circuit Breaker', 'Implementing resilience patterns', 'video', '/content/circuit-breaker.mp4', 5, 45, NOW()),

-- Implementation Section
(2, 'Implementation', 'Containerization with Docker', 'Dockerizing microservices', 'video', '/content/docker-micro.mp4', 6, 70, NOW()),
(2, 'Implementation', 'Orchestration with Kubernetes', 'Managing microservices with K8s', 'video', '/content/kubernetes.mp4', 7, 85, NOW()),
(2, 'Implementation', 'Building Your First Microservice', 'Hands-on microservice development', 'video', '/content/first-microservice.mp4', 8, 95, NOW());

-- Add lessons to Course 3 (Data Visualization)
INSERT INTO Lesson (CourseId, SectionTitle, Title, Description, ContentType, ContentUrl, SortOrder, Duration, CreatedAt) VALUES
-- Fundamentals Section
(3, 'Fundamentals', 'Introduction to Data Visualization', 'Why visualization matters', 'video', '/content/viz-intro.mp4', 1, 25, NOW()),
(3, 'Fundamentals', 'Types of Charts', 'Understanding different chart types', 'video', '/content/chart-types.mp4', 2, 35, NOW()),
(3, 'Fundamentals', 'Color Theory for Data', 'Using colors effectively in visualizations', 'video', '/content/color-theory.mp4', 3, 30, NOW()),

-- Python Libraries Section
(3, 'Python Libraries', 'Matplotlib Basics', 'Getting started with Matplotlib', 'video', '/content/matplotlib.mp4', 4, 50, NOW()),
(3, 'Python Libraries', 'Seaborn for Statistical Plots', 'Creating beautiful statistical visualizations', 'video', '/content/seaborn.mp4', 5, 55, NOW()),
(3, 'Python Libraries', 'Plotly Interactive Charts', 'Building interactive visualizations', 'video', '/content/plotly.mp4', 6, 60, NOW()),

-- Advanced Techniques Section
(3, 'Advanced Techniques', 'Dashboard Creation', 'Building interactive dashboards', 'video', '/content/dashboards.mp4', 7, 75, NOW()),
(3, 'Advanced Techniques', 'Geospatial Visualization', 'Visualizing geographic data', 'video', '/content/geospatial.mp4', 8, 65, NOW()),
(3, 'Advanced Techniques', 'Real-time Data Visualization', 'Creating live updating charts', 'video', '/content/realtime-viz.mp4', 9, 70, NOW());

-- Create sample enrollments for testing
-- User 2 (ahmed@example.com) enrolled in Course 1
INSERT INTO Enrollment (UserId, CourseId, EnrollmentDate, ProgressPercentage, CompletionStatus)
VALUES (2, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), 0.00, 'not_started');

-- User 3 (khaled@example.com) enrolled in Course 2
INSERT INTO Enrollment (UserId, CourseId, EnrollmentDate, ProgressPercentage, CompletionStatus)
VALUES (3, 2, DATE_SUB(NOW(), INTERVAL 5 DAY), 0.00, 'not_started');

-- User 4 (mohamed@example.com) enrolled in Course 3
INSERT INTO Enrollment (UserId, CourseId, EnrollmentDate, ProgressPercentage, CompletionStatus)
VALUES (4, 3, DATE_SUB(NOW(), INTERVAL 3 DAY), 0.00, 'not_started');

-- Note: After running this seed file, you can test the course player by:
-- 1. Logging in as ahmed@example.com (password: password123)
-- 2. Navigating to: /pages/user/course_player.html?course_id=1
