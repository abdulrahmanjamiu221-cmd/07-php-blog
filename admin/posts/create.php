<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$title = '';
$slug = '';
$body = '';
$status = 'draft';
$categoryId = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = slugify((string) ($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = slugify($title);
    }
    $body = trim((string) ($_POST['body'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'draft');
    $categoryId = (string) ($_POST['category_id'] ?? '');
    $categoryValid = filter_var($categoryId, FILTER_VALIDATE_INT) !== false;
    if ($title === '' || mb_strlen($title) > 240 || $slug === '' || mb_strlen($slug) > 260 || $body === '' ||
        !in_array($status, ['draft', 'published'], true) || !$categoryValid) {
        $error = 'Enter a title, valid slug, body, and category.';
    } else {
        $categoryCheck = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $categoryCheck->execute(['id' => $categoryId]);
        $slugCheck = $pdo->prepare('SELECT id FROM posts WHERE slug = :slug');
        $slugCheck->execute(['slug' => $slug]);
        if (!$categoryCheck->fetch()) {
            $error = 'Choose an existing category.';
        } elseif ($slugCheck->fetch()) {
            $error = 'That slug is already in use. Choose another slug.';
        } else {
            $cover = '';
            try {
                $cover = store_cover_upload($_FILES['cover_image'] ?? []);
                $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                $insert = $pdo->prepare(
                    'INSERT INTO posts (category_id, author_id, title, slug, body, status, cover_image, published_at)
                     VALUES (:category_id, :author_id, :title, :slug, :body, :status, :cover_image, :published_at)'
                );
                $insert->execute([
                    'category_id' => $categoryId,
                    'author_id' => (int) $_SESSION['admin_id'],
                    'title' => $title,
                    'slug' => $slug,
                    'body' => $body,
                    'status' => $status,
                    'cover_image' => $cover === '' ? null : $cover,
                    'published_at' => $publishedAt,
                ]);
                set_flash('success', 'Post created.');
                redirect('admin/posts/');
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($cover !== '') {
                    remove_cover_image($cover);
                }
                error_log('Post creation failed: ' . $exception->getMessage());
                $error = 'The post could not be saved. Check that the slug is unique and try again.';
            }
        }
    }
}

admin_header('Create post');
?>
<?php if (!$categories): ?><div class="alert alert-error">Create a category before writing a post. <a href="<?= e(url('admin/categories/create.php')) ?>">Add a category</a>.</div><?php endif; ?>
<form class="form-card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <div class="form-group"><label for="title">Title</label><input id="title" name="title" maxlength="240" required value="<?= e($title) ?>"></div>
    <div class="form-group"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="260" value="<?= e($slug) ?>"><div class="form-help">Leave blank to create a slug from the title.</div></div>
    <div class="form-group"><label for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Select a category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $categoryId ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="body">Body</label><textarea id="body" name="body" rows="16" required><?= e($body) ?></textarea><div class="form-help">Text only; line breaks are preserved on the public page.</div></div>
    <div class="form-group"><label for="cover_image">Cover image</label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"><div class="form-help">JPEG, PNG, or WebP; maximum 5 MB.</div></div>
    <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option></select></div>
    <div class="form-actions"><button class="button" type="submit" <?= !$categories ? 'disabled' : '' ?>>Create post</button><a href="<?= e(url('admin/posts/')) ?>">Cancel</a></div>
</form>
<?php admin_footer(); ?>
