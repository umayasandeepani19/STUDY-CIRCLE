<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$tab = get_str('tab', 'questions') === 'answers' ? 'answers' : 'questions';
if (is_post()) {
    verify_csrf();
    $a = post('action'); $id = (int) post('id');
    if ($a === 'delete_question') { q('DELETE FROM questions WHERE id = ?', [$id]); flash('success', 'Question deleted.'); }
    elseif ($a === 'close') { q("UPDATE questions SET status='closed' WHERE id = ?", [$id]); flash('success', 'Question closed. No new answers can be posted.'); }
    elseif ($a === 'reopen') { q("UPDATE questions SET status = IF(EXISTS (SELECT 1 FROM answers WHERE question_id = ? AND is_accepted = 1), 'solved', 'open') WHERE id = ?", [$id, $id]); flash('success', 'Question reopened.'); }
    elseif ($a === 'delete_answer') {
        $qid = (int) val('SELECT question_id FROM answers WHERE id = ?', [$id]);
        q('DELETE FROM answers WHERE id = ?', [$id]);
        if ($qid && !val('SELECT COUNT(*) FROM answers WHERE question_id = ? AND is_accepted = 1', [$qid])) q("UPDATE questions SET status='open' WHERE id = ? AND status='solved'", [$qid]);
        flash('success', 'Answer deleted.');
    }
    redirect('admin/forum.php?tab=' . $tab);
}
$qs = get_str('q'); $like = "%$qs%";
$questions = $tab === 'questions' ? rows('SELECT q.*, u.full_name, s.code, (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) AS n FROM questions q JOIN users u ON u.id = q.user_id JOIN subjects s ON s.id = q.subject_id WHERE q.title LIKE ? OR q.body LIKE ? ORDER BY q.created_at DESC LIMIT 100', [$like, $like]) : [];
$answers = $tab === 'answers' ? rows('SELECT a.*, u.full_name, q.title AS qtitle FROM answers a JOIN users u ON u.id = a.user_id JOIN questions q ON q.id = a.question_id WHERE a.body LIKE ? ORDER BY a.created_at DESC LIMIT 100', [$like]) : [];
$pageTitle = 'Forum moderation'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="btn-row" style="margin-bottom:16px">
  <a class="btn btn-sm <?= $tab === 'questions' ? 'btn-primary' : 'btn-ghost' ?>" href="?tab=questions">Questions</a>
  <a class="btn btn-sm <?= $tab === 'answers' ? 'btn-primary' : 'btn-ghost' ?>" href="?tab=answers">Answers</a>
  <form method="get" class="inline" style="margin-left:auto"><input type="hidden" name="tab" value="<?= h($tab) ?>"><input type="search" name="q" value="<?= h($qs) ?>" placeholder="Search text" style="width:220px" aria-label="Search"></form>
</div>
<?php if ($tab === 'questions'): ?>
  <?php if (!$questions): ?><div class="empty"><b>No questions found</b></div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Question</th><th>Subject</th><th>Author</th><th>Answers</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($questions as $x): ?>
    <tr><td style="max-width:320px"><a href="<?= h(url('student/question_view.php?id=' . $x['id'])) ?>"><b><?= h($x['title']) ?></b></a><div class="muted small"><?= h(time_ago($x['created_at'])) ?></div></td>
      <td><?= h($x['code']) ?></td><td><?= h($x['full_name']) ?></td><td><?= (int) $x['n'] ?></td><td><?= badge($x['status']) ?></td>
      <td class="actions">
        <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="action" value="<?= $x['status'] === 'closed' ? 'reopen' : 'close' ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= $x['status'] === 'closed' ? 'Reopen' : 'Close' ?></button></form>
        <form class="inline" method="post" data-confirm="Delete this question and its answers?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="action" value="delete_question"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php else: ?>
  <?php if (!$answers): ?><div class="empty"><b>No answers found</b></div><?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>Answer</th><th>On question</th><th>Author</th><th>Posted</th><th></th></tr></thead><tbody>
  <?php foreach ($answers as $x): ?>
    <tr><td style="max-width:360px"><?= h(mb_strimwidth($x['body'], 0, 160, '...')) ?><?= $x['is_accepted'] ? ' <span class="badge badge-green">Accepted</span>' : '' ?></td>
      <td><a href="<?= h(url('student/question_view.php?id=' . $x['question_id'])) ?>"><?= h(mb_strimwidth($x['qtitle'], 0, 50, '...')) ?></a></td><td><?= h($x['full_name']) ?></td><td><?= h(time_ago($x['created_at'])) ?></td>
      <td class="actions"><form class="inline" method="post" data-confirm="Delete this answer?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>"><input type="hidden" name="action" value="delete_answer"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
