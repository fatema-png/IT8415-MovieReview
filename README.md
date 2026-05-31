# Movie Review System

A data-driven web application built with **Apache, PHP and MySQL**, where users can
browse, search, rate and review movies. Built for the IT8415 group project.

## How to run it

1. Install **XAMPP** (or any Apache + PHP + MySQL/MariaDB stack).
2. Copy this `IT8415-MovieReview` folder into `htdocs`.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and **import `database.sql`**.
   This creates the `dbproj_movies` database, all tables, the stored procedure,
   the trigger, and the demo data.
4. Visit `http://localhost/IT8415-MovieReview/`.

The database settings are in `db.php` (host `localhost`, user `root`, no password —
the XAMPP defaults). Change them there if your setup is different.

## Demo accounts

The password for **all** demo accounts is: `password`

| Email             | Role        | Can do                                  |
|-------------------|-------------|------------------------------------------|
| admin@movies.com  | Admin       | Everything: moderate content, manage users, reports |
| john@movies.com   | Creator     | Add / edit / publish their own reviews  |
| sara@movies.com   | Creator     | Add / edit / publish their own reviews  |
| mike@movies.com   | Viewer      | Browse, search, rate, comment           |
| anna@movies.com   | Viewer      | Browse, search, rate, comment           |

## Where each requirement is met

**User roles & authentication**
- Three roles (Admin, Creator, Viewer) — `dbproj_roles`, `auth.php`
- Sign-up / login / logout — `register.php`, `login.php`, `logout.php`
- Sessions — `auth.php`
- Passwords encrypted with bcrypt — `register.php` (`password_hash`), `login.php` (`password_verify`)
- JavaScript validation — `login.php`, `register.php`, `search.php`, `create_post.php`
- Server-side validation — every form handler

**Home page** — `index.php`: navigation, search link, newest-first list, posters, descriptions, "View Review" links.

**Search** — `search.php`: title (FULLTEXT index), date range, creator, popularity (views / rating), with live suggestions (`search_suggestions.php`) and pagination.

**Comments & ratings** — `comments_section.php` + `ajax/` endpoints: star rating, comments for logged-in users, admins can remove comments, everyone can read them.

**Creator panel** — `creator_dashboard.php`, `create_post.php`, `save_post.php`,
`edit_post.php`, `update_post.php`, `delete_post.php`: add, edit, upload an image,
save as draft, then publish, and view own content.

**Admin panel** — `admin_content.php` (moderate / remove all content),
`admin_users.php` (manage users), `admin_reports.php` (reports).

**Reports** — `admin_reports.php`:
1. Most popular movies in a date range (uses the **stored procedure**).
2. All content created by a chosen user.

**Database**
- ERD: `NwERD.png`
- Stored procedure: `GetPopularMoviesByDateRange` (in `database.sql`)
- Trigger: `before_movie_update` (in `database.sql`)
- 3 roles, 16 movies, media, ratings, comments — well above the 15-record minimum,
  with 5 movies in the "Drama" category.
- All tables use the `dbproj_` prefix.

**Advanced features used** (more than the required 2)
- AJAX (comments, ratings, search suggestions)
- Prepared statements (used everywhere user input touches the database)
- Trigger
- Full-text indexed search

## Note about the trailers
The seed data uses YouTube trailer links. If any trailer ever becomes unavailable,
just edit that movie and paste a new embed URL (format: `https://www.youtube.com/embed/VIDEO_ID`).
