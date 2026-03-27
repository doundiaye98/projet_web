<?php
/**
 * Partial: _stars.php
 * Affiche une note sous forme d'étoiles (Phosphor Icons).
 * 
 * Variables attendues :
 * - $rating : float|int - La note (ex: 4.5)
 * - $max : int - Nombre d'étoiles maximum (défaut 5)
 * - $size : string - Classe de taille (défaut text-sm)
 */
$max = $max ?? 5;
$size = $size ?? 'text-base';
$fullStars = floor($rating);
$hasHalf   = ($rating - $fullStars) >= 0.5;
$emptyStars = $max - $fullStars - ($hasHalf ? 1 : 0);
$colorClass = ($rating > 0) ? 'text-accent' : 'text-stone-300';
?>
<div class="flex items-center gap-0.5 <?= $colorClass ?> <?= $size ?>">
    <?php for ($i = 0; $i < $fullStars; $i++): ?>
        <i class="ph-fill ph-star"></i>
    <?php endfor; ?>
    
    <?php if ($hasHalf): ?>
        <i class="ph-fill ph-star-half"></i>
    <?php endif; ?>
    
    <?php for ($i = 0; $i < $emptyStars; $i++): ?>
        <i class="ph ph-star"></i>
    <?php endfor; ?>
    
    <?php if (isset($showValue) && $showValue): ?>
        <span class="ml-1.5 text-xs font-bold text-ink"><?= number_format($rating, 1) ?></span>
    <?php endif; ?>
</div>
