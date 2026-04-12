<?php
/**
 * comments_section.php
 * Include this file inside movie.php (Member 3's page).
 *
 * Usage:
 *   $movie_id = <the movie's ID>;
 *   include 'comments_section.php';
 *
 * Requires:
 *   - includes/db.php already included
 *   - includes/auth.php already included
 */

if (!isset($movie_id) || !isset($conn)) {
    die('comments_section.php requires $movie_id and $conn to be set.');
}

// Fetch all comments for this movie
$comments_stmt = $conn->prepare("
    SELECT c.comment_id, c.comment_text, c.created_at,
           u.username, u.user_id
    FROM dbproj_comments c
    JOIN dbproj_users u ON c.user_id = u.user_id
    WHERE c.movie_id = ?
    ORDER BY c.created_at DESC
");
$comments_stmt->bind_param('i', $movie_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$comments_stmt->close();

// Fetch current user's existing rating (if logged in)
$user_rating = 0;
if (isLoggedIn()) {
    $ur_stmt = $conn->prepare("SELECT rating_value FROM dbproj_ratings WHERE movie_id = ? AND user_id = ?");
    $ur_stmt->bind_param('ii', $movie_id, getCurrentUserId());
    $ur_stmt->execute();
    $ur_row = $ur_stmt->get_result()->fetch_assoc();
    $user_rating = $ur_row['rating_value'] ?? 0;
    $ur_stmt->close();
}

// Fetch current average rating
$avg_stmt = $conn->prepare("
    SELECT ROUND(AVG(rating_value), 1) AS avg_rating, COUNT(*) AS total_ratings
    FROM dbproj_ratings WHERE movie_id = ?
");
$avg_stmt->bind_param('i', $movie_id);
$avg_stmt->execute();
$rating_stats = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();
?>

<div class="comments-ratings-section" id="commentsSection" style="margin-top: 40px;">

    <!-- ========== STAR RATING ========== -->
    <div class="rating-box" style="background:#1e1e2e; border-radius:10px; padding:24px; margin-bottom:28px;">
        <h3 style="color:#fff; margin-bottom:12px;">⭐ Rating</h3>
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div id="avgRatingDisplay">
                <?php if ($rating_stats['total_ratings'] > 0): ?>
                    <span style="font-size:2rem; color:#f5c518; font-weight:700;">
                        <?= $rating_stats['avg_rating'] ?>
                    </span>
                    <span style="color:#999;"> / 5 &nbsp;(<?= $rating_stats['total_ratings'] ?> ratings)</span>
                    <div style="color:#f5c518; font-size:1.4rem;">
                        <?= str_repeat('★', round($rating_stats['avg_rating'])) ?><?= str_repeat('☆', 5 - round($rating_stats['avg_rating'])) ?>
                    </div>
                <?php else: ?>
                    <span style="color:#777;">No ratings yet. Be the first!</span>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn()): ?>
                <div>
                    <p style="color:#ccc; font-size:0.88rem; margin-bottom:6px;">
                        <?= $user_rating ? "Your rating: {$user_rating}/5 — Click to change" : 'Rate this review:' ?>
                    </p>
                    <div class="star-input" id="starInput" data-movie="<?= $movie_id ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= $user_rating ? 'filled' : '' ?>"
                                  data-value="<?= $i ?>"
                                  style="font-size:1.8rem; cursor:pointer; color:<?= $i <= $user_rating ? '#f5c518' : '#555' ?>;"
                                  title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p id="ratingMsg" style="color:#4caf50; font-size:0.85rem; margin-top:6px; display:none;"></p>
                </div>
            <?php else: ?>
                <p style="color:#888;"><a href="login.php" style="color:#e50914;">Log in</a> to rate this review.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== COMMENTS ========== -->
    <div class="comments-box" style="background:#1e1e2e; border-radius:10px; padding:24px;">
        <h3 style="color:#fff; margin-bottom:20px;">
            💬 Comments <span style="color:#888; font-weight:400; font-size:1rem;">(<?= count($comments) ?>)</span>
        </h3>

        <!-- Add Comment Form (logged-in only) -->
        <?php if (isLoggedIn()): ?>
            <div style="margin-bottom:28px; border-bottom:1px solid #333; padding-bottom:24px;">
                <textarea id="commentInput" rows="3" maxlength="1000"
                    placeholder="Share your thoughts..."
                    style="width:100%; padding:12px; background:#2a2a3e; border:1px solid #444; border-radius:8px; color:#fff; font-size:0.95rem; resize:vertical; box-sizing:border-box;"></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; flex-wrap:wrap; gap:10px;">
                    <span id="charCount" style="color:#666; font-size:0.82rem;">0 / 1000 characters</span>
                    <button id="submitCommentBtn"
                        data-movie="<?= $movie_id ?>"
                        style="background:#e50914; color:#fff; border:none; padding:10px 24px; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.95rem;">
                        Post Comment
                    </button>
                </div>
                <p id="commentMsg" style="margin-top:8px; font-size:0.88rem; display:none;"></p>
            </div>
        <?php else: ?>
            <p style="color:#888; margin-bottom:24px; border-bottom:1px solid #333; padding-bottom:20px;">
                <a href="login.php" style="color:#e50914;">Log in</a> to leave a comment.
            </p>
        <?php endif; ?>

        <!-- Comments List -->
        <div id="commentsList">
            <?php if (empty($comments)): ?>
                <p id="noComments" style="color:#666; text-align:center; padding:30px 0;">
                    No comments yet. Be the first to comment!
                </p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div class="comment-item" id="comment-<?= $c['comment_id'] ?>"
                         style="padding:14px 0; border-bottom:1px solid #2a2a3e;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="width:38px; height:38px; border-radius:50%; background:#e50914; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex-shrink:0;">
                                    <?= strtoupper(substr($c['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong style="color:#fff; font-size:0.92rem;">
                                        <?= htmlspecialchars($c['username']) ?>
                                    </strong>
                                    <span style="color:#666; font-size:0.8rem; margin-left:10px;">
                                        <?= date('M d, Y H:i', strtotime($c['created_at'])) ?>
                                    </span>
                                    <p style="color:#ccc; margin:6px 0 0; font-size:0.93rem;">
                                        <?= nl2br(htmlspecialchars($c['comment_text'])) ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (isAdmin()): ?>
                                <button class="delete-comment-btn"
                                        data-id="<?= $c['comment_id'] ?>"
                                        title="Remove comment"
                                        style="background:none; border:1px solid #c00; color:#c00; border-radius:5px; padding:4px 10px; cursor:pointer; font-size:0.8rem; flex-shrink:0;">
                                    🗑 Remove
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========== JAVASCRIPT (AJAX) ========== -->
<script>
(function () {
    // ---- Star Rating ----
    const starContainer = document.getElementById('starInput');
    if (starContainer) {
        const stars = starContainer.querySelectorAll('.star');
        const movieId = starContainer.dataset.movie;

        // Hover effects
        stars.forEach(star => {
            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.dataset.value);
                stars.forEach(s => s.style.color = parseInt(s.dataset.value) <= val ? '#f5c518' : '#555');
            });
        });
        starContainer.addEventListener('mouseleave', () => {
            stars.forEach(s => s.style.color = s.classList.contains('filled') ? '#f5c518' : '#555');
        });

        // Click to rate
        stars.forEach(star => {
            star.addEventListener('click', async () => {
                const rating = parseInt(star.dataset.value);
                const res = await fetch('ajax/submit_rating.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `movie_id=${movieId}&rating=${rating}`,
                });
                const data = await res.json();
                const msg = document.getElementById('ratingMsg');
                if (data.success) {
                    // Update star highlights
                    stars.forEach(s => {
                        const filled = parseInt(s.dataset.value) <= data.your_rating;
                        s.classList.toggle('filled', filled);
                        s.style.color = filled ? '#f5c518' : '#555';
                    });
                    // Update average display
                    document.getElementById('avgRatingDisplay').innerHTML =
                        `<span style="font-size:2rem; color:#f5c518; font-weight:700;">${data.avg_rating}</span>
                         <span style="color:#999;"> / 5 &nbsp;(${data.total_ratings} ratings)</span>
                         <div style="color:#f5c518; font-size:1.4rem;">${'★'.repeat(Math.round(data.avg_rating))}${'☆'.repeat(5 - Math.round(data.avg_rating))}</div>`;
                    msg.style.color = '#4caf50';
                } else {
                    msg.style.color = '#e50914';
                }
                msg.textContent = data.message;
                msg.style.display = 'block';
                setTimeout(() => msg.style.display = 'none', 3000);
            });
        });
    }

    // ---- Comment Character Counter ----
    const commentInput = document.getElementById('commentInput');
    const charCount    = document.getElementById('charCount');
    if (commentInput) {
        commentInput.addEventListener('input', () => {
            const len = commentInput.value.length;
            charCount.textContent = `${len} / 1000 characters`;
            charCount.style.color = len > 900 ? '#e50914' : '#666';
        });
    }

    // ---- Submit Comment (AJAX) ----
    const submitBtn = document.getElementById('submitCommentBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const text    = commentInput.value.trim();
            const movieId = submitBtn.dataset.movie;
            const msgEl   = document.getElementById('commentMsg');

            // Client-side validation
            if (text.length < 3) {
                showMsg(msgEl, 'Comment must be at least 3 characters.', '#e50914');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            const res = await fetch('ajax/submit_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `movie_id=${movieId}&comment_text=${encodeURIComponent(text)}`,
            });
            const data = await res.json();

            if (data.success) {
                commentInput.value = '';
                charCount.textContent = '0 / 1000 characters';
                showMsg(msgEl, '✓ Comment posted!', '#4caf50');

                // Prepend new comment to list (no page reload)
                const noComments = document.getElementById('noComments');
                if (noComments) noComments.remove();

                const newComment = document.createElement('div');
                newComment.className = 'comment-item';
                newComment.id = `comment-${data.comment_id}`;
                newComment.style.cssText = 'padding:14px 0; border-bottom:1px solid #2a2a3e;';
                newComment.innerHTML = `
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="width:38px; height:38px; border-radius:50%; background:#e50914; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex-shrink:0;">
                            ${data.username.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <strong style="color:#fff; font-size:0.92rem;">${data.username}</strong>
                            <span style="color:#666; font-size:0.8rem; margin-left:10px;">${data.created_at}</span>
                            <p style="color:#ccc; margin:6px 0 0; font-size:0.93rem;">${data.comment.replace(/\n/g, '<br>')}</p>
                        </div>
                    </div>`;
                document.getElementById('commentsList').prepend(newComment);

                // Update count in header
                const h3 = document.querySelector('#commentsSection h3');
                if (h3) {
                    const current = parseInt(h3.querySelector('span')?.textContent || '0');
                    h3.querySelector('span').textContent = `(${current + 1})`;
                }
            } else {
                showMsg(msgEl, data.message, '#e50914');
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Post Comment';
        });
    }

    // ---- Delete Comment (Admin, AJAX) ----
    document.addEventListener('click', async (e) => {
        if (!e.target.classList.contains('delete-comment-btn')) return;
        if (!confirm('Remove this comment? This cannot be undone.')) return;

        const btn       = e.target;
        const commentId = btn.dataset.id;

        const res = await fetch('ajax/delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}`,
        });
        const data = await res.json();

        if (data.success) {
            const el = document.getElementById(`comment-${commentId}`);
            if (el) {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
        } else {
            alert(data.message);
        }
    });

    function showMsg(el, text, color) {
        el.textContent = text;
        el.style.color = color;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }
})();
</script>
