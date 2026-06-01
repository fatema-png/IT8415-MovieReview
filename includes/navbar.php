<nav class="navbar navbar-expand-lg navbar-dark" style="background:#111827;">
  <div class="container">

    <a class="navbar-brand fw-bold" href="index.php">
      🎬 Movie Review
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="search.php">Search</a>
        </li>

        <?php
        // categories menu: list the genres so users can jump straight to a category.
        if (isset($conn)):
            $navGenres = $conn->query("SELECT genre_id, genre_name FROM dbproj_genres ORDER BY genre_name");
            if ($navGenres && $navGenres->num_rows > 0):
        ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="genreMenu" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="genreMenu">
              <?php while ($g = $navGenres->fetch_assoc()): ?>
                <li>
                  <a class="dropdown-item" href="search.php?genre=<?= (int)$g['genre_id'] ?>">
                    <?= htmlspecialchars($g['genre_name']) ?>
                  </a>
                </li>
              <?php endwhile; ?>
            </ul>
          </li>
        <?php
            endif;
        endif;
        ?>

        <?php if (isLoggedIn()): ?>

          <?php if (isCreator() || isAdmin()): ?>
            <li class="nav-item">
              <a class="nav-link" href="creator_dashboard.php">Dashboard</a>
            </li>
          <?php endif; ?>

          <?php if (isAdmin()): ?>
            <li class="nav-item">
              <a class="nav-link" href="admin_content.php">Moderate</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin_users.php">Users</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin_reports.php">Reports</a>
            </li>
          <?php endif; ?>

          <li class="nav-item">
            <span class="nav-link" style="color:#9ca3af;">
              👤 <?= htmlspecialchars(getCurrentUsername()) ?>
            </span>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="logout.php"
               style="color:#ef4444; font-weight:600;">Logout</a>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>

          <li class="nav-item">
            <a class="btn btn-sm ms-2" href="register.php"
               style="background:#ef4444; color:#fff; border-radius:8px; padding:6px 16px; font-weight:600;">
              Register
            </a>
          </li>

        <?php endif; ?>

      </ul>
    </div>

  </div>
</nav>
