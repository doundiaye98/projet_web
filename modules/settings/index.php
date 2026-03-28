<?php
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

require_once __DIR__ . '/functions.php';

$user = getUserById($mysqli, $_SESSION['user_id']);

$pageTitle = 'Paramètres';
include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <!-- Section : Mon profil -->
    <div class="mb-14">
        <div class="mb-8">
            <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Mon profil</h1>
            <p class="text-muted mt-2">Vos informations personnelles.</p>
        </div>

        <?php
            $words    = array_filter(explode(' ', trim($user['nom'])));
            $initials = !empty($words) ? mb_strtoupper(mb_substr(reset($words), 0, 1)) : '?';
            if (count($words) > 1) {
                $initials .= mb_strtoupper(mb_substr(end($words), 0, 1));
            }
            $avatarUrl = !empty($user['avatar_path']) ? BASE_URL . '/' . ltrim($user['avatar_path'], '/') : null;
        ?>
        <div class="flex gap-12">
            <div class="flex-shrink-0">
                <?php if ($avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar"
                         class="w-20 h-20 rounded-full border border-accent/40 object-cover">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-accent/15 border border-accent/30 text-accent flex items-center justify-center text-2xl font-display select-none">
                        <?= $initials ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-1">
            <form action="<?= BASE_URL ?>/settings" method="POST" id="profileForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>"
                            class="profile-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Photo de profil</label>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-sm text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-ink file:text-cream hover:file:bg-stone-800 cursor-pointer bg-cream border border-border rounded-xl px-2 py-1.5">
                        <p class="mt-1 text-[11px] text-muted">JPG, PNG ou WEBP, taille raisonnable.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Email</label>
                        <?php if ($user['is_system']): ?>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                class="w-full px-0 py-1 bg-cream border-0 text-sm text-muted cursor-not-allowed">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                        <?php else: ?>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                                class="profile-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors">
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Rôle</label>
                        <?php
                            $roleColors = [
                                'admin'      => 'bg-accent/10 text-accent',
                                'moderateur' => 'bg-amber-100 text-amber-700',
                                'membre'     => 'bg-stone-100 text-stone-600',
                            ];
                            $roleClass = $roleColors[$user['role']] ?? 'bg-stone-100 text-stone-600';
                        ?>
                        <div class="py-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleClass ?>">
                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Statut</label>
                        <?php $statutClass = $user['statut'] === 'actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>
                        <div class="py-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statutClass ?>">
                                <?= htmlspecialchars(ucfirst($user['statut'])) ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Membre depuis</label>
                        <p class="py-1 text-sm text-muted">
                            <?= (new DateTime($user['created_at']))->format('d/m/Y') ?>
                        </p>
                    </div>
                </div>

                <div id="saveBar" class="grid grid-rows-[0fr] opacity-0 -translate-y-4 pointer-events-none transition-all duration-300 ease-out overflow-hidden mt-0">
                    <div class="min-h-0">
                        <div class="flex items-center justify-end gap-3 pt-8 pb-4">
                            <button type="button" id="cancelBtn"
                                class="px-5 py-2 border border-border bg-cream text-muted text-sm font-medium rounded-xl hover:bg-stone-100 transition-all shadow-sm">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-5 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-md">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Section : Mot de passe -->
    <div class="mt-14">
        <div class="mb-8">
            <h2 class="text-xl font-body font-semibold tracking-tight text-ink">Mot de passe</h2>
            <p class="text-muted mt-2">Modifiez votre mot de passe de connexion.</p>
        </div>

        <form action="<?= BASE_URL ?>/settings" method="POST" id="passwordForm">
            <input type="hidden" name="action" value="update_password">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Mot de passe actuel</label>
                    <input type="password" name="current_password"
                        class="pwd-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors"
                        placeholder="••••••••">
                </div>
                <div class="hidden md:block"></div>
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Nouveau mot de passe</label>
                    <input type="password" name="new_password"
                        class="pwd-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors"
                        placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password"
                        class="pwd-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors"
                        placeholder="••••••••">
                </div>

            </div>

            <div id="pwdSaveBar" class="grid grid-rows-[0fr] opacity-0 -translate-y-4 pointer-events-none transition-all duration-300 ease-out overflow-hidden mt-0">
                <div class="min-h-0">
                    <div class="flex items-center justify-end gap-3 pt-8 pb-4">
                        <button type="button" id="pwdCancelBtn"
                            class="px-5 py-2 border border-border bg-cream text-muted text-sm font-medium rounded-xl hover:bg-stone-100 transition-all shadow-sm">
                            Annuler
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-md">
                            Mettre à jour le mot de passe
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

</main>

<script>
    const inputs = document.querySelectorAll('.profile-input');
    const saveBar = document.getElementById('saveBar');
    const cancelBtn = document.getElementById('cancelBtn');
    const originals = {};

    inputs.forEach(input => {
        originals[input.name] = input.value;
        input.addEventListener('input', () => {
            const changed = Array.from(inputs).some(i => i.value !== originals[i.name]);
            if (changed) {
                saveBar.classList.remove('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                saveBar.classList.add('grid-rows-[1fr]');
            } else {
                saveBar.classList.add('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                saveBar.classList.remove('grid-rows-[1fr]');
            }
        });
    });

    cancelBtn.addEventListener('click', () => {
        inputs.forEach(input => input.value = originals[input.name]);
        saveBar.classList.add('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
        saveBar.classList.remove('grid-rows-[1fr]');
    });

    const pwdInputs = document.querySelectorAll('.pwd-input');
    const pwdSaveBar = document.getElementById('pwdSaveBar');
    const pwdCancelBtn = document.getElementById('pwdCancelBtn');

    pwdInputs.forEach(input => {
        input.addEventListener('input', () => {
            const anyNotEmpty = Array.from(pwdInputs).some(i => i.value.trim() !== '');
            if (anyNotEmpty) {
                pwdSaveBar.classList.remove('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                pwdSaveBar.classList.add('grid-rows-[1fr]');
            } else {
                pwdSaveBar.classList.add('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                pwdSaveBar.classList.remove('grid-rows-[1fr]');
            }
        });
    });

    pwdCancelBtn.addEventListener('click', () => {
        pwdInputs.forEach(input => input.value = '');
        pwdSaveBar.classList.add('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
        pwdSaveBar.classList.remove('grid-rows-[1fr]');
    });
</script>


<?php 
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php'; 
?>
