<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
/**
 * Lecteur de livre intégré - Version avec Suivi de Progression (Style Marque-page Épuré)
 */
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stream = (int)($_GET['stream'] ?? 0);

$doc = getDocumentById($mysqli, $id);

if (!$doc) {
    die("Erreur : Document introuvable.");
}

// Mode Flux (Stream) : Envoie le fichier au navigateur
if ($stream === 1) {
    $filePath = __DIR__ . '/../../' . $doc['filepath'];
    
    if (file_exists($filePath)) {
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: ' . $doc['mime']);
        header('Content-Disposition: inline; filename="' . basename($doc['filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit();
    }
    die("Erreur : Fichier physique introuvable.");
}

// Mode UI
$pageTitle = "Lecture : " . htmlspecialchars($doc['book_title']);
$containerClass = 'max-w-full px-4 sm:px-8 lg:px-12'; 
$hideNav = true;
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="flex flex-col flex-1 min-h-0 bg-cream">

    <!-- En-tête épuré Full Width (Sans Navbar principale) -->
    <div class="flex items-center justify-between border-b border-border/20 px-6 py-3 shrink-0 bg-white/50 backdrop-blur-md">
        <div class="flex items-center gap-6 overflow-hidden">
            <a href="<?= BASE_URL ?>/books/<?= $doc['book_id'] ?>"
               class="p-2 text-muted hover:text-ink transition-all flex items-center justify-center shrink-0"
               title="Retour au livre">
                <i class="ph ph-arrow-left text-2xl"></i>
            </a>
            <div class="truncate">
                <h1 class="text-lg font-body font-bold text-ink truncate leading-tight">
                    <?= htmlspecialchars($doc['book_title']) ?>
                </h1>
                <p class="text-xs text-muted uppercase tracking-[0.2em] font-medium opacity-80"><?= htmlspecialchars($doc['author_name']) ?></p>
            </div>
        </div>
        
        <div class="flex items-center gap-4 shrink-0">
            <!-- Icone Bookmark -> Ouvre la modale de progression (Style harmonieux) -->
            <button onclick="toggleModal('progressModal', true)" 
                    class="p-2 text-muted hover:text-accent transition-all shrink-0"
                    title="Marquer ma progression">
                <i class="ph ph-bookmark-simple text-2xl"></i>
            </button>

            <a href="<?= BASE_URL ?>/download?id=<?= $id ?>"
               class="p-2 text-muted hover:text-ink transition-all shrink-0"
               title="Télécharger l'ouvrage">
                <i class="ph ph-download-simple text-2xl"></i>
            </a>
        </div>
    </div>

    <!-- Zone du lecteur PDF plein écran -->
    <div class="flex-1 relative bg-stone-100 min-h-0">
        <iframe
            src="<?= BASE_URL ?>/read?id=<?= $id ?>&stream=1"
            class="w-full h-full border-none block"
            title="Lecteur PDF">
            Votre navigateur ne supporte pas l'affichage des PDF.
        </iframe>
    </div>

</div>

<!-- MODALE DE PROGRESSION (Harmonie visuelle avec addReviewModal) -->
<div id="progressModal" class="fixed inset-0 z-[100] opacity-0 pointer-events-none transition-opacity duration-300 ease-out">
    <div id="progressModalOverlay" class="absolute inset-0 bg-stone-900/0 transition-all duration-300" onclick="toggleModal('progressModal', false)"></div>
    
    <div class="flex items-center justify-center min-h-screen px-4">
        <div id="progressModalContent" class="relative bg-cream w-full max-w-lg mx-auto rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 ease-out border border-border">
            
            <div class="px-6 pt-6 pb-2 sm:px-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold tracking-tight text-ink">Où en êtes-vous ?</h3>
                    <button onclick="toggleModal('progressModal', false)" class="text-muted hover:text-ink transition-colors">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/books" method="POST" class="px-6 pb-6 sm:px-8 sm:pb-8 space-y-5">
                <input type="hidden" name="action" value="save_progress">
                <input type="hidden" name="book_id" value="<?= $doc['book_id'] ?>">

                <div class="space-y-2">
                    <label for="page_actuelle" class="block text-xs font-medium text-ink uppercase tracking-widest">Page actuelle <span class="text-accent">*</span></label>
                    <div class="relative">
                        <input type="number" name="page_actuelle" id="page_actuelle" 
                               min="1" max="<?= $doc['nb_pages'] ?>" required
                               class="w-full px-4 py-3 bg-white border border-border rounded-xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition font-bold"
                               placeholder="Ex: 42">
                        <?php if($doc['nb_pages'] > 0): ?>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-muted font-bold uppercase tracking-widest bg-stone-50 px-2 py-1 rounded border border-border/40">
                                sur <?= $doc['nb_pages'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <button type="button" onclick="toggleModal('progressModal', false)" 
                            class="px-5 py-2.5 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition-all shadow-sm active:scale-[0.98]">
                        Enregistrer & Quitter
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
