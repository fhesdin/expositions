-- =============================================================
-- Seed : rôles, catégories, types de lieux, publics, labels
-- =============================================================

INSERT INTO roles (id, name, `rank`, description) VALUES
(1,'visiteur',1,'Consultation seule'),
(2,'membre',2,'Favoris, agenda, commentaires'),
(3,'contributeur',3,'Artiste / gestionnaire de lieu'),
(4,'moderateur',4,'Validation des contenus'),
(5,'admin',5,'Administration complète');

INSERT INTO categories (slug, name, color, icon, is_active) VALUES
('art-contemporain','Art contemporain','#e91e63','palette',1),
('beaux-arts','Beaux-arts','#3f51b5','brush',1),
('photographie','Photographie','#009688','camera',1),
('sculpture','Sculpture et installation','#795548','architecture',1),
('arts-graphiques','Arts graphiques, illustration, BD','#ff9800','edit',1),
('design','Design, architecture, arts décoratifs','#607d8b','design',1),
('artisanat','Artisanat d''art','#8d6e63','handyman',1),
('numerique','Arts numériques','#673ab7','memory',1),
('patrimoine','Patrimoine et histoire','#5d4037','account_balance',1),
('archeologie','Archéologie','#8bc34a','explore',1),
('sciences','Sciences et techniques','#03a9f4','science',1),
('nature','Nature et environnement','#4caf50','eco',1),
('societe','Société et ethnologie','#9c27b0','people',1);

INSERT INTO place_types (slug, name, is_ephemeral) VALUES
('musee','Musée',0),
('centre-art','Centre d''art',0),
('galerie-publique','Galerie publique',0),
('galerie-privee','Galerie privée',0),
('mediatheque','Médiathèque',0),
('archives','Archives',0),
('mairie','Mairie',0),
('chateau','Château et monument',0),
('atelier','Atelier d''artiste',0),
('tiers-lieu','Tiers-lieu',0),
('lieu-ephemere','Lieu éphémère',1),
('espace-public','Espace public',1),
('salon-foire','Salon / foire d''art',1);

INSERT INTO publics (slug, name) VALUES
('tout-public','Tout public'),
('familles','Familles'),
('jeune-public','Jeune public'),
('scolaires','Scolaires');

INSERT INTO labels (slug, name, year) VALUES
('journees-europeennes-du-patrimoine','Journées européennes du patrimoine',2026),
('nuit-des-musees','Nuit des musées',2026),
('rendez-vous-aux-jardins','Rendez-vous aux jardins',2026);

-- Paramètres par défaut
INSERT INTO settings (`key`, value) VALUES
('site_name','expositions.top — la Somme'),
('moderation_delay_hours','72'),
('trust_confirmed_threshold','5'),
('newsletter_frequency','hebdomadaire');