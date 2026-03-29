<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
require_once __DIR__ . '/functions.php';
requireLogin();

$pageTitle = 'Sessions';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = getUserRole();

// Récupération des données via les fonctions du module
$sessions = getSessionsList($mysqli, $userId);
$booksForSession = [];

if (in_array($userRole, ['admin', 'moderateur'], true)) {
    $booksForSession = getBooksForSessions($mysqli);
}

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="w-full py-10 px-4 sm:px-8 lg:px-20">
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Sessions de lecture</h1>
            <p class="text-muted mt-2">Retrouvez les prochaines rencontres du club.</p>
        </div>
        <?php if (in_array($userRole, ['admin', 'moderateur'], true)): ?>
            <button type="button" onclick="toggleModal('sessionCreateModal', true)"
                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle session
            </button>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <?php if (empty($sessions)): ?>
        <div class="rounded-2xl border border-border p-8 text-center">
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
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($sessions as $session): ?>
                <?php
                $sessionProgress = (int) ($session['session_progress'] ?? 0);
                $totalProgressBar = (int) ($session['nb_pages'] ?? 0);
                $dateTs = strtotime($session['date_heure']);
                $isPast = $dateTs < time();
                
                // Calcul du statut étendu
                if ($totalProgressBar > 0 && $sessionProgress >= $totalProgressBar) {
                    $badgeText = 'Terminee';
                    $badgeClass = 'bg-stone-200 text-stone-700';
                } elseif ($isPast) {
                    $badgeText = 'En cours';
                    $badgeClass = 'bg-accent/10 text-accent';
                } else {
                    $badgeText = 'A venir';
                    $badgeClass = 'bg-green-100 text-green-700';
                }

                $statusMap = [
                    'inscrit' => 'Inscrit',
                    'present' => 'Present',
                    'absent' => 'Absent',
                ];
                $myStatus = $session['my_status'] ?? null;
                $isCreator = (int)$session['created_by'] === $userId || $userRole === 'admin';
                ?>
                <article class="w-full min-w-0 rounded-2xl border border-border bg-cream/50 p-3 shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-body font-semibold text-ink"><?= htmlspecialchars($session['titre']) ?></h2>
                            <p class="text-sm text-muted mt-1"><?= htmlspecialchars($session['book_title']) ?></p>
                        </div>
                        <div class="flex items-center gap-1">
                            <?php if ($isCreator): ?>
                                <button type="button" 
                                    class="open-progress-modal text-accent hover:bg-accent/10 p-2 rounded-xl transition-all active:scale-90 flex items-center justify-center group"
                                    data-id="<?= (int) $session['id'] ?>"
                                    data-title="<?= htmlspecialchars($session['titre'], ENT_QUOTES) ?>"
                                    data-current="<?= $sessionProgress ?>"
                                    data-total="<?= $totalProgressBar ?>">
                                    <i class="ph ph-bookmark-simple text-xl group-hover:scale-110 transition-transform"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($userRole === 'admin'): ?>
                                <button type="button" 
                                    class="delete-session-trigger text-red-400 hover:text-red-500 p-2 rounded-xl hover:bg-red-50 transition-all" 
                                    title="Supprimer la session"
                                    data-id="<?= (int) $session['id'] ?>"
                                    data-title="<?= htmlspecialchars($session['titre'], ENT_QUOTES) ?>">
                                    <i class="ph ph-trash text-xl"></i>
                                </button>
                            <?php endif; ?>
                            <div class="ml-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-0.5 rounded-full border border-border text-muted">
                                    <?= $badgeText ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1.5 text-sm text-muted">
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
                        <?php if (!empty($myStatus)): ?>
                            <div class="mt-2.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-accent text-white uppercase tracking-widest shadow-sm">
                                    <i class="ph ph-check-circle mr-1.5 text-xs"></i>
                                    <?= $statusMap[$myStatus] ?? ucfirst($myStatus) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($session['description'])): ?>
                        <p class="mt-3 text-sm text-ink/80 leading-relaxed">
                            <?= nl2br(htmlspecialchars($session['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-3 pt-3 border-t border-border/10 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <p class="text-[11px] text-muted font-medium uppercase tracking-wider">Par <?= htmlspecialchars($session['creator_name']) ?></p>
                            
                            <?php if ($sessionProgress > 0): ?>
                                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-accent/5 border border-accent/10">
                                    <i class="ph ph-flag-pennant text-accent text-xs"></i>
                                    <span class="text-[11px] font-bold text-accent uppercase tracking-wider">
                                        Objectif : Page <?= $sessionProgress ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
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
                                   class="inline-flex items-center gap-1 text-sm text-accent hover:underline font-medium">
                                    <?= !empty(trim((string) ($session['lien'] ?? ''))) ? 'Rejoindre' : 'Voir livre' ?>
                                    <i class="ph ph-arrow-up-right text-sm"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!$isPast): ?>
                                <?php if (empty($myStatus)): ?>
                                    <form action="<?= BASE_URL ?>/sessions" method="POST">
                                        <input type="hidden" name="action" value="join_session">
                                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-ink text-cream text-[11px] font-bold uppercase tracking-wider hover:bg-stone-800 transition shadow-sm active:scale-95">
                                            Participer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?= BASE_URL ?>/sessions" method="POST">
                                        <input type="hidden" name="action" value="leave_session">
                                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-border text-muted text-[11px] font-bold uppercase tracking-wider hover:bg-stone-100 transition active:scale-95">
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
                            min="<?= date('Y-m-d\TH:i') ?>"
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

<!-- Modal Progression Collective (Style Épuré Read) -->
<div id="sessionProgressModal"
    class="fixed inset-0 z-[100] opacity-0 pointer-events-none transition-all duration-300 ease-out"
    role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div id="sessionProgressModalOverlay" class="absolute inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('sessionProgressModal', false)"></div>
        
        <div id="sessionProgressModalContent"
            class="relative bg-cream w-full max-w-md mx-auto rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out border border-border font-body">
            
            <div class="px-6 pt-6 pb-2 sm:px-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold tracking-tight text-ink">Point de lecture</h3>
                    <button type="button" onclick="toggleModal('sessionProgressModal', false)" class="text-muted hover:text-ink transition-colors">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
                <p class="text-[11px] text-muted uppercase tracking-widest font-bold mt-1">Session : <span id="progressSessionTitle" class="text-accent"></span></p>
            </div>

            <form action="<?= BASE_URL ?>/sessions" method="POST" class="px-6 pb-6 sm:px-8 sm:pb-8 space-y-6 mt-4">
                <input type="hidden" name="action" value="update_session_progression">
                <input type="hidden" name="session_id" id="progressSessionId">
                
                <div class="space-y-2">
                    <label for="progressPageInput" class="block text-xs font-medium text-ink uppercase tracking-widest">Page actuelle du club <span class="text-accent">*</span></label>
                    <div class="relative">
                        <input type="number" name="page_actuelle" id="progressPageInput" required min="0"
                            class="w-full px-4 py-3 bg-white border border-border rounded-xl text-sm text-ink font-bold focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition shadow-sm"
                            placeholder="Ex: 42">
                        <div id="progressTotalDisplay" class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-muted font-bold uppercase tracking-widest bg-stone-50 px-2 py-1 rounded border border-border/40">
                            sur ...
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3 justify-end">
                    <button type="button" onclick="toggleModal('sessionProgressModal', false)"
                        class="px-5 py-2.5 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">Annuler</button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-sm">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($userRole === 'admin'): ?>
<!-- Modal Confirmation Suppression Session -->
<div id="deleteSessionModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div id="deleteSessionModalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('deleteSessionModal', false)"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="deleteSessionModalContent"
            class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-border font-body">
            <div class="px-6 py-6 sm:px-8">
                <div class="text-center">
                    <h3 class="text-xl font-body font-bold text-ink mb-2">Supprimer la session</h3>
                    <p class="text-sm text-muted mb-6">
                        Êtes-vous sûr de vouloir supprimer la session <span id="deleteSessionTitle" class="font-bold text-ink"></span> ? Les inscriptions seront également supprimées.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="hidden" id="deleteSessionIdInput">
                    <button type="button" onclick="toggleModal('deleteSessionModal', false)"
                        class="flex-1 px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">Annuler</button>
                    <button type="button" id="confirmDeleteSessionBtn"
                        class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 active:scale-[0.98] transition-all shadow-sm">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Gestion de la modale de progression
    document.querySelectorAll('.open-progress-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const current = this.dataset.current;
            const total = this.dataset.total;
            
            document.getElementById('progressSessionId').value = id;
            document.getElementById('progressSessionTitle').textContent = title;
            document.getElementById('progressPageInput').value = current;
            if (total > 0) {
                document.getElementById('progressPageInput').max = total;
                document.getElementById('progressTotalDisplay').textContent = "Sur un total de " + total + " pages";
            }
            
            toggleModal('sessionProgressModal', true);
        });
    });

    // Gestion de l'ouverture du modal de suppression
    document.querySelectorAll('.delete-session-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            document.getElementById('deleteSessionIdInput').value = id;
            document.getElementById('deleteSessionTitle').textContent = title;
            toggleModal('deleteSessionModal', true);
        });
    });

    // Confirmation de suppression
    document.getElementById('confirmDeleteSessionBtn')?.addEventListener('click', function() {
        const sessionId = document.getElementById('deleteSessionIdInput').value;
        if (!sessionId) return;

        fetch('<?= BASE_URL ?>/sessions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete_session&session_id=' + encodeURIComponent(sessionId)
        })
        .then(r => r.text())
        .then(() => {
            toggleModal('deleteSessionModal', false);
            window.location.reload();
        })
        .catch(() => {
            alert("Erreur lors de la suppression de la session.");
            toggleModal('deleteSessionModal', false);
        });
    });
});
</script>
<?php endif; ?>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>
