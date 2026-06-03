<?php
// Template for the PRODUCTION database credentials.
//
// On the production server, copy this file to "config.prod.php" and fill in the
// real values. config.prod.php is git-ignored so the credentials never end up
// in the repository. db.php loads it automatically when not running locally.

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
