<?php
require_once __DIR__ . '/functions.php';

$pageTitle = 'Livres';
$containerClass = 'max-w-7xl';
$authors = getAllAuthors($mysqli);
$books = getAllBooks($mysqli);
$userId = $_SESSION['user_id'] ?? 0;
$readingList = getUserReadingList($mysqli, $userId);

include __DIR__ . '/../../includes/layout/header.php';
?>

<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

  <!-- En-tête Original -->
  <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
    <div>
      <h1 class="text-4xl font-body font-bold tracking-tight text-ink mb-2">Livres</h1>
      <p class="text-muted text-lg">Bibliothèque complète des ouvrages.</p>
    </div>

    <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
      <div class="relative w-full sm:w-64">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
          <i class="ph ph-magnifying-glass text-lg"></i>
        </span>
        <input type="text" id="bookSearch" placeholder="Rechercher..."
          class="block w-full pl-10 pr-3 py-2 border border-border rounded-xl bg-white/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all text-sm">
      </div>
      <?php if (in_array(getUserRole(), ['admin', 'moderateur'])): ?>
        <button onclick="resetAddBookModal(); toggleModal('addBookModal', true)"
          class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 active:scale-[0.98] transition-all shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Ajouter un livre
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php include __DIR__ . '/../../includes/layout/partials/_flash.php'; ?>

  <!-- Container Tabs + Sort -->
  <div class="flex items-center justify-between mb-8 border-b border-border/40">
    
    <!-- Tabs (Sliding effect) -->
    <div class="relative flex items-center gap-8 w-max">
      <button onclick="switchMainTab('catalogue')" id="btn-tab-catalogue" 
              class="pb-3 text-xs font-bold uppercase tracking-[0.2em] transition-all text-accent outline-none">
        Catalogue
      </button>
      <button onclick="switchMainTab('reading')" id="btn-tab-reading" 
              class="pb-3 text-xs font-bold uppercase tracking-[0.2em] transition-all text-muted hover:text-ink outline-none">
        Ma lecture
        <?php if (count($readingList) > 0): ?>
          <span class="ml-1.5 px-1.5 py-0.5 bg-accent/10 text-accent text-[9px] rounded-full pointer-events-none"><?= count($readingList) ?></span>
        <?php endif; ?>
      </button>
      
      <!-- Underline coulissant -->
      <div id="main-tab-underline" class="absolute bottom-[-1px] left-0 h-0.5 bg-accent transition-all duration-300 ease-in-out pointer-events-none"></div>
    </div>

    <!-- Bouton Trier (Menu Radio) -->
    <div class="relative pb-3">
        <button onclick="toggleSortMenu()" id="btn-sort" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-muted hover:text-ink transition-all outline-none">
            <i class="ph ph-arrows-down-up text-sm"></i>
            Trier par
        </button>
        
        <!-- Dropdown Sort -->
        <div id="sortMenu" class="hidden absolute right-0 top-full mt-2 w-48 bg-white border border-border rounded-2xl shadow-xl z-30 overflow-hidden transform scale-95 opacity-0 transition-all duration-200 origin-top-right">
            <div class="p-2 space-y-1">
                <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 rounded-lg cursor-pointer group transition-colors">
                    <input type="radio" name="sortBy" value="title-asc" checked onchange="applySort(this.value)" class="w-3.5 h-3.5 text-accent border-border focus:ring-accent/20">
                    <span class="text-[11px] font-medium text-muted group-hover:text-ink">Titre (A-Z)</span>
                </label>
                <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 rounded-lg cursor-pointer group transition-colors">
                    <input type="radio" name="sortBy" value="title-desc" onchange="applySort(this.value)" class="w-3.5 h-3.5 text-accent border-border focus:ring-accent/20">
                    <span class="text-[11px] font-medium text-muted group-hover:text-ink">Titre (Z-A)</span>
                </label>
                <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 rounded-lg cursor-pointer group transition-colors">
                    <input type="radio" name="sortBy" value="recent" onchange="applySort(this.value)" class="w-3.5 h-3.5 text-accent border-border focus:ring-accent/20">
                    <span class="text-[11px] font-medium text-muted group-hover:text-ink">Plus récent</span>
                </label>
                <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 rounded-lg cursor-pointer group transition-colors">
                    <input type="radio" name="sortBy" value="rating" onchange="applySort(this.value)" class="w-3.5 h-3.5 text-accent border-border focus:ring-accent/20">
                    <span class="text-[11px] font-medium text-muted group-hover:text-ink">Mieux notés</span>
                </label>
            </div>
        </div>
    </div>

  </div>

  <!-- Zone des Grilles -->
  <div class="min-h-[60vh] flex flex-col">
    
    <!-- GRID CATALOGUE -->
    <div id="grid-catalogue" class="tab-content transition-all duration-300">
      <?php if (empty($books)): ?>
        <div class="flex-1 flex flex-col items-center justify-center text-center text-muted py-20">
          <i class="ph ph-books text-6xl block mb-4 opacity-20"></i>
          <p class="text-lg font-medium text-ink/60">Aucun livre pour l'instant.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6 pb-20">
          <?php foreach ($books as $book): ?>
            <?php include __DIR__ . '/../../includes/layout/partials/_book_card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- GRID MA LECTURE -->
    <div id="grid-reading" class="tab-content hidden opacity-0 transition-all duration-300">
      <?php if (empty($readingList)): ?>
        <div class="flex-1 flex flex-col items-center justify-center text-center text-muted py-20">
          <i class="ph ph-book-open text-6xl block mb-4 opacity-20"></i>
          <p class="text-lg font-medium text-ink/60">Vous n'avez commencé aucune lecture.</p>
          <p class="text-sm mt-2">Explorez le catalogue pour commencer un livre.</p>
          <button onclick="switchMainTab('catalogue')" class="mt-6 px-6 py-2 border border-border rounded-xl text-ink hover:bg-stone-50 transition-all font-medium">
            Voir le catalogue
          </button>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6 pb-20">
          <?php foreach ($readingList as $book): ?>
            <?php include __DIR__ . '/../../includes/layout/partials/_book_card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Message "Aucun résultat" pour la recherche -->
    <div id="noSearchResults" class="hidden flex-1 flex flex-col items-center justify-center text-center text-muted py-20">
      <i class="ph ph-magnifying-glass text-6xl block mb-4 opacity-20"></i>
      <p class="text-lg font-medium text-ink/60">Aucun livre ne correspond à votre recherche.</p>
      <button onclick="document.getElementById('bookSearch').value = ''; document.getElementById('bookSearch').dispatchEvent(new Event('input'))" 
              class="text-sm text-accent mt-4 font-medium hover:underline">
        Effacer la recherche
      </button>
    </div>

  </div>

