<?php
require_once __DIR__ . '/../../auth/session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On récupère uniquement depuis la session pour éviter le problème de persistance au refresh (URL param)
$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;

// On nettoie la session immédiatement pour que le message ne s'affiche qu'une fois
unset($_SESSION['flash_success']);
unset($_SESSION['flash_error']);
?>

<?php if ($success): ?>
    <div id="flash-success-container" class="transition-all duration-500 overflow-hidden max-h-40 mb-6">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
            <p class="font-bold">Succès</p>
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div id="flash-error-container" class="transition-all duration-500 overflow-hidden max-h-40 mb-6">
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
            <p class="font-bold">Erreur</p>
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const successContainer = document.getElementById('flash-success-container');
        const errorContainer = document.getElementById('flash-error-container');

        const dismiss = (el) => {
            if (!el) return;
            setTimeout(() => {
                el.style.opacity = '0';
                el.style.maxHeight = '0';
                el.style.marginBottom = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(() => el.remove(), 500);
            }, el.id.includes('success') ? 2500 : 4000);
        };

        dismiss(successContainer);
        dismiss(errorContainer);
    });
</script>
