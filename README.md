# NSBM StudyCircle

Student community for notes and study sharing - Final Project (Topic 4), Web and Mobile Application Development, Batch 26.1 IFSL, NSBM Green University.

Stack: HTML, CSS, JavaScript, PHP (PDO), MySQL. Theme: white, green, blue.

## Run it on your computer (XAMPP / WAMP)

1. Copy this folder to `C:\xampp\htdocs\studycircle`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/studycircle/setup.php` once. It creates the `studycircle` database, all tables and starter data.
4. Open `http://localhost/studycircle/` and log in:

| Role    | Email                | Password     |
|---------|----------------------|--------------|
| Admin   | admin@nsbm.ac.lk     | Admin@123    |
| Student | student@nsbm.ac.lk   | Student@123  |

Default XAMPP settings (`root`, empty password, `localhost`) are already in `config/config.php`. If your MySQL uses a password, change it there.

## Deploy on InfinityFree (live link for the report)

1. Create an account, a hosting account, and a MySQL database in the control panel. Note the **host, database name, username, password**.
2. Copy `config/config.local.example.php` to `config/config.local.php` and fill in those details.
3. Upload everything into `htdocs/` with the File Manager or FTP.
4. Open phpMyAdmin, select your database, and **Import** `database/studycircle.sql` (or open `yoursite/setup.php` once).
5. Make sure `uploads/` is writable (permission 755 or 777).
6. **Delete `setup.php`** from the server afterwards.

`config.local.php` is in `.gitignore`, so database passwords are never pushed to GitHub.

## Features

**Student**: register / login, profile and password change, upload notes (PDF, Word, PowerPoint, Excel, text, ZIP, images up to 10 MB), edit and delete own uploads, browse and filter materials by subject, type and search, download, rate (1-5 stars) and comment, Q&A forum (ask, answer, accept an answer, edit, delete), create or join study groups, schedule, edit and delete group study sessions.

**Admin**: login, dashboard, manage users (add, edit, block, delete), approve or reject uploads with a note, manage subjects and categories, moderate forum questions, answers and comments, reports (most downloaded, top rated, engagement per subject, top contributors, uploads per month) with print and CSV export.

**Security**: PDO prepared statements, `password_hash`, CSRF token on every form, output escaping, session regeneration on login, upload extension whitelist with random file names, PHP execution blocked in `uploads/`.

## Folder structure

```
config/      database settings
includes/    db connection, helper functions, shared header and footer
database/    studycircle.sql (schema + starter data)
assets/      css and js
student/     student pages
admin/       admin pages
uploads/     uploaded files
index.php, login.php, register.php, logout.php, setup.php
```

## Database tables

users, subjects, categories, resources, resource_ratings, resource_comments, downloads, questions, answers, study_groups, group_members, study_sessions.
