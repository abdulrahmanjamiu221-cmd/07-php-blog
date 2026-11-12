<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$statement = $pdo->query(
    "SELECT p.id, p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.username AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC, p.id DESC
     LIMIT 20"
);
$posts = $statement->fetchAll();

page_header('Latest posts');
?>
<section class="hero">
    <p class="eyebrow">Ideas, stories, and insights</p>
    <h1>Welcome to SWD Blog</h1>
    <p>Explore the latest articles and thoughts from our community.</p>
    <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
        <label class="sr-only" for="site-search">Search posts</label>
        <input id="site-search" type="search" name="q" placeholder="Search articles..." required>
        <button class="button" type="submit">Search</button>
    </form>
</section>
<section aria-labelledby="latest-heading">
    <div class="section-heading">
        <h2 id="latest-heading">Latest articles</h2>
    </div>
    <?php if (!$posts): ?>
        <p class="empty-state">No posts have been published yet.</p>
    <?php else: ?>
        <div class="post-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['cover_image']): ?>
                        <a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>">
                            <img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="">
                        </a>
                    <?php endif; ?>
                    <div class="post-card-content">
                        <a class="category-tag" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
                        <h3><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h3>
                        <p><?= e($post['excerpt'] ?: mb_strimwidth(strip_tags($post['body']), 0, 170, '…')) ?></p>
                        <div class="post-meta">By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php page_footer(); ?>
