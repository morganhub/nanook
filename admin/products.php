<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Produits';
$activeMenu = 'products';
require __DIR__ . '/_header.php';
?>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 16px;
        }
        .page {
            max-width: 1080px;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .title {
            font-size: 20px;
            font-weight: 600;
        }
        .brand {
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        .filters input[type="text"] {
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            min-width: 220px;
        }
        .filters select {
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            min-width: 180px;
        }
        .filters button {
            padding: 8px 12px;
            border-radius: 4px;
            border: none;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary {
            padding: 8px 14px;
            border-radius: 4px;
            border: none;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-secondary {
            padding: 8px 14px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary[disabled],
        .btn-secondary[disabled] {
            opacity: 0.6;
            cursor: default;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: #f9fafb;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
        }
        th {
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            border-bottom: 1px solid #f3f4f6;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-green {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-red {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }
        .categories-list {
            font-size: 12px;
            color: #4b5563;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 13px;
        }
        .pagination-controls {
            display: flex;
            gap: 6px;
        }
        .message {
            margin-bottom: 8px;
            font-size: 13px;
        }
        .message.error {
            color: #b91c1c;
        }
        .message.info {
            color: #4b5563;
        }
        a.link {
            color: #111827;
            text-decoration: none;
        }
        a.link:hover {
            text-decoration: underline;
        }
        @media (max-width: 720px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

<div class="page">
    <div class="page-header">
        <div>
            <div class="title"><span class="brand">NANOOK</span> · Produits</div>
        </div>
        <div>
            <button class="btn-primary" id="addProductButton">Ajouter un produit</button>
        </div>
    </div>

    <div id="message" class="message info" style="display:none;"></div>

    <div class="filters">
        <input type="text" id="searchInput" placeholder="Recherche par nom ou slug…">
        <select id="categoryFilter">
            <option value="">Toutes les catégories</option>
        </select>
        <button id="searchButton">Filtrer</button>
    </div>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Statut</th>
            <th>Catégories</th>
            <th>Créé le</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="productsTableBody">
        </tbody>
    </table>

    <div class="pagination">
        <div id="paginationInfo"></div>
        <div class="pagination-controls">
            <button class="btn-secondary" id="prevPageButton">&larr; Précédent</button>
            <button class="btn-secondary" id="nextPageButton">Suivant &rarr;</button>
        </div>
    </div>
</div>

<script>
    const apiBaseUrl = '/admin/api';

    const messageEl = document.getElementById('message');
    const productsTableBody = document.getElementById('productsTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const prevPageButton = document.getElementById('prevPageButton');
    const nextPageButton = document.getElementById('nextPageButton');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const searchButton = document.getElementById('searchButton');
    const addProductButton = document.getElementById('addProductButton');

    let currentPage = 1;
    let totalPages = 1;
    let currentQuery = '';
    let currentCategoryId = '';

    function showMessage(text, type = 'info') {
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

    async function loadCategories() {
        try {
            const res = await fetch(`${apiBaseUrl}/categories/list.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                return;
            }
            const categories = data.data;
            categoryFilter.innerHTML = '<option value="">Toutes les catégories</option>';
            for (let cat of categories) {
                const option = document.createElement('option');
                option.value = String(cat.id);
                option.textContent = cat.name;
                categoryFilter.appendChild(option);
            }
        } catch (error) {
            console.error(error);
        }
    }

    function formatPrice(price) {
        const euros = price
        return euros + ' €';
    }

    function renderProducts(items) {
        productsTableBody.innerHTML = '';
        if (!items || !items.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 8;
            td.textContent = 'Aucun produit trouvé.';
            td.style.fontSize = '13px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            productsTableBody.appendChild(tr);
            return;
        }

        for (let p of items) {
            const tr = document.createElement('tr');

            const tdId = document.createElement('td');
            tdId.textContent = p.id;
            tr.appendChild(tdId);

            const tdName = document.createElement('td');
            const nameLink = document.createElement('a');
            nameLink.href = `/admin/product_form.php?id=${encodeURIComponent(p.id)}`;
            nameLink.textContent = p.name;
            nameLink.className = 'link';
            tdName.appendChild(nameLink);
            const slugSpan = document.createElement('div');
            slugSpan.style.fontSize = '11px';
            slugSpan.style.color = '#6b7280';
            slugSpan.textContent = p.slug;
            tdName.appendChild(slugSpan);
            tr.appendChild(tdName);

            const tdPrice = document.createElement('td');
            tdPrice.textContent = formatPrice(p.price);
            tr.appendChild(tdPrice);

            const tdStock = document.createElement('td');
            tdStock.textContent = p.stock_quantity;
            if (p.stock_quantity === 0) {
                if (p.allow_preorder_when_oos) {
                    const badge = document.createElement('span');
                    badge.className = 'badge badge-yellow';
                    badge.textContent = 'Précommande';
                    badge.style.marginLeft = '4px';
                    tdStock.appendChild(badge);
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'badge badge-red';
                    badge.textContent = 'Rupture';
                    badge.style.marginLeft = '4px';
                    tdStock.appendChild(badge);
                }
            }
            tr.appendChild(tdStock);

            const tdStatus = document.createElement('td');
            const badge = document.createElement('span');
            if (p.is_active) {
                badge.className = 'badge badge-green';
                badge.textContent = 'Actif';
            } else {
                badge.className = 'badge badge-red';
                badge.textContent = 'Inactif';
            }
            tdStatus.appendChild(badge);
            tr.appendChild(tdStatus);

            const tdCategories = document.createElement('td');
            const cats = p.categories || [];
            if (cats.length) {
                const listSpan = document.createElement('span');
                listSpan.className = 'categories-list';
                listSpan.textContent = cats.map(c => c.name).join(', ');
                tdCategories.appendChild(listSpan);
            } else {
                tdCategories.textContent = '-';
            }
            tr.appendChild(tdCategories);

            const tdCreated = document.createElement('td');
            tdCreated.textContent = p.created_at ?? '';
            tr.appendChild(tdCreated);

            const tdActions = document.createElement('td');
            const editLink = document.createElement('a');
            editLink.href = `/admin/product_form.php?id=${encodeURIComponent(p.id)}`;
            editLink.textContent = 'Modifier';
            editLink.className = 'link';
            tdActions.appendChild(editLink);
            tr.appendChild(tdActions);

            productsTableBody.appendChild(tr);
        }
    }

    async function loadProducts(page = 1) {
        try {
            const params = new URLSearchParams();
            params.set('page', String(page));
            params.set('per_page', '20');
            if (currentQuery) {
                params.set('q', currentQuery);
            }
            if (currentCategoryId) {
                params.set('category_id', currentCategoryId);
            }

            showMessage('Chargement des produits…', 'info');

            const res = await fetch(`${apiBaseUrl}/products/list.php?` + params.toString(), {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();

            if (!data.success) {
                showMessage('Erreur lors du chargement des produits.', 'error');
                return;
            }

            const payload = data.data;
            renderProducts(payload.items);

            currentPage = payload.pagination.page;
            totalPages = payload.pagination.total_pages;

            paginationInfo.textContent =
                `Page ${payload.pagination.page} / ${payload.pagination.total_pages} · ` +
                `${payload.pagination.total_items} produit(s)`;

            prevPageButton.disabled = currentPage <= 1;
            nextPageButton.disabled = currentPage >= totalPages;

            if (!payload.items.length) {
                showMessage('Aucun produit ne correspond aux filtres.', 'info');
            } else {
                showMessage('', 'info');
            }
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    searchButton.addEventListener('click', () => {
        currentQuery = searchInput.value.trim();
        currentCategoryId = categoryFilter.value;
        currentPage = 1;
        loadProducts(currentPage);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchButton.click();
        }
    });

    categoryFilter.addEventListener('change', () => {
        currentQuery = searchInput.value.trim();
        currentCategoryId = categoryFilter.value;
        currentPage = 1;
        loadProducts(currentPage);
    });

    prevPageButton.addEventListener('click', () => {
        if (currentPage > 1) {
            loadProducts(currentPage - 1);
        }
    });

    nextPageButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            loadProducts(currentPage + 1);
        }
    });

    addProductButton.addEventListener('click', () => {
        window.location.href = '/admin/product_form.php';
    });

    (async function init() {
        await ensureAuthenticated();
        await loadCategories();
        await loadProducts(1);
    })();
</script>
<?php
require __DIR__ . '/_footer.php';