## How to run (local)

1. Install **XAMPP** and copy this folder into `htdocs`.
2. In **phpMyAdmin**, import `database.sql` (creates the database, tables and demo data).
3. Open `http://localhost/IT8415-MovieReview/`.

`db.php` auto detects the environment, so the same code works on local XAMPP and on
the deployed server with no manual editing.

## Live deployment

- **URL:** `http://20.74.143.233/~u202102192/`
- **Deploy:** upload changed files over SFTP (NetBeans → Run As → *Remote Web Site*), then reload.
- **Database:** `db202102192`, managed via phpMyAdmin.

## Demo accounts

password for **all** accounts: `password`

| Email             | Role    | Can do                                   |
|-------------------|---------|------------------------------------------|
| admin@movies.com  | Admin   | Manage users, content, reports; moderate comments |
| john@movies.com   | Creator | Add / edit / publish their own reviews   |
| sara@movies.com   | Creator | Add / edit / publish their own reviews   |
| mike@movies.com   | Viewer  | Browse, search, rate, comment            |
| anna@movies.com   | Viewer  | Browse, search, rate, comment            |

## Main features

- **Roles & auth:** Admin / Creator / Viewer, with register, login, logout and sessions. Passwords hashed with bcrypt.
- **Home page:** newest reviews with posters, descriptions and links.
- **Search:** live results as you type — by title, creator, genre, date range and sort, with pagination.
- **Comments & ratings:** star ratings and comments via AJAX; admins can remove comments.
- **Creator panel:** add, edit, publish or delete your own reviews (posters are added by image URL).
- **Admin panel:** manage content, users, and reports (popular movies by date range, content by user).

## Database

- ERD: `finalERD.png`
- Stored procedure `GetPopularMoviesByDateRange` and a trigger, both in `database.sql`.
- All tables use the `dbproj_` prefix.

## Notes

- posters and trailers are stored as links. If one ever stops loading, edit the movie and paste a new URL
  (trailer format: `https://www.youtube.com/embed/VIDEO_ID`).
