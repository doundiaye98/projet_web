<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth/session.php';
require_once __DIR__ . '/functions.php';

// Protection : Accès réservé aux administrateurs
if (getUserRole() !== 'admin') {
    header('Location: /index.php');
    exit();
}

$pageTitle = 'Gestion des Membres';
$users = getAllUsers($mysqli);

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-body font-bold tracking-tight text-ink">Gestion des membres</h1>
            <p class="text-muted mt-2">Gérez les rôles et les accès des utilisateurs de la plateforme.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
            <!-- Barre de recherche -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="userSearch" placeholder="Rechercher un membre..."
                    class="block w-full pl-10 pr-3 py-2 border border-border rounded-xl bg-white/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all text-sm">
            </div>

            <!-- Bouton Ajouter -->
            <button onclick="toggleModal('addUserModal', true)"
                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un membre
            </button>
        </div>
    </div>

    <!-- Affichage des messages Flash -->
    <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

    <div class="rounded-xl border border-border overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-border" id="usersTable">
            <thead class="bg-stone-200/40">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-muted uppercase tracking-wider">
                        Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-muted uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-muted uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-muted uppercase tracking-wider">Statut
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-muted uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-stone-200/30 transition-colors user-row">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-ink user-name">
                            <?= htmlspecialchars($user['nom']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted user-email">
                            <?= htmlspecialchars($user['email']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($user['is_system']): ?>
                                <span class="text-muted italic flex items-center">
                                    Super Admin
                                </span>
                            <?php else: ?>
                                <form action="process.php" method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role" onchange="this.form.submit()"
                                        class="bg-transparent border-none text-muted text-sm focus:ring-0 cursor-pointer hover:text-ink transition-colors">
                                        <option value="membre" <?= $user['role'] === 'membre' ? 'selected' : '' ?>>Membre</option>
                                        <option value="moderateur" <?= $user['role'] === 'moderateur' ? 'selected' : '' ?>>
                                            Modérateur</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($user['statut'] === 'actif'): ?>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                            <?php else: ?>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Banni</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if (!$user['is_system'] && $user['id'] != $_SESSION['user_id']): ?>
                                <div class="flex items-center justify-end space-x-3">
                                    <!-- Bouton Bannir/Débannir -->
                                    <form action="process.php" method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_statut">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="statut"
                                            value="<?= $user['statut'] === 'actif' ? 'banni' : 'actif' ?>">
                                        <button type="submit"
                                            title="<?= $user['statut'] === 'actif' ? 'Bannir' : 'Débannir' ?>"
                                            class="<?= $user['statut'] === 'actif' ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' ?> transition-colors">
                                            <?php if ($user['statut'] === 'actif'): ?>
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2l4-4" />
                                                </svg>
                                            <?php endif; ?>
                                        </button>
                                    </form>

                                    <!-- Bouton Supprimer -->
                                    <button type="button" 
                                        onclick="openDeleteModal('<?= $user['id'] ?>', '<?= addslashes(htmlspecialchars($user['nom'])) ?>')"
                                        class="text-red-600 hover:text-red-900 transition-colors"
                                        title="Supprimer">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Message Aucun Résultat -->
        <div id="noResults" class="hidden p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-stone-300 mb-4" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-muted font-medium">Aucun membre ne correspond à votre recherche</p>
        </div>
    </div>
</main>

<!-- Modal Ajouter Utilisateur -->
<div id="addUserModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div id="modalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('addUserModal', false)"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div id="modalContent"
            class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-border font-body">
            <div class="px-6 py-6 sm:px-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-body font-bold tracking-tight text-ink" id="modal-title">Nouveau membre</h3>
                    <button type="button" onclick="toggleModal('addUserModal', false)"
                        class="text-muted hover:text-ink transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_user">

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Nom complet</label>
                        <input type="text" name="nom" required
                            class="w-full px-4 py-2 bg-stone-50 border border-border rounded-xl focus:ring-2 focus:ring-ink/5 focus:border-ink outline-none transition-all text-sm font-body"
                            placeholder="Ex: Jean Dupont">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Email</label>
                        <input type="email" name="email" required
                            class="w-full px-4 py-2 bg-stone-50 border border-border rounded-xl focus:ring-2 focus:ring-ink/5 focus:border-ink outline-none transition-all text-sm font-body"
                            placeholder="jean@exemple.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted mb-1">Mot de passe</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-2 bg-stone-50 border border-border rounded-xl focus:ring-2 focus:ring-ink/5 focus:border-ink outline-none transition-all text-sm font-body"
                            placeholder="••••••••">
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-medium text-muted mb-1">Rôle</label>
                        <div class="relative">
                            <select name="role"
                                class="w-full px-4 py-2 bg-stone-50 border border-border rounded-xl focus:ring-2 focus:ring-ink/5 focus:border-ink outline-none transition-all text-sm font-body appearance-none pr-10">
                                <option value="membre">Membre (standard)</option>
                                <option value="moderateur">Modérateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center gap-3">
                        <button type="button" onclick="toggleModal('addUserModal', false)"
                            class="flex-1 px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all font-body">
                            Annuler
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition-all shadow-sm font-body">
                            Créer le membre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Suppression -->
<div id="deleteConfirmModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
            onclick="toggleModal('deleteConfirmModal', false)"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div id="deleteModalContent"
            class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-border font-body">
            <div class="px-6 py-6 sm:px-8">
                <div class="text-center">
                    <h3 class="text-xl font-body font-bold text-ink mb-2">Confirmer la suppression</h3>
                    <p class="text-sm text-muted mb-6">
                        Êtes-vous sûr de vouloir supprimer <span id="deleteUserName" class="font-bold text-ink"></span> ? Cette action est irréversible.
                    </p>
                </div>

                <form id="deleteForm" action="process.php" method="POST" class="flex items-center gap-3">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <button type="button" onclick="toggleModal('deleteConfirmModal', false)"
                        class="flex-1 px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all font-body">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 active:scale-[0.98] transition-all shadow-sm font-body">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Logique de recherche (Simple)
    document.getElementById('userSearch').addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.user-row');
        let hasResults = false;

        rows.forEach(row => {
            const name = row.querySelector('.user-name').textContent.toLowerCase();
            const email = row.querySelector('.user-email').textContent.toLowerCase();

            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                row.classList.remove('hidden');
                hasResults = true;
            } else {
                row.classList.add('hidden');
            }
        });

        document.getElementById('noResults').classList.toggle('hidden', hasResults);
    });

    // Fermer modal avec Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            toggleModal('addUserModal', false);
            toggleModal('deleteConfirmModal', false);
        }
    });

    // Fonction pour ouvrir le modal de suppression
    function openDeleteModal(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = name;
        toggleModal('deleteConfirmModal', true);
    }
</script>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>