<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

$pageTitle = 'Dashboard';
$userRole = getUserRole();
$userId = (int) ($_SESSION['user_id'] ?? 0);

$stats = [
    'books' => 0,
    'reviews' => 0,
    'sessions' => 0,
    'users' => 0,
];

$upcomingSessions = [];
$readingProgress = [];
$progressStats = [
    'average' => 0,
    'completed' => 0,
];

$result = $mysqli->query("SELECT COUNT(*) AS total FROM books");
if ($result) {
    $stats['books'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM reviews WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stats['reviews'] = (int) ($row['total'] ?? 0);
    $stmt->close();
}

$result = $mysqli->query("SELECT COUNT(*) AS total FROM sessions WHERE date_heure >= NOW()");
if ($result) {
    $stats['sessions'] = (int) ($result->fetch_assoc()['total'] ?? 0);
}

if ($userRole === 'admin') {
    $result = $mysqli->query("SELECT COUNT(*) AS total FROM users");
    if ($result) {
        $stats['users'] = (int) ($result->fetch_assoc()['total'] ?? 0);
    }
}

$sqlUpcoming = "
    SELECT s.id, s.titre, s.date_heure, s.lieu, b.titre AS book_title
    FROM sessions s
    JOIN books b ON b.id = s.book_id
    WHERE s.date_heure >= NOW()
    ORDER BY s.date_heure ASC
    LIMIT 5
";
$result = $mysqli->query($sqlUpcoming);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $upcomingSessions[] = $row;
    }
}

$sqlProgress = "
    SELECT
        b.id,
        b.titre,
        b.nb_pages,
        ps.page_actuelle,
        ps.updated_at
    FROM progress_solo ps
    JOIN books b ON b.id = ps.book_id
    WHERE ps.user_id = ?
    ORDER BY ps.updated_at DESC
    LIMIT 5
";
$stmt = $mysqli->prepare($sqlProgress);
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalPercent = 0;
    $countPercent = 0;
    while ($row = $result->fetch_assoc()) {
        $nbPages = (int) ($row['nb_pages'] ?? 0);
        $currentPage = (int) ($row['page_actuelle'] ?? 0);
        $percent = 0;

        if ($nbPages > 0) {
            $percent = (int) round(min(100, ($currentPage / $nbPages) * 100));
            $totalPercent += $percent;
            $countPercent++;
            if ($currentPage >= $nbPages) {
                $progressStats['completed']++;
            }
        }

        $row['percent'] = $percent;
        $readingProgress[] = $row;
    }

    if ($countPercent > 0) {
        $progressStats['average'] = (int) round($totalPercent / $countPercent);
    }

    $stmt->close();
}

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Dashboard</h1>
        <p class="text-muted mt-2">Vue rapide de l'activite du club de lecture.</p>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
        <a href="<?= BASE_URL ?>/books" class="rounded-2xl border border-border bg-white/70 p-5 hover:bg-white transition-colors block">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Livres</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['books'] ?></p>
        </a>
        <a href="<?= BASE_URL ?>/books" class="rounded-2xl border border-border bg-white/70 p-5 hover:bg-white transition-colors block">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Mes avis</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['reviews'] ?></p>
        </a>
        <a href="<?= BASE_URL ?>/sessions" class="rounded-2xl border border-border bg-white/70 p-5 hover:bg-white transition-colors block">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Sessions a venir</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['sessions'] ?></p>
        </a>
        <a href="<?= BASE_URL ?><?= $userRole === 'admin' ? '/members' : '/settings' ?>" class="rounded-2xl border border-border bg-white/70 p-5 hover:bg-white transition-colors block">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Membres</p>
            <p class="text-3xl font-body font-bold text-ink mt-2">
                <?= $userRole === 'admin' ? $stats['users'] : '-' ?>
            </p>
        </a>
    </section>

    <section class="rounded-2xl border border-border bg-white/70 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-body font-semibold text-ink">Progression de lecture</h2>
            <span class="text-xs px-2.5 py-1 rounded-full bg-accent/10 text-accent">
                Moyenne: <?= $progressStats['average'] ?>%
            </span>
        </div>

        <?php if (empty($readingProgress)): ?>
            <p class="text-muted text-sm">Aucune progression enregistree pour le moment.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($readingProgress as $progress): ?>
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="text-sm font-medium text-ink"><?= htmlspecialchars($progress['titre']) ?></p>
                            <p class="text-xs text-muted">
                                <?= (int) $progress['page_actuelle'] ?>/<?= (int) $progress['nb_pages'] ?> pages
                            </p>
                        </div>
                        <div class="w-full h-2 rounded-full bg-stone-200/80 overflow-hidden">
                            <div class="h-full bg-accent rounded-full transition-all" style="width: <?= (int) $progress['percent'] ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-muted mt-4">Livres termines: <?= $progressStats['completed'] ?></p>
        <?php endif; ?>
    </section>

    <section class="rounded-2xl border border-border bg-white/70 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-body font-semibold text-ink">Prochaines sessions</h2>
            <a href="<?= BASE_URL ?>/sessions" class="text-sm text-accent hover:underline">Voir tout</a>
        </div>

        <?php if (empty($upcomingSessions)): ?>
            <p class="text-muted text-sm">Aucune session planifiee pour le moment.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($upcomingSessions as $session): ?>
                    <div class="rounded-xl border border-border/80 bg-cream/60 p-4">
                        <p class="text-sm font-medium text-ink"><?= htmlspecialchars($session['titre']) ?></p>
                        <p class="text-xs text-muted mt-1">
                            Livre: <?= htmlspecialchars($session['book_title']) ?> -
                            <?= (new DateTime($session['date_heure']))->format('d/m/Y H:i') ?>
                            <?php if (!empty($session['lieu'])): ?>
                                - <?= htmlspecialchars($session['lieu']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>
