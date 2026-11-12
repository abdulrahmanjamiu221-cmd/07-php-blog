<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid category.');
}
$statement = $pdo->prepare('SELECT id, name, slug, description FROM categories WHERE id = :id');
$statement->execute(['id' => $id]);
$category = $statement->fetch();
if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}
$name = $category['name'];
$slug = $category['slug'];
$description = $category['description'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = slugify((string) ($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = slugify($name);
    }
    $description = trim((string) ($_POST['description'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120 || $slug === '' || mb_strlen($slug) > 160) {
        $error = 'Enter a category name and a valid slug.';
    } else {
        $check = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug AND id <> :id');
        $check->execute(['slug' => $slug, 'id' => $id]);
        if ($check->fetch()) {
            $error = 'That slug is already in use. Choose another slug.';
        } else {
            try {
                $update = $pdo->prepare(
                    'UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id'
                );
                $update->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description ?: null,
                    'id' => $id,
                ]);
                set_flash('success', 'Category updated.');
                redirect('admin/categories/');
            } catch (PDOException $exception) {
                error_log('Category update failed: ' . $exception->getMessage());
                $error = 'The category could not be saved. Check that the slug is unique and try again.';
            }
        }
    }
}

admin_header('Edit category');
?>
<form class="form-card" method="post">
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <div class="form-group"><label for="name">Name</label><input id="name" name="name" maxlength="120" required value="<?= e($name) ?>"></div>
    <div class="form-group"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="160" required value="<?= e($slug) ?>"></div>
    <div class="form-group"><label for="description">Description</label><textarea id="description" name="description" rows="5"><?= e($description) ?></textarea></div>
    <div class="form-actions"><button class="button" type="submit">Save changes</button><a href="<?= e(url('admin/categories/')) ?>">Cancel</a></div>
</form>
<?php admin_footer(); ?>
