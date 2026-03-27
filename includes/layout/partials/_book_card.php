<?php
/**
 * Partial: _book_card.php
 * Affiche une carte de livre avec les actions d'édition et de suppression.
 * 
 * Variables attendues :
 * - $book : array - Les données du livre (id, titre, auteur, genre, nb_pages, cover_path)
 */
?>
<a href="<?= BASE_URL ?>/books/<?= $book['id'] ?>" class="book-card group flex flex-col relative"
     data-id="<?= $book['id'] ?>"
     data-title="<?= htmlspecialchars($book['titre']) ?>"
     data-author="<?= htmlspecialchars($book['auteur']) ?>"
     data-author-id="<?= $book['author_id'] ?>"
     data-genre="<?= htmlspecialchars($book['genre'] ?? '') ?>"
     data-description="<?= htmlspecialchars($book['description'] ?? '') ?>"
     data-pages="<?= $book['nb_pages'] ?>"
     data-date="<?= strtotime($book['created_at']) ?>"
     data-rating="<?= $book['avg_rating'] ?: 0 ?>"
     data-cover-path="<?= htmlspecialchars($book['cover_path'] ?? '') ?>"
     data-resources='<?= htmlspecialchars(json_encode($book['resources_list'] ?? []), ENT_QUOTES, 'UTF-8') ?>'
     data-search-title="<?= htmlspecialchars(strtolower($book['titre'])) ?>"
     data-search-author="<?= htmlspecialchars(strtolower($book['auteur'])) ?>">

  <!-- Overlay d'actions (Visible au survol pour Admin/Modo) -->
  <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-all duration-200 flex flex-col gap-2">
    <?php if (in_array(getUserRole(), ['admin', 'moderateur'])): ?>
    <button onclick="event.preventDefault(); event.stopPropagation(); openEditModal(this.closest('.book-card').dataset)" 
            class="p-2 bg-white/90 backdrop-blur-sm rounded-lg shadow-sm border border-border text-ink hover:text-accent hover:bg-white transition-all active:scale-95"
            title="Modifier le livre">
      <i class="ph ph-pencil-line text-lg"></i>
    </button>
    <?php endif; ?>
    
    <?php if (getUserRole() === 'admin'): ?>
    <button onclick="event.preventDefault(); event.stopPropagation(); openDeleteBookModal('<?= $book['id'] ?>', '<?= addslashes(htmlspecialchars($book['titre'])) ?>')"
            class="p-2 bg-white/90 backdrop-blur-sm rounded-lg shadow-sm border border-border text-red-600 hover:bg-red-50 transition-all active:scale-95"
            title="Supprimer le livre">
      <i class="ph ph-trash text-lg"></i>
    </button>
    <?php endif; ?>
  </div>

  <!-- Couverture -->
  <div class="aspect-[2/3] rounded-xl overflow-hidden bg-stone-200 shadow-sm group-hover:shadow-md transition-shadow mb-3 relative">
    <?php if ($book['cover_path']): ?>
    <img src="<?= BASE_URL . '/' . htmlspecialchars($book['cover_path']) ?>"
         alt="<?= htmlspecialchars($book['titre']) ?>"
         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
    <?php else: ?>
    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-stone-200 to-stone-300">
      <i class="ph ph-book text-4xl text-stone-400"></i>
    </div>
    <?php endif; ?>
    
    <!-- Gradient overlay au hover -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
  </div>

  <!-- Infos -->
  <div class="flex-1 min-w-0 space-y-0.5 px-0.5">
    <p class="text-sm font-medium text-ink truncate leading-snug"
       title="<?= htmlspecialchars($book['titre']) ?>">
      <?= htmlspecialchars($book['titre']) ?>
    </p>
    <p class="text-xs text-muted truncate"><?= htmlspecialchars($book['auteur']) ?></p>
    
    <!-- Stars -->
    <div class="flex items-center gap-1.5 py-0.5">
        <?php
        $rating = $book['avg_rating'] ?: 0;
        $size = 'text-[10px]';
        include __DIR__ . '/_stars.php';
        ?>
    </div>
    
    <div class="flex items-center gap-1.5 pt-1 flex-wrap">
      <?php if ($book['genre']): ?>
      <span class="text-[10px] bg-accent/10 text-accent px-2 py-0.5 rounded-full font-medium">
        <?= htmlspecialchars($book['genre']) ?>
      </span>
      <?php endif; ?>
      <?php if ($book['nb_pages'] > 0): ?>
      <span class="text-[10px] text-muted/80 font-medium"><?= $book['nb_pages'] ?> p.</span>
      <?php endif; ?>
    </div>
  </div>

</a>
