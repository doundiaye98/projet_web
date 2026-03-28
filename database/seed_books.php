<?php
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/db.php';

$adminEmail = 'admin@club.test';

// Récupère l'ID admin (compatible sans mysqlnd/get_result)
$adminId = null;
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    die("Erreur SQL (users): " . $mysqli->error);
}
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($adminId);
$stmt->fetch();
$stmt->close();

if (!$adminId) {
    die("Admin introuvable. Assure-toi que l'email admin existe : {$adminEmail}");
}

function getOrCreateAuthor($mysqli, $nom, $bio = null) {
    $nom = trim($nom);
    if ($nom === '') return null;

    $stmt = $mysqli->prepare("SELECT id FROM authors WHERE nom = ? LIMIT 1");
    if (!$stmt) {
        die("Erreur SQL (authors): " . $mysqli->error);
    }
    $stmt->bind_param("s", $nom);
    $stmt->execute();
    $id = null;
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    if ($id) return (int)$id;

    $stmt = $mysqli->prepare("INSERT INTO authors (nom, bio) VALUES (?, ?)");
    if (!$stmt) {
        die("Erreur SQL (authors insert): " . $mysqli->error);
    }
    $stmt->bind_param("ss", $nom, $bio);
    $stmt->execute();
    $id = (int)$mysqli->insert_id;
    $stmt->close();
    return $id;
}

function bookExists($mysqli, $authorId, $titre) {
    $stmt = $mysqli->prepare("SELECT id FROM books WHERE author_id = ? AND titre = ? LIMIT 1");
    if (!$stmt) {
        die("Erreur SQL (books exists): " . $mysqli->error);
    }
    $stmt->bind_param("is", $authorId, $titre);
    $stmt->execute();
    $id = null;
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    return (bool)$id;
}

function addBook($mysqli, $authorId, $titre, $genre, $description, $createdBy, $nbPages = 0) {
    $stmt = $mysqli->prepare("
        INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, created_by)
        VALUES (?, ?, ?, ?, NULL, ?, ?)
    ");
    if (!$stmt) {
        die("Erreur SQL (books insert): " . $mysqli->error);
    }
    $stmt->bind_param("isssii", $authorId, $titre, $genre, $description, $nbPages, $createdBy);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

$books = [
    [
        'titre' => "Le Petit Prince",
        'author' => 'Antoine de Saint-Exupery',
        'genre' => 'Conte',
        'description' => "Une histoire poétique sur l'essentiel, la découverte et la responsabilité.",
    ],
    [
        'titre' => "1984",
        'author' => 'George Orwell',
        'genre' => 'Dystopie',
        'description' => "Dans un monde totalitaire, la vérité devient une arme et la liberté un rêve interdit.",
    ],
    [
        'titre' => "L'Étranger",
        'author' => 'Albert Camus',
        'genre' => 'Roman',
        'description' => "Un récit bref et intense sur l'absurde, le regard détaché et les conséquences.",
    ],
    [
        'titre' => "Le Comte de Monte-Cristo",
        'author' => 'Alexandre Dumas',
        'genre' => 'Aventure',
        'description' => "Trahison, emprisonnement et vengeance au grand souffle romanesque.",
    ],
];

$added = 0;
$skipped = 0;

$mysqli->begin_transaction();
try {
    foreach ($books as $b) {
        $authorId = getOrCreateAuthor($mysqli, $b['author'], null);
        if (!$authorId) continue;

        if (bookExists($mysqli, $authorId, $b['titre'])) {
            $skipped++;
            continue;
        }

        // Sans PDF pour l'instant : nb_pages=0 et aucun document => bouton "Lire" désactivé.
        if (addBook($mysqli, $authorId, $b['titre'], $b['genre'], $b['description'], $adminId, 0)) {
            $added++;
        }
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    die("Erreur seed_books.php : " . $e->getMessage());
}

echo "Seed livres terminé. Ajoutés: {$added}, déjà existants: {$skipped}.";

