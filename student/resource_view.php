<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$me = current_user(); $uid = (int) $me['id']; $id = get_int('id');
$r = row(RESOURCE_SELECT . ' WHERE r.id = ?', [$id]);
if (!$r || ($r['status'] !== 'approved' && (int) $r['user_id'] !== $uid && !is_admin())) {
    flash('error', 'That study material was not found or is not approved yet.');
    redirect('student/resources.php');
}
$approved = $r['status'] === 'approved';
$isOwner = (int) $r['user_id'] === $uid;

if (is_post() && $approved) {
    verify_csrf();
    $a = post('action');
    if ($a === 'rate') {
        $n = (int) post('rating');
        if ($isOwner) flash('error', 'You cannot rate your own upload.');
        elseif ($n < 1 || $n > 5) flash('error', 'Choose a rating from 1 to 5 stars.');
        else {
            q('INSERT INTO resource_ratings (resource_id, user_id, rating) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)', [$id, $uid, $n]);
            flash('success', 'Thanks, your rating is saved.');
        }
    } elseif ($a === 'comment') {
        $body = post('body');
        if (mb_strlen($body) < 2) flash('error', 'Write a comment first.');
        elseif (mb_strlen($body) > 1000) flash('error', 'Comments can be up to 1000 characters.');
        else { q('INSERT INTO resource_comments (resource_id, user_id, body) VALUES (?,?,?)', [$id, $uid, $body]); flash('success', 'Comment posted.'); }
    } elseif ($a === 'delete_comment') {
        $cid = (int) post('comment_id');
        q('DELETE FROM resource_comments WHERE id = ? AND resource_id = ? AND (user_id = ? OR ?)', [$cid, $id, $uid, is_admin() ? 1 : 0]);
        flash('success', 'Comment deleted.');
    }
    redirect('student/resource_view.php?id=' . $id);
}
$myRating = (int) val('SELECT rating FROM resource_ratings WHERE resource_id = ? AND user_id = ?', [$id, $uid]);
$comments = rows('SELECT c.*, u.full_name FROM resource_comments c JOIN users u ON u.id = c.user_id WHERE c.resource_id = ? ORDER BY c.created_at DESC', [$id]);
$pageTitle = $r['title']; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= h(url('student/resources.php')) ?>">&larr; Back to study materials</a></p>
<div class="split">
  <div>
    <div class="card">
      <div class="meta" style="margin-bottom:8px"><span class="tag green"><?= h($r['subject_code']) ?></span><span class="tag"><?= h($r['category_name']) ?></span> <?= badge($r['status']) ?></div>
      <h1><?= h($r['title']) ?></h1>
      <p class="prewrap"><?= h($r['description'] ?: 'No description provided.') ?></p>
      <?php if ($r['status'] === 'rejected' && $r['admin_note']): ?><div class="alert alert-error">Admin note: <?= h($r['admin_note']) ?></div><?php endif; ?>
      <?php if ($approved || $isOwner || is_admin()): ?>
        <a class="btn btn-primary" href="<?= h(url('student/download.php?id=' . $id)) ?>">Download (<?= h(format_bytes((int) $r['file_size'])) ?>)</a>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-head"><h2>Comments (<?= count($comments) ?>)</h2></div>
      <?php if ($approved): ?>
      <form method="post" style="margin-bottom:12px">
        <?= csrf_field() ?><input type="hidden" name="action" value="comment">
        <div class="field"><label for="body" class="small muted">Add a comment</label><textarea id="body" name="body" maxlength="1000" required style="min-height:80px"></textarea></div>
        <button class="btn btn-blue btn-sm" type="submit">Post comment</button>
      </form>
      <?php endif; ?>
      <?php if (!$comments): ?><p class="muted">No comments yet.</p><?php endif; ?>
      <?php foreach ($comments as $c): ?>
        <div class="comment"><span class="avatar"><?= h(initials($c['full_name'])) ?></span>
          <div style="flex:1"><b><?= h($c['full_name']) ?></b> <span class="muted small"><?= h(time_ago($c['created_at'])) ?></span>
          <p><?= h($c['body']) ?></p></div>
          <?php if ((int) $c['user_id'] === $uid || is_admin()): ?>
          <form method="post" data-confirm="Delete this comment?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_comment"><input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <aside>
    <div class="card">
      <h3>Details</h3>
      <dl class="kv">
        <dt>Shared by</dt><dd><?= h($r['full_name']) ?></dd>
        <dt>Subject</dt><dd><?= h($r['subject_name']) ?></dd>
        <dt>Uploaded</dt><dd><?= h(date('j M Y', strtotime($r['created_at']))) ?></dd>
        <dt>File</dt><dd><?= h($r['file_name']) ?></dd>
        <dt>Downloads</dt><dd><?= (int) $r['downloads'] ?></dd>
        <dt>Rating</dt><dd><?= stars($r['avg_rating'] !== null ? (float) $r['avg_rating'] : null, (int) $r['rating_count']) ?></dd>
      </dl>
    </div>
    <?php if ($approved && !$isOwner): ?>
    <div class="card">
      <h3><?= $myRating ? 'Update your rating' : 'Rate this material' ?></h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="rate">
        <div class="rate" role="radiogroup" aria-label="Rating">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="st<?= $i ?>" name="rating" value="<?= $i ?>" <?= $myRating === $i ? 'checked' : '' ?> required><label for="st<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
          <?php endfor; ?>
        </div>
        <div style="margin-top:10px"><button class="btn btn-primary btn-sm" type="submit">Save rating</button></div>
      </form>
    </div>
    <?php endif; ?>
  </aside>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
