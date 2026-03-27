<?php
$pageTitle = 'Accueil';
$containerClass = 'max-w-7xl';
include __DIR__ . '/../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-body font-bold tracking-tight text-ink">
        Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> !
    </h1>
</main>


<?php 
$hideFooterContent = true;
include __DIR__ . '/../includes/layout/footer.php'; 
?>
