<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$stats = [
    'posts' => (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'categories' => (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
$recentPosts = $pdo->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name
     FROM posts p JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC LIMIT 8'
)->fetchAll();

admin_header('Dashboard');
?>
<div class="stat-grid">
    <div class="stat-card"><span>All posts</span><strong><?= $stats['posts'] ?></strong></div>
    <div class="stat-card"><span>Published</span><strong><?= $stats['published'] ?></strong></div>
    <div class="stat-card"><span>Categories</span><strong><?= $stats['categories'] ?></strong></div>
</div>
<section class="panel">
    <div class="page-actions">
        <h2>Recent posts</h2>
        <a class="button" href="<?= e(url('admin/posts/create.php')) ?>">New post</a>
    </div>
    <?php if (!$recentPosts): ?><p class="empty-state">No posts yet. Create your first post to get started.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Last updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['category_name']) ?></td>
                        <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                        <td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
                        <td><a href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>
