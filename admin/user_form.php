<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$me = (int) current_user()['id']; $id = get_int('id');
$u = ['full_name' => '', 'email' => '', 'student_id' => '', 'faculty' => '', 'role' => 'student', 'status' => 'active'];
if ($id) { $u = row('SELECT * FROM users WHERE id = ?', [$id]); if (!$u) { flash('error', 'User not found.'); redirect('admin/users.php'); } }
if (is_post()) {
    verify_csrf();
    foreach (['full_name', 'student_id', 'faculty'] as $k) $u[$k] = post($k);
    $u['email'] = strtolower(post('email'));
    $u['role'] = post('role') === 'admin' ? 'admin' : 'student';
    $u['status'] = post('status') === 'blocked' ? 'blocked' : 'active';
    $pass = (string) ($_POST['password'] ?? '');
    $err = null;
    if (mb_strlen($u['full_name']) < 3) $err = 'Enter the full name.';
    elseif (!filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif (val('SELECT id FROM users WHERE email = ? AND id <> ?', [$u['email'], $id])) $err = 'Another account already uses this email.';
    elseif (!$id && strlen($pass) < 8) $err = 'Set a password of at least 8 characters.';
    elseif ($id && $pass !== '' && strlen($pass) < 8) $err = 'New password must be at least 8 characters.';
    elseif ($id === $me && ($u['role'] !== 'admin' || $u['status'] !== 'active')) $err = 'You cannot demote or block yourself.';
    if ($err) flash('error', $err);
    else {
        if ($id) {
            q('UPDATE users SET full_name=?, email=?, student_id=?, faculty=?, role=?, status=? WHERE id=?', [$u['full_name'], $u['email'], $u['student_id'] ?: null, $u['faculty'] ?: null, $u['role'], $u['status'], $id]);
            if ($pass !== '') q('UPDATE users SET password = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            flash('success', 'User updated.');
        } else {
            q('INSERT INTO users (full_name, email, student_id, faculty, password, role, status) VALUES (?,?,?,?,?,?,?)', [$u['full_name'], $u['email'], $u['student_id'] ?: null, $u['faculty'] ?: null, password_hash($pass, PASSWORD_DEFAULT), $u['role'], $u['status']]);
            flash('success', 'User created.');
        }
        redirect('admin/users.php');
    }
}
$pageTitle = $id ? 'Edit user' : 'Add user'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="card form-narrow">
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" value="<?= h($u['full_name']) ?>" required minlength="3" maxlength="100"></div>
    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= h($u['email']) ?>" required maxlength="150"></div>
    <div class="grid g2">
      <div class="field"><label for="student_id">Student ID</label><input type="text" id="student_id" name="student_id" value="<?= h($u['student_id']) ?>" maxlength="30"></div>
      <div class="field"><label for="faculty">Faculty</label><input type="text" id="faculty" name="faculty" value="<?= h($u['faculty']) ?>" maxlength="100"></div>
      <div class="field"><label for="role">Role</label><select id="role" name="role"><option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
      <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="blocked" <?= $u['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option></select></div>
    </div>
    <div class="field"><label for="password">Password <?= $id ? '<span class="muted">(leave empty to keep current)</span>' : '' ?></label><input type="password" id="password" name="password" minlength="8" <?= $id ? '' : 'required' ?>></div>
    <div class="btn-row"><button class="btn btn-primary" type="submit">Save user</button><a class="btn btn-ghost" href="<?= h(url('admin/users.php')) ?>">Cancel</a></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
