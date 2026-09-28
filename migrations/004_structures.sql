-- =============================================================
-- 004 : Structures (organismes : associations, musées, collectifs…)
-- Un utilisateur : individuel OU membre d'une ou plusieurs structures.
-- Un lieu : optionnellement rattaché à une structure.
-- Rôles par structure : admin(3) > contributeur(2) > membre(1)
-- =============================================================

CREATE TABLE structures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL,
    nom VARCHAR(180) NOT NULL,
    type ENUM('association','musee','collectif','mairie','galerie','autre') NOT NULL DEFAULT 'association',
    description TEXT NULL,
    website VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    commune_id INT UNSIGNED NULL,
    logo_path VARCHAR(255) NULL,
    status ENUM('pending','published','archived') NOT NULL DEFAULT 'pending',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_structures_slug (slug),
    KEY idx_structures_nom (nom),
    KEY idx_structures_status (status),
    CONSTRAINT fk_structures_commune FOREIGN KEY (commune_id) REFERENCES communes (id) ON DELETE SET NULL,
    CONSTRAINT fk_structures_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE structure_members (
    structure_id INT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('member','contributor','admin') NOT NULL DEFAULT 'member',
    status ENUM('pending','active','refused') NOT NULL DEFAULT 'pending',
    -- sens de la demande : user a demandé / structure a invité
    requested_by ENUM('user','structure') NOT NULL DEFAULT 'user',
    joined_at DATETIME NULL,
    UNIQUE KEY uq_sm (structure_id, user_id),
    KEY idx_sm_user (user_id),
    CONSTRAINT fk_sm_structure FOREIGN KEY (structure_id) REFERENCES structures (id) ON DELETE CASCADE,
    CONSTRAINT fk_sm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE places ADD COLUMN structure_id INT UNSIGNED NULL,
  ADD KEY idx_places_structure (structure_id),
  ADD CONSTRAINT fk_places_structure FOREIGN KEY (structure_id) REFERENCES structures (id) ON DELETE SET NULL;

ALTER TABLE exhibitions ADD COLUMN structure_id INT UNSIGNED NULL,
  ADD KEY idx_exhibitions_structure (structure_id),
  ADD CONSTRAINT fk_exhibitions_structure FOREIGN KEY (structure_id) REFERENCES structures (id) ON DELETE SET NULL;
