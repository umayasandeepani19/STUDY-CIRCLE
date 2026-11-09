<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id']; $admin = is_admin(); $id = get_int('id');
$g = row('SELECT g.*, s.code, s.name AS subject_name, u.full_name AS owner FROM study_groups g JOIN subjects s ON s.id = g.subject_id JOIN users u ON u.id = g.created_by WHERE g.id = ?', [$id]);
if (!$g) { flash('error', 'Group not found.'); redirect('student/groups.php'); }
$isOwner = (int) $g['created_by'] === $uid;
$isMember = (bool) val('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?', [$id, $uid]);
$count = (int) val('SELECT COUNT(*) FROM group_members WHERE group_id = ?', [$id]);

if (is_post()) {
    verify_csrf();
    $a = post('action');
    if ($a === 'join' && !$isMember) {
        if ($count >= (int) $g['max_members']) flash('error', 'This group is full.');
        else { q('INSERT INTO group_members (group_id, user_id) VALUES (?,?)', [$id, $uid]); flash('success', 'You joined the group.'); }
    } elseif ($a === 'leave' && $isMember && !$isOwner) {
        q('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$id, $uid]); flash('success', 'You left the group.');
    } elseif ($a === 'remove' && ($isOwner || $admin)) {
        $m = (int) post('user_id');
        if ($m !== (int) $g['created_by']) { q('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$id, $m]); flash('success', 'Member removed.'); }
    } elseif ($a === 'delete_session') {
        q('DELETE FROM study_sessions WHERE id = ? AND group_id = ? AND (created_by = ? OR ? OR ?)', [(int) post('session_id'), $id, $uid, $isOwner ? 1 : 0, $admin ? 1 : 0]);
        flash('success', 'Session deleted.');
    } elseif ($a === 'delete_group' && ($isOwner || $admin)) {
        q('DELETE FROM study_groups WHERE id = ?', [$id]);
        flash('success', 'Group deleted.');
        redirect('student/groups.php');
    }
    redirect('student/group_view.php?id=' . $id);
}
$members = rows('SELECT u.id, u.full_name, u.faculty, gm.joined_at FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY gm.joined_at', [$id]);
$upcoming = rows('SELECT ss.*, u.full_name FROM study_sessions ss JOIN users u ON u.id = ss.created_by WHERE ss.group_id = ? AND ss.session_date >= CURDATE() ORDER BY ss.session_date, ss.session_time', [$id]);
$past = rows('SELECT ss.* FROM study_sessions ss WHERE ss.group_id = ? AND ss.session_date < CURDATE() ORDER BY ss.session_date DESC LIMIT 5', [$id]);
$pageTitle = $g['name']; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= h(url('student/groups.php')) ?>">&larr; Back to study groups</a></p>
<div class="card">
  <div class="card-head">
    <div><span class="tag green"><?= h($g['code']) ?></span> <span class="muted small"><?= h($g['subject_name']) ?></span><h1 style="margin:6px 0 0"><?= h($g['name']) ?></h1></div>
    <div class="btn-row">
      <?php if ($isMember && !$isOwner): ?><form method="post" class="inline" data-confirm="Leave this group?"><?= csrf_field() ?><input type="hidden" name="action" value="leave"><button class="btn btn-ghost" type="submit">Leave group</button></form>
      <?php elseif (!$isMember): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="join"><button class="btn btn-primary" type="submit" <?= $count >= (int) $g['max_members'] ? 'disabled' : '' ?>><?= $count >= (int) $g['max_members'] ? 'Group is full' : 'Join group' ?></button></form><?php endif; ?>
      <?php if ($isOwner || $admin): ?>
        <a class="btn btn-ghost" href="<?= h(url('student/group_form.php?id=' . $id)) ?>">Edit</a>
        <form method="post" class="inline" data-confirm="Delete this group and all its sessions?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_group"><button class="btn btn-danger" type="submit">Delete</button></form>
      <?php endif; ?>
    </div>
  </div>
  <p class="prewrap"><?= h($g['description'] ?: 'No description yet.') ?></p>
  <p class="muted small" style="margin:0">Created by <?= h($g['owner']) ?> &middot; <?= $count ?>/<?= (int) $g['max_members'] ?> members</p>
</div>

<div class="split" style="margin-top:20px">
  <div class="card">
    <div class="card-head"><h2>Upcoming sessions</h2>
      <?php if ($isMember): ?><a class="btn btn-blue btn-sm" href="<?= h(url('student/session_form.php?group_id=' . $id)) ?>">Schedule a session</a><?php endif; ?></div>
    <?php if (!$upcoming): ?><p class="muted">No sessions planned.<?= $isMember ? ' Schedule the first one.' : ' Join the group to schedule one.' ?></p><?php endif; ?>
    <?php foreach ($upcoming as $s): ?>
      <div class="list-item"><div>
        <h3><?= h($s['title']) ?></h3>
        <div class="small"><b><?= h(date('D, j M Y', strtotime($s['session_date']))) ?></b> at <b><?= h(date('g:i A', strtotime($s['session_time']))) ?></b> &middot; <?= h($s['location']) ?></div>
        <?php if ($s['details']): ?><div class="muted small"><?= h($s['details']) ?></div><?php endif; ?>
        <div class="muted small">Added by <?= h($s['full_name']) ?></div>
      </div>
      <?php if ((int) $s['created_by'] === $uid || $isOwner || $admin): ?>
      <div class="btn-row" style="align-items:flex-start">
        <a class="btn btn-ghost btn-sm" href="<?= h(url('student/session_form.php?group_id=' . $id . '&id=' . $s['id'])) ?>">Edit</a>
        <form method="post" class="inline" data-confirm="Delete this session?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_session"><input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </div><?php endif; ?></div>
    <?php endforeach; ?>
    <?php if ($past): ?><h3 style="margin-top:20px" class="muted">Recent past sessions</h3>
      <?php foreach ($past as $s): ?><div class="muted small" style="padding:4px 0"><?= h(date('j M Y', strtotime($s['session_date']))) ?> &middot; <?= h($s['title']) ?></div><?php endforeach; ?>
    <?php endif; ?>
  </div>
  <aside class="card">
    <h3>Members (<?= $count ?>)</h3>
    <?php foreach ($members as $m): ?>
      <div class="comment" style="align-items:center"><span class="avatar"><?= h(initials($m['full_name'])) ?></span>
        <div style="flex:1"><b><?= h($m['full_name']) ?></b><?= (int) $m['id'] === (int) $g['created_by'] ? ' <span class="badge badge-blue">Owner</span>' : '' ?><div class="muted small"><?= h($m['faculty'] ?: '') ?></div></div>
        <?php if (($isOwner || $admin) && (int) $m['id'] !== (int) $g['created_by']): ?>
          <form method="post" data-confirm="Remove this member?"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Remove</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </aside>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
