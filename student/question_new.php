<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
$id = get_int('id');
$q = ['title' => '', 'body' => '', 'subject_id' => 0];
if ($id) {
    $q = row('SELECT * FROM questions WHERE id = ? AND (user_id = ? OR ?)', [$id, $uid, is_admin() ? 1 : 0]);
    if (!$q) { flash('error', 'Question not found.'); redirect('student/forum.php'); }
}
$subjects = rows('SELECT id, code, name FROM subjects ORDER BY code');
if (is_post()) {
    verify_csrf();
    $q['title'] = post('title'); $q['body'] = post('body'); $q['subject_id'] = (int) post('subject_id');
    if (mb_strlen($q['title']) < 8 || mb_strlen($q['title']) > 200) flash('error', 'Title must be 8 to 200 characters.');
    elseif (mb_strlen($q['body']) < 15) flash('error', 'Describe your question in at least 15 characters.');
    elseif (!val('SELECT id FROM subjects WHERE id = ?', [$q['subject_id']])) flash('error', 'Choose a subject.');
    else {
        if ($id) {
            q('UPDATE questions SET title = ?, body = ?, subject_id = ? WHERE id = ?', [$q['title'], $q['body'], $q['subject_id'], $id]);
            flash('success', 'Question updated.');
        } else {
            q('INSERT INTO questions (user_id, subject_id, title, body) VALUES (?,?,?,?)', [$uid, $q['subject_id'], $q['title'], $q['body']]);
            $id = (int) db()->lastInsertId();
            flash('success', 'Your question is posted.');
        }
        redirect('student/question_view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit question' : 'Ask a question'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1><?= h($pageTitle) ?></h1><p>Clear questions get faster answers: say what you tried and where you got stuck.</p></div></div>
<div class="card form-narrow">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="title">Question title</label><input type="text" id="title" name="title" value="<?= h($q['title']) ?>" required minlength="8" maxlength="200"></div>
    <div class="field"><label for="subject_id">Subject</label><select id="subject_id" name="subject_id" required><option value="">Choose...</option>
      <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= (int) $q['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code'] . ' - ' . $s['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="body">Details</label><textarea id="body" name="body" required minlength="15" style="min-height:160px"><?= h($q['body']) ?></textarea></div>
    <div class="btn-row"><button class="btn btn-primary" type="submit"><?= $id ? 'Save changes' : 'Post question' ?></button><a class="btn btn-ghost" href="<?= h(url('student/forum.php')) ?>">Cancel</a></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
