<?php
require_once __DIR__ . '/../auth/session.php';
?>

<?php if (!isset($hideFooterContent) || !$hideFooterContent): ?>
<footer class="bg-cream mt-auto py-8 w-full text-center text-muted text-sm">
    &copy; <?= date('Y') ?> Book Club. Tous droits réservés.
</footer>
<?php endif; ?>

</body>
</html>