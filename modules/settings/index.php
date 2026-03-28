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

            <div class="flex-shrink-0 relative select-none" id="avatarPreviewContainer">
                <img id="avatarImg" src="<?= $avatarUrl ? htmlspecialchars($avatarUrl) : '' ?>" alt="Avatar"
                     class="w-20 h-20 rounded-full border border-accent/40 object-cover <?= !$avatarUrl ? 'hidden' : '' ?>">
                <div id="avatarInitials" class="w-20 h-20 rounded-full bg-accent/15 border border-accent/30 text-accent items-center justify-center text-2xl font-display <?= $avatarUrl ? 'hidden' : 'flex' ?>">
                    <?= $initials ?>
                </div>
                <!-- Icônes affichées uniquement au survol du cercle -->
                <div class="absolute inset-0 flex items-center justify-center group/avatar">
                    <div class="flex gap-2 opacity-0 group-hover/avatar:opacity-100 transition-opacity duration-200">
                        <label for="avatarInput" title="Changer la photo" class="bg-white border border-border rounded-full p-1 shadow flex items-center justify-center text-accent hover:bg-accent/10 cursor-pointer transition w-7 h-7">
                            <i class="ph ph-camera text-base"></i>
                        </label>
                        <button type="button" id="removeAvatarBtn" title="Supprimer la photo"
                            class="bg-white border border-border rounded-full p-1 shadow flex items-center justify-center text-red-600 hover:bg-red-50 transition w-7 h-7 <?= !$avatarUrl ? '!hidden' : '' ?>">
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1">
            <form action="<?= BASE_URL ?>/settings" method="POST" id="profileForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <input id="avatarInput" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="hidden">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>"
                            class="profile-input w-full px-0 py-1 bg-cream border-0 border-b border-transparent text-sm text-ink focus:outline-none focus:border-border transition-colors">
                    </div>

                    <!-- Champ photo de profil masqué, géré par l'icône -->

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
document.addEventListener('DOMContentLoaded', () => {
    // DRY : Gestionnaire de barre d'enregistrement (no redeclaration)
    if (!window._setupSaveBar) {
        window._setupSaveBar = function({ formId, barId, inputsSelector, cancelBtnId, onCancel }) {
            const form = document.getElementById(formId);
            const bar = document.getElementById(barId);
            const inputs = document.querySelectorAll(inputsSelector);
            const cancelBtn = document.getElementById(cancelBtnId);
            const originals = {};
            const updateBarVisibility = () => {
                const hasChanged = Array.from(inputs).some(input => {
                    if (input.type === 'file') return input.files.length > 0;
                    return input.value !== (originals[input.name] || '');
                }) || (form.querySelector('#removeAvatarFlag')?.value === '1');
                if (hasChanged) {
                    bar.classList.remove('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                    bar.classList.add('grid-rows-[1fr]');
                } else {
                    bar.classList.add('opacity-0', '-translate-y-4', 'pointer-events-none', 'grid-rows-[0fr]');
                    bar.classList.remove('grid-rows-[1fr]');
                }
            };
            inputs.forEach(input => {
                originals[input.name] = input.value;
                input.addEventListener('input', updateBarVisibility);
                input.addEventListener('change', updateBarVisibility);
            });
            cancelBtn?.addEventListener('click', () => {
                inputs.forEach(input => {
                    if (input.type === 'file') input.value = '';
                    else input.value = originals[input.name] || '';
                });
                form.querySelector('#removeAvatarFlag')?.remove();
                onCancel?.();
                updateBarVisibility();
            });
            return { updateBarVisibility };
        }
    }

    // Configuration Profil
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');
    const avatarInput = document.getElementById('avatarInput');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');
    const profileForm = document.getElementById('profileForm');
    const initialAvatarSrc = avatarImg.src;
    const initialAvatarHidden = avatarImg.classList.contains('hidden');
    const profileManager = window._setupSaveBar({
        formId: 'profileForm',
        barId: 'saveBar',
        inputsSelector: '.profile-input, #avatarInput',
        cancelBtnId: 'cancelBtn',
        onCancel: () => {
            avatarImg.src = initialAvatarSrc;
            if (initialAvatarHidden) {
                avatarImg.classList.add('hidden');
                avatarInitials.classList.remove('hidden');
                avatarInitials.classList.add('flex');
            } else {
                avatarImg.classList.remove('hidden');
                avatarInitials.classList.add('hidden');
            }
            removeAvatarBtn.classList.toggle('!hidden', initialAvatarHidden);
        }
    });
    // Prévisualisation de l'avatar
    avatarInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                avatarImg.src = ev.target.result;
                avatarImg.classList.remove('hidden');
                avatarInitials.classList.add('hidden');
                removeAvatarBtn.classList.remove('!hidden');
            };
            reader.readAsDataURL(file);
        }
        // Remove the remove flag if user picks a new file
        const removeFlag = document.getElementById('removeAvatarFlag');
        if (removeFlag) removeFlag.remove();
        profileManager.updateBarVisibility();
    });
    // Suppression de l'avatar (UI)
    removeAvatarBtn.addEventListener('click', () => {
        let input = document.getElementById('removeAvatarFlag');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'remove_avatar';
            input.value = '1';
            input.id = 'removeAvatarFlag';
            profileForm.appendChild(input);
        }
        avatarImg.classList.add('hidden');
        avatarInitials.classList.remove('hidden');
        avatarInitials.classList.add('flex');
        removeAvatarBtn.classList.add('!hidden');
        // Also clear file input if any
        avatarInput.value = '';
        profileManager.updateBarVisibility();
    });
    // Configuration Mot de passe
    window._setupSaveBar({
        formId: 'passwordForm',
        barId: 'pwdSaveBar',
        inputsSelector: '.pwd-input',
        cancelBtnId: 'pwdCancelBtn'
    });
});
</script>


<?php 
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php'; 
?>
