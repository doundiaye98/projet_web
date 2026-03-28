<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

$userRole = getUserRole();
if (!in_array($userRole, ['admin', 'moderateur'], true)) {
    http_response_code(403);
    $errorCode = 403;
    include __DIR__ . '/../../includes/layout/error_page.php';
    exit();
}

$pageTitle = 'Moderation';
$hiddenReviews = [];
$stats = [
    'hidden_reviews' => 0,
    'reported_books' => 0,
    'active_moderators' => 0,
];

$result = $mysqli->query("SELECT COUNT(*) AS total FROM reviews WHERE visible = 0");
if ($result) {
    $stats['hidden_reviews'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("
    SELECT COUNT(DISTINCT b.id) AS total
    FROM books b
    JOIN reviews r ON r.book_id = b.id
    WHERE r.visible = 0
");
if ($result) {
    $stats['reported_books'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role IN ('admin', 'moderateur') AND statut = 'actif'
");
if ($result) {
    $stats['active_moderators'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("
    SELECT r.id, r.note, r.commentaire, r.created_at, b.titre AS book_title, u.nom AS author_name
    FROM reviews r
    JOIN books b ON b.id = r.book_id
    JOIN users u ON u.id = r.user_id
    WHERE r.visible = 0
    ORDER BY r.created_at DESC
    LIMIT 8
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hiddenReviews[] = $row;
    }
}

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Modération</h1>
        <p class="text-muted mt-2">Espace de suivi des contenus masques et de la securite de la communaute.</p>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-10">
        <article class="rounded-2xl border border-border bg-white/70 p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Avis masques</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['hidden_reviews'] ?></p>
        </article>
        <article class="rounded-2xl border border-border bg-white/70 p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Livres concernes</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['reported_books'] ?></p>
        </article>
        <article class="rounded-2xl border border-border bg-white/70 p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Moderateurs actifs</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['active_moderators'] ?></p>
        </article>
    </section>

    <section class="rounded-2xl border border-border bg-white/70 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-body font-semibold text-ink">Derniers avis masques</h2>
            <span class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">Lecture seule</span>
        </div>

        <?php if (empty($hiddenReviews)): ?>
            <div class="rounded-xl border border-border/80 bg-cream/60 p-6 text-center">
                <p class="text-ink font-medium">Aucun avis masque actuellement.</p>
                <p class="text-sm text-muted mt-1">La file de moderation est vide.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($hiddenReviews as $review): ?>
                    <article class="rounded-xl border border-border/80 bg-cream/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-ink"><?= htmlspecialchars($review['book_title']) ?></p>
                                <p class="text-xs text-muted mt-0.5">
                                    Par <?= htmlspecialchars($review['author_name']) ?>
                                    - <?= (new DateTime($review['created_at']))->format('d/m/Y H:i') ?>
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-stone-200 text-stone-700">
                                Note <?= (int) $review['note'] ?>/5
                            </span>
                        </div>
                        <?php if (!empty($review['commentaire'])): ?>
                            <p class="text-sm text-ink/80 mt-3 leading-relaxed">
                                <?= nl2br(htmlspecialchars($review['commentaire'])) ?>
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>
