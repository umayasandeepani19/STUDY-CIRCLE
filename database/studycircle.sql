-- NSBM StudyCircle database schema + starter data
-- Safe to run more than once (IF NOT EXISTS / INSERT IGNORE).
-- Each statement MUST end with a semicolon at the end of a line.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  student_id VARCHAR(30) NULL,
  faculty VARCHAR(100) NULL,
  bio VARCHAR(300) NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(300) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_size INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  downloads INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resource_ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rating (resource_id, user_id),
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resource_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS downloads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  downloaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('open','solved','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  is_accepted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS study_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(500) NULL,
  subject_id INT UNSIGNED NOT NULL,
  created_by INT UNSIGNED NOT NULL,
  max_members TINYINT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS group_members (
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  FOREIGN KEY (group_id) REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS study_sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  session_date DATE NOT NULL,
  session_time TIME NOT NULL,
  location VARCHAR(150) NOT NULL,
  details VARCHAR(500) NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Starter data ----------
-- Admin login:   admin@nsbm.ac.lk   /  Admin@123
-- Student login: student@nsbm.ac.lk /  Student@123
INSERT IGNORE INTO users (id, full_name, email, student_id, faculty, password, role) VALUES
(1, 'StudyCircle Admin', 'admin@nsbm.ac.lk', NULL, 'Faculty of Computing', '$2y$10$3pUrxMtfVdH3aJgexka7mu85vj.Yg5ZCKSnJB3f0VpOruNMLqa5ie', 'admin'),
(2, 'Demo Student', 'student@nsbm.ac.lk', '26.1-IFSL-001', 'Faculty of Computing', '$2y$10$vZPiyJAFKeNWEDITBsmqLeafQOTrHFcTMzkfOPuB81hDfXUi.e7Eq', 'student');

INSERT IGNORE INTO subjects (id, code, name, description) VALUES
(1, 'WMAD', 'Web and Mobile Application Development', 'HTML, CSS, JavaScript, PHP and MySQL'),
(2, 'DBMS', 'Database Management Systems', 'Relational design, SQL and normalisation'),
(3, 'OOP', 'Object Oriented Programming', 'Classes, inheritance and design patterns'),
(4, 'DSA', 'Data Structures and Algorithms', 'Lists, trees, graphs, sorting and searching'),
(5, 'NET', 'Computer Networks', 'Protocols, addressing and network security basics');

INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Lecture Notes'),
(2, 'Past Papers'),
(3, 'Tutorials'),
(4, 'Lab Sheets'),
(5, 'Summaries');

INSERT IGNORE INTO resources (id, user_id, subject_id, category_id, title, description, file_name, file_path, file_size, status, downloads) VALUES
(1, 2, 1, 5, 'PHP and MySQL quick reference', 'A short sample note so the download feature can be tested straight after setup.', 'sample_notes.txt', 'sample_notes.txt', 310, 'approved', 0);

INSERT IGNORE INTO questions (id, user_id, subject_id, title, body, status) VALUES
(1, 2, 2, 'What is the difference between 2NF and 3NF?', 'I keep mixing up partial and transitive dependencies. Can someone explain with a small example?', 'open');

INSERT IGNORE INTO study_groups (id, name, description, subject_id, created_by, max_members) VALUES
(1, 'WMAD Final Project Crew', 'Weekly check-ins for the PHP and MySQL final project.', 1, 2, 10);

INSERT IGNORE INTO group_members (group_id, user_id) VALUES (1, 2);

INSERT IGNORE INTO study_sessions (id, group_id, title, session_date, session_time, location, details, created_by) VALUES
(1, 1, 'Database design review', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 'Library discussion room 2', 'Bring your ER diagrams.', 2);
