<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$name = '';
$slug = '';
$description = '';
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
        $check = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
        $check->execute(['slug' => $slug]);
        if ($check->fetch()) {
            $error = 'That slug is already in use. Choose another slug.';
        } else {
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)'
                );
                $insert->execute(['name' => $name, 'slug' => $slug, 'description' => $description ?: null]);
                set_flash('success', 'Category created.');
                redirect('admin/categories/');
            } catch (PDOException $exception) {
                error_log('Category creation failed: ' . $exception->getMessage());
                $error = 'The category could not be saved. Check that the slug is unique and try again.';
            }
        }
    }
}

admin_header('Create category');
?>
<form class="form-card" method="post">
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <div class="form-group"><label for="name">Name</label><input id="name" name="name" maxlength="120" required value="<?= e($name) ?>"></div>
    <div class="form-group"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="160" value="<?= e($slug) ?>"><div class="form-help">Leave blank to create a slug from the category name.</div></div>
    <div class="form-group"><label for="description">Description</label><textarea id="description" name="description" rows="5"><?= e($description) ?></textarea></div>
    <div class="form-actions"><button class="button" type="submit">Create category</button><a href="<?= e(url('admin/categories/')) ?>">Cancel</a></div>
</form>
<?php admin_footer(); ?>
