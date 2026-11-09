<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
$my = [
  'uploads'   => (int) val('SELECT COUNT(*) FROM resources WHERE user_id = ?', [$uid]),
  'downloads' => (int) val('SELECT COALESCE(SUM(downloads),0) FROM resources WHERE user_id = ?', [$uid]),
  'questions' => (int) val('SELECT COUNT(*) FROM questions WHERE user_id = ?', [$uid]),
  'groups'    => (int) val('SELECT COUNT(*) FROM group_members WHERE user_id = ?', [$uid]),
];
$latest = rows(RESOURCE_SELECT . " WHERE r.status='approved' ORDER BY r.created_at DESC LIMIT 3");
$sessions = rows("SELECT ss.*, g.name AS group_name, g.id AS gid FROM study_sessions ss
    JOIN study_groups g ON g.id = ss.group_id JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ?
    WHERE ss.session_date >= CURDATE() ORDER BY ss.session_date, ss.session_time LIMIT 4", [$uid]);
$open = rows("SELECT q.id, q.title, s.code, (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) AS n
    FROM questions q JOIN subjects s ON s.id = q.subject_id WHERE q.status='open' ORDER BY n ASC, q.created_at DESC LIMIT 4");
$pending = (int) val("SELECT COUNT(*) FROM resources WHERE user_id = ? AND status='pending'", [$uid]);
$pageTitle = 'Dashboard'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Hi, <?= h(explode(' ', current_user()['full_name'])[0]) ?></h1><p>Here is what is happening in your study circle.</p></div>
  <a class="btn btn-primary" href="<?= h(url('student/upload.php')) ?>">Upload notes</a>
</div>
<?php if ($pending): ?><div class="alert alert-info"><?= $pending ?> of your uploads <?= $pending === 1 ? 'is' : 'are' ?> waiting for admin approval.</div><?php endif; ?>
<div class="grid g4" style="margin-bottom:24px">
  <div class="stat"><b><?= $my['uploads'] ?></b><span>My uploads</span></div>
  <div class="stat blue"><b><?= $my['downloads'] ?></b><span>Downloads of my notes</span></div>
  <div class="stat"><b><?= $my['questions'] ?></b><span>Questions asked</span></div>
  <div class="stat blue"><b><?= $my['groups'] ?></b><span>Study groups joined</span></div>
</div>
<div class="split">
  <div>
    <div class="card-head"><h2>Newest study materials</h2><a href="<?= h(url('student/resources.php')) ?>">Browse all</a></div>
    <?php if (!$latest): ?><div class="empty"><b>No approved materials yet</b>Be the first to share your notes.</div>
    <?php else: ?><div class="res-grid"><?php foreach ($latest as $r) echo resource_card($r); ?></div><?php endif; ?>
    <div class="card" style="margin-top:20px">
      <div class="card-head"><h3>Questions that need an answer</h3><a href="<?= h(url('student/forum.php')) ?>">Open forum</a></div>
      <?php if (!$open): ?><p class="muted">Nothing waiting. Nice.</p><?php endif; ?>
      <?php foreach ($open as $q): ?>
        <div class="list-item"><div><h3><a href="<?= h(url('student/question_view.php?id=' . $q['id'])) ?>"><?= h($q['title']) ?></a></h3><span class="tag green"><?= h($q['code']) ?></span></div>
        <div class="side-count"><b><?= (int) $q['n'] ?></b>answers</div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <aside class="card">
    <div class="card-head"><h3>Upcoming study sessions</h3></div>
    <?php if (!$sessions): ?><p class="muted">No sessions scheduled. <a href="<?= h(url('student/groups.php')) ?>">Find a group</a> or start your own.</p><?php endif; ?>
    <?php foreach ($sessions as $s): ?>
      <div class="list-item"><div>
        <h3><a href="<?= h(url('student/group_view.php?id=' . $s['gid'])) ?>"><?= h($s['title']) ?></a></h3>
        <div class="muted small"><?= h(date('D, j M', strtotime($s['session_date']))) ?> at <?= h(date('g:i A', strtotime($s['session_time']))) ?><br><?= h($s['location']) ?> &middot; <?= h($s['group_name']) ?></div>
      </div></div>
    <?php endforeach; ?>
  </aside>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
