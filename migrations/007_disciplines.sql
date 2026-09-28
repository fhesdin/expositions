-- 007 : Disciplines artistiques = tags administrés (plus de saisie libre)
CREATE TABLE IF NOT EXISTS disciplines (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_disciplines_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_disciplines (
    artist_id INT UNSIGNED NOT NULL,
    discipline_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (artist_id, discipline_id),
    CONSTRAINT fk_ad_artist FOREIGN KEY (artist_id) REFERENCES artists (id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_discipline FOREIGN KEY (discipline_id) REFERENCES disciplines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed : disciplines courantes (inspirées des pratiques existantes)
INSERT INTO disciplines (slug, name) VALUES
('peinture', 'Peinture'),
('photographie', 'Photographie'),
('sculpture', 'Sculpture'),
('ceramique', 'Céramique'),
('gravure', 'Gravure'),
('illustration', 'Illustration'),
('installation', 'Installation'),
('video', 'Vidéo'),
('textile', 'Art textile'),
('mosaique', 'Mosaïque'),
('dessin', 'Dessin'),
('street-art', 'Street art');

-- Migration des valeurs libres existantes -> tags + liaison
INSERT INTO disciplines (slug, name)
SELECT LOWER(TRIM(d.val)), TRIM(d.val) FROM (
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(a.disciplines, ',', n.n), ',', -1) AS val
    FROM artists a
    JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) n
      ON n.n <= 1 + LENGTH(a.disciplines) - LENGTH(REPLACE(a.disciplines, ',', ''))
    WHERE a.disciplines IS NOT NULL AND TRIM(a.disciplines) <> ''
) d
WHERE NOT EXISTS (SELECT 1 FROM disciplines dd WHERE dd.slug = LOWER(TRIM(d.val)));

INSERT IGNORE INTO artist_disciplines (artist_id, discipline_id)
SELECT a.id, d.id FROM artists a
JOIN (
    SELECT a2.id AS artist_id, SUBSTRING_INDEX(SUBSTRING_INDEX(a2.disciplines, ',', n2.n), ',', -1) AS val
    FROM artists a2
    JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) n2
      ON n2.n <= 1 + LENGTH(a2.disciplines) - LENGTH(REPLACE(a2.disciplines, ',', ''))
    WHERE a2.disciplines IS NOT NULL AND TRIM(a2.disciplines) <> ''
) x ON x.artist_id = a.id
JOIN disciplines d ON d.slug = LOWER(TRIM(x.val));
