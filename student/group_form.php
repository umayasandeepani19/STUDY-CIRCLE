<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id']; $id = get_int('id');
$g = ['name' => '', 'description' => '', 'subject_id' => 0, 'max_members' => 10];
if ($id) {
    $g = row('SELECT * FROM study_groups WHERE id = ? AND (created_by = ? OR ?)', [$id, $uid, is_admin() ? 1 : 0]);
    if (!$g) { flash('error', 'You can only edit groups you created.'); redirect('student/groups.php'); }
}
$subjects = rows('SELECT id, code, name FROM subjects ORDER BY code');
if (is_post()) {
    verify_csrf();
    $g['name'] = post('name'); $g['description'] = post('description'); $g['subject_id'] = (int) post('subject_id'); $g['max_members'] = (int) post('max_members');
    $min = $id ? (int) val('SELECT COUNT(*) FROM group_members WHERE group_id = ?', [$id]) : 1;
    if (mb_strlen($g['name']) < 3 || mb_strlen($g['name']) > 120) flash('error', 'Group name must be 3 to 120 characters.');
    elseif (!val('SELECT id FROM subjects WHERE id = ?', [$g['subject_id']])) flash('error', 'Choose a subject.');
    elseif ($g['max_members'] < max(2, $min) || $g['max_members'] > 50) flash('error', 'Maximum members must be between ' . max(2, $min) . ' and 50.');
    else {
        if ($id) {
            q('UPDATE study_groups SET name=?, description=?, subject_id=?, max_members=? WHERE id=?', [$g['name'], $g['description'] ?: null, $g['subject_id'], $g['max_members'], $id]);
            flash('success', 'Group updated.');
        } else {
            q('INSERT INTO study_groups (name, description, subject_id, created_by, max_members) VALUES (?,?,?,?,?)', [$g['name'], $g['description'] ?: null, $g['subject_id'], $uid, $g['max_members']]);
            $id = (int) db()->lastInsertId();
            q('INSERT INTO group_members (group_id, user_id) VALUES (?,?)', [$id, $uid]);
            flash('success', 'Group created. Invite classmates and schedule your first session.');
        }
        redirect('student/group_view.php?id=' . $id);
    }
}
$pageTitle = $id ? 'Edit group' : 'Create a study group'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1><?= h($pageTitle) ?></h1></div></div>
<div class="card form-narrow">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="name">Group name</label><input type="text" id="name" name="name" value="<?= h($g['name']) ?>" required minlength="3" maxlength="120"></div>
    <div class="grid g2">
      <div class="field"><label for="subject_id">Subject</label><select id="subject_id" name="subject_id" required><option value="">Choose...</option>
        <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= (int) $g['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code'] . ' - ' . $s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="max_members">Maximum members</label><input type="number" id="max_members" name="max_members" value="<?= (int) $g['max_members'] ?>" min="2" max="50" required></div>
    </div>
    <div class="field"><label for="description">What will you study?</label><textarea id="description" name="description" maxlength="500"><?= h($g['description']) ?></textarea></div>
    <div class="btn-row"><button class="btn btn-primary" type="submit"><?= $id ? 'Save changes' : 'Create group' ?></button><a class="btn btn-ghost" href="<?= h(url('student/groups.php')) ?>">Cancel</a></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
