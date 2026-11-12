-- Upgrade the earlier SWD Blog schema without removing any existing content.
-- Run against swd_blog once if it was created by the original schema version.
ALTER TABLE admins
    CHANGE COLUMN name username VARCHAR(80) NOT NULL,
    MODIFY COLUMN email VARCHAR(190) NOT NULL,
    ADD UNIQUE KEY uq_admins_username (username);

ALTER TABLE categories
    MODIFY COLUMN name VARCHAR(120) NOT NULL,
    MODIFY COLUMN slug VARCHAR(160) NOT NULL,
    ADD COLUMN description TEXT NULL;

ALTER TABLE posts
    DROP FOREIGN KEY posts_ibfk_1,
    DROP FOREIGN KEY posts_ibfk_2;

ALTER TABLE posts
    MODIFY COLUMN title VARCHAR(240) NOT NULL,
    MODIFY COLUMN slug VARCHAR(260) NOT NULL,
    MODIFY COLUMN excerpt VARCHAR(300) NULL,
    MODIFY COLUMN category_id INT UNSIGNED NULL,
    ADD KEY idx_posts_status_published (status, published_at);

ALTER TABLE posts
    ADD CONSTRAINT fk_posts_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_posts_author FOREIGN KEY (author_id)
        REFERENCES admins (id) ON UPDATE CASCADE ON DELETE RESTRICT;
