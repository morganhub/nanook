<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Catégories';
$activeMenu = 'categories';
require __DIR__ . '/_header.php';
?>


<div class="page">
    <div class="page-header">
        <div class="title"><span class="brand">NANOOK</span> · Catégories</div>
        <a href="/admin/products.php" class="back-link">&larr; Retour produits</a>
    </div>

    <div class="card">
        <div id="message" class="message" style="display:none;"></div>

        <div class="actions">
            <button type="button" class="btn-primary" id="addCategoryButton">Ajouter une catégorie</button>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th>Parent</th>
                <th>Ordre</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="categoriesTableBody"></tbody>
        </table>
    </div>
</div>

<div class="overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Catégorie</div>
            <button type="button" class="modal-close" id="modalCloseButton">&times;</button>
        </div>
        <div id="modalMessage" class="message" style="display:none;"></div>
        <form id="categoryForm">
            <input type="hidden" id="categoryId">
            <label for="categoryNameInput">Nom</label>
            <input type="text" id="categoryNameInput" required>

            <label for="categorySlugInput">Slug</label>
            <input type="text" id="categorySlugInput" required>

            <label for="categoryParentInput">Parent</label>
            <select id="categoryParentInput">
                <option value="">Aucun (racine)</option>
            </select>

            <label for="categoryOrderInput">Ordre d’affichage</label>
            <input type="number" id="categoryOrderInput" value="0">

            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="modalCancelButton">Annuler</button>
                <button type="submit" class="btn-primary" id="modalSaveButton">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    const apiBaseUrl = '/admin/api';

    const messageEl = document.getElementById('message');
    const categoriesTableBody = document.getElementById('categoriesTableBody');
    const addCategoryButton = document.getElementById('addCategoryButton');

    const modalOverlay = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalCloseButton = document.getElementById('modalCloseButton');
    const modalCancelButton = document.getElementById('modalCancelButton');
    const modalSaveButton = document.getElementById('modalSaveButton');
    const modalMessage = document.getElementById('modalMessage');
    const categoryForm = document.getElementById('categoryForm');

    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryNameInput');
    const categorySlugInput = document.getElementById('categorySlugInput');
    const categoryParentInput = document.getElementById('categoryParentInput');
    const categoryOrderInput = document.getElementById('categoryOrderInput');

    let categoriesCache = [];

    function showMessage(text, type = 'success') {
        if (!text) {
            messageEl.style.display = 'none';
            messageEl.textContent = '';
            messageEl.className = 'message';
            return;
        }
        messageEl.textContent = text;
        messageEl.className = 'message ' + type;
        messageEl.style.display = 'block';
    }

    function showModalMessage(text, type = 'error') {
        if (!text) {
            modalMessage.style.display = 'none';
            modalMessage.textContent = '';
            modalMessage.className = 'message';
            return;
        }
        modalMessage.textContent = text;
        modalMessage.className = 'message ' + type;
        modalMessage.style.display = 'block';
    }

    function openModal(category = null) {
        showModalMessage('', 'error');
        if (category) {
            modalTitle.textContent = 'Modifier la catégorie';
            categoryIdInput.value = category.id;
            categoryNameInput.value = category.name;
            categorySlugInput.value = category.slug;
            categoryOrderInput.value = category.display_order ?? 0;
            categoryParentInput.value = category.parent_id ? String(category.parent_id) : '';
        } else {
            modalTitle.textContent = 'Ajouter une catégorie';
            categoryIdInput.value = '';
            categoryNameInput.value = '';
            categorySlugInput.value = '';
            categoryOrderInput.value = '0';
            categoryParentInput.value = '';
        }
        modalOverlay.classList.add('active');
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
    }

    function slugify(value) {
        let text = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        text = text.toLowerCase();
        text = text.replace(/[^a-z0-9]+/g, '-');
        text = text.replace(/^-+|-+$/g, '');
        return text;
    }

    categoryNameInput.addEventListener('input', () => {
        if (!categorySlugInput.value) {
            categorySlugInput.value = slugify(categoryNameInput.value);
        }
    });

    modalCloseButton.addEventListener('click', () => closeModal());
    modalCancelButton.addEventListener('click', () => closeModal());

    modalOverlay.addEventListener('click', (event) => {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });

    async function ensureAuthenticated() {
        try {
            const res = await fetch(`${apiBaseUrl}/me.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.authenticated) {
                window.location.href = '/admin/index.php';
            }
        } catch (error) {
            console.error(error);
            window.location.href = '/admin/index.php';
        }
    }

    function renderParentOptions() {
        categoryParentInput.innerHTML = '<option value="">Aucun (racine)</option>';
        for (let cat of categoriesCache) {
            const option = document.createElement('option');
            option.value = String(cat.id);
            option.textContent = cat.name;
            categoryParentInput.appendChild(option);
        }
    }

    function renderCategories() {
        categoriesTableBody.innerHTML = '';
        if (!categoriesCache.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 6;
            td.textContent = 'Aucune catégorie.';
            td.style.fontSize = '13px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            categoriesTableBody.appendChild(tr);
            return;
        }

        for (let cat of categoriesCache) {
            const tr = document.createElement('tr');

            const parentCat = categoriesCache.find(c => c.id === cat.parent_id);

            let tdId = document.createElement('td');
            tdId.textContent = cat.id;
            tr.appendChild(tdId);

            let tdName = document.createElement('td');
            tdName.textContent = cat.name;
            tr.appendChild(tdName);

            let tdSlug = document.createElement('td');
            tdSlug.textContent = cat.slug;
            tr.appendChild(tdSlug);

            let tdParent = document.createElement('td');
            tdParent.textContent = parentCat ? parentCat.name : '-';
            tr.appendChild(tdParent);

            let tdOrder = document.createElement('td');
            tdOrder.textContent = cat.display_order ?? 0;
            tr.appendChild(tdOrder);

            let tdActions = document.createElement('td');
            let editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn-secondary';
            editBtn.textContent = 'Éditer';
            editBtn.addEventListener('click', () => openModal(cat));

            let deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn-danger';
            deleteBtn.style.marginLeft = '6px';
            deleteBtn.textContent = 'Supprimer';
            deleteBtn.addEventListener('click', () => confirmDeleteCategory(cat));

            tdActions.appendChild(editBtn);
            tdActions.appendChild(deleteBtn);
            tr.appendChild(tdActions);

            categoriesTableBody.appendChild(tr);
        }
    }

    async function loadCategories() {
        try {
            const res = await fetch(`${apiBaseUrl}/categories/list.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                showMessage('Erreur lors du chargement des catégories.', 'error');
                return;
            }
            categoriesCache = data.data.map(cat => ({
                id: cat.id,
                name: cat.name,
                slug: cat.slug,
                parent_id: cat.parent_id ?? null,
                display_order: cat.display_order ?? 0
            }));
            renderParentOptions();
            renderCategories();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function saveCategory(event) {
        event.preventDefault();
        showModalMessage('', 'error');
        modalSaveButton.disabled = true;

        const id = categoryIdInput.value ? parseInt(categoryIdInput.value, 10) : 0;
        const payload = {
            id: id || undefined,
            name: categoryNameInput.value.trim(),
            slug: categorySlugInput.value.trim(),
            parent_id: categoryParentInput.value ? parseInt(categoryParentInput.value, 10) : null,
            display_order: parseInt(categoryOrderInput.value, 10) || 0
        };

        if (!payload.name || !payload.slug) {
            showModalMessage('Le nom et le slug sont obligatoires.', 'error');
            modalSaveButton.disabled = false;
            return;
        }

        try {
            const res = await fetch(`${apiBaseUrl}/categories/save.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showModalMessage('Erreur lors de l’enregistrement de la catégorie.', 'error');
                modalSaveButton.disabled = false;
                return;
            }
            closeModal();
            showMessage('Catégorie enregistrée.', 'success');
            await loadCategories();
        } catch (error) {
            console.error(error);
            showModalMessage('Erreur de communication avec le serveur.', 'error');
        } finally {
            modalSaveButton.disabled = false;
        }
    }

    async function confirmDeleteCategory(category) {
        if (!window.confirm('Supprimer la catégorie "' + category.name + '" ?')) {
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/categories/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({id: category.id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la suppression.', 'error');
                return;
            }
            showMessage('Catégorie supprimée.', 'success');
            await loadCategories();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    addCategoryButton.addEventListener('click', () => openModal(null));
    categoryForm.addEventListener('submit', saveCategory);

    (async function init() {
        await ensureAuthenticated();
        await loadCategories();
    })();
</script>
<?php
require __DIR__ . '/_footer.php';
