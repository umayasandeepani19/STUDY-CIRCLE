<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$editId = get_int('edit');
$edit = $editId ? row('SELECT * FROM categories WHERE id = ?', [$editId]) : null;
if (is_post()) {
    verify_csrf();
    $a = post('action'); $id = (int) post('id');
    try {
        if ($a === 'save') {
            $name = post('name');
            if (mb_strlen($name) < 3 || mb_strlen($name) > 80) throw new RuntimeException('Category name must be 3 to 80 characters.');
            if ($id) { q('UPDATE categories SET name=? WHERE id=?', [$name, $id]); flash('success', 'Category updated.'); }
            else { q('INSERT INTO categories (name) VALUES (?)', [$name]); flash('success', 'Category added.'); }
        } elseif ($a === 'delete') {
            q('DELETE FROM categories WHERE id = ?', [$id]); flash('success', 'Category deleted.');
        }
    } catch (RuntimeException $e) { flash('error', $e->getMessage()); }
    catch (PDOException $e) {
        flash('error', $a === 'save' ? 'That category name already exists.' : 'This category is used by materials, so it cannot be deleted. Move or delete those materials first.');
    }
    redirect('admin/categories.php');
}
$list = rows('SELECT c.*, (SELECT COUNT(*) FROM resources WHERE category_id = c.id) AS res FROM categories c ORDER BY c.name');
$pageTitle = 'Categories'; $area = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="split">
  <div class="table-wrap"><table>
    <thead><tr><th>Category</th><th>Materials</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $c): ?>
      <tr><td><b><?= h($c['name']) ?></b></td><td><?= (int) $c['res'] ?></td>
        <td class="actions"><a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $c['id'] ?>">Edit</a>
          <form class="inline" method="post" data-confirm="Delete this category?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="card">
    <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
      <div class="field"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= h($edit['name'] ?? '') ?>" required maxlength="80" placeholder="e.g. Lab Sheets"></div>
      <div class="btn-row"><button class="btn btn-primary" type="submit"><?= $edit ? 'Save changes' : 'Add category' ?></button><?php if ($edit): ?><a class="btn btn-ghost" href="categories.php">Cancel</a><?php endif; ?></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
