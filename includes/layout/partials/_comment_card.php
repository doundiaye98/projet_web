<?php
/**
 * Partial : Carte d'avis lecteur (Commentaire)
 * @var array $rev Données de l'avis (note, commentaire, created_at, auteur_critique)
 */
require_once __DIR__ . '/../../helpers.php';

$revAuthor = $rev['auteur_critique'] ?? 'Anonyme';
$revInitials = getUserInitials($revAuthor);
$revDate = formatDateFR($rev['created_at']);
$revRating = $rev['note'] ?? 0;
$revComment = $rev['commentaire'] ?? '';
?>

<div class="group p-6 border border-border/40 rounded-[2rem] flex flex-col gap-4 transition-all duration-300 hover:border-accent/40 hover:bg-accent/5 mb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <!-- Avatar / Initiales -->
            <div class="w-10 h-10 shrink-0 rounded-full bg-stone-200/50 flex items-center justify-center text-ink/40 text-[10px] font-bold uppercase tracking-widest group-hover:bg-accent group-hover:text-cream transition-all duration-500">
                <?= $revInitials ?>
            </div>
            <div>
                <p class="text-sm font-bold text-ink">
                    <?= htmlspecialchars($revAuthor) ?>
                </p>
                <p class="text-[10px] text-muted font-bold uppercase tracking-[0.1em] mt-0.5">
                    <?= $revDate ?>
                </p>
            </div>
        </div>

        <!-- Actions / Edit -->
        <div class="flex items-center gap-2">
            <!-- Rating Stars -->
            <div class="flex items-center gap-1">
                <?php 
                $rating = $revRating;
                $size = 'text-sm';
                include __DIR__ . '/_stars.php'; 
                ?>
            </div>

            <?php if (isUserLoggedIn() && $rev['user_id'] == $_SESSION['user_id']): ?>
                <button onclick="openEditReviewModal(<?= $revRating ?>, '<?= addslashes(str_replace(["\r", "\n"], ' ', $revComment)) ?>')" 
                        class="text-muted hover:text-accent transition-colors p-2 rounded-full hover:bg-accent/10"
                        title="Modifier mon avis">
                    <i class="ph ph-pencil-simple-line text-lg"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($revComment)): ?>
        <div class="pl-14">
            <p class="text-sm text-ink/80 leading-relaxed font-light italic">
                "<?= nl2br(htmlspecialchars($revComment)) ?>"
            </p>
        </div>
    <?php endif; ?>
</div>
