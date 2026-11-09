<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
if (is_post()) {
    verify_csrf();
    $id = (int) post('id'); $a = post('action');
    $r = row('SELECT id, file_path FROM resources WHERE id = ?', [$id]);
    if ($r && $a === 'approve') { q("UPDATE resources SET status='approved', admin_note=NULL WHERE id=?", [$id]); flash('success', 'Approved. It is now visible to all students.'); }
    elseif ($r && $a === 'reject') { q("UPDATE resources SET status='rejected', admin_note=? WHERE id=?", [mb_substr(post('note'), 0, 250) ?: null, $id]); flash('success', 'Rejected. The uploader can see your note.'); }
    elseif ($r && $a === 'delete') { q('DELETE FROM resources WHERE id=?', [$id]); delete_upload($r['file_path']); flash('success', 'Material deleted.'); }
    redirect('admin/resources.php?' . http_build_query(['status' => get_str('status', 'pending')]));
}
$st = get_str('status', 'pending'); $qs = get_str('q'); $sub = get_int('subject');
$where = ['1=1']; $p = [];
if (in_array($st, ['pending', 'approved', 'rejected'], true)) { $where[] = 'r.status = ?'; $p[] = $st; }
if ($qs !== '') { $where[] = '(r.title LIKE ? OR u.full_name LIKE ?)'; array_push($p, "%$qs%", "%$qs%"); }
if ($sub) { $where[] = 'r.subject_id = ?'; $p[] = $sub; }
$per = 12; $page = page_num();
$total = (int) val('SELECT COUNT(*) FROM resources r JOIN users u ON u.id = r.user_id WHERE ' . implode(' AND ', $where), $p);
$list = rows(RESOURCE_SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY r.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
$subjects = rows('SELECT id, code FROM subjects ORDER BY code');
$counts = rows('SELECT status, COUNT(*) n FROM resources GROUP BY status'); $cm = array_column($counts, 'n', 'status');
$pageTitle = 'Notes & materials'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="btn-row" style="margin-bottom:16px">
  <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $l): ?>
    <a class="btn btn-sm <?= $st === $k ? 'btn-primary' : 'btn-ghost' ?>" href="?status=<?= $k ?>"><?= $l ?><?= $k !== 'all' ? ' (' . (int) ($cm[$k] ?? 0) . ')' : '' ?></a>
  <?php endforeach; ?>
</div>
<form class="filters" method="get"><input type="hidden" name="status" value="<?= h($st) ?>">
  <div class="grow"><label for="q">Search</label><input type="search" id="q" name="q" value="<?= h($qs) ?>" placeholder="Title or uploader"></div>
  <div><label for="subject">Subject</label><select id="subject" name="subject" data-autosubmit><option value="0">All</option><?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $sub === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code']) ?></option><?php endforeach; ?></select></div>
  <div style="flex:0 0 auto"><button class="btn btn-blue" type="submit">Search</button></div>
</form>
<?php if (!$list): ?><div class="empty"><b>Nothing here</b>No materials match this view.</div><?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Title</th><th>Subject</th><th>Uploader</th><th>File</th><th>Status</th><th>Review</th></tr></thead><tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td style="max-width:260px"><a href="<?= h(url('student/resource_view.php?id=' . $r['id'])) ?>"><b><?= h($r['title']) ?></b></a><div class="muted small"><?= h($r['category_name']) ?> &middot; <?= h(time_ago($r['created_at'])) ?></div>
        <?php if ($r['admin_note']): ?><div class="small" style="color:var(--red)">Note: <?= h($r['admin_note']) ?></div><?php endif; ?></td>
      <td><?= h($r['subject_code']) ?></td><td><?= h($r['full_name']) ?></td>
      <td><a href="<?= h(url('student/download.php?id=' . $r['id'])) ?>"><?= h(format_bytes((int) $r['file_size'])) ?></a></td>
      <td><?= badge($r['status']) ?></td>
      <td class="actions">
        <?php if ($r['status'] !== 'approved'): ?>
          <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-primary btn-sm" type="submit">Approve</button></form>
        <?php endif; ?>
        <?php if ($r['status'] !== 'rejected'): ?>
          <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="reject">
            <input type="text" name="note" placeholder="Reason (optional)" maxlength="250" style="width:150px;padding:5px 8px;font-size:.85rem" aria-label="Rejection reason"><button class="btn btn-ghost btn-sm" type="submit">Reject</button></form>
        <?php endif; ?>
        <form class="inline" method="post" data-confirm="Delete this material permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?= pager($total, $per, $page) ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
