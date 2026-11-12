<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$slugValue = $_GET['slug'] ?? '';
$slug = is_string($slugValue) ? trim($slugValue) : '';
$statement = $pdo->prepare(
    "SELECT p.id, p.category_id, p.title, p.slug, p.body, p.cover_image,
            COALESCE(p.published_at, p.created_at) AS published_at,
            c.name AS category_name, c.slug AS category_slug, a.username AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published'
     LIMIT 1"
);
$statement->execute(['slug' => $slug]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
} else {
    $relatedQuery = $pdo->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image,
                COALESCE(p.published_at, p.created_at) AS published_at,
                c.name AS category_name, c.slug AS category_slug, a.username AS author_name
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published' AND p.category_id = :category_id AND p.id <> :post_id
         ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
         LIMIT 3"
    );
    $relatedQuery->execute([
        'category_id' => $post['category_id'],
        'post_id' => $post['id'],
    ]);
    $relatedPosts = $relatedQuery->fetchAll();

    preg_match_all('/[\p{L}\p{N}]+/u', $post['body'], $wordMatches);
    $readingMinutes = max(1, (int) ceil(count($wordMatches[0]) / 200));
}

page_header($post ? $post['title'] : 'Post not found');
if (!$post): ?>
    <section class="empty-state"><h1>Post not found</h1><p>This article may have been removed or is not published.</p><a href="<?= e(url()) ?>">Return home</a></section>
<?php else: ?>
    <article class="article">
        <a class="category-tag" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
        <h1><?= e($post['title']) ?></h1>
        <div class="post-meta">By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?> · <?= $readingMinutes ?> min read</div>
        <?php if ($post['cover_image']): ?>
            <img class="article-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="">
        <?php endif; ?>
        <div class="article-body"><?= render_paragraphs($post['body']) ?></div>
    </article>
    <section class="related-section" aria-labelledby="related-heading">
        <h2 id="related-heading">Related posts</h2>
        <?php if (!$relatedPosts): ?>
            <p class="empty-state">No related posts yet.</p>
        <?php else: ?>
            <div class="post-grid">
                <?php foreach ($relatedPosts as $related): ?>
                    <article class="post-card">
                        <?php if ($related['cover_image']): ?>
                            <a href="<?= e(url('post/' . rawurlencode($related['slug']))) ?>">
                                <img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($related['cover_image']))) ?>" alt="">
                            </a>
                        <?php endif; ?>
                        <div class="post-card-content">
                            <h3><a href="<?= e(url('post/' . rawurlencode($related['slug']))) ?>"><?= e($related['title']) ?></a></h3>
                            <p><?= e($related['excerpt'] ?: mb_strimwidth(strip_tags($related['body']), 0, 170, '…')) ?></p>
                            <div class="post-meta">By <?= e($related['author_name']) ?> · <?= e(date('M j, Y', strtotime($related['published_at']))) ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; page_footer(); ?>
