<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
if (is_post()) {
    verify_csrf();
    $gid = (int) post('group_id');
    $g = row('SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS n FROM study_groups g WHERE g.id = ?', [$gid]);
    if ($g && post('action') === 'join') {
        if (val('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid])) flash('info', 'You are already in this group.');
        elseif ((int) $g['n'] >= (int) $g['max_members']) flash('error', 'This group is full.');
        else { q('INSERT INTO group_members (group_id, user_id) VALUES (?,?)', [$gid, $uid]); flash('success', 'You joined ' . $g['name'] . '.'); }
    } elseif ($g && post('action') === 'leave') {
        if ((int) $g['created_by'] === $uid) flash('error', 'You created this group, so you cannot leave it. Delete the group instead.');
        else { q('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$gid, $uid]); flash('success', 'You left the group.'); }
    }
    redirect('student/groups.php' . (get_str('mine') ? '?mine=1' : ''));
}
$qs = get_str('q'); $sub = get_int('subject'); $mine = get_str('mine') === '1';
$where = ['1=1']; $p = [$uid];
if ($qs !== '') { $where[] = '(g.name LIKE ? OR g.description LIKE ?)'; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($sub) { $where[] = 'g.subject_id = ?'; $p[] = $sub; }
if ($mine) { $where[] = 'EXISTS (SELECT 1 FROM group_members x WHERE x.group_id = g.id AND x.user_id = ' . $uid . ')'; }
$list = rows('SELECT g.*, s.code, u.full_name AS owner,
    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) AS n,
    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND user_id = ?) AS joined
    FROM study_groups g JOIN subjects s ON s.id = g.subject_id JOIN users u ON u.id = g.created_by
    WHERE ' . implode(' AND ', $where) . ' ORDER BY g.created_at DESC', $p);
$subjects = rows('SELECT id, code, name FROM subjects ORDER BY code');
$pageTitle = 'Study groups'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>Study groups</h1><p>Join classmates studying the same module, or start your own.</p></div>
  <a class="btn btn-primary" href="<?= h(url('student/group_form.php')) ?>">Create a group</a></div>
<form class="filters" method="get">
  <div class="grow"><label for="q">Search</label><input type="search" id="q" name="q" value="<?= h($qs) ?>" placeholder="Group name or topic"></div>
  <div><label for="subject">Subject</label><select id="subject" name="subject" data-autosubmit><option value="0">All subjects</option>
    <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $sub === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code']) ?></option><?php endforeach; ?></select></div>
  <div><label for="mine">Show</label><select id="mine" name="mine" data-autosubmit><option value="">All groups</option><option value="1" <?= $mine ? 'selected' : '' ?>>My groups</option></select></div>
  <div style="flex:0 0 auto"><button class="btn btn-blue" type="submit">Search</button> <a class="btn btn-ghost" href="groups.php">Reset</a></div>
</form>
<?php if (!$list): ?>
  <div class="empty"><b>No groups found</b>Start one and invite your classmates.</div>
<?php else: ?>
<div class="res-grid">
  <?php foreach ($list as $g): $full = (int) $g['n'] >= (int) $g['max_members']; ?>
  <article class="res">
    <div class="meta"><span class="tag green"><?= h($g['code']) ?></span><?php if ($g['joined']): ?><span class="badge badge-blue">Member</span><?php endif; ?></div>
    <h3><a href="<?= h(url('student/group_view.php?id=' . $g['id'])) ?>"><?= h($g['name']) ?></a></h3>
    <p class="muted small" style="margin:0"><?= h(mb_strimwidth((string) $g['description'], 0, 110, '...')) ?></p>
    <div class="foot"><span><?= (int) $g['n'] ?>/<?= (int) $g['max_members'] ?> members &middot; by <?= h($g['owner']) ?></span>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
        <?php if ($g['joined']): ?>
          <?php if ((int) $g['created_by'] !== $uid): ?><input type="hidden" name="action" value="leave"><button class="btn btn-ghost btn-sm" type="submit">Leave</button><?php endif; ?>
        <?php elseif ($full): ?><span class="badge badge-grey">Full</span>
        <?php else: ?><input type="hidden" name="action" value="join"><button class="btn btn-primary btn-sm" type="submit">Join</button><?php endif; ?>
      </form></div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
