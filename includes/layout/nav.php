<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
require_once __DIR__ . '/../auth/session.php';
$userRole = getUserRole();
$userAvatar = $_SESSION['user_avatar'] ?? null;
if (!isUserLoggedIn()) return;
?>
<nav class="fixed top-0 left-0 right-0 bg-stone-800/95 backdrop-blur-md border-b border-stone-700 py-3 px-6 z-50">
  <div class="max-w-7xl mx-auto flex items-center justify-between">

    <a href="<?= BASE_URL ?>/"
       class="font-display text-xl text-cream/90 hover:text-cream tracking-wide transition-colors">
      ◈ Book Club
    </a>

    <!-- Liens desktop -->
    <div class="hidden md:flex items-center gap-1">
      <a href="<?= BASE_URL ?>/"
         class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
        <i class="ph ph-house text-base"></i>
        Accueil
      </a>
      <a href="<?= BASE_URL ?>/books"
         class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
        <i class="ph ph-books text-base"></i>
        Livres
      </a>
      <a href="<?= BASE_URL ?>/sessions"
         class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
        <i class="ph ph-calendar-blank text-base"></i>
        Sessions
      </a>
      <?php if ($userRole === 'admin' || $userRole === 'moderateur'): ?>
        <a href="<?= BASE_URL ?>/moderation"
           class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
          <i class="ph ph-shield-check text-base"></i>
          Modération
        </a>
      <?php endif; ?>
      <?php if ($userRole === 'admin'): ?>
        <a href="<?= BASE_URL ?>/members"
           class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
          <i class="ph ph-users text-base"></i>
          Membres
        </a>
      <?php endif; ?>
    </div>

    <!-- Droite desktop + burger mobile -->
    <div class="flex items-center gap-1">
      <!-- Avatar + logout (toujours visibles) -->
      <a href="<?= BASE_URL ?>/settings"
         class="flex items-center gap-2 px-2 py-1.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all" title="Profil">
        <?php if ($userAvatar): ?>
          <img src="<?= htmlspecialchars(BASE_URL . '/' . ltrim($userAvatar, '/')) ?>"
               alt="Avatar"
               class="w-7 h-7 rounded-full object-cover border border-stone-500/60">
        <?php else: ?>
          <i class="ph ph-user-circle text-lg"></i>
        <?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>/logout"
         class="flex items-center gap-2 px-3 py-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all" title="Déconnexion">
        <i class="ph ph-sign-out text-lg"></i>
      </a>

      <!-- Burger (mobile uniquement) -->
      <button id="navBurger" type="button"
        class="md:hidden flex items-center justify-center p-2 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 transition-all"
        aria-label="Menu">
        <i id="navBurgerIcon" class="ph ph-list text-xl"></i>
      </button>
    </div>

  </div>

  <!-- Menu mobile -->
  <div id="navMobileMenu"
       class="md:hidden hidden flex-col gap-1 pt-3 pb-2 border-t border-stone-700 mt-3">
    <a href="<?= BASE_URL ?>/"
       class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
      <i class="ph ph-house text-base"></i>
      Accueil
    </a>
    <a href="<?= BASE_URL ?>/books"
       class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
      <i class="ph ph-books text-base"></i>
      Livres
    </a>
    <a href="<?= BASE_URL ?>/sessions"
       class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
      <i class="ph ph-calendar-blank text-base"></i>
      Sessions
    </a>
    <?php if ($userRole === 'admin' || $userRole === 'moderateur'): ?>
      <a href="<?= BASE_URL ?>/moderation"
         class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
        <i class="ph ph-shield-check text-base"></i>
        Modération
      </a>
    <?php endif; ?>
    <?php if ($userRole === 'admin'): ?>
      <a href="<?= BASE_URL ?>/members"
         class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-stone-300 hover:text-cream hover:bg-stone-700 text-sm transition-all">
        <i class="ph ph-users text-base"></i>
        Membres
      </a>
    <?php endif; ?>
  </div>
</nav>

<script>
  (function () {
    const burger = document.getElementById('navBurger');
    const menu = document.getElementById('navMobileMenu');
    const icon = document.getElementById('navBurgerIcon');
    if (!burger) return;
    burger.addEventListener('click', function () {
      const open = !menu.classList.contains('hidden');
      menu.classList.toggle('hidden', open);
      menu.classList.toggle('flex', !open);
      icon.className = open ? 'ph ph-list text-xl' : 'ph ph-x text-xl';
    });
  })();
</script>
