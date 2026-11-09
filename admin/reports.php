<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (get_str('export') !== '') {                      // CSV download
    $type = get_str('export');
    $sets = [
        'resources' => ['SELECT r.id, r.title, s.code AS subject, c.name AS category, u.full_name AS uploader, r.status, r.downloads, r.created_at FROM resources r JOIN subjects s ON s.id=r.subject_id JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.user_id ORDER BY r.id'],
        'users'     => ['SELECT id, full_name, email, student_id, faculty, role, status, created_at FROM users ORDER BY id'],
        'questions' => ['SELECT q.id, q.title, s.code AS subject, u.full_name AS author, q.status, (SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) AS answers, q.created_at FROM questions q JOIN subjects s ON s.id=q.subject_id JOIN users u ON u.id=q.user_id ORDER BY q.id'],
    ];
    if (!isset($sets[$type])) redirect('admin/reports.php');
    $data = rows($sets[$type][0]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="studycircle_' . $type . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($data) fputcsv($out, array_keys($data[0]));
    foreach ($data as $r) fputcsv($out, $r);
    exit;
}

$totals = [
  'Registered students' => (int) val("SELECT COUNT(*) FROM users WHERE role='student'"),
  'Approved materials'  => (int) val("SELECT COUNT(*) FROM resources WHERE status='approved'"),
  'Total downloads'     => (int) val('SELECT COUNT(*) FROM downloads'),
  'Ratings given'       => (int) val('SELECT COUNT(*) FROM resource_ratings'),
  'Comments posted'     => (int) val('SELECT COUNT(*) FROM resource_comments'),
  'Questions asked'     => (int) val('SELECT COUNT(*) FROM questions'),
  'Answers given'       => (int) val('SELECT COUNT(*) FROM answers'),
  'Study groups'        => (int) val('SELECT COUNT(*) FROM study_groups'),
  'Study sessions'      => (int) val('SELECT COUNT(*) FROM study_sessions'),
];
$topDown = rows("SELECT r.id, r.title, s.code, r.downloads FROM resources r JOIN subjects s ON s.id=r.subject_id WHERE r.status='approved' ORDER BY r.downloads DESC, r.title LIMIT 5");
$topRated = rows("SELECT r.id, r.title, s.code, AVG(x.rating) AS avg_r, COUNT(x.id) AS n FROM resources r JOIN subjects s ON s.id=r.subject_id JOIN resource_ratings x ON x.resource_id=r.id WHERE r.status='approved' GROUP BY r.id, r.title, s.code ORDER BY avg_r DESC, n DESC LIMIT 5");
$bySubject = rows("SELECT s.code, s.name, COUNT(DISTINCT r.id) AS res, COALESCE(SUM(r.downloads),0) AS dl,
    (SELECT COUNT(*) FROM questions q WHERE q.subject_id = s.id) AS qs
    FROM subjects s LEFT JOIN resources r ON r.subject_id = s.id AND r.status='approved' GROUP BY s.id, s.code, s.name ORDER BY dl DESC, res DESC");
$contrib = rows("SELECT u.full_name, COUNT(DISTINCT r.id) AS n_up, (SELECT COUNT(*) FROM answers a WHERE a.user_id = u.id) AS n_ans
    FROM users u LEFT JOIN resources r ON r.user_id = u.id AND r.status='approved' WHERE u.role='student' GROUP BY u.id, u.full_name ORDER BY n_up + n_ans DESC, u.full_name LIMIT 5");
$months = rows("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS n FROM resources WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY m");
$mmax = max(1, ...array_map(function ($x) { return (int) $x['n']; }, $months ?: [['n' => 1]]));
$pageTitle = 'Reports'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="btn-row no-print" style="margin-bottom:18px">
  <button class="btn btn-ghost btn-sm" type="button" onclick="window.print()">Print report</button>
  <a class="btn btn-ghost btn-sm" href="?export=resources">Export materials (CSV)</a>
  <a class="btn btn-ghost btn-sm" href="?export=users">Export users (CSV)</a>
  <a class="btn btn-ghost btn-sm" href="?export=questions">Export questions (CSV)</a>
</div>
<p class="muted">Generated <?= h(date('j M Y, g:i A')) ?></p>
<div class="grid g3" style="margin-bottom:24px">
  <?php $i = 0; foreach ($totals as $l => $n): ?><div class="stat <?= $i++ % 2 ? 'blue' : '' ?>"><b><?= $n ?></b><span><?= h($l) ?></span></div><?php endforeach; ?>
</div>
<div class="grid g2">
  <div class="card"><h2>Most downloaded</h2>
    <?php if (!$topDown) echo '<p class="muted">No data yet.</p>'; foreach ($topDown as $r): ?>
      <div class="list-item"><div><a href="<?= h(url('student/resource_view.php?id=' . $r['id'])) ?>"><?= h($r['title']) ?></a> <span class="tag green"><?= h($r['code']) ?></span></div><b><?= (int) $r['downloads'] ?></b></div><?php endforeach; ?></div>
  <div class="card" style="margin-top:0"><h2>Top rated</h2>
    <?php if (!$topRated) echo '<p class="muted">No ratings yet.</p>'; foreach ($topRated as $r): ?>
      <div class="list-item"><div><a href="<?= h(url('student/resource_view.php?id=' . $r['id'])) ?>"><?= h($r['title']) ?></a> <span class="tag green"><?= h($r['code']) ?></span></div><div><?= stars((float) $r['avg_r'], (int) $r['n']) ?></div></div><?php endforeach; ?></div>
</div>
<div class="card"><h2>Engagement by subject</h2>
  <div class="table-wrap"><table><thead><tr><th>Subject</th><th>Approved materials</th><th>Downloads</th><th>Forum questions</th></tr></thead><tbody>
  <?php foreach ($bySubject as $s): ?><tr><td><b><?= h($s['code']) ?></b> <span class="muted"><?= h($s['name']) ?></span></td><td><?= (int) $s['res'] ?></td><td><?= (int) $s['dl'] ?></td><td><?= (int) $s['qs'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<div class="grid g2" style="margin-top:16px">
  <div class="card" style="margin-top:0"><h2>Top contributors</h2>
    <?php if (!$contrib) echo '<p class="muted">No students yet.</p>'; foreach ($contrib as $c): ?>
      <div class="list-item"><b><?= h($c['full_name']) ?></b><span class="muted"><?= (int) $c['n_up'] ?> uploads &middot; <?= (int) $c['n_ans'] ?> answers</span></div><?php endforeach; ?></div>
  <div class="card" style="margin-top:0"><h2>Uploads, last 6 months</h2>
    <?php if (!$months) echo '<p class="muted">No uploads in this period.</p>'; foreach ($months as $m): ?>
      <div class="bar-row"><span><?= h(date('M Y', strtotime($m['m'] . '-01'))) ?></span><span class="bar-track"><span class="bar-fill blue" style="width:<?= round($m['n'] / $mmax * 100) ?>%"></span></span><b><?= (int) $m['n'] ?></b></div><?php endforeach; ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
