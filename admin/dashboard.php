<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$s = [
  'users'     => (int) val("SELECT COUNT(*) FROM users WHERE role='student'"),
  'approved'  => (int) val("SELECT COUNT(*) FROM resources WHERE status='approved'"),
  'pending'   => (int) val("SELECT COUNT(*) FROM resources WHERE status='pending'"),
  'downloads' => (int) val('SELECT COALESCE(SUM(downloads),0) FROM resources'),
  'questions' => (int) val('SELECT COUNT(*) FROM questions'),
  'unanswered'=> (int) val("SELECT COUNT(*) FROM questions q WHERE NOT EXISTS (SELECT 1 FROM answers a WHERE a.question_id = q.id)"),
  'groups'    => (int) val('SELECT COUNT(*) FROM study_groups'),
  'sessions'  => (int) val('SELECT COUNT(*) FROM study_sessions WHERE session_date >= CURDATE()'),
];
$queue = rows("SELECT r.id, r.title, r.created_at, u.full_name, sj.code FROM resources r JOIN users u ON u.id = r.user_id JOIN subjects sj ON sj.id = r.subject_id WHERE r.status='pending' ORDER BY r.created_at LIMIT 6");
$bySubject = rows("SELECT sj.code, COUNT(r.id) AS n FROM subjects sj LEFT JOIN resources r ON r.subject_id = sj.id AND r.status='approved' GROUP BY sj.id, sj.code ORDER BY n DESC, sj.code");
$max = max(1, ...array_map(function ($x) { return (int) $x['n']; }, $bySubject ?: [['n' => 1]]));
$newUsers = rows("SELECT full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$pageTitle = 'Dashboard'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g4" style="margin-bottom:24px">
  <div class="stat"><b><?= $s['users'] ?></b><span>Students</span></div>
  <div class="stat blue"><b><?= $s['approved'] ?></b><span>Approved materials</span></div>
  <div class="stat"><b><?= $s['pending'] ?></b><span>Waiting for approval</span></div>
  <div class="stat blue"><b><?= $s['downloads'] ?></b><span>Total downloads</span></div>
  <div class="stat blue"><b><?= $s['questions'] ?></b><span>Forum questions (<?= $s['unanswered'] ?> unanswered)</span></div>
  <div class="stat"><b><?= $s['groups'] ?></b><span>Study groups</span></div>
  <div class="stat blue"><b><?= $s['sessions'] ?></b><span>Upcoming sessions</span></div>
</div>
<div class="split">
  <div class="card">
    <div class="card-head"><h2>Approval queue</h2><a href="<?= h(url('admin/resources.php?status=pending')) ?>">View all</a></div>
    <?php if (!$queue): ?><p class="muted">All caught up. Nothing is waiting for review.</p><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Title</th><th>Subject</th><th>By</th><th>Sent</th></tr></thead><tbody>
      <?php foreach ($queue as $r): ?><tr><td><a href="<?= h(url('admin/resources.php?status=pending')) ?>"><?= h($r['title']) ?></a></td><td><?= h($r['code']) ?></td><td><?= h($r['full_name']) ?></td><td><?= h(time_ago($r['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <div class="card-head" style="margin-top:24px"><h2>Newest members</h2><a href="<?= h(url('admin/users.php')) ?>">Manage users</a></div>
    <?php foreach ($newUsers as $u): ?><div class="list-item"><div><b><?= h($u['full_name']) ?></b><div class="muted small"><?= h($u['email']) ?></div></div><span class="muted small"><?= h(time_ago($u['created_at'])) ?></span></div><?php endforeach; ?>
  </div>
  <div class="card">
    <h2>Materials by subject</h2>
    <?php foreach ($bySubject as $b): ?>
      <div class="bar-row"><span><?= h($b['code']) ?></span><span class="bar-track"><span class="bar-fill" style="width:<?= round($b['n'] / $max * 100) ?>%"></span></span><b><?= (int) $b['n'] ?></b></div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
