<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id']; $admin = is_admin(); $id = get_int('id');
$qn = row('SELECT q.*, u.full_name, s.code, s.name AS subject_name FROM questions q JOIN users u ON u.id = q.user_id JOIN subjects s ON s.id = q.subject_id WHERE q.id = ?', [$id]);
if (!$qn) { flash('error', 'Question not found.'); redirect('student/forum.php'); }
$mine = (int) $qn['user_id'] === $uid;

if (is_post()) {
    verify_csrf();
    $a = post('action');
    if ($a === 'answer') {
        $body = post('body');
        if ($qn['status'] === 'closed') flash('error', 'This question is closed.');
        elseif (mb_strlen($body) < 5) flash('error', 'Write an answer first (at least 5 characters).');
        else { q('INSERT INTO answers (question_id, user_id, body) VALUES (?,?,?)', [$id, $uid, $body]); flash('success', 'Answer posted.'); }
    } elseif ($a === 'accept' && ($mine || $admin)) {
        $aid = (int) post('answer_id');
        if (val('SELECT id FROM answers WHERE id = ? AND question_id = ?', [$aid, $id])) {
            q('UPDATE answers SET is_accepted = 0 WHERE question_id = ?', [$id]);
            q('UPDATE answers SET is_accepted = 1 WHERE id = ?', [$aid]);
            q("UPDATE questions SET status = 'solved' WHERE id = ? AND status <> 'closed'", [$id]);
            flash('success', 'Marked as the accepted answer.');
        }
    } elseif ($a === 'unaccept' && ($mine || $admin)) {
        q('UPDATE answers SET is_accepted = 0 WHERE question_id = ?', [$id]);
        q("UPDATE questions SET status = 'open' WHERE id = ? AND status = 'solved'", [$id]);
        flash('success', 'Question reopened.');
    } elseif ($a === 'delete_answer') {
        q('DELETE FROM answers WHERE id = ? AND question_id = ? AND (user_id = ? OR ?)', [(int) post('answer_id'), $id, $uid, $admin ? 1 : 0]);
        if (!val('SELECT COUNT(*) FROM answers WHERE question_id = ? AND is_accepted = 1', [$id])) {
            q("UPDATE questions SET status = 'open' WHERE id = ? AND status = 'solved'", [$id]);
        }
        flash('success', 'Answer deleted.');
    } elseif ($a === 'delete_question' && ($mine || $admin)) {
        q('DELETE FROM questions WHERE id = ?', [$id]);
        flash('success', 'Question deleted.');
        redirect('student/forum.php');
    }
    redirect('student/question_view.php?id=' . $id);
}
$answers = rows('SELECT a.*, u.full_name FROM answers a JOIN users u ON u.id = a.user_id WHERE a.question_id = ? ORDER BY a.is_accepted DESC, a.created_at ASC', [$id]);
$pageTitle = 'Question'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= h(url('student/forum.php')) ?>">&larr; Back to forum</a></p>
<div class="card">
  <div class="meta" style="margin-bottom:8px"><span class="tag green"><?= h($qn['code']) ?></span> <?= badge($qn['status']) ?></div>
  <h1><?= h($qn['title']) ?></h1>
  <p class="muted small">Asked by <?= h($qn['full_name']) ?> &middot; <?= h(time_ago($qn['created_at'])) ?></p>
  <p class="prewrap"><?= h($qn['body']) ?></p>
  <?php if ($mine || $admin): ?>
  <div class="btn-row">
    <a class="btn btn-ghost btn-sm" href="<?= h(url('student/question_new.php?id=' . $id)) ?>">Edit</a>
    <form class="inline" method="post" data-confirm="Delete this question and all its answers?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_question"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
  </div>
  <?php endif; ?>
</div>

<h2 style="margin-top:28px"><?= count($answers) ?> answer<?= count($answers) === 1 ? '' : 's' ?></h2>
<?php if (!$answers): ?><div class="empty"><b>No answers yet</b>Know this one? Share what you know below.</div><?php endif; ?>
<?php foreach ($answers as $a): ?>
  <div class="answer <?= $a['is_accepted'] ? 'accepted' : '' ?>">
    <?php if ($a['is_accepted']): ?><span class="badge badge-green">Accepted answer</span><?php endif; ?>
    <p class="prewrap" style="margin:6px 0"><?= h($a['body']) ?></p>
    <div class="muted small">by <b><?= h($a['full_name']) ?></b> &middot; <?= h(time_ago($a['created_at'])) ?></div>
    <div class="btn-row" style="margin-top:8px">
      <?php if (($mine || $admin) && !$a['is_accepted']): ?>
        <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="accept"><input type="hidden" name="answer_id" value="<?= (int) $a['id'] ?>"><button class="btn btn-primary btn-sm" type="submit">Accept answer</button></form>
      <?php elseif (($mine || $admin) && $a['is_accepted']): ?>
        <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="unaccept"><button class="btn btn-ghost btn-sm" type="submit">Remove acceptance</button></form>
      <?php endif; ?>
      <?php if ((int) $a['user_id'] === $uid || $admin): ?>
        <form class="inline" method="post" data-confirm="Delete this answer?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_answer"><input type="hidden" name="answer_id" value="<?= (int) $a['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($qn['status'] !== 'closed'): ?>
<div class="card" style="margin-top:20px">
  <h3>Your answer</h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="answer">
    <div class="field"><textarea name="body" required minlength="5" aria-label="Your answer"></textarea></div>
    <button class="btn btn-blue" type="submit">Post answer</button>
  </form>
</div>
<?php else: ?><div class="alert alert-info">This question was closed by a moderator, so new answers are off.</div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
