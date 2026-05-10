USE taleeq_db;

-- 1. Add some categories first
INSERT INTO Category (Name, Description) VALUES
                                             ('Web Development', 'Learn how to build modern websites and applications.'),
                                             ('Graphic Design', 'Master the tools and principles of visual communication.'),
                                             ('Business', 'Develop essential skills for the modern professional workplace.');

-- 2. Add sample courses linked to those categories
INSERT INTO Course (CategoryId, Title, Description, Price, DurationHours, Level, IsPublished) VALUES
                                                                                                  (1, 'Introduction to PHP', 'A comprehensive guide to backend programming with PHP and MySQL.', 49.99, 12.5, 'beginner', 1),
                                                                                                  (1, 'Advanced JavaScript Patterns', 'Master closures, prototypes, and asynchronous programming in JS.', 75.00, 20.0, 'advanced', 1),
                                                                                                  (2, 'UI/UX Design Basics', 'Learn the fundamentals of user-centered design and wireframing.', 35.00, 8.0, 'beginner', 1),
                                                                                                  (2, 'Mastering Adobe Illustrator', 'Go from beginner to pro with vector-based graphic design.', 60.00, 15.5, 'intermediate', 1),
                                                                                                  (3, 'Digital Marketing Strategy', 'How to grow your brand using SEO, social media, and paid ads.', 25.00, 10.0, 'beginner', 1);

-- ============================================
-- 5 Additional Courses
-- ============================================
INSERT INTO Course (CategoryId, Title, Description, Price, DurationHours, Level, Language, ThumbnailUrl, IsPublished) VALUES
                                                                                                                          (1, 'React.js for Beginners', 'Learn the basics of React and component-based UI.', 55.00, 14.0, 'beginner', 'English', '../images/placeholder.png', 1),
                                                                                                                          (2, 'Advanced Typography', 'Master the art of arranging type to make written language appealing.', 45.00, 6.5, 'advanced', 'English', '../images/placeholder.png', 1),
                                                                                                                          (3, 'Project Management 101', 'Learn Agile, Scrum, and Waterfall methodologies.', 70.00, 18.0, 'beginner', 'English', '../images/placeholder.png', 1),
                                                                                                                          (1, 'Python for Data Science', 'Data analysis using Pandas, NumPy, and Matplotlib.', 85.00, 25.0, 'intermediate', 'English', '../images/placeholder.png', 1),
                                                                                                                          (3, 'Public Speaking Masterclass', 'Overcome stage fright and deliver compelling presentations.', 40.00, 5.0, 'beginner', 'English', '../images/placeholder.png', 1);


-- ============================================
-- 10 New Workshops
-- ============================================
INSERT INTO Workshop (CategoryId, Title, Description, Price, Location, Capacity, Language, IsPublished) VALUES
                                                                                                            (1, 'Weekend Coding Bootcamp', 'Intensive 2-day hands-on coding session.', 150.00, 'Main Tech Hub', 30, 'English', 1),
                                                                                                            (2, 'Logo Design Sprint', 'Create a professional logo from scratch in 4 hours.', 50.00, 'Design Studio', 20, 'English', 1),
                                                                                                            (3, 'Startup Pitch Workshop', 'Perfect your pitch deck and presentation skills.', 75.00, 'Business Center', 40, 'English', 1),
                                                                                                            (1, 'Hackathon Prep Session', 'Strategies and team building for upcoming hackathons.', 25.00, 'Online / Zoom', 100, 'English', 1),
                                                                                                            (2, 'Color Theory in Practice', 'Hands-on painting and digital color mixing.', 60.00, 'Art Gallery', 15, 'Arabic', 1),
                                                                                                            (3, 'Negotiation Skills Workshop', 'Role-playing exercises for real-world business negotiations.', 120.00, 'Conference Hall B', 25, 'English', 1),
                                                                                                            (1, 'Cloud Deployment Hands-on', 'Deploy your first app to AWS and Azure.', 90.00, 'Tech Lab 3', 20, 'English', 1),
                                                                                                            (2, 'UI/UX Prototyping Lab', 'Wireframing and prototyping using Figma.', 80.00, 'Creative Workspace', 25, 'English', 1),
                                                                                                            (3, 'Leadership Training Day', 'Develop emotional intelligence and team management skills.', 200.00, 'Grand Hotel', 50, 'Arabic', 1),
                                                                                                            (1, 'Cybersecurity Basics Seminar', 'Learn to protect yourself from cyber threats.', 45.00, 'Online / Zoom', 200, 'English', 1);