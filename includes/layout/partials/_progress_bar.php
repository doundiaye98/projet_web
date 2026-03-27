<?php
/**
 * Partial : Barre de progression de lecture
 * Reçoit : $current (page actuelle) et $total (nombre de pages)
 */
$current = (int)($current ?? 0);
$total = (int)($total ?? 0);

// On n'affiche rien si la progression n'a pas commencé
if ($current <= 0 || $total <= 0) return;

$percentage = round(($current / $total) * 100);
$percentage = min(100, $percentage);
?>

<div class="w-full space-y-2 py-4 border-t border-border/10">
    <div class="flex justify-between items-end">
        <div class="space-y-0.5">
            <span class="block text-[10px] font-bold uppercase tracking-[0.2em] text-muted">Ma progression</span>
            <span class="block text-xs font-bold text-ink">Page <?= $current ?> <span class="text-muted/60 font-medium">sur <?= $total ?></span></span>
        </div>
        <div class="text-right">
            <span class="text-sm font-bold text-accent"><?= $percentage ?>%</span>
        </div>
    </div>

    <!-- Piste de la barre -->
    <div class="h-1.5 w-full bg-stone-100 rounded-full overflow-hidden border border-border/10">
        <!-- Remplissage avec transition -->
        <div class="h-full bg-accent transition-all duration-700 ease-out" 
             style="width: <?= $percentage ?>%">
        </div>
    </div>
</div>
