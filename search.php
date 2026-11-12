<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if ($query !== '') {
    $statement = $pdo->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
                c.name AS category_name, c.slug AS category_slug, a.username AS author_name
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published'
           AND MATCH(p.title, p.body) AGAINST (:query IN NATURAL LANGUAGE MODE)
         ORDER BY p.published_at DESC, p.id DESC
         LIMIT 50"
    );
    $statement->execute(['query' => $query]);
    $posts = $statement->fetchAll();
}

page_header('Search');
?>
<section class="page-intro">
    <p class="eyebrow">Search the blog</p>
    <h1>Find an article</h1>
    <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
        <label class="sr-only" for="search-query">Search posts</label>
        <input id="search-query" type="search" name="q" value="<?= e($query) ?>" placeholder="Search articles..." required>
        <button class="button" type="submit">Search</button>
    </form>
</section>
<?php if ($query !== '' && !$posts): ?><p class="empty-state">No published posts matched “<?= e($query) ?>”.</p>
<?php elseif ($posts): ?>
    <p class="result-count"><?= count($posts) ?> result<?= count($posts) === 1 ? '' : 's' ?> for “<?= e($query) ?>”</p>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content"><a class="category-tag" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
                    <h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: mb_strimwidth(strip_tags($post['body']), 0, 170, '…')) ?></p>
                    <div class="post-meta">By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php page_footer(); ?>
