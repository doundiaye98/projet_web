<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");

require_once __DIR__ . '/../../includes/auth/session.php';
require_once __DIR__ . '/functions.php';
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
$stats = getModerationStats($mysqli);
$hiddenReviews = getHiddenReviews($mysqli, 8);

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="w-full py-10 px-4 sm:px-8 lg:px-20">
    <div class="mb-8">
        <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Modération</h1>
        <p class="text-muted mt-2">Espace de suivi des contenus à modérer et de la sécurité de la communauté.</p>
    </div>

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-10">
        <article class="rounded-2xl border border-border p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Avis en attente</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['hidden_reviews'] ?></p>
        </article>
        <article class="rounded-2xl border border-border p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Livres concernes</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['reported_books'] ?></p>
        </article>
        <article class="rounded-2xl border border-border p-5">
            <p class="text-xs uppercase tracking-[0.15em] text-muted">Moderateurs actifs</p>
            <p class="text-3xl font-body font-bold text-ink mt-2"><?= $stats['active_moderators'] ?></p>
        </article>
    </section>

    <section class="rounded-2xl border border-border p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-body font-semibold text-ink">Avis en attente</h2>
        </div>

        <?php if (empty($hiddenReviews)): ?>
            <div class="py-12 text-center">
                <p class="text-ink font-medium">Aucun avis masque actuellement.</p>
                <p class="text-sm text-muted mt-1">La file de moderation est vide.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($hiddenReviews as $review): ?>
                    <?php 
                    // On capture les boutons spécifiques à la modération
                    ob_start(); ?>
                    <button type="button"
                        class="accept-review-btn text-green-600 hover:text-green-800 transition p-1.5 rounded-full hover:bg-green-100"
                        title="Accepter l'avis" data-review-id="<?= $review['id'] ?>">
                        <i class="ph ph-check-circle text-xl"></i>
                    </button>
                    <button type="button" class="delete-trigger text-red-600 hover:text-red-800 transition p-1.5 rounded-full hover:bg-red-100"
                        title="Supprimer l'avis" data-id="<?= $review['id'] ?>"
                        data-title="<?= htmlspecialchars($review['book_title'], ENT_QUOTES) ?>">
                        <i class="ph ph-trash text-xl"></i>
                    </button>
                    <?php 
                    $revActions = ob_get_clean();
                    
                    // On prépare les données pour le partial
                    $rev = $review; 
                    include __DIR__ . '/../../includes/layout/partials/_comment_card.php'; 
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<!-- Modal Confirmation Suppression (Style identique à Membres) -->
<div id="deleteConfirmModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div id="deleteModalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('deleteConfirmModal', false)"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="deleteModalContent"
            class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-border font-body">
            <div class="px-6 py-6 sm:px-8">
                <div class="text-center">
                    <h3 class="text-xl font-body font-bold text-ink mb-2">Confirmer la suppression</h3>
                    <p class="text-sm text-muted mb-6">
                        Êtes-vous sûr de vouloir supprimer définitivement l'avis pour <span id="deleteBookTitle"
                            class="font-bold text-ink"></span> ? Cette action est irréversible.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="hidden" id="deleteReviewId">
                    <button type="button" onclick="toggleModal('deleteConfirmModal', false)"
                        class="flex-1 px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">Annuler</button>
                    <button type="button" id="confirmDeleteBtn"
                        class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 active:scale-[0.98] transition-all shadow-sm">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentReviewToDelete = null;

    function openDeleteModal(id, bookTitle) {
        currentReviewToDelete = id;
        document.getElementById('deleteReviewId').value = id;
        document.getElementById('deleteBookTitle').textContent = bookTitle;
        toggleModal('deleteConfirmModal', true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Bouton de validation (Acceptation)
        document.querySelectorAll('.accept-review-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const reviewId = this.dataset.reviewId;
                if (!reviewId) return;
                fetch('<?= BASE_URL ?>/moderation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=accept_review&review_id=' + encodeURIComponent(reviewId)
                })
                    .then(r => r.text())
                    .then(() => window.location.reload())
                    .catch(() => alert("Erreur lors de la validation de l'avis."));
            });
        });

        // Boutons de suppression (Ouverture modal)
        document.querySelectorAll('.delete-trigger').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const title = this.dataset.title;
                openDeleteModal(id, title);
            });
        });

        // Confirmation dans le modal (Suppression)
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', function () {
            const reviewId = document.getElementById('deleteReviewId').value;
            if (!reviewId) return;

            fetch('<?= BASE_URL ?>/moderation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete_review&review_id=' + encodeURIComponent(reviewId)
            })
                .then(r => r.text())
                .then(() => {
                    toggleModal('deleteConfirmModal', false);
                    window.location.reload();
                })
                .catch(() => {
                    alert("Erreur lors de la suppression de l'avis.");
                    toggleModal('deleteConfirmModal', false);
                });
        });
    });
</script>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>