-- Run once if posts was created from a schema version without the excerpt column.
ALTER TABLE posts
    ADD COLUMN excerpt VARCHAR(300) NULL AFTER slug;