</main>

<script>
  function switchMainTab(tab) {
    const isCatalogue = tab === 'catalogue';
    const gridCat = document.getElementById('grid-catalogue');
    const gridRead = document.getElementById('grid-reading');
    const btnCat = document.getElementById('btn-tab-catalogue');
    const btnRead = document.getElementById('btn-tab-reading');
    const underline = document.getElementById('main-tab-underline');
    const targetBtn = isCatalogue ? btnCat : btnRead;

    if (underline) {
        underline.style.left = targetBtn.offsetLeft + 'px';
        underline.style.width = targetBtn.offsetWidth + 'px';
    }

    if (isCatalogue) {
      btnCat.classList.replace('text-muted', 'text-accent');
      btnRead.classList.replace('text-accent', 'text-muted');
      gridRead.classList.add('opacity-0');
      setTimeout(() => {
        gridRead.classList.add('hidden');
        gridCat.classList.remove('hidden');
        setTimeout(() => gridCat.classList.remove('opacity-0'), 10);
      }, 300);
    } else {
      btnRead.classList.replace('text-muted', 'text-accent');
      btnCat.classList.replace('text-accent', 'text-muted');
      gridCat.classList.add('opacity-0');
      setTimeout(() => {
        gridCat.classList.add('hidden');
        gridRead.classList.remove('hidden');
        setTimeout(() => gridRead.classList.remove('opacity-0'), 10);
      }, 300);
    }
  }

  // --- LOGIQUE TRI ---
  function toggleSortMenu(force) {
    const menu = document.getElementById('sortMenu');
    if (!menu) return;
    const isHidden = menu.classList.contains('hidden');
    const show = force !== undefined ? force : isHidden;

    if (show) {
        menu.classList.remove('hidden');
        setTimeout(() => {
            menu.classList.remove('opacity-0', 'scale-95');
        }, 10);
    } else {
        menu.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            menu.classList.add('hidden');
        }, 200);
    }
  }

  function applySort(criteria) {
    const gridIds = ['grid-catalogue', 'grid-reading'];
    
    gridIds.forEach(id => {
        const grid = document.getElementById(id);
        if (!grid) return;
        const container = grid.querySelector('.grid');
        if (!container) return;
        
        const cards = Array.from(container.querySelectorAll('.book-card'));
        
        cards.sort((a, b) => {
            if (criteria === 'title-asc') return a.dataset.title.localeCompare(b.dataset.title);
            if (criteria === 'title-desc') return b.dataset.title.localeCompare(a.dataset.title);
            if (criteria === 'recent') return parseInt(b.dataset.date) - parseInt(a.dataset.date);
            if (criteria === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
            return 0;
        });
        
        cards.forEach(card => container.appendChild(card));
    });
    
    toggleSortMenu(false);
  }

  // Fermer le menu si clic à l'extérieur
  document.addEventListener('click', (e) => {
    const menu = document.getElementById('sortMenu');
    const btn = document.getElementById('btn-sort');
    if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
        toggleSortMenu(false);
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const btnCat = document.getElementById('btn-tab-catalogue');
    const underline = document.getElementById('main-tab-underline');
    if (btnCat && underline) {
        underline.style.left = btnCat.offsetLeft + 'px';
        underline.style.width = btnCat.offsetWidth + 'px';
    }
  });
</script>

<!-- Modal : Ajouter un livre -->
<?php if (in_array(getUserRole(), ['admin', 'moderateur'])): ?>
  <div id="addBookModal"
    class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
    role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div id="addBookModalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
        onclick="toggleModal('addBookModal', false)"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div id="addBookModalContent"
        class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:w-full sm:max-w-4xl border border-border font-body">

        <div class="px-6 pt-6 pb-4 sm:px-8">
          <div class="flex items-center justify-between">
            <h3 id="addBookModalTitle" class="text-xl font-body font-bold tracking-tight text-ink">Ajouter un livre</h3>
            <button type="button" onclick="toggleModal('addBookModal', false)"
              class="text-muted hover:text-ink transition-colors">
              <i class="ph ph-x text-2xl"></i>
            </button>
          </div>
        </div>

        <form action="<?= BASE_URL ?>/books" method="POST" enctype="multipart/form-data"
          class="px-6 pb-6 sm:px-8 sm:pb-8">
          <input type="hidden" name="action" id="bookAction" value="add_book">
          <input type="hidden" name="book_id" id="bookIdField">
          <input type="hidden" name="author_name" id="authorNameHidden">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-1">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">
                Titre <span class="text-accent">*</span>
              </label>
              <input type="text" name="titre" required placeholder="Titre du livre"
                class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">
                Auteur <span class="text-accent">*</span>
              </label>
              <div class="relative" id="authorComboWrapper">
                <input type="text" id="authorComboInput" autocomplete="off" placeholder="Choisir ou créer un auteur..."
                  class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition cursor-pointer">
                <div id="authorDropdown"
                  class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-border rounded-xl shadow-lg max-h-48 overflow-y-auto">
                </div>
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">Genre</label>
              <input type="text" name="genre" placeholder="Roman, Sci-Fi..."
                class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">Couverture</label>
              <input type="file" name="cover" id="coverInput"
                accept=".jpg,.jpeg,.png,.webp"
                class="w-full px-4 py-2.5 bg-white border border-border rounded-2xl text-sm text-ink file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-ink file:text-cream hover:file:bg-stone-800 cursor-pointer transition">
              <div id="stagedCover" class="hidden text-xs text-accent font-medium mt-1"></div>
              <div id="currentCoverInfo" class="hidden text-xs text-accent font-medium mt-1"></div>
            </div>

            <div class="space-y-1 md:col-span-2">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">Description</label>
              <textarea name="description" rows="3" placeholder="Résumé..."
                class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition resize-none"></textarea>
            </div>

            <div class="space-y-1 md:col-span-2">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">
                Livre PDF (Principal) <span class="text-accent">*</span>
              </label>
              <input type="file" name="pdf" required accept=".pdf"
                class="w-full px-4 py-2.5 bg-white border border-border rounded-2xl text-sm text-ink file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-ink file:text-cream hover:file:bg-stone-800 cursor-pointer transition">
              <p id="pdfHint" class="text-xs text-muted mb-2">max 32 Mo &bull; Détection auto des pages</p>
              <div id="stagedPdf" class="hidden text-xs text-accent font-medium mt-1"></div>
              <div id="currentPdfInfo" class="hidden text-xs text-accent font-medium mt-1"></div>
            </div>

            <div class="space-y-1 md:col-span-2">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">Nombre de pages</label>
              <input type="number" name="nb_pages" id="nbPagesField" min="0" placeholder="Auto-détecté"
                class="w-full px-4 py-3 bg-white border border-border rounded-2xl text-sm text-ink focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/10 transition">
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="block text-xs font-medium text-ink uppercase tracking-widest">Ressources compl.</label>
              <input type="file" name="resources[]" multiple id="resourcesInput"
                class="w-full px-4 py-2.5 bg-white border border-border rounded-2xl text-sm text-ink file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-ink file:text-cream hover:file:bg-stone-800 cursor-pointer transition">
              <div id="stagedResources" class="hidden space-y-1"></div>
            </div>

            <div id="resourcesSection" class="hidden space-y-1 md:col-span-2 pt-4 border-t border-border/50">
              <div id="resourcesList" class="space-y-1"></div>
              <div id="deleteResourcesContainer"></div>
            </div>
          </div>

          <div class="pt-4 flex items-center gap-3 justify-end">
            <button type="button" onclick="toggleModal('addBookModal', false)"
              class="px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">
              Annuler
            </button>
            <button type="submit" id="addBookSubmitBtn"
              class="px-4 py-2 bg-ink text-cream text-sm font-medium rounded-xl hover:bg-stone-800 transition-all shadow-sm">
              Ajouter le livre
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Confirmation Modal -->
<div id="deleteBookModal"
  class="fixed inset-0 z-50 overflow-y-auto opacity-0 pointer-events-none transition-all duration-300 ease-out"
  role="dialog" aria-modal="true">
  <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
    <div id="deleteBookModalOverlay" class="fixed inset-0 bg-stone-900/0 transition-all duration-300 ease-out"
      onclick="toggleModal('deleteBookModal', false)"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
    <div id="deleteBookModalContent"
      class="inline-block align-bottom bg-cream rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all duration-300 ease-out opacity-0 scale-95 sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-border font-body">
      <div class="px-6 py-6 sm:px-8 text-center">
        <h3 class="text-xl font-body font-bold text-ink mb-2">Confirmer la suppression</h3>
        <p class="text-sm text-muted mb-6">Êtes-vous sûr de vouloir supprimer <span id="deleteBookTitle" class="font-bold text-ink"></span> ?</p>
        <form action="<?= BASE_URL ?>/books" method="POST" class="flex items-center gap-3">
          <input type="hidden" name="action" value="delete_book">
          <input type="hidden" name="book_id" id="deleteBookId">
          <button type="button" onclick="toggleModal('deleteBookModal', false)"
            class="flex-1 px-4 py-2 border border-border text-muted text-sm font-medium rounded-xl hover:bg-stone-50 transition-all">Annuler</button>
          <button type="submit"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition-all shadow-sm">Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  const authorsList = <?= json_encode(array_column($authors, 'nom')) ?>;

  document.addEventListener('DOMContentLoaded', function () {

    function handleFileStaging(input, containerId, isMultiple = false) {
      const container = document.getElementById(containerId);
      if (!container) return;
      let selectedFiles = [];

      input.addEventListener('change', function () {
        if (isMultiple) {
          const newFiles = Array.from(this.files);
          newFiles.forEach(file => {
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
              selectedFiles.push(file);
            }
          });
          syncInput();
        } else {
          selectedFiles = Array.from(this.files);
        }
        renderStagedFiles();
      });

      function syncInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
      }

      function renderStagedFiles() {
        container.innerHTML = '';
        if (selectedFiles.length > 0) {
          container.classList.remove('hidden');
          selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'text-[11px] font-medium text-accent group flex items-center gap-2';
            item.innerHTML = `${file.name} <button type="button" class="text-red-500"><i class="ph ph-x-circle"></i></button>`;
            item.querySelector('button').onclick = () => removeStagedFile(index);
            container.appendChild(item);
          });
        } else { container.classList.add('hidden'); }
      }

      function removeStagedFile(index) {
        selectedFiles.splice(index, 1);
        syncInput();
        renderStagedFiles();
      }
    }

    // Filtrage recherche
    const searchInput = document.getElementById('bookSearch');
    const noResults = document.getElementById('noSearchResults');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        let visibleCount = 0;
        const activeGrid = document.querySelector('.tab-content:not(.hidden)');
        if (!activeGrid) return;

        activeGrid.querySelectorAll('.book-card').forEach(card => {
          const title = (card.dataset.searchTitle || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
          const author = (card.dataset.searchAuthor || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
          const isMatch = !q || title.includes(q) || author.includes(q);
          card.classList.toggle('hidden', !isMatch);
          if (isMatch) visibleCount++;
        });

        if (noResults) {
          if (visibleCount === 0 && q !== '') {
            noResults.classList.remove('hidden');
            activeGrid.querySelector('.grid').classList.add('hidden');
          } else {
            noResults.classList.add('hidden');
            const gridInner = activeGrid.querySelector('.grid');
            if (gridInner) gridInner.classList.remove('hidden');
          }
        }
      });
    }

    // Combobox auteur
    const comboInput = document.getElementById('authorComboInput');
    const dropdown = document.getElementById('authorDropdown');
    const authorHidden = document.getElementById('authorNameHidden');
    if (comboInput) {
      function renderDropdown(q) {
        const filtered = q ? authorsList.filter(n => n.toLowerCase().includes(q.toLowerCase())) : authorsList;
        dropdown.innerHTML = '';
        filtered.forEach(nom => {
          const d = document.createElement('div');
          d.className = 'px-4 py-2 text-sm text-ink hover:bg-stone-100 cursor-pointer';
          d.textContent = nom;
          d.onmousedown = () => { comboInput.value = nom; authorHidden.value = nom; dropdown.classList.add('hidden'); };
          dropdown.appendChild(d);
        });
      }
      comboInput.onfocus = () => renderDropdown(comboInput.value.trim());
      comboInput.oninput = () => renderDropdown(comboInput.value.trim());
      comboInput.onblur = () => setTimeout(() => dropdown.classList.add('hidden'), 150);
    }

    const coverInput = document.getElementById('coverInput');
    const resourcesInput = document.getElementById('resourcesInput');
    const pdfInput = document.querySelector('input[name="pdf"]');
    if (coverInput) handleFileStaging(coverInput, 'stagedCover');
    if (resourcesInput) handleFileStaging(resourcesInput, 'stagedResources', true);
    if (pdfInput) handleFileStaging(pdfInput, 'stagedPdf', false);

    window.openEditModal = function (data) {
      const form = document.querySelector('#addBookModal form');
      if (!form) return;
      document.getElementById('addBookModalTitle').textContent = 'Modifier le livre';
      document.getElementById('bookAction').value = 'update_book';
      document.getElementById('bookIdField').value = data.id;
      document.getElementById('addBookSubmitBtn').textContent = 'Enregistrer';
      form.titre.value = data.title || '';
      form.genre.value = data.genre || '';
      form.description.value = data.description || '';
      if (document.getElementById('authorComboInput')) document.getElementById('authorComboInput').value = data.author || '';
      if (document.getElementById('authorNameHidden')) document.getElementById('authorNameHidden').value = data.author || '';
      if (document.getElementById('nbPagesField')) document.getElementById('nbPagesField').value = data.pages || '';
      if (form.pdf) form.pdf.required = false;
      toggleModal('addBookModal', true);
    };

    window.openDeleteBookModal = function (id, title) {
      document.getElementById('deleteBookId').value = id;
      document.getElementById('deleteBookTitle').textContent = title;
      toggleModal('deleteBookModal', true);
    };

    window.resetAddBookModal = function () {
      const form = document.querySelector('#addBookModal form');
      if (form) form.reset();
      document.getElementById('addBookModalTitle').textContent = 'Ajouter un livre';
      document.getElementById('bookAction').value = 'add_book';
      document.getElementById('bookIdField').value = '';
      if (form.pdf) form.pdf.required = true;
    };
  });
</script>

<?php 
$hideFooterContent = true;
include __DIR__ . '/../../includes/layout/footer.php'; 
?>