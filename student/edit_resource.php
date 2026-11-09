<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid = (int) current_user()['id'];
$r = row('SELECT * FROM resources WHERE id = ? AND user_id = ?', [get_int('id'), $uid]);
if (!$r) { flash('error', 'Upload not found.'); redirect('student/my_uploads.php'); }
$subjects = rows('SELECT id, code, name FROM subjects ORDER BY code');
$cats = rows('SELECT id, name FROM categories ORDER BY name');
if (is_post()) {
    verify_csrf();
    $title = post('title'); $desc = post('description'); $sid = (int) post('subject_id'); $cid = (int) post('category_id');
    try {
        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) throw new RuntimeException('Title must be 3 to 150 characters.');
        if (!val('SELECT id FROM subjects WHERE id = ?', [$sid]) || !val('SELECT id FROM categories WHERE id = ?', [$cid])) throw new RuntimeException('Choose a subject and a type.');
        $path = $r['file_path']; $name = $r['file_name']; $size = (int) $r['file_size'];
        $newFile = !empty($_FILES['file']['name']);
        if ($newFile) {
            $f = handle_upload($_FILES['file']);
            delete_upload($r['file_path']);
            $path = $f['path']; $name = $f['name']; $size = $f['size'];
        }
        // any edit sends it back to the admin queue
        q("UPDATE resources SET title=?, description=?, subject_id=?, category_id=?, file_path=?, file_name=?, file_size=?, status='pending', admin_note=NULL WHERE id=?",
          [$title, $desc ?: null, $sid, $cid, $path, $name, $size, $r['id']]);
        flash('success', 'Saved. The material goes back to admins for approval.');
        redirect('student/my_uploads.php');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        $r['title'] = $title; $r['description'] = $desc; $r['subject_id'] = $sid; $r['category_id'] = $cid;
    }
}
$pageTitle = 'Edit upload'; $area = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>Edit upload</h1><p>Changes are re-checked by an admin before the file is shown again.</p></div></div>
<div class="card form-narrow">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="field"><label for="title">Title</label><input type="text" id="title" name="title" value="<?= h($r['title']) ?>" required minlength="3" maxlength="150"></div>
    <div class="grid g2">
      <div class="field"><label for="subject_id">Subject</label><select id="subject_id" name="subject_id" required>
        <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= (int) $r['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['code'] . ' - ' . $s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="category_id">Type</label><select id="category_id" name="category_id" required>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int) $r['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="field"><label for="description">Description</label><textarea id="description" name="description" maxlength="2000"><?= h($r['description']) ?></textarea></div>
    <div class="field"><label for="file">Replace file <span class="muted">(optional)</span></label><input type="file" id="file" name="file" data-max="<?= MAX_UPLOAD_MB ?>">
      <div class="hint">Current file: <?= h($r['file_name']) ?> (<?= h(format_bytes((int) $r['file_size'])) ?>)</div></div>
    <div class="btn-row"><button class="btn btn-primary" type="submit">Save changes</button><a class="btn btn-ghost" href="<?= h(url('student/my_uploads.php')) ?>">Cancel</a></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
