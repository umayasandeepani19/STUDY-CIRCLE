<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$editId = get_int('edit');
$edit = $editId ? row('SELECT * FROM subjects WHERE id = ?', [$editId]) : null;
if (is_post()) {
    verify_csrf();
    $a = post('action'); $id = (int) post('id');
    try {
        if ($a === 'save') {
            $code = strtoupper(post('code')); $name = post('name'); $desc = post('description');
            if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $code)) throw new RuntimeException('Subject code must be 2 to 20 letters, numbers or hyphens.');
            if (mb_strlen($name) < 3) throw new RuntimeException('Enter the subject name.');
            if ($id) { q('UPDATE subjects SET code=?, name=?, description=? WHERE id=?', [$code, $name, $desc ?: null, $id]); flash('success', 'Subject updated.'); }
            else { q('INSERT INTO subjects (code, name, description) VALUES (?,?,?)', [$code, $name, $desc ?: null]); flash('success', 'Subject added.'); }
        } elseif ($a === 'delete') {
            q('DELETE FROM subjects WHERE id = ?', [$id]); flash('success', 'Subject deleted.');
        }
    } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('admin/subjects.php' . ($id ? '?edit=' . $id : '')); }
    catch (PDOException $e) {
        flash('error', $e->getCode() === '23000' && $a === 'save' ? 'That subject code already exists.' : 'This subject is used by materials, questions or groups, so it cannot be deleted.');
    }
    redirect('admin/subjects.php');
}
$list = rows('SELECT s.*, (SELECT COUNT(*) FROM resources WHERE subject_id = s.id) AS res, (SELECT COUNT(*) FROM questions WHERE subject_id = s.id) AS qs, (SELECT COUNT(*) FROM study_groups WHERE subject_id = s.id) AS grp FROM subjects s ORDER BY s.code');
$pageTitle = 'Subjects'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="split">
  <div class="table-wrap"><table>
    <thead><tr><th>Code</th><th>Name</th><th>Materials</th><th>Questions</th><th>Groups</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $s): ?>
      <tr><td><span class="tag green"><?= h($s['code']) ?></span></td><td><b><?= h($s['name']) ?></b><div class="muted small"><?= h($s['description']) ?></div></td>
        <td><?= (int) $s['res'] ?></td><td><?= (int) $s['qs'] ?></td><td><?= (int) $s['grp'] ?></td>
        <td class="actions"><a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $s['id'] ?>">Edit</a>
          <form class="inline" method="post" data-confirm="Delete this subject?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="card">
    <h2><?= $edit ? 'Edit subject' : 'Add subject' ?></h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
      <div class="field"><label for="code">Code</label><input type="text" id="code" name="code" value="<?= h($edit['code'] ?? '') ?>" required maxlength="20" placeholder="e.g. WMAD"></div>
      <div class="field"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= h($edit['name'] ?? '') ?>" required maxlength="120"></div>
      <div class="field"><label for="description">Description</label><textarea id="description" name="description" maxlength="300" style="min-height:70px"><?= h($edit['description'] ?? '') ?></textarea></div>
      <div class="btn-row"><button class="btn btn-primary" type="submit"><?= $edit ? 'Save changes' : 'Add subject' ?></button><?php if ($edit): ?><a class="btn btn-ghost" href="subjects.php">Cancel</a><?php endif; ?></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
