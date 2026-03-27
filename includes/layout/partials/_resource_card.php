<?php
/**
 * Partial : Carte de ressource individuelle (document complémentaire)
 * @var array $res Données de la ressource (filename, filepath, size, type)
 */
$resFilename = $res['filename'] ?? 'Fichier';
$resPath = $res['filepath'] ?? '#';
$resSize = isset($res['size']) ? round($res['size'] / 1024 / 1024, 2) . ' Mo' : 'Taille inconnue';
$resExt = pathinfo($resFilename, PATHINFO_EXTENSION);

// Icône selon extension
$icon = 'ph-file';
if (in_array($resExt, ['pdf'])) $icon = 'ph-file-pdf';
if (in_array($resExt, ['jpg', 'jpeg', 'png', 'webp'])) $icon = 'ph-image';
if (in_array($resExt, ['zip', 'rar'])) $icon = 'ph-archive';
?>

<div class="group p-5 border border-border/40 rounded-[2rem] flex items-center justify-between transition-all duration-300 hover:border-accent/40 hover:bg-accent/5 mb-4 h-24">
    <div class="flex items-center gap-6 flex-1 min-w-0">
        <div class="w-14 h-14 shrink-0 rounded-2xl bg-stone-200/50 flex items-center justify-center text-ink/40 group-hover:bg-accent group-hover:text-cream transition-all duration-500">
            <i class="ph <?= $icon ?> text-3xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-lg font-bold text-ink group-hover:text-accent transition-colors truncate" title="<?= htmlspecialchars($resFilename) ?>">
                <?= htmlspecialchars($resFilename) ?>
            </p>
            <div class="flex items-center gap-3 mt-1.5">
                <span class="text-[10px] text-accent/60 font-bold uppercase tracking-[0.2em] bg-accent/5 px-2 py-0.5 rounded-md"><?= $resExt ?></span>
                <span class="w-1 h-1 rounded-full bg-border/60"></span>
                <span class="text-[10px] text-muted font-bold uppercase tracking-wider"><?= $resSize ?></span>
            </div>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <a href="<?= BASE_URL ?>/download?id=<?= $res['id'] ?>" 
           class="w-12 h-12 flex items-center justify-center rounded-2xl text-muted hover:text-accent hover:bg-accent/5 transition-all active:scale-90"
           title="Télécharger">
            <i class="ph ph-download-simple text-2xl"></i>
        </a>
    </div>
</div>
