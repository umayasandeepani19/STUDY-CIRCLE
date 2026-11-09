<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$me = current_user(); $uid = (int) $me['id'];
if (is_post()) {
    verify_csrf();
    if (post('action') === 'profile') {
        $name = post('full_name'); $sid = post('student_id'); $fac = post('faculty'); $bio = post('bio');
        if (mb_strlen($name) < 3) flash('error', 'Enter your full name.');
        elseif ($sid !== '' && !preg_match('/^[A-Za-z0-9.\-\/ ]{3,30}$/', $sid)) flash('error', 'Student ID may only contain letters, numbers and . - /');
        else { q('UPDATE users SET full_name=?, student_id=?, faculty=?, bio=? WHERE id=?', [$name, $sid ?: null, $fac ?: null, $bio ?: null, $uid]); flash('success', 'Profile saved.'); }
    } elseif (post('action') === 'password') {
        $cur = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['new_password'] ?? ''); $new2 = (string) ($_POST['new_password2'] ?? '');
        $hash = val('SELECT password FROM users WHERE id = ?', [$uid]);
        if (!password_verify($cur, $hash)) flash('error', 'Your current password is incorrect.');
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) flash('error', 'New password needs at least 8 characters with a letter and a number.');
        elseif ($new !== $new2) flash('error', 'The new passwords do not match.');
        else { q('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]); flash('success', 'Password changed.'); }
    } elseif (post('action') === 'delete_account') {
        if (is_admin()) { flash('error', 'Admin accounts cannot be deleted here.'); }
        elseif (password_verify((string) ($_POST['confirm_password'] ?? ''), val('SELECT password FROM users WHERE id = ?', [$uid]))) {
            $files = rows('SELECT file_path FROM resources WHERE user_id = ?', [$uid]);
            q('DELETE FROM users WHERE id = ?', [$uid]);
            foreach ($files as $f) delete_upload($f['file_path']);
            $_SESSION = []; session_destroy(); session_start();
            flash('success', 'Your account and uploads were deleted.');
            redirect('index.php');
        } else flash('error', 'Password is incorrect, so the account was not deleted.');
    }
    redirect('student/profile.php');
}
$me = row('SELECT * FROM users WHERE id = ?', [$uid]);
$pageTitle = 'My profile'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>My profile</h1><p>Member since <?= h(date('j M Y', strtotime($me['created_at']))) ?> &middot; <?= h($me['email']) ?></p></div></div>
<div class="grid g2">
  <div class="card">
    <h2>Details</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" value="<?= h($me['full_name']) ?>" required minlength="3" maxlength="100"></div>
      <div class="field"><label for="student_id">Student ID</label><input type="text" id="student_id" name="student_id" value="<?= h($me['student_id']) ?>" maxlength="30"></div>
      <div class="field"><label for="faculty">Faculty</label><input type="text" id="faculty" name="faculty" value="<?= h($me['faculty']) ?>" maxlength="100"></div>
      <div class="field"><label for="bio">About you</label><textarea id="bio" name="bio" maxlength="300" style="min-height:80px"><?= h($me['bio']) ?></textarea></div>
      <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
  </div>
  <div>
    <div class="card">
      <h2>Change password</h2>
      <form method="post" data-match><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div class="field"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required></div>
        <div class="field"><label for="new_password">New password</label><input type="password" id="new_password" name="new_password" required minlength="8"></div>
        <div class="field"><label for="new_password2">Confirm new password</label><input type="password" id="new_password2" name="new_password2" required minlength="8"></div>
        <button class="btn btn-blue" type="submit">Update password</button>
      </form>
    </div>
    <?php if ($me['role'] !== 'admin'): ?>
    <div class="card">
      <h3>Delete account</h3>
      <p class="muted small">This removes your profile, uploads, questions and groups you created. It cannot be undone.</p>
      <form method="post" data-confirm="Delete your account permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_account">
        <div class="field"><input type="password" name="confirm_password" required placeholder="Confirm with your password" aria-label="Confirm with your password"></div>
        <button class="btn btn-danger" type="submit">Delete my account</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
