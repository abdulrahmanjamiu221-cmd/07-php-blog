<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid post.');
}
$statement = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
$statement->execute(['id' => $id]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$title = $post['title'];
$excerpt = $post['excerpt'] ?? '';
$body = $post['body'];
$status = $post['status'];
$categoryId = (string) $post['category_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'draft');
    $categoryId = (string) ($_POST['category_id'] ?? '');
    $categoryValid = filter_var($categoryId, FILTER_VALIDATE_INT) !== false;
    if ($title === '' || mb_strlen($title) > 240 || mb_strlen($excerpt) > 300 || $body === '' ||
        !in_array($status, ['draft', 'published'], true) || !$categoryValid) {
        $error = 'Enter a title, excerpt no longer than 300 characters, body, and category.';
    } else {
        $categoryCheck = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $categoryCheck->execute(['id' => $categoryId]);
        if (!$categoryCheck->fetch()) {
            $error = 'Choose an existing category.';
        } else {
            $cover = '';
            try {
                $cover = store_cover_upload($_FILES['cover_image'] ?? []);
                $slug = unique_post_slug($pdo, $title, $id);
                $removeCover = isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1';
                $newCover = $cover !== '' ? $cover : ($removeCover ? null : $post['cover_image']);
                $update = $pdo->prepare(
                    'UPDATE posts SET category_id = :category_id, title = :title, slug = :slug, excerpt = :excerpt,
                     body = :body, status = :status, cover_image = :cover_image,
                     published_at = IF(:is_published = 1, COALESCE(published_at, NOW()), NULL)
                     WHERE id = :id'
                );
                $update->execute([
                    'category_id' => $categoryId,
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt === '' ? null : $excerpt,
                    'body' => $body,
                    'status' => $status,
                    'cover_image' => $newCover,
                    'is_published' => $status === 'published' ? 1 : 0,
                    'id' => $id,
                ]);
                if ($cover !== '' || $removeCover) {
                    remove_cover_image($post['cover_image']);
                }
                set_flash('success', 'Post updated.');
                redirect('admin/posts/');
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($cover !== '') {
                    remove_cover_image($cover);
                }
                error_log('Post update failed: ' . $exception->getMessage());
                $error = 'The post could not be saved. Check that the slug is unique and try again.';
            }
        }
    }
}

admin_header('Edit post');
?>
<?php if (!$categories): ?><div class="alert alert-error">No categories are available. <a href="<?= e(url('admin/categories/create.php')) ?>">Add a category</a>.</div><?php endif; ?>
<form class="form-card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <div class="form-group"><label for="title">Title</label><input id="title" name="title" maxlength="240" required value="<?= e($title) ?>"></div>
    <div class="form-help">The post URL is generated from the title. Duplicate titles get a unique numbered suffix.</div>
    <div class="form-group"><label for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Select a category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $categoryId ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="excerpt">Excerpt</label><textarea id="excerpt" name="excerpt" rows="3" maxlength="300"><?= e($excerpt) ?></textarea><div class="form-help">Optional short summary, up to 300 characters.</div></div>
    <div class="form-group"><label for="body">Body</label><textarea id="body" name="body" rows="16" required><?= e($body) ?></textarea><div class="form-help">Text only; line breaks are preserved on the public page.</div></div>
    <div class="form-group"><label for="cover_image">Replace cover image</label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"><div class="form-help">JPEG, PNG, or WebP; maximum 2 MB.</div>
        <?php if ($post['cover_image']): ?><p class="form-help">Current: <?= e($post['cover_image']) ?></p><label><input type="checkbox" name="remove_cover" value="1" style="width:auto"> Remove current cover</label><?php endif; ?>
    </div>
    <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option></select></div>
    <div class="form-actions"><button class="button" type="submit">Save changes</button><a href="<?= e(url('admin/posts/')) ?>">Cancel</a></div>
</form>
<?php admin_footer(); ?>
