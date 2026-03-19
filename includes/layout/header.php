<?php
require_once __DIR__ . '/../auth/session.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Book Club') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            display: ['Playfair Display', 'serif'],
            body:    ['DM Sans', 'sans-serif'],
          },
          colors: {
            cream:  '#F5F0E8',
            ink:    '#1C1917',
            muted:  '#78716C',
            accent: '#C2410C',
            border: '#E2D9CC',
          }
        }
      }
    }
  </script>
  
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
</head>
<body class="bg-cream min-h-screen font-body flex flex-col">
<?php include __DIR__ . '/nav.php'; ?>