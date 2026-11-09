<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$me = (int) current_user()['id'];
if (is_post()) {
    verify_csrf();
    $id = (int) post('id'); $a = post('action');
    $u = row('SELECT id, role FROM users WHERE id = ?', [$id]);
    if (!$u) flash('error', 'User not found.');
    elseif ($id === $me) flash('error', 'You cannot change your own account here.');
    elseif ($a === 'block') { q("UPDATE users SET status='blocked' WHERE id = ?", [$id]); flash('success', 'User blocked. They can no longer log in.'); }
    elseif ($a === 'unblock') { q("UPDATE users SET status='active' WHERE id = ?", [$id]); flash('success', 'User unblocked.'); }
    elseif ($a === 'delete') {
        $files = rows('SELECT file_path FROM resources WHERE user_id = ?', [$id]);
        q('DELETE FROM users WHERE id = ?', [$id]);
        foreach ($files as $f) delete_upload($f['file_path']);
        flash('success', 'User and their content deleted.');
    }
    redirect('admin/users.php');
}
$qs = get_str('q'); $role = get_str('role'); $st = get_str('status');
$where = ['1=1']; $p = [];
if ($qs !== '') { $where[] = '(full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)'; array_push($p, "%$qs%", "%$qs%", "%$qs%"); }
if (in_array($role, ['student', 'admin'], true)) { $where[] = 'role = ?'; $p[] = $role; }
if (in_array($st, ['active', 'blocked'], true)) { $where[] = 'status = ?'; $p[] = $st; }
$per = 15; $page = page_num();
$total = (int) val('SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where), $p);
$list = rows('SELECT u.*, (SELECT COUNT(*) FROM resources WHERE user_id = u.id) AS uploads FROM users u WHERE ' . implode(' AND ', $where) . " ORDER BY u.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
$pageTitle = 'Users'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><p><?= $total ?> account<?= $total === 1 ? '' : 's' ?></p></div><a class="btn btn-primary" href="<?= h(url('admin/user_form.php')) ?>">Add user</a></div>
<form class="filters" method="get">
  <div class="grow"><label for="q">Search</label><input type="search" id="q" name="q" value="<?= h($qs) ?>" placeholder="Name, email or student ID"></div>
  <div><label for="role">Role</label><select id="role" name="role" data-autosubmit><option value="">All</option><option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option><option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
  <div><label for="status">Status</label><select id="status" name="status" data-autosubmit><option value="">All</option><option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option><option value="blocked" <?= $st === 'blocked' ? 'selected' : '' ?>>Blocked</option></select></div>
  <div style="flex:0 0 auto"><button class="btn btn-blue" type="submit">Search</button> <a class="btn btn-ghost" href="users.php">Reset</a></div>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>Name</th><th>Email</th><th>Student ID</th><th>Role</th><th>Status</th><th>Uploads</th><th>Joined</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $u): ?>
    <tr>
      <td><b><?= h($u['full_name']) ?></b></td><td><?= h($u['email']) ?></td><td><?= h($u['student_id'] ?: '-') ?></td>
      <td><?= badge($u['role']) ?></td><td><?= badge($u['status']) ?></td><td><?= (int) $u['uploads'] ?></td><td><?= h(date('j M Y', strtotime($u['created_at']))) ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= h(url('admin/user_form.php?id=' . $u['id'])) ?>">Edit</a>
        <?php if ((int) $u['id'] !== $me): ?>
          <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="action" value="<?= $u['status'] === 'active' ? 'block' : 'unblock' ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?></button></form>
          <form class="inline" method="post" data-confirm="Delete this user and everything they uploaded?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="8" class="muted" style="text-align:center;padding:30px">No users match.</td></tr><?php endif; ?>
  </tbody></table></div>
<?= pager($total, $per, $page) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
