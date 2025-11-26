<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Produits';
$activeMenu = 'products';
require __DIR__ . '/_header.php';
?>
    <style>

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

        /* Styles spécifiques pour la hiérarchie */
        tr.is-variant td {
            background-color: #fafafa;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        tr.is-variant td:first-child {
            border-left: 3px solid #ddd; /* Ligne visuelle */
        }
        .variant-indent {
            padding-left: 20px;
            position: relative;
        }
        .variant-indent::before {
            content: "↳";
            position: absolute;
            left: 0;
            color: #999;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .categories-list { font-size: 12px; color: #4b5563; }

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
        .message { margin-bottom: 8px; font-size: 13px; }
        .message.error { color: #b91c1c; }
        .message.info { color: #4b5563; }
        a.link { color: #111827; text-decoration: none; }
        a.link:hover { text-decoration: underline; }
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
                <th>Nom / Variantes</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Statut</th>
                <th>Catégories</th>
                <th>Date Création</th>
                <th>Voir</th>
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
            if (price === null) return '—';
            return price.toFixed(2) + '&nbsp;€';
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
                const hasVariants = (p.variants && p.variants.length > 0);

                // 1. Ligne Parent
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
                if(hasVariants) {
                    const vCount = document.createElement('span');
                    vCount.style.fontSize = '11px'; vCount.style.color = '#666'; vCount.style.marginLeft = '5px';
                    vCount.innerHTML = `<br/>${p.variants.length} variantes`;
                    tdName.appendChild(vCount);
                }
                const slugSpan = document.createElement('div');
                slugSpan.style.fontSize = '11px';
                slugSpan.style.color = '#6b7280';
                // slugSpan.innerHTML = p.slug;
                tdName.appendChild(slugSpan);
                tr.appendChild(tdName);

                // Prix
                const tdPrice = document.createElement('td');
                if(hasVariants) {
                    tdPrice.innerHTML = '—'; // Prix géré par variante
                } else {
                    tdPrice.innerHTML = formatPrice(p.price);
                }
                tr.appendChild(tdPrice);

                // Stock
                const tdStock = document.createElement('td');
                if(hasVariants) {
                    tdStock.textContent = '—'; // Stock géré par variante
                } else {
                    tdStock.textContent = p.stock_quantity;
                    if (p.stock_quantity <= 0) {
                        if (p.allow_preorder_when_oos) {
                            const badge = document.createElement('span'); badge.className = 'badge badge-yellow'; badge.textContent = 'Précommande'; badge.style.marginLeft = '4px'; tdStock.appendChild(badge);
                        } else {
                            const badge = document.createElement('span'); badge.className = 'badge badge-red'; badge.textContent = 'Rupture'; badge.style.marginLeft = '4px'; tdStock.appendChild(badge);
                        }
                    }
                }
                tr.appendChild(tdStock);

                // Statut
                const tdStatus = document.createElement('td');
                const badge = document.createElement('span');
                if (p.is_active) { badge.className = 'badge badge-green'; badge.textContent = 'Actif'; }
                else { badge.className = 'badge badge-red'; badge.textContent = 'Inactif'; }
                tdStatus.appendChild(badge);
                tr.appendChild(tdStatus);

                // Catégories
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
                tdCreated.textContent = p.created_at ? p.created_at.split(' ')[0] : '';
                tr.appendChild(tdCreated);

                // --- Nouvelle Colonne VOIR ---
                const tdView = document.createElement('td');
                const viewLink = document.createElement('a');
                viewLink.href = `/p/${p.slug}`;
                viewLink.target = '_blank';
                viewLink.className = 'btn-icon';
                viewLink.title = 'Voir sur le site';
                // Icône Oeil SVG
                viewLink.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
                tdView.appendChild(viewLink);
                tr.appendChild(tdView);

                const tdActions = document.createElement('td');
                const editLink = document.createElement('a');
                editLink.href = `/admin/product_form.php?id=${encodeURIComponent(p.id)}`;
                editLink.textContent = 'Modifier';
                editLink.className = 'link';
                tdActions.appendChild(editLink);
                tr.appendChild(tdActions);

                productsTableBody.appendChild(tr);

                // 2. Lignes Variantes (le cas échéant)
                if (hasVariants) {
                    for (let v of p.variants) {
                        const trV = document.createElement('tr');
                        trV.className = 'is-variant';

                        const tdIdV = document.createElement('td'); // ID vide ou tiret
                        trV.appendChild(tdIdV);

                        const tdNameV = document.createElement('td');
                        const divIndent = document.createElement('div');
                        divIndent.className = 'variant-indent';
                        divIndent.textContent = v.name;
                        tdNameV.appendChild(divIndent);
                        trV.appendChild(tdNameV);

                        const tdPriceV = document.createElement('td');
                        // Si prix null, prend prix du parent (mais ici on affiche le prix spécifique ou 'Parent')
                        tdPriceV.innerHTML = v.price !== null ? formatPrice(v.price) : formatPrice(p.price) + ' (P)';
                        trV.appendChild(tdPriceV);

                        const tdStockV = document.createElement('td');
                        tdStockV.textContent = v.stock_quantity;
                        if (v.stock_quantity <= 0) {
                            if (v.allow_preorder_when_oos) {
                                const b = document.createElement('span'); b.className = 'badge badge-yellow'; b.textContent = 'Préco'; b.style.marginLeft = '4px'; tdStockV.appendChild(b);
                            } else {
                                const b = document.createElement('span'); b.className = 'badge badge-red'; b.textContent = 'Rupture'; b.style.marginLeft = '4px'; tdStockV.appendChild(b);
                            }
                        }
                        trV.appendChild(tdStockV);

                        const tdStatusV = document.createElement('td');
                        const bStat = document.createElement('span');
                        if (v.is_active) { bStat.className = ''; bStat.textContent = ''; }
                        else { bStat.className = 'badge badge-red'; bStat.textContent = 'OFF'; }
                        tdStatusV.appendChild(bStat);
                        trV.appendChild(tdStatusV);

                        // Col vides pour alignement
                        trV.appendChild(document.createElement('td')); // Cats
                        trV.appendChild(document.createElement('td')); // Date
                        trV.appendChild(document.createElement('td')); // Actions (on peut mettre un lien d'edit direct vers le parent)

                        productsTableBody.appendChild(trV);
                    }
                }
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