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

$booksForSession = [];
if (in_array($userRole, ['admin', 'moderateur'], true)) {
    $bq = $mysqli->query(
        "SELECT b.id, b.titre, a.nom AS auteur
         FROM books b
         JOIN authors a ON a.id = b.author_id
         ORDER BY b.titre ASC"
    );
    if ($bq) {
        while ($row = $bq->fetch_assoc()) {
            $booksForSession[] = $row;
        }
    }
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
            <button type="button" onclick="toggleModal('sessionCreateModal', true)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-ink text-cream text-sm font-medium hover:bg-stone-800 transition-colors shadow-sm">
                <i class="ph ph-shield-check text-base"></i>
                Gestionnaire de sessions
            </button>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <?php if (empty($sessions)): ?>
        <div class="rounded-2xl border border-border bg-white/70 p-8 text-center">
            <i class="ph ph-calendar-blank text-5xl text-muted/40"></i>
            <p class="mt-3 text-ink font-medium">Aucune session pour le moment.</p>
            <p class="text-sm text-muted mt-1">Revenez bientot pour les prochaines dates.</p>
            <?php if (in_array($userRole, ['admin', 'moderateur'], true)): ?>
                <p class="text-sm text-muted mt-3">
                    En tant que moderateur, vous pouvez
                    <button type="button" onclick="toggleModal('sessionCreateModal', true)" class="text-accent font-medium hover:underline">
                        creer une session
                    </button>.
                </p>
            <?php endif; ?>
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

<?php if (in_array($userRole, ['admin', 'moderateur'], true)): ?>
<div id="sessionCreateModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    role="dialog" aria-modal="true" aria-labelledby="sessionCreateModalTitle">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div id="sessionCreateModalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('sessionCreateModal', false)"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="sessionCreateModalContent"
            class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:w-full sm:max-w-lg border border-border font-body">

            <div class="px-6 pt-6 pb-4 sm:px-8">
                <div class="flex items-center justify-between">
                    <h3 id="sessionCreateModalTitle" class="text-xl font-body font-bold tracking-tight text-ink">Nouvelle session</h3>
                    <button type="button" onclick="toggleModal('sessionCreateModal', false)"
                        class="text-muted hover:text-ink transition-colors">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
                <p class="text-sm text-muted mt-2">Planifiez une rencontre autour d'un ouvrage du catalogue.</p>
            </div>

            <?php if (empty($booksForSession)): ?>
                <div class="px-6 pb-6 sm:px-8 sm:pb-8">
                    <p class="text-sm text-ink/80">Ajoutez d'abord au moins un livre au catalogue pour creer une session.</p>
                    <div class="mt-4 flex justify-end gap-3">
                        <a href="<?= BASE_URL ?>/books" class="inline-flex items-center px-4 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition">
                            Aller au catalogue
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form action="<?= BASE_URL ?>/sessions" method="POST" class="px-6 pb-6 sm:px-8 sm:pb-8 space-y-4">
                    <input type="hidden" name="action" value="create_session">

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Livre <span class="text-accent">*</span></label>
                        <select name="book_id" required
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
                            <option value="">Choisir un livre</option>
                            <?php foreach ($booksForSession as $b): ?>
                                <option value="<?= (int) $b['id'] ?>">
                                    <?= htmlspecialchars($b['titre']) ?> — <?= htmlspecialchars($b['auteur']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Titre de la session <span class="text-accent">*</span></label>
                        <input type="text" name="titre" required maxlength="255" placeholder="Ex. : Club de mars — deuxieme partie"
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Date et heure <span class="text-accent">*</span></label>
                        <input type="datetime-local" name="date_heure" required
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Lieu</label>
                        <input type="text" name="lieu" maxlength="255" placeholder="Salle, adresse..."
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Lien (visio, etc.)</label>
                        <input type="text" name="lien" maxlength="500" placeholder="https://... ou domaine seul"
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
                        <p class="text-[11px] text-muted">L'URL peut etre incomplete : elle sera completee si besoin.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-ink uppercase tracking-widest">Description</label>
                        <textarea name="description" rows="3" placeholder="Ordre du jour, consignes..."
                            class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition resize-none"></textarea>
                    </div>

                    <div class="pt-2 flex items-center gap-3 justify-end">
                        <button type="button" onclick="toggleModal('sessionCreateModal', false)"
                            class="px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">
                            Annuler
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition-all shadow-sm">
                            Creer la session
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>
