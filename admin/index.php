<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$stats = $pdo->query(
    "SELECT COUNT(*) AS total_posts,
            COUNT(CASE WHEN status = 'published' THEN 1 END) AS published_posts,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) AS draft_posts,
            (SELECT COUNT(*) FROM categories) AS total_categories
     FROM posts"
)->fetch();
$recentPosts = $pdo->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at,
            c.name AS category_name, a.username AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC, p.id DESC
     LIMIT 5'
)->fetchAll();
$postsPerCategory = $pdo->query(
    'SELECT c.id, c.name, COUNT(p.id) AS post_count
     FROM categories c
     LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name'
)->fetchAll();

admin_header('Dashboard');
?>
<div class="stat-grid">
    <div class="stat-card"><span>All posts</span><strong><?= (int) $stats['total_posts'] ?></strong></div>
    <div class="stat-card"><span>Published</span><strong><?= (int) $stats['published_posts'] ?></strong></div>
    <div class="stat-card"><span>Drafts</span><strong><?= (int) $stats['draft_posts'] ?></strong></div>
    <div class="stat-card"><span>Categories</span><strong><?= (int) $stats['total_categories'] ?></strong></div>
</div>
<section class="panel">
    <div class="page-actions">
        <h2>Latest 5 posts</h2>
        <a class="button" href="<?= e(url('admin/posts/create.php')) ?>">New post</a>
    </div>
    <?php if (!$recentPosts): ?><p class="empty-state">No posts yet. Create your first post to get started.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Last updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['category_name']) ?></td>
                        <td><?= e($post['author_name']) ?></td>
                        <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                        <td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a>
                            <?php if ($post['status'] === 'published'): ?><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>" target="_blank" rel="noopener">View</a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<section class="panel category-breakdown">
    <h2>Posts per category</h2>
    <?php if (!$postsPerCategory): ?>
        <p class="empty-state">No categories yet. Create a category to organize posts.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Category</th><th>Posts</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($postsPerCategory as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><?= (int) $category['post_count'] ?></td>
                        <td><a href="<?= e(url('admin/posts/')) ?>">View posts</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>
