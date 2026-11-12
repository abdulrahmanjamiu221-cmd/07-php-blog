<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        exit('Invalid category.');
    }

    try {
        $delete = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $delete->execute(['id' => $id]);
        set_flash($delete->rowCount() ? 'success' : 'error', $delete->rowCount() ? 'Category deleted.' : 'Category not found.');
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }
        set_flash('error', 'This category has posts assigned to it. Move or delete those posts before deleting the category.');
    }
    redirect('admin/categories/');
}

$categories = $pdo->query(
    'SELECT c.id, c.name, c.slug, c.description, c.created_at, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

admin_header('Categories');
?>
<div class="page-actions"><p>Organize posts into categories.</p><a class="button" href="<?= e(url('admin/categories/create.php')) ?>">Create category</a></div>
<?php if (!$categories): ?><p class="empty-state">No categories yet. Add one before creating a post.</p>
<?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($categories as $category): ?>
        <tr>
            <td><?= e($category['name']) ?></td><td><?= e($category['slug']) ?></td><td><?= (int) $category['post_count'] ?></td>
            <td class="table-actions">
                <a href="<?= e(url('admin/categories/edit.php?id=' . (int) $category['id'])) ?>">Edit</a>
                <form class="inline-form" method="post" onsubmit="return confirm('Delete this category? Categories with posts cannot be deleted.');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                    <button class="link-danger" type="submit" style="border:0;background:none;padding:0;cursor:pointer">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
<?php admin_footer(); ?>
