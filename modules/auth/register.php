<?php
$pageTitle = 'Inscription';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="flex-1 flex items-center justify-center">
  <div class="w-full max-w-sm">

    <div class="mb-8 text-center">
      <h1 class="font-display text-3xl text-ink">◈ Book Club</h1>
      <p class="text-muted text-sm mt-1 font-light">Créez votre compte</p>
    </div>

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>


    <form action="<?= BASE_URL ?>/register" method="POST" class="space-y-4">
      <input type="hidden" name="action" value="register">

      <div class="space-y-1">
        <label for="nom" class="block text-xs font-medium text-ink uppercase tracking-widest">Nom</label>
        <input type="text" id="nom" name="nom" required autocomplete="name" placeholder="Jean Dupont"
          value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
          class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink placeholder-stone-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
      </div>

      <div class="space-y-1">
        <label for="email" class="block text-xs font-medium text-ink uppercase tracking-widest">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email" placeholder="vous@exemple.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink placeholder-stone-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
      </div>

      <div class="space-y-1">
        <label for="password" class="block text-xs font-medium text-ink uppercase tracking-widest">Mot de passe</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="••••••••"
          class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink placeholder-stone-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
      </div>

      <div class="space-y-1">
        <label for="password_confirm" class="block text-xs font-medium text-ink uppercase tracking-widest">Confirmer le
          mot de passe</label>
        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
          placeholder="••••••••"
          class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink placeholder-stone-400 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
      </div>

      <button type="submit"
        class="w-full py-3 bg-ink text-cream text-sm font-medium rounded-2xl hover:bg-stone-800 active:scale-[0.99] transition">
        Créer mon compte
      </button>
    </form>

    <div class="flex items-center gap-3 my-6">
      <div class="flex-1 h-px bg-border"></div>
      <span class="text-xs text-muted uppercase tracking-widest">ou</span>
      <div class="flex-1 h-px bg-border"></div>
    </div>

    <p class="text-center text-sm text-muted">
      Déjà un compte ?
      <a href="<?= BASE_URL ?>/login"
        class="text-ink font-medium hover:text-accent underline underline-offset-2 transition-colors">
        Se connecter
      </a>
    </p>


  </div>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>