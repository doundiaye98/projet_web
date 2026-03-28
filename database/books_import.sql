USE projet_web;

-- Auteurs
INSERT INTO authors (nom, bio)
SELECT 'Antoine de Saint-Exupery', NULL
WHERE NOT EXISTS (SELECT 1 FROM authors WHERE nom = 'Antoine de Saint-Exupery');

INSERT INTO authors (nom, bio)
SELECT 'George Orwell', NULL
WHERE NOT EXISTS (SELECT 1 FROM authors WHERE nom = 'George Orwell');

INSERT INTO authors (nom, bio)
SELECT 'Albert Camus', NULL
WHERE NOT EXISTS (SELECT 1 FROM authors WHERE nom = 'Albert Camus');

INSERT INTO authors (nom, bio)
SELECT 'Alexandre Dumas', NULL
WHERE NOT EXISTS (SELECT 1 FROM authors WHERE nom = 'Alexandre Dumas');

-- Livres (adapte a ta structure actuelle avec genre + date_debut/date_fin)
INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, date_debut, date_fin, created_by)
SELECT
    (SELECT id FROM authors WHERE nom = 'Antoine de Saint-Exupery' LIMIT 1),
    'Le Petit Prince',
    'Conte',
    'Une histoire poetique sur l essentiel, la decouverte et la responsabilite.',
    NULL,
    0,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    (SELECT id FROM users WHERE email = 'admin@club.test' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM books b
    JOIN authors a ON a.id = b.author_id
    WHERE b.titre = 'Le Petit Prince' AND a.nom = 'Antoine de Saint-Exupery'
);

INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, date_debut, date_fin, created_by)
SELECT
    (SELECT id FROM authors WHERE nom = 'George Orwell' LIMIT 1),
    '1984',
    'Dystopie',
    'Dans un monde totalitaire, la verite devient une arme et la liberte un reve interdit.',
    NULL,
    0,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    (SELECT id FROM users WHERE email = 'admin@club.test' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM books b
    JOIN authors a ON a.id = b.author_id
    WHERE b.titre = '1984' AND a.nom = 'George Orwell'
);

INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, date_debut, date_fin, created_by)
SELECT
    (SELECT id FROM authors WHERE nom = 'Albert Camus' LIMIT 1),
    'L''Etranger',
    'Roman',
    'Un recit bref et intense sur l absurde, le regard detache et les consequences.',
    NULL,
    0,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    (SELECT id FROM users WHERE email = 'admin@club.test' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM books b
    JOIN authors a ON a.id = b.author_id
    WHERE b.titre = 'L''Etranger' AND a.nom = 'Albert Camus'
);

INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, date_debut, date_fin, created_by)
SELECT
    (SELECT id FROM authors WHERE nom = 'Alexandre Dumas' LIMIT 1),
    'Le Comte de Monte-Cristo',
    'Aventure',
    'Trahison, emprisonnement et vengeance au grand souffle romanesque.',
    NULL,
    0,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    (SELECT id FROM users WHERE email = 'admin@club.test' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM books b
    JOIN authors a ON a.id = b.author_id
    WHERE b.titre = 'Le Comte de Monte-Cristo' AND a.nom = 'Alexandre Dumas'
);

