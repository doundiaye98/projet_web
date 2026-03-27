<!-- header.php : uniquement le HTML du <head> -->
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Book Club') ?></title>
  <?php include __DIR__ . '/partials/_head_tags.php'; ?>
  
  <script>
    // Utilitaires Globaux
    function toggleModal(id, show) {
        const modal = document.getElementById(id);
        const overlay = modal.querySelector('[id$="Overlay"]');
        const content = modal.querySelector('[id$="Content"]');

        if (show) {
            modal.classList.remove('pointer-events-none');
            modal.classList.replace('opacity-0', 'opacity-100');
            if(overlay) {
                overlay.classList.replace('bg-stone-900/0', 'bg-stone-900/60');
            }
            if(content) {
                content.classList.replace('opacity-0', 'opacity-100');
                content.classList.replace('scale-95', 'scale-100');
            }
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('pointer-events-none');
            modal.classList.replace('opacity-100', 'opacity-0');
            if(overlay) {
                overlay.classList.replace('bg-stone-900/60', 'bg-stone-900/0');
            }
            if(content) {
                content.classList.replace('opacity-100', 'opacity-0');
                content.classList.replace('scale-100', 'scale-95');
            }
            document.body.style.overflow = 'auto';
        }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>
</head>
<body class="bg-cream font-body flex flex-col text-ink <?= ($hideNav ?? false) ? 'h-screen overflow-hidden' : 'min-h-screen pt-14' ?>">
  <?php if (!($hideNav ?? false)) include __DIR__ . '/nav.php'; ?>

  <!-- Container Global pour les Messages Flash (masqué sur les pages sans nav) -->
  <?php if (!($hideNav ?? false)): ?>
  <div class="<?= $containerClass ?? 'max-w-7xl' ?> mx-auto w-full px-4 sm:px-6 lg:px-8 mt-6">
    <?php include __DIR__ . "/partials/_flash.php"; ?>
  </div>
  <?php endif; ?>