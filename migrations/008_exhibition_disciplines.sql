-- 008 : Disciplines d'une exposition (tags administrés, comme pour les artistes)
CREATE TABLE IF NOT EXISTS exhibition_disciplines (
    exhibition_id INT UNSIGNED NOT NULL,
    discipline_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (exhibition_id, discipline_id),
    CONSTRAINT fk_ed_expo FOREIGN KEY (exhibition_id) REFERENCES exhibitions (id) ON DELETE CASCADE,
    CONSTRAINT fk_ed_disc FOREIGN KEY (discipline_id) REFERENCES disciplines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;