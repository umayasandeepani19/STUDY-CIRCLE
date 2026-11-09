<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
if (is_post()) {
    verify_csrf();
    q('DELETE FROM resource_comments WHERE id = ?', [(int) post('id')]);
    flash('success', 'Comment deleted.');
    redirect('admin/comments.php');
}
$qs = get_str('q');
$list = rows('SELECT c.*, u.full_name, r.title FROM resource_comments c JOIN users u ON u.id = c.user_id JOIN resources r ON r.id = c.resource_id WHERE c.body LIKE ? ORDER BY c.created_at DESC LIMIT 100', ["%$qs%"]);
$pageTitle = 'Comments'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<form class="filters" method="get"><div class="grow"><label for="q">Search comments</label><input type="search" id="q" name="q" value="<?= h($qs) ?>"></div><div style="flex:0 0 auto"><button class="btn btn-blue" type="submit">Search</button></div></form>
<?php if (!$list): ?><div class="empty"><b>No comments found</b></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Comment</th><th>Author</th><th>On material</th><th>Posted</th><th></th></tr></thead><tbody>
<?php foreach ($list as $c): ?>
  <tr><td style="max-width:360px"><?= h($c['body']) ?></td><td><?= h($c['full_name']) ?></td>
    <td><a href="<?= h(url('student/resource_view.php?id=' . $c['resource_id'])) ?>"><?= h(mb_strimwidth($c['title'], 0, 40, '...')) ?></a></td><td><?= h(time_ago($c['created_at'])) ?></td>
    <td><form class="inline" method="post" data-confirm="Delete this comment?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
