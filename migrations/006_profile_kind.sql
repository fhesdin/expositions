ALTER TABLE users ADD COLUMN profile_kind ENUM('membre','artiste','lieu','structure') NOT NULL DEFAULT 'membre' AFTER commune_id;
