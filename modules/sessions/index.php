<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

$pageTitle = 'Sessions';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = getUserRole();
$sessions = [];

/** Rend une URL de réunion / visioconf clickable (ajoute https si le schéma est omis). */
function normalize_session_join_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    $lower = strtolower($url);
    if (strpos($lower, 'mailto:') === 0 || strpos($lower, 'tel:') === 0) {
        return $url;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    if (isset($url[0]) && $url[0] === '/') {
        return $url;
    }
    return 'https://' . $url;
}

$sql = "
    SELECT
        s.id,
        s.book_id,
        s.titre,
        s.date_heure,
        s.lieu,
        s.lien,
        s.description,
        b.titre AS book_title,
        u.nom AS creator_name,
        SUM(CASE WHEN sa.statut IN ('inscrit', 'present') THEN 1 ELSE 0 END) AS participants_count,
        MAX(CASE WHEN sa.user_id = ? THEN sa.statut ELSE NULL END) AS my_status
    FROM sessions s
    JOIN books b ON b.id = s.book_id
    JOIN users u ON u.id = s.created_by
    LEFT JOIN session_attendance sa ON sa.session_id = s.id
    GROUP BY s.id, s.book_id, s.titre, s.date_heure, s.lieu, s.lien, s.description, b.titre, u.nom
    ORDER BY s.date_heure ASC
";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    $stmt->close();
}

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Sessions de lecture</h1>
            <p class="text-muted mt-2">Retrouvez les prochaines rencontres du club.</p>
        </div>
        <?php if (in_array($userRole, ['admin', 'moderateur'], true)): ?>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-ink text-cream text-sm">
                <i class="ph ph-shield-check text-base"></i>
                Gestionnaire de sessions
            </span>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <?php if (empty($sessions)): ?>
        <div class="rounded-2xl border border-border bg-white/70 p-8 text-center">
            <i class="ph ph-calendar-blank text-5xl text-muted/40"></i>
            <p class="mt-3 text-ink font-medium">Aucune session pour le moment.</p>
            <p class="text-sm text-muted mt-1">Revenez bientot pour les prochaines dates.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <?php foreach ($sessions as $session): ?>
                <?php
                $isPast = strtotime($session['date_heure']) < time();
                $statusMap = [
                    'inscrit' => 'Inscrit',
                    'present' => 'Present',
                    'absent' => 'Absent',
                ];
                $myStatus = $session['my_status'] ?? null;
                ?>
                <article class="rounded-2xl border border-border bg-white/80 p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-body font-semibold text-ink"><?= htmlspecialchars($session['titre']) ?></h2>
                            <p class="text-sm text-muted mt-1"><?= htmlspecialchars($session['book_title']) ?></p>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full <?= $isPast ? 'bg-stone-200 text-stone-700' : 'bg-green-100 text-green-700' ?>">
                            <?= $isPast ? 'Terminee' : 'A venir' ?>
                        </span>
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-muted">
                        <p class="flex items-center gap-2">
                            <i class="ph ph-clock text-base"></i>
                            <?= (new DateTime($session['date_heure']))->format('d/m/Y H:i') ?>
                        </p>
                        <?php if (!empty($session['lieu'])): ?>
                            <p class="flex items-center gap-2">
                                <i class="ph ph-map-pin text-base"></i>
                                <?= htmlspecialchars($session['lieu']) ?>
                            </p>
                        <?php endif; ?>
                        <p class="flex items-center gap-2">
                            <i class="ph ph-users text-base"></i>
                            <?= (int) $session['participants_count'] ?> participant(s)
                        </p>
                    </div>

                    <?php if (!empty($session['description'])): ?>
                        <p class="mt-4 text-sm text-ink/80 leading-relaxed">
                            <?= nl2br(htmlspecialchars($session['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <p class="text-xs text-muted">Cree par <?= htmlspecialchars($session['creator_name']) ?></p>
                        <div class="flex items-center gap-2">
                            <?php if (!empty($myStatus)): ?>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-accent/10 text-accent">
                                    Mon statut: <?= $statusMap[$myStatus] ?? ucfirst($myStatus) ?>
                                </span>
                            <?php endif; ?>
                            <?php
                            $joinHref = normalize_session_join_url($session['lien'] ?? '');
                            $bookIdForFallback = (int) ($session['book_id'] ?? 0);
                            if ($joinHref === '' && $bookIdForFallback > 0) {
                                $joinHref = BASE_URL . '/books/' . $bookIdForFallback;
                            }
                            ?>
                            <?php if ($joinHref !== ''): ?>
                                <?php
                                $openNewTab = false;
                                if (preg_match('#^https?://#i', $joinHref)) {
                                    $here = strtolower($_SERVER['HTTP_HOST'] ?? '');
                                    $openNewTab = $here === '' || stripos($joinHref, $here) === false;
                                }
                                ?>
                                <a href="<?= htmlspecialchars($joinHref, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $openNewTab ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                                   class="inline-flex items-center gap-1 text-sm text-accent hover:underline">
                                    <?= !empty(trim((string) ($session['lien'] ?? ''))) ? 'Rejoindre' : 'Voir le livre' ?>
                                    <i class="ph ph-arrow-up-right text-sm"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!$isPast): ?>
                                <?php if (empty($myStatus)): ?>
                                    <form action="<?= BASE_URL ?>/sessions" method="POST">
                                        <input type="hidden" name="action" value="join_session">
                                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-ink text-cream text-xs hover:bg-stone-800 transition">
                                            Participer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?= BASE_URL ?>/sessions" method="POST">
                                        <input type="hidden" name="action" value="leave_session">
                                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-border text-muted text-xs hover:bg-stone-100 transition">
                                            Quitter
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>
