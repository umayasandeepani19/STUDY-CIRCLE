<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
if (is_post()) {
    verify_csrf();
    $r = row('SELECT id, file_path FROM resources WHERE id = ? AND user_id = ?', [(int) post('id'), $uid]);
    if ($r) {
        q('DELETE FROM resources WHERE id = ?', [$r['id']]);
        delete_upload($r['file_path']);
        flash('success', 'Upload deleted.');
    }
    redirect('student/my_uploads.php');
}
$list = rows(RESOURCE_SELECT . ' WHERE r.user_id = ? ORDER BY r.created_at DESC', [$uid]);
$pageTitle = 'My uploads'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>My uploads</h1><p>Track approval and see how often your notes are downloaded.</p></div>
  <a class="btn btn-primary" href="<?= h(url('student/upload.php')) ?>">Upload notes</a></div>
<?php if (!$list): ?>
  <div class="empty"><b>You have not uploaded anything yet</b>Share a set of notes to help the next batch.</div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Title</th><th>Subject</th><th>Status</th><th>Downloads</th><th>Rating</th><th>Uploaded</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td><a href="<?= h(url('student/resource_view.php?id=' . $r['id'])) ?>"><b><?= h($r['title']) ?></b></a>
        <?php if ($r['status'] === 'rejected' && $r['admin_note']): ?><div class="small" style="color:var(--red)">Admin note: <?= h($r['admin_note']) ?></div><?php endif; ?></td>
      <td><?= h($r['subject_code']) ?></td><td><?= badge($r['status']) ?></td><td><?= (int) $r['downloads'] ?></td>
      <td><?= stars($r['avg_rating'] !== null ? (float) $r['avg_rating'] : null, (int) $r['rating_count']) ?></td>
      <td><?= h(date('j M Y', strtotime($r['created_at']))) ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= h(url('student/edit_resource.php?id=' . $r['id'])) ?>">Edit</a>
        <form class="inline" method="post" data-confirm="Delete this upload permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
