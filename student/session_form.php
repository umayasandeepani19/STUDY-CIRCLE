<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id']; $gid = get_int('group_id'); $id = get_int('id');
$g = row('SELECT * FROM study_groups WHERE id = ?', [$gid]);
if (!$g) { flash('error', 'Group not found.'); redirect('student/groups.php'); }
$isOwner = (int) $g['created_by'] === $uid;
if (!val('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid]) && !is_admin()) {
    flash('error', 'Join the group before scheduling sessions.'); redirect('student/group_view.php?id=' . $gid);
}
$s = ['title' => '', 'session_date' => '', 'session_time' => '', 'location' => '', 'details' => ''];
if ($id) {
    $s = row('SELECT * FROM study_sessions WHERE id = ? AND group_id = ?', [$id, $gid]);
    if (!$s || ((int) $s['created_by'] !== $uid && !$isOwner && !is_admin())) { flash('error', 'You cannot edit that session.'); redirect('student/group_view.php?id=' . $gid); }
    $s['session_time'] = substr($s['session_time'], 0, 5);
}
if (is_post()) {
    verify_csrf();
    foreach (['title', 'session_date', 'session_time', 'location', 'details'] as $k) { $s[$k] = post($k); }
    $d = DateTime::createFromFormat('Y-m-d', $s['session_date']);
    $t = DateTime::createFromFormat('H:i', $s['session_time']);
    if (mb_strlen($s['title']) < 3) flash('error', 'Give the session a title (at least 3 characters).');
    elseif (!$d || $d->format('Y-m-d') !== $s['session_date']) flash('error', 'Choose a valid date.');
    elseif (!$id && $s['session_date'] < date('Y-m-d')) flash('error', 'The date cannot be in the past.');
    elseif (!$t) flash('error', 'Choose a valid time.');
    elseif ($s['location'] === '') flash('error', 'Say where the group will meet.');
    else {
        if ($id) {
            q('UPDATE study_sessions SET title=?, session_date=?, session_time=?, location=?, details=? WHERE id=?', [$s['title'], $s['session_date'], $s['session_time'] . ':00', $s['location'], $s['details'] ?: null, $id]);
            flash('success', 'Session updated.');
        } else {
            q('INSERT INTO study_sessions (group_id, title, session_date, session_time, location, details, created_by) VALUES (?,?,?,?,?,?,?)',
              [$gid, $s['title'], $s['session_date'], $s['session_time'] . ':00', $s['location'], $s['details'] ?: null, $uid]);
            flash('success', 'Session scheduled.');
        }
        redirect('student/group_view.php?id=' . $gid);
    }
}
$pageTitle = $id ? 'Edit session' : 'Schedule a session'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1><?= h($pageTitle) ?></h1><p>Group: <?= h($g['name']) ?></p></div></div>
<div class="card form-narrow">
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="title">Session title</label><input type="text" id="title" name="title" value="<?= h($s['title']) ?>" required minlength="3" maxlength="150" placeholder="e.g. PHP sessions revision"></div>
    <div class="grid g2">
      <div class="field"><label for="session_date">Date</label><input type="date" id="session_date" name="session_date" value="<?= h($s['session_date']) ?>" <?= $id ? '' : 'min="' . date('Y-m-d') . '"' ?> required></div>
      <div class="field"><label for="session_time">Time</label><input type="time" id="session_time" name="session_time" value="<?= h($s['session_time']) ?>" required></div>
    </div>
    <div class="field"><label for="location">Location</label><input type="text" id="location" name="location" value="<?= h($s['location']) ?>" required maxlength="150" placeholder="Room, library table or meeting link"></div>
    <div class="field"><label for="details">Notes <span class="muted">(optional)</span></label><textarea id="details" name="details" maxlength="500" style="min-height:80px"><?= h($s['details']) ?></textarea></div>
    <div class="btn-row"><button class="btn btn-primary" type="submit"><?= $id ? 'Save changes' : 'Schedule session' ?></button><a class="btn btn-ghost" href="<?= h(url('student/group_view.php?id=' . $gid)) ?>">Cancel</a></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
