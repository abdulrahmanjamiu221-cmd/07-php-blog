<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/app/bootstrap.php';

$authorId = (int) $pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
if ($authorId === 0) {
    fwrite(STDERR, "Create an administrator before seeding: php create-admin.php <username> <email>\n");
    exit(1);
}

$categoryNames = [
    'tutorials' => 'Tutorials',
    'news' => 'News',
    'career' => 'Career',
];
$seedPosts = [
    [
        'tutorials',
        'A Practical Guide to Planning Your First Website',
        'Turn a rough idea into a focused, achievable website plan.',
        "Start by writing down who the site is for and what those visitors need to accomplish. A clear audience and purpose will guide every later decision.\n\nNext, sketch the essential pages and the path between them. Keep the first version small enough to build, test, and improve.",
        'published',
    ],
    [
        'tutorials',
        'Understanding HTML Forms from the Ground Up',
        'Learn how form controls collect useful information accessibly.',
        "Forms connect people to the services a website provides. Use a label for each control, choose an input type that matches the data, and make required fields clear.\n\nOn the server, validate every submitted value again. Browser validation helps users, but it does not replace server-side checks.",
        'published',
    ],
    [
        'tutorials',
        'A Beginner-Friendly Introduction to CSS Grid',
        'Build flexible page layouts with a few clear grid rules.',
        "CSS Grid lets you describe rows and columns without hard-coding every card position. Start with repeat(), minmax(), and a sensible gap to create a responsive collection of cards.\n\nTest the layout at narrow and wide viewport sizes. Content should remain readable even when the grid becomes a single column.",
        'published',
    ],
    [
        'tutorials',
        'Debugging PHP Database Queries Safely',
        'A repeatable approach to finding and fixing query issues.',
        "Check the connection, inspect the SQLSTATE, and verify that the table and column names match the active schema. During development, log detailed errors rather than displaying them to site visitors.\n\nUse prepared statements for values and test changes with representative data before publishing them.",
        'draft',
    ],
    [
        'news',
        'What We Learned from This Month’s Community Meetup',
        'A few useful ideas shared by local developers and designers.',
        "The meetup brought together people working on projects at every stage. A recurring theme was the value of showing work early and asking specific questions.\n\nThanks to everyone who shared a demo, offered feedback, or helped welcome a first-time attendee.",
        'published',
    ],
    [
        'news',
        'New Learning Resources Added to the Community Hub',
        'Explore a fresh collection of beginner-focused references.',
        "We have added a set of practical references covering accessibility, version control, and web performance. Each resource was chosen to help readers move from explanation to a small hands-on exercise.\n\nIf you have a useful resource to recommend, share its title and a short note about who it helps.",
        'published',
    ],
    [
        'news',
        'A Small Update to Our Publishing Workflow',
        'The blog editor now makes drafting and reviewing clearer.',
        "The latest workflow update separates draft work from published articles and makes category organization easier to understand.\n\nWe will continue collecting feedback and prioritizing improvements that make publishing more reliable.",
        'published',
    ],
    [
        'news',
        'Upcoming Workshops: Building Better Web Habits',
        'A preview of upcoming sessions and topics.',
        "The next workshop series will cover readable interfaces, practical testing, and safer handling of user-submitted data.\n\nDates and registration details will be shared here once they are confirmed.",
        'draft',
    ],
    [
        'career',
        'Preparing a Portfolio That Tells a Clear Story',
        'Present project decisions, outcomes, and lessons—not just screenshots.',
        "Choose a small number of projects that demonstrate the kind of work you want to do. For each one, explain the problem, your contribution, and the result in plain language.\n\nBe honest about unfinished parts and describe what you would improve next. Clear reasoning is more useful than an exaggerated claim.",
        'published',
    ],
    [
        'career',
        'How to Make the Most of a Technical Interview',
        'Simple ways to communicate your reasoning during a challenge.',
        "Before coding, restate the problem and ask questions about inputs, constraints, and expected output. This creates a shared understanding and gives you a useful starting point.\n\nThink aloud, test a small example, and explain trade-offs. Interviewers are interested in your approach as well as the final result.",
        'published',
    ],
    [
        'career',
        'Building a Sustainable Routine for Learning to Code',
        'Make steady progress with focused, repeatable practice.',
        "Choose a regular time and a specific task small enough to finish in one sitting. Consistent practice helps you notice patterns and build confidence without relying on long bursts of motivation.\n\nKeep brief notes about what you tried, what worked, and what you want to revisit.",
        'published',
    ],
    [
        'career',
        'Writing a Useful Project Retrospective',
        'Turn a completed project into practical lessons for the next one.',
        "A good retrospective is specific and kind. Record the decisions that helped, the obstacles you encountered, and one change you would make next time.\n\nShare credit with collaborators and focus on actions that are within your control.",
        'published',
    ],
];

$pdo->beginTransaction();
try {
    $findCategory = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
    $categoryIds = [];
    foreach ($categoryNames as $slug => $name) {
        $findCategory->execute(['slug' => $slug]);
        $categoryId = $findCategory->fetchColumn();
        if ($categoryId === false) {
            $insertCategory->execute(['name' => $name, 'slug' => $slug]);
            $categoryId = $pdo->lastInsertId();
        }
        $categoryIds[$slug] = (int) $categoryId;
    }

    $findPost = $pdo->prepare(
        'SELECT id FROM posts WHERE category_id = :category_id AND title = :title LIMIT 1'
    );
    $insertPost = $pdo->prepare(
        'INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, published_at)
         VALUES (:category_id, :author_id, :title, :slug, :excerpt, :body, :status,
                 IF(:is_published = 1, NOW(), NULL))'
    );

    $created = 0;
    $skipped = 0;
    foreach ($seedPosts as [$categorySlug, $title, $excerpt, $body, $status]) {
        $categoryId = $categoryIds[$categorySlug];
        $findPost->execute(['category_id' => $categoryId, 'title' => $title]);
        if ($findPost->fetchColumn() !== false) {
            $skipped++;
            continue;
        }

        $insertPost->execute([
            'category_id' => $categoryId,
            'author_id' => $authorId,
            'title' => $title,
            'slug' => unique_post_slug($pdo, $title),
            'excerpt' => $excerpt,
            'body' => $body,
            'status' => $status,
            'is_published' => $status === 'published' ? 1 : 0,
        ]);
        $created++;
    }

    $pdo->commit();
    fwrite(STDOUT, "Seed complete: created {$created} posts, skipped {$skipped} existing posts.\n");
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Blog seeding failed: ' . $exception->getMessage());
    fwrite(STDERR, "Seeding failed. Check that the database schema is current and try again.\n");
    exit(1);
}
