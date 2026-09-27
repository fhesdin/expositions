-- =============================================================
-- expositions.top — schéma MVP (utf8mb4, dates NULL, FK CASCADE)
-- Département 80 (Somme) ; l'architecture prévoit d'autres territoires.
-- =============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Communes de la Somme (référentiel importé, alimenté par seed)
-- ------------------------------------------------------------
CREATE TABLE communes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code_insee    VARCHAR(5)  NOT NULL,
    nom           VARCHAR(120) NOT NULL,
    code_postal   VARCHAR(5)  NULL,
    latitude      DECIMAL(9,6) NULL,
    longitude     DECIMAL(9,6) NULL,
    intercommunalite VARCHAR(150) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_communes_insee (code_insee),
    KEY idx_communes_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Utilisateurs & rôles
-- ------------------------------------------------------------
CREATE TABLE roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)  NOT NULL,
    `rank`      TINYINT UNSIGNED NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name),
    UNIQUE KEY uq_roles_rank (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    username        VARCHAR(100) NOT NULL,
    role_id         TINYINT UNSIGNED NOT NULL,
    commune_id      INT UNSIGNED NULL,
    interests       JSON NULL,               -- catégories suivies
    notify_email    TINYINT(1) NOT NULL DEFAULT 1,
    notify_weekly   TINYINT(1) NOT NULL DEFAULT 1,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    email_confirmed_at DATETIME NULL,
    confirm_token   VARCHAR(64) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at   DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    KEY idx_users_commune (commune_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_users_commune FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rangs : 1 visiteur, 2 membre, 3 contributeur (artiste / gestionnaire), 4 modérateur, 5 admin
ALTER TABLE users
    ADD COLUMN remember_selector VARCHAR(24) NULL,
    ADD COLUMN remember_validator VARCHAR(255) NULL,
    ADD COLUMN remember_expires_at DATETIME NULL,
    ADD COLUMN trusted_level TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=nouveau,2=confirme,3=suspendu',
    ADD COLUMN artist_verified TINYINT(1) NOT NULL DEFAULT 0;

-- ------------------------------------------------------------
-- Taxonomie
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(80) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    color       CHAR(7) NOT NULL DEFAULT '#607d8b',
    icon        VARCHAR(40) NULL,
    intro       TEXT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
    id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug      VARCHAR(80) NOT NULL,
    name      VARCHAR(80) NOT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE place_types (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug  VARCHAR(60) NOT NULL,
    name  VARCHAR(100) NOT NULL,
    is_ephemeral TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_place_types_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE publics (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug  VARCHAR(60) NOT NULL,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_publics_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE labels (
    id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug  VARCHAR(80) NOT NULL,
    name  VARCHAR(120) NOT NULL,          -- Journées du patrimoine, Nuit des musées…
    year  SMALLINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_labels_slug_year (slug, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Lieux
-- ------------------------------------------------------------
CREATE TABLE places (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(160) NOT NULL,
    name          VARCHAR(180) NOT NULL,
    type_id       TINYINT UNSIGNED NULL,
    commune_id    INT UNSIGNED NULL,
    address       VARCHAR(255) NULL,
    latitude      DECIMAL(9,6) NULL,
    longitude     DECIMAL(9,6) NULL,
    description   TEXT NULL,
    access_info   VARCHAR(500) NULL,
    opening_hours TEXT NULL,               -- horaires habituels (texte libre MVP)
    access_pmr    TINYINT(1) NOT NULL DEFAULT 0,
    access_visual  TINYINT(1) NOT NULL DEFAULT 0,
    access_hearing TINYINT(1) NOT NULL DEFAULT 0,
    access_mental  TINYINT(1) NOT NULL DEFAULT 0,
    services      VARCHAR(255) NULL,
    phone         VARCHAR(30) NULL,
    email         VARCHAR(255) NULL,
    website       VARCHAR(255) NULL,
    facebook      VARCHAR(255) NULL,
    instagram     VARCHAR(255) NULL,
    free_access   TINYINT(1) NULL DEFAULT NULL COMMENT 'NULL=variable',
    image_path    VARCHAR(255) NULL,
    status        ENUM('draft','pending','published','rejected','hidden') NOT NULL DEFAULT 'pending',
    verified      TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'institution vérifiée',
    is_ephemeral  TINYINT(1) NOT NULL DEFAULT 0,
    source_import VARCHAR(50) NULL,
    created_by    BIGINT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_places_slug (slug),
    KEY idx_places_commune (commune_id),
    KEY idx_places_type (type_id),
    KEY idx_places_status (status),
    CONSTRAINT fk_places_commune FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE SET NULL,
    CONSTRAINT fk_places_type FOREIGN KEY (type_id) REFERENCES place_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_places_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gestionnaires de lieux (plusieurs par lieu, un principal)
CREATE TABLE place_managers (
    place_id   INT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (place_id, user_id),
    CONSTRAINT fk_pm_place FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Artistes
-- ------------------------------------------------------------
CREATE TABLE artists (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(160) NOT NULL,
    name          VARCHAR(180) NOT NULL,
    disciplines   VARCHAR(255) NULL,
    commune_id    INT UNSIGNED NULL,
    bio           TEXT NULL,
    statement     TEXT NULL COMMENT 'démarche artistique',
    image_path    VARCHAR(255) NULL,
    website       VARCHAR(255) NULL,
    facebook      VARCHAR(255) NULL,
    instagram     VARCHAR(255) NULL,
    workshop_open TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'atelier ouvert',
    workshop_info VARCHAR(255) NULL,
    contact_email VARCHAR(255) NULL,
    user_id       BIGINT UNSIGNED NULL COMMENT 'compte artiste lié',
    status        ENUM('draft','pending','published','rejected','hidden') NOT NULL DEFAULT 'pending',
    is_historical TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'décédé / historique',
    created_by    BIGINT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_artists_slug (slug),
    KEY idx_artists_commune (commune_id),
    KEY idx_artists_status (status),
    CONSTRAINT fk_artists_commune FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE SET NULL,
    CONSTRAINT fk_artists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_artists_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artist_works (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    artist_id  INT UNSIGNED NOT NULL,
    title      VARCHAR(180) NOT NULL,
    year       VARCHAR(10) NULL,
    technique  VARCHAR(120) NULL,
    dimensions VARCHAR(60) NULL,
    image_path VARCHAR(255) NOT NULL,
    caption    VARCHAR(255) NULL,
    position   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_works_artist (artist_id),
    CONSTRAINT fk_works_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Revendications de pages (artiste ou lieu)
CREATE TABLE claims (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind       ENUM('artist','place') NOT NULL,
    entity_id  INT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NULL COMMENT 'null = visiteur anonyme',
    email      VARCHAR(255) NULL,
    justification TEXT NULL,
    status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    moderator_id BIGINT UNSIGNED NULL,
    moderator_note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_claims_status (status),
    CONSTRAINT fk_claims_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_claims_moderator FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Expositions
-- ------------------------------------------------------------
CREATE TABLE exhibitions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(200) NOT NULL,
    title           VARCHAR(120) NOT NULL,
    place_id        INT UNSIGNED NULL COMMENT 'NULL = hors les murs / proposition sans lieu',
    commune_id      INT UNSIGNED NULL,
    summary         VARCHAR(300) NOT NULL,
    description     TEXT NULL,
    starts_at       DATE NULL,
    ends_at         DATE NULL,
    is_permanent    TINYINT(1) NOT NULL DEFAULT 0,
    opening_hours   TEXT NULL COMMENT 'exceptions / dérogations',
    poster_path     VARCHAR(255) NULL,
    poster_credit   VARCHAR(180) NULL,
    commissionner   VARCHAR(180) NULL,
    price_kind      ENUM('gratuit','payant','reservation','libre') NOT NULL DEFAULT 'libre',
    price_detail    VARCHAR(255) NULL,
    ticket_url      VARCHAR(255) NULL,
    video_url       VARCHAR(255) NULL,
    access_pmr      TINYINT(1) NOT NULL DEFAULT 0,
    access_visual   TINYINT(1) NOT NULL DEFAULT 0,
    access_hearing  TINYINT(1) NOT NULL DEFAULT 0,
    access_mental   TINYINT(1) NOT NULL DEFAULT 0,
    languages       VARCHAR(120) NULL,
    category_id     SMALLINT UNSIGNED NULL,
    label_id        SMALLINT UNSIGNED NULL,
    status          ENUM('draft','pending','published','rejected','archived','cancelled','postponed') NOT NULL DEFAULT 'draft',
    featured        TINYINT(1) NOT NULL DEFAULT 0,
    featured_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    view_count      INT UNSIGNED NOT NULL DEFAULT 0,
    contact_email   VARCHAR(255) NULL COMMENT 'proposition sans compte',
    submitted_by    BIGINT UNSIGNED NULL,
    moderated_by    BIGINT UNSIGNED NULL,
    moderated_at    DATETIME NULL,
    reject_reason   VARCHAR(255) NULL,
    scheduled_status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=auto-archive OK',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_exhibitions_slug (slug),
    KEY idx_exhibitions_place (place_id),
    KEY idx_exhibitions_commune (commune_id),
    KEY idx_exhibitions_category (category_id),
    KEY idx_exhibitions_status (status),
    KEY idx_exhibitions_dates (starts_at, ends_at),
    CONSTRAINT fk_exhibitions_place FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE SET NULL,
    CONSTRAINT fk_exhibitions_commune FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE SET NULL,
    CONSTRAINT fk_exhibitions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_exhibitions_label FOREIGN KEY (label_id) REFERENCES labels(id) ON DELETE SET NULL,
    CONSTRAINT fk_exhibitions_submitter FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_exhibitions_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Artistes liés à une exposition (nom libre si pas de page)
CREATE TABLE exhibition_artists (
    exhibition_id INT UNSIGNED NOT NULL,
    artist_id     INT UNSIGNED NULL,
    free_name     VARCHAR(180) NULL,
    UNIQUE KEY uq_ea (exhibition_id, artist_id, free_name),
    KEY idx_ea_artist (artist_id),
    CONSTRAINT fk_ea_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ea_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exhibition_tags (
    exhibition_id INT UNSIGNED NOT NULL,
    tag_id        SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (exhibition_id, tag_id),
    CONSTRAINT fk_et_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_et_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exhibition_publics (
    exhibition_id INT UNSIGNED NOT NULL,
    public_id     TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (exhibition_id, public_id),
    CONSTRAINT fk_ep_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_public FOREIGN KEY (public_id) REFERENCES publics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exhibition_images (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    exhibition_id INT UNSIGNED NOT NULL,
    image_path    VARCHAR(255) NOT NULL,
    caption       VARCHAR(255) NULL,
    credit        VARCHAR(180) NULL,
    position      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_ei_exhibition (exhibition_id),
    CONSTRAINT fk_ei_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exhibition_docs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    exhibition_id INT UNSIGNED NOT NULL,
    kind          ENUM('presse','livret') NOT NULL DEFAULT 'presse',
    title         VARCHAR(180) NULL,
    file_path     VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ed_exhibition (exhibition_id),
    CONSTRAINT fk_ed_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Événements associés (vernissage, visite guidée, conférence…)
-- ------------------------------------------------------------
CREATE TABLE exhibition_events (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    exhibition_id INT UNSIGNED NOT NULL,
    type          ENUM('vernissage','finissage','visite','conference','atelier','rencontre','nocturne','jpo','autre') NOT NULL DEFAULT 'autre',
    title         VARCHAR(180) NOT NULL,
    starts_at     DATETIME NULL,
    ends_at       DATETIME NULL,
    duration_min  SMALLINT UNSIGNED NULL,
    description   TEXT NULL,
    price_kind    ENUM('gratuit','payant','reservation','libre') NOT NULL DEFAULT 'libre',
    capacity      SMALLINT UNSIGNED NULL,
    booking_url   VARCHAR(255) NULL,
    recurrence    VARCHAR(180) NULL COMMENT 'ex : tous les samedis à 15h',
    place_id      INT UNSIGNED NULL COMMENT 'override (hors les murs)',
    status        ENUM('draft','pending','published','rejected','archived','cancelled') NOT NULL DEFAULT 'draft',
    view_count    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ee_exhibition (exhibition_id),
    KEY idx_ee_dates (starts_at),
    CONSTRAINT fk_ee_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ee_place FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Communauté : favoris, agenda, suivis, newsletters, signalements
-- ------------------------------------------------------------
CREATE TABLE favorites (
    user_id    BIGINT UNSIGNED NOT NULL,
    kind       ENUM('exhibition','artist','place') NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, kind, item_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "J'y vais" / "J'y suis allé"
CREATE TABLE visits (
    user_id        BIGINT UNSIGNED NOT NULL,
    exhibition_id  INT UNSIGNED NOT NULL,
    going          TINYINT(1) NOT NULL DEFAULT 0,
    went           TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, exhibition_id),
    CONSTRAINT fk_visits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_visits_exhibition FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follows (
    user_id BIGINT UNSIGNED NOT NULL,
    kind    ENUM('artist','place','category','tag','commune') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, kind, item_id),
    CONSTRAINT fk_follows_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(255) NOT NULL,
    confirmed     TINYINT(1) NOT NULL DEFAULT 0,
    confirm_token VARCHAR(64) NULL,
    unsubscribe_token VARCHAR(64) NULL,
    commune_id    INT UNSIGNED NULL,
    interests     JSON NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_email (email),
    CONSTRAINT fk_news_commune FOREIGN KEY (commune_id) REFERENCES communes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind       ENUM('exhibition','event','artist','place','comment') NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NULL,
    email      VARCHAR(255) NULL,
    reason     VARCHAR(500) NOT NULL,
    status     ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    moderator_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reports_status (status),
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_moderator FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suggestions (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kind       ENUM('missing_expo','error') NOT NULL DEFAULT 'missing_expo',
    place_name VARCHAR(180) NULL,
    title      VARCHAR(180) NULL,
    commune    VARCHAR(120) NULL,
    dates      VARCHAR(120) NULL,
    message    TEXT NULL,
    email      VARCHAR(255) NULL,
    status     ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    moderator_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Journal de modération + settings
-- ------------------------------------------------------------
CREATE TABLE mod_actions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    action      VARCHAR(60) NOT NULL,     -- approve, reject, edit, hide, merge, claim.approve...
    kind        VARCHAR(30) NULL,
    item_id     INT UNSIGNED NULL,
    details     VARCHAR(500) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mod_user (user_id),
    CONSTRAINT fk_mod_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    `key`   VARCHAR(100) NOT NULL,
    value   TEXT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;