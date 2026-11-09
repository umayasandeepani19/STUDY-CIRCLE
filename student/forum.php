<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$qs = get_str('q'); $sub = get_int('subject'); $st = get_str('status');
$where = ['1=1']; $p = [];
if ($qs !== '') { $where[] = '(q.title LIKE ? OR q.body LIKE ?)'; $p[] = "%$qs%"; $p[] = "%$qs%"; }
if ($sub) { $where[] = 'q.subject_id = ?'; $p[] = $sub; }
if (in_array($st, ['open', 'solved', 'closed'], true)) { $where[] = 'q.status = ?'; $p[] = $st; }
$per = 10; $page = page_num();
$total = (int) val('SELECT COUNT(*) FROM questions q WHERE ' . implode(' AND ', $where), $p);
$list = rows('SELECT q.*, u.full_name, s.code, (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) AS n
    FROM questions q JOIN users u ON u.id = q.user_id JOIN subjects s ON s.id = q.subject_id
    WHERE ' . implode(' AND ', $where) . " ORDER BY q.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
$subjects = rows('SELECT id, code, name FROM subjects ORDER BY code');
$pageTitle = 'Q&A forum'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>Q&amp;A forum</h1><p>Ask an academic question or help someone with theirs.</p></div>
  <a class="btn btn-primary" href="<?= h(url('student/question_new.php')) ?>">Ask a question</a></div>
<form class="filters" method="get">
  <div class="grow"><label for="q">Search</label><input type="search" id="q" name="q" value="<?= h($qs) ?>" placeholder="Search questions"></div>
  <div><label for="subject">Subject</label><select id="subject" name="subject" data-autosubmit><option value="0">All subjects</option>
    <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $sub === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code']) ?></option><?php endforeach; ?></select></div>
  <div><label for="status">Status</label><select id="status" name="status" data-autosubmit><option value="">Any</option>
    <?php foreach (['open', 'solved', 'closed'] as $o): ?><option value="<?= $o ?>" <?= $st === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option><?php endforeach; ?></select></div>
  <div style="flex:0 0 auto"><button class="btn btn-blue" type="submit">Search</button> <a class="btn btn-ghost" href="forum.php">Reset</a></div>
</form>
<?php if (!$list): ?>
  <div class="empty"><b>No questions found</b>Be the first to ask one.</div>
<?php else: ?>
<div class="card">
  <?php foreach ($list as $q): ?>
    <div class="list-item">
      <div>
        <h3><a href="<?= h(url('student/question_view.php?id=' . $q['id'])) ?>"><?= h($q['title']) ?></a></h3>
        <span class="tag green"><?= h($q['code']) ?></span> <?= badge($q['status']) ?>
        <span class="muted small">asked by <?= h($q['full_name']) ?> &middot; <?= h(time_ago($q['created_at'])) ?></span>
      </div>
      <div class="side-count <?= $q['status'] === 'solved' ? 'ok' : '' ?>"><b><?= (int) $q['n'] ?></b>answers</div>
    </div>
  <?php endforeach; ?>
</div>
<?= pager($total, $per, $page) ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
