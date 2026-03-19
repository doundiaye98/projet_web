<?php
require_once __DIR__ . '/../auth/session.php';
$userRole = getUserRole();
if (!isUserLoggedIn()) return;
?>
<nav class="w-full bg-ink text-cream py-4 px-6 flex items-center justify-between shadow-md">
  <!-- Left section: Brand/Home & Modules -->
  <div class="flex items-center space-x-10">
    <a href="/PHP_cours/Lecture/projet_web/index.php" class="hover:text-accent font-bold text-xl flex items-center transition-colors">
      <!-- Home icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m5-8l2 2" /></svg>
      Accueil
    </a>

    <div class="flex items-center space-x-6">
      <a href="/PHP_cours/Lecture/projet_web/modules/books/index.php" class="hover:text-accent flex items-center font-semibold transition-colors">
        <!-- Book icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m6-6H6" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20" /></svg>
        Livres
      </a>
      <a href="/PHP_cours/Lecture/projet_web/modules/sessions/index.php" class="hover:text-accent flex items-center font-semibold transition-colors">
        <!-- Calendar/Sessions icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M16 3v4M8 3v4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sessions
      </a>
      <?php if ($userRole === 'admin' || $userRole === 'moderateur'): ?>
        <a href="/PHP_cours/Lecture/projet_web/modules/moderation/index.php" class="hover:text-accent flex items-center font-semibold transition-colors">
          <!-- Moderation icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4" /><circle cx="12" cy="12" r="10" stroke-width="2" /></svg>
          Modération
        </a>
      <?php endif; ?>
      <?php if ($userRole === 'admin'): ?>
        <a href="/PHP_cours/Lecture/projet_web/modules/members/index.php" class="hover:text-accent flex items-center font-semibold transition-colors">
          <!-- Members icon (Users) -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Membres
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right section: Settings & Logout (Icons only) -->
  <div class="flex items-center space-x-6">
    <a href="/PHP_cours/Lecture/projet_web/settings.php" class="hover:text-accent transition-colors" title="Paramètres">
      <!-- Settings icon (gear) -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
    </a>
    <a href="/PHP_cours/Lecture/projet_web/modules/auth/logout.php" class="hover:text-accent transition-colors" title="Déconnexion">
      <!-- Logout icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" /></svg>
    </a>
  </div>
</nav>
