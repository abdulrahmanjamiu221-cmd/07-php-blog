<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$categoryQuery = $pdo->prepare('SELECT id, name, description FROM categories WHERE slug = :slug LIMIT 1');
$categoryQuery->execute(['slug' => $slug]);
$category = $categoryQuery->fetch();
$posts = [];
if ($category) {
    $postQuery = $pdo->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, a.username AS author_name
         FROM posts p JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = :category_id AND p.status = 'published'
         ORDER BY p.published_at DESC, p.id DESC"
    );
    $postQuery->execute(['category_id' => $category['id']]);
    $posts = $postQuery->fetchAll();
} else {
    http_response_code(404);
}

page_header($category ? $category['name'] : 'Category not found');
if (!$category): ?>
    <section class="empty-state"><h1>Category not found</h1><p>This category does not exist.</p><a href="<?= e(url()) ?>">Return home</a></section>
<?php else: ?>
    <section class="page-intro"><p class="eyebrow">Category</p><h1><?= e($category['name']) ?></h1><?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?></section>
    <?php if (!$posts): ?><p class="empty-state">There are no published posts in this category yet.</p>
    <?php else: ?><div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content"><h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: mb_strimwidth(strip_tags($post['body']), 0, 170, '…')) ?></p>
                    <div class="post-meta">By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div><?php endif; ?>
<?php endif; page_footer(); ?>
