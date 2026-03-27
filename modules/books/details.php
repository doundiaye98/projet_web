<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
/**
 * Vue détaillée d'un livre
 * Accessible via /books/[id]
 */
require_once __DIR__ . '/functions.php';

$book = getBookDetails($mysqli, $bookId);
$userProgress = getUserProgress($mysqli, $_SESSION['user_id'], $bookId);

if (!$book) {
    http_response_code(404);
    $errorCode = 404;
    include __DIR__ . '/../../includes/layout/error_page.php';
    exit;
}

$pageTitle = $book['titre'] . ' - Book Club';
$containerClass = 'max-w-6xl';
include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-6xl mx-auto py-12 px-4 sm:px-6 lg:px-8">

    <!-- Fil d'ariane / Retour -->
    <nav class="mb-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border/40 pb-6">
        <div class="flex items-center gap-6">
            <a href="<?= BASE_URL ?>/books"
                class="group inline-flex items-center text-sm font-medium text-muted hover:text-ink transition-colors">
                <i class="ph ph-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Retour
            </a>
            <?php if (!empty($book['main_pdf'])): ?>
                <a href="<?= BASE_URL ?>/download?id=<?= $book['main_pdf_id'] ?>" 
                    class="inline-flex items-center text-sm font-medium text-accent hover:text-accent/80 transition-colors">
                    <i class="ph ph-download-simple mr-2"></i>
                    Télécharger le PDF
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($book['main_pdf_id'])): ?>
            <a href="<?= BASE_URL ?>/read?id=<?= $book['main_pdf_id'] ?>"
                class="inline-flex items-center justify-center px-8 py-2.5 bg-ink text-cream text-xs font-bold uppercase tracking-widest rounded-full hover:bg-stone-800 transition-all active:scale-95 shadow-lg shadow-ink/20">
                Lire l'ouvrage
            </a>
        <?php else: ?>
            <button disabled class="inline-flex items-center justify-center px-8 py-2.5 bg-stone-300 text-stone-500 text-xs font-bold uppercase tracking-widest rounded-full cursor-not-allowed">
                Lire l'ouvrage
            </button>
        <?php endif; ?>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">

        <!-- Colonne Gauche : Cover + Stats -->
        <div class="lg:col-span-4 space-y-6">
            <div class="aspect-[2/3] rounded-3xl overflow-hidden bg-stone-200 shadow-2xl border border-white/20">
                <?php if ($book['cover_path']): ?>
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($book['cover_path']) ?>"
                        alt="<?= htmlspecialchars($book['titre']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div
                        class="w-full h-full flex items-center justify-center bg-gradient-to-br from-stone-200 to-stone-300">
                        <i class="ph ph-book text-8xl text-stone-400"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- New Rating Display below cover -->
            <div class="flex flex-col items-center justify-center py-2">
                <?php
                $rating = $book['avg_rating'] ?: 0;
                $size = 'text-2xl';
                include __DIR__ . '/../../includes/layout/partials/_stars.php';
                ?>
                <p class="text-[10px] text-muted uppercase tracking-[0.2em] mt-2 font-bold">
                    <?= $book['reviews_count'] > 0 ? $book['reviews_count'] . ' avis lecteurs' : 'Aucun avis pour le moment' ?>
                </p>
            </div>

            <!-- Ma Progression -->
            <?php if ($book['nb_pages'] > 0): ?>
                <?php
                $current = $userProgress;
                $total = $book['nb_pages'];
                include __DIR__ . '/../../includes/layout/partials/_progress_bar.php';
                ?>
            <?php endif; ?>

            <!-- Détails techniques -->
            <div class="space-y-4 px-2 pt-4 border-t border-border/20">
                <div class="flex justify-between items-center py-2 border-b border-border/40">
                    <span class="text-xs text-muted uppercase tracking-wider">Genre</span>
                    <span
                        class="text-xs font-bold text-ink bg-accent/5 px-3 py-1 rounded-full text-accent"><?= htmlspecialchars($book['genre'] ?? 'Non spécifié') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-border/40">
                    <span class="text-xs text-muted uppercase tracking-wider">Pages</span>
                    <span
                        class="text-sm font-medium text-ink"><?= $book['nb_pages'] > 0 ? $book['nb_pages'] . ' pages' : 'Inconnu' ?></span>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Infos + Description -->
        <div class="lg:col-span-8 space-y-12">
            <header class="space-y-4">
                <h1 class="text-5xl md:text-6xl font-display font-bold text-ink leading-tight">
                    <?= htmlspecialchars($book['titre']) ?>
                </h1>
                <p class="text-xl md:text-2xl text-muted font-light italic">
                    par <span class="text-ink font-medium not-italic"><?= htmlspecialchars($book['auteur']) ?></span>
                </p>
            </header>

            <div class="max-w-none">
                <h3 class="block text-[10px] font-bold text-ink/50 uppercase tracking-[0.3em] mb-1 leading-none italic opacity-80">À propos de l'œuvre</h3>
                <div class="text-lg text-ink/90 leading-relaxed font-light whitespace-pre-line mt-[-2px]">
                    <?= !empty($book['description']) ? trim(htmlspecialchars($book['description'])) : 'Aucune description disponible pour ce livre.' ?>
                </div>
            </div>

            <!-- Section Ressources & Avis détaillés (Tabs) -->
            <div class="pt-8">
                <!-- Tab Headers -->
                <div class="relative border-b border-border/40 flex items-center justify-between">
                    <div class="flex items-center gap-12">
                        <button onclick="switchTab('resources')" id="tab-resources"
                            class="pb-4 text-[10px] font-bold uppercase tracking-[0.3em] text-accent transition-colors relative z-10">
                            Ressources
                        </button>
                        <button onclick="switchTab('reviews')" id="tab-reviews"
                            class="pb-4 text-[10px] font-bold uppercase tracking-[0.3em] text-muted hover:text-ink transition-colors relative z-10">
                            Commentaires
                        </button>
                    </div>

                    <!-- Add Comment Button (Placeholder) -->
                    <div id="add-comment-btn" class="mb-4 hidden">
                        <button onclick="openAddReviewModal()" class="w-8 h-8 rounded-full bg-accent/5 text-accent hover:bg-accent hover:text-cream flex items-center justify-center transition-all active:scale-90" title="Laisser un avis">
                            <i class="ph ph-plus text-base font-bold"></i>
                        </button>
                    </div>

                    <!-- Sliding Underline -->
                    <div id="tab-underline"
                        class="absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-in-out"
                        style="width: 80px;"></div>
                </div>

                <!-- Tab Content -->
                <div class="py-8">
                    <!-- Ressources Tab -->
                    <div id="content-resources" class="tab-content transition-all duration-300 max-w-3xl mx-auto">
                        <?php $resources = getBookResources($mysqli, $bookId); ?>
                        <?php if (empty($resources)): ?>
                            <div class="py-10 text-center border-2 border-dashed border-border/30 rounded-3xl">
                                <p class="text-sm text-muted">Aucune ressource complémentaire pour ce livre.</p>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col">
                                <?php foreach ($resources as $res): ?>
                                    <?php include __DIR__ . '/../../includes/layout/partials/_resource_card.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="content-reviews" class="tab-content hidden opacity-0 transition-all duration-300 max-w-3xl mx-auto">
                        <?php $reviews = getBookReviewsList($mysqli, $bookId); ?>
                        <?php if (empty($reviews)): ?>
                            <div class="py-10 text-center border-2 border-dashed border-border/30 rounded-3xl max-w-2xl mx-auto">
                                <p class="text-sm text-muted italic">Aucun commentaire pour le moment. Soyez le premier à donner votre avis !</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4 max-w-2xl mx-auto">
                                <?php foreach ($reviews as $rev): ?>
                                    <?php include __DIR__ . '/../../includes/layout/partials/_comment_card.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    function switchTab(tab) {
        const resourcesTab = document.getElementById('tab-resources');
        const reviewsTab = document.getElementById('tab-reviews');
        const resourcesContent = document.getElementById('content-resources');
        const reviewsContent = document.getElementById('content-reviews');
        const underline = document.getElementById('tab-underline');
        const addCommentBtn = document.getElementById('add-comment-btn');
 
        if (!resourcesContent || !reviewsContent) return;
 
        if (tab === 'resources') {
            // Menu
            resourcesTab.classList.replace('text-muted', 'text-accent');
            reviewsTab.classList.replace('text-accent', 'text-muted');
 
            // Button
            if (addCommentBtn) addCommentBtn.classList.add('hidden');
 
            // Underline
            underline.style.left = '0px';
            underline.style.width = resourcesTab.offsetWidth + 'px';
 
            // Content
            reviewsContent.classList.add('hidden', 'opacity-0');
            resourcesContent.classList.remove('hidden');
            setTimeout(() => resourcesContent.classList.remove('opacity-0'), 10);
        } else {
            // Menu
            reviewsTab.classList.replace('text-muted', 'text-accent');
            resourcesTab.classList.replace('text-accent', 'text-muted');
 
            // Button
            if (addCommentBtn) addCommentBtn.classList.remove('hidden');
 
            // Underline
            const offset = reviewsTab.offsetLeft;
            underline.style.left = offset + 'px';
            underline.style.width = reviewsTab.offsetWidth + 'px';
 
            // Content
            resourcesContent.classList.add('hidden', 'opacity-0');
            reviewsContent.classList.remove('hidden');
            setTimeout(() => reviewsContent.classList.remove('opacity-0'), 10);
        }
    }

    // Initialiser l'underline au chargement
    document.addEventListener('DOMContentLoaded', () => {
        const activeTab = document.getElementById('tab-resources');
        const underline = document.getElementById('tab-underline');
        if (activeTab && underline) {
            underline.style.width = activeTab.offsetWidth + 'px';
        }

        // Si on vient d'un envoi de commentaire (hash #commentaires)
        if (window.location.hash === '#commentaires') {
            switchTab('reviews');
        }
    });

    // Modals
    function openAddReviewModal(note = 5, comment = '') {
        const modal = document.getElementById('addReviewModal');
        const title = document.getElementById('addReviewModalTitle');
        const noteInput = modal.querySelector('input[name="note"]');
        const commentInput = modal.querySelector('textarea[name="commentaire"]');

        if (title) title.textContent = comment ? 'Modifier mon avis' : 'Donner mon avis';
        if (noteInput) noteInput.value = note;
        if (commentInput) commentInput.value = comment;

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.modal-container').classList.replace('scale-95', 'scale-100');
            modal.querySelector('.modal-container').classList.replace('opacity-0', 'opacity-100');
            modal.classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function openEditReviewModal(note, comment) {
        openAddReviewModal(note, comment);
    }

    function closeAddReviewModal() {
        const modal = document.getElementById('addReviewModal');
        modal.querySelector('.modal-container').classList.replace('scale-100', 'scale-95');
        modal.querySelector('.modal-container').classList.replace('opacity-100', 'opacity-0');
        modal.classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
</script>

<!-- Modal Ajouter un Avis -->
<div id="addReviewModal" class="fixed inset-0 z-[100] hidden opacity-0 transition-opacity duration-300 ease-out">
    <div class="absolute inset-0 bg-stone-900/40" onclick="closeAddReviewModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="modal-container inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:w-full sm:max-w-xl border border-border">
            
            <div class="px-6 pt-6 pb-4 sm:px-8">
                <div class="flex items-center justify-between">
                    <h3 id="addReviewModalTitle" class="text-xl font-bold tracking-tight text-ink">Donner mon avis</h3>
                    <button onclick="closeAddReviewModal()" class="text-muted hover:text-ink transition-colors">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/books" method="POST" class="px-6 pb-6 sm:px-8 sm:pb-8 space-y-5">
                <input type="hidden" name="action" value="add_review">
                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                
                <!-- Note Selection -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-ink uppercase tracking-widest">Note <span class="text-accent">*</span></label>
                    <input type="number" name="note" min="0" max="5" step="1" value="5" required
                           class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition font-bold"
                           placeholder="0 à 5">
                    <p class="text-[10px] text-muted italic">Note entière de 0 à 5</p>
                </div>

                <!-- Commentaire -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-ink uppercase tracking-widest">Commentaire</label>
                    <textarea name="commentaire" 
                              maxlength="2000"
                              placeholder="Qu'avez-vous pensé de cet ouvrage ?"
                              class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink placeholder-stone-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition min-h-[140px] resize-none"></textarea>
                    <div class="flex justify-end pr-1">
                        <span class="text-[9px] text-muted font-bold uppercase tracking-widest">Max 2000 caractères</span>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeAddReviewModal()" 
                            class="px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition-all shadow-sm active:scale-95">
                        Envoyer l'avis
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php';
?>