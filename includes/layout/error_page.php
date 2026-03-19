<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);

// Calcul du chemin de base du projet (supporte les sous-dossiers)
$projectRoot = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(__DIR__ . '/../../'));
$projectRoot = str_replace('\\', '/', $projectRoot); // Pour compatibilité Windows si besoin

$homeUrl = $projectRoot . '/index.php';
$loginUrl = $projectRoot . '/modules/auth/login.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - Book Club</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Playfair Display', 'serif'],
                        body: ['DM Sans', 'sans-serif'],
                    },
                    colors: {
                        cream: '#F5F0E8',
                        ink: '#1C1917',
                        muted: '#78716C',
                        accent: '#C2410C',
                        border: '#E2D9CC',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">
</head>

<body class="bg-cream min-h-screen font-body flex items-center justify-center p-6 text-ink">
    <div class="max-w-md w-full text-center space-y-8">
        <!-- Icon/Visual -->
        <div class="relative inline-block">
            <div class="w-24 h-24 bg-accent/10 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-12 h-12 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <div
                class="absolute -top-2 -right-2 bg-white px-2 py-1 rounded-md shadow-sm border border-border text-[10px] font-bold tracking-widest text-accent uppercase">
                403
            </div>
        </div>

        <div class="space-y-4">
            <h1 class="font-display text-4xl">Oups ! Vous vous êtes perdu...</h1>
            <p class="text-muted leading-relaxed">
                Il semble que vous vous soyez égaré dans les rayons de la librairie. Cette section est réservée ou
                inaccessible directement.
            </p>
        </div>

        <div class="flex flex-col gap-3 pt-4">
            <?php if ($isLoggedIn): ?>
                <a href="<?= $homeUrl ?>"
                    class="bg-ink text-cream px-6 py-3 rounded-xl font-medium hover:bg-stone-800 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Retour au menu
                </a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>"
                    class="bg-ink text-cream px-6 py-3 rounded-xl font-medium hover:bg-stone-800 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Se connecter
                </a>
            <?php endif; ?>

            <button onclick="window.location.reload()"
                class="bg-white text-ink border border-border px-6 py-3 rounded-xl font-medium hover:bg-stone-50 transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Réessayer
            </button>
        </div>

        <p class="text-[11px] text-muted/60 pt-8 uppercase tracking-widest font-medium">
            &copy; <?= date('Y') ?> Book Club • Système de Protection
        </p>
    </div>
</body>

</html>