<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        exit('Invalid post.');
    }
    $statement = $pdo->prepare('SELECT cover_image FROM posts WHERE id = :id');
    $statement->execute(['id' => $id]);
    $post = $statement->fetch();
    if (!$post) {
        set_flash('error', 'Post not found.');
    } else {
        $delete = $pdo->prepare('DELETE FROM posts WHERE id = :id');
        $delete->execute(['id' => $id]);
        remove_cover_image($post['cover_image']);
        set_flash('success', 'Post deleted.');
    }
    redirect('admin/posts/');
}

$posts = $pdo->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name, a.username AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC'
)->fetchAll();

admin_header('Posts');
?>
<div class="page-actions"><p>Manage your articles and publication status.</p><a class="button" href="<?= e(url('admin/posts/create.php')) ?>">Create post</a></div>
<?php if (!$posts): ?><p class="empty-state">No posts found. Create your first article.</p>
<?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($posts as $post): ?>
        <tr>
            <td><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><?= e($post['author_name']) ?></td>
            <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
            <td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
            <td class="table-actions">
                <a href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a>
                <?php if ($post['status'] === 'published'): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>" target="_blank" rel="noopener">View</a><?php endif; ?>
                <form class="inline-form" method="post" onsubmit="return confirm('Delete this post permanently?');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                    <button class="link-danger" type="submit" style="border:0;background:none;padding:0;cursor:pointer">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
<?php admin_footer(); ?>
