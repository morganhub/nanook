<?php

declare(strict_types=1);

$pageTitle = 'Produits';
$activeMenu = 'products';
require __DIR__ . '/_header.php';
?>
    <style>
        .filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .filters input[type="text"] { padding: 8px 10px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 14px; min-width: 220px; }
        .filters select { padding: 8px 10px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 14px; min-width: 180px; }
        .filters button { padding: 8px 12px; border-radius: 4px; border: none; background: #111827; color: #ffffff; font-size: 14px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 8px; overflow: hidden; }
        thead { background: #f9fafb; }
        th, td { padding: 8px 10px; text-align: left; font-size: 13px; }
        th { font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        td { border-bottom: 1px solid #f3f4f6; }
        tr:last-child td { border-bottom: none; }

        
        tr.is-variant td { background-color: #fafafa; color: #555; border-bottom: 1px solid #eee; }
        tr.is-variant td:first-child { border-left: 3px solid #ddd; }
        .variant-indent { padding-left: 20px; position: relative; font-size: 12px; }
        .variant-indent::before { content: "↳"; position: absolute; left: 5px; color: #999; }

        .categories-list { font-size: 12px; color: #4b5563; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 13px; }
        .pagination-controls { display: flex; gap: 6px; }
        a.link { color: #111827; text-decoration: none; }
        a.link:hover { text-decoration: underline; }
        a.btn-icon { color: #666; } a.btn-icon:hover { color: #111; }
    </style>

    <div class="page">
        <div class="page-header">
            <div><div class="title"><span class="brand">NANOOK</span> · Produits</div></div>
            <div><button class="btn-primary" id="addProductButton">Ajouter un produit</button></div>
        </div>

        <div id="message" class="message info" style="display:none;"></div>

        <div class="filters">
            <input type="text" id="searchInput" placeholder="Recherche par nom ou slug…">
            <select id="categoryFilter"><option value="">Toutes les catégories</option></select>
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
            <tbody id="productsTableBody"></tbody>
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
            if (!text) { messageEl.style.display = 'none'; return; }
            messageEl.textContent = text;
            messageEl.className = 'message ' + type;
            messageEl.style.display = 'block';
        }

        async function ensureAuthenticated() {
            try {
                const res = await fetch(`${apiBaseUrl}/me.php`);
                const data = await res.json();
                if (!data.authenticated) window.location.href = '/admin/index.php';
            } catch (error) { window.location.href = '/admin/index.php'; }
        }

        async function loadCategories() {
            try {
                const res = await fetch(`${apiBaseUrl}/categories/list.php`);
                const data = await res.json();
                if (!data.success) return;
                categoryFilter.innerHTML = '<option value="">Toutes les catégories</option>';
                data.data.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = String(cat.id);
                    option.textContent = cat.name;
                    categoryFilter.appendChild(option);
                });
            } catch (error) { console.error(error); }
        }

        function formatPrice(price) {
            if (price === null) return '—';
            return price.toFixed(2) + '&nbsp;€';
        }

        function renderProducts(items) {
            productsTableBody.innerHTML = '';
            if (!items || !items.length) {
                productsTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center; color:#6b7280;">Aucun produit trouvé.</td></tr>';
                return;
            }

            for (let p of items) {
                const hasVariants = (p.variants && p.variants.length > 0);

                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${p.id}</td>
                    <td>
                        <a href="/admin/product_form.php?id=${p.id}" class="link" style="font-weight:600;">${p.name}</a>
                        ${hasVariants ? `<span style="font-size:11px; color:#666; margin-left:5px;">(${p.variants.length} variantes)</span>` : ''}
                        <div style="font-size:11px; color:#9ca3af;">${p.slug}</div>
                    </td>
                    <td>${hasVariants ? '—' : formatPrice(p.price)}</td>
                    <td>${renderStock(p.stock_quantity, p.allow_preorder_when_oos, hasVariants)}</td>
                    <td>${renderStatus(p.is_active)}</td>
                    <td>${renderCats(p.categories)}</td>
                    <td>${p.created_at ? p.created_at.split(' ')[0] : ''}</td>
                    <td>
                        <a href="/p/${p.slug}" target="_blank" class="btn-icon" title="Voir sur le site">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </a>
                    </td>
                    <td><a href="/admin/product_form.php?id=${p.id}" class="link">Modifier</a></td>
                `;
                productsTableBody.appendChild(tr);

                
                if (hasVariants) {
                    for (let v of p.variants) {
                        const trV = document.createElement('tr');
                        trV.className = 'is-variant';
                        trV.innerHTML = `
                            <td></td>
                            <td><div class="variant-indent">${v.name}</div></td>
                            <td>${v.price !== null ? formatPrice(v.price) : '<span style="color:#999;font-size:11px;">(Parent)</span>'}</td>
                            <td>${renderStock(v.stock_quantity, v.allow_preorder_when_oos, false)}</td>
                            <td>${renderStatus(v.is_active)}</td>
                            <td colspan="4"></td>
                        `;
                        productsTableBody.appendChild(trV);
                    }
                }
            }
        }

        function renderStock(qty, allowPreorder, isParentWithVariants) {
            if(isParentWithVariants) return '—';
            if (qty <= 0) {
                if (allowPreorder) return '<span class="badge badge-yellow">Précommande</span>';
                return '<span class="badge badge-red">Rupture</span>';
            }
            return qty;
        }

        function renderStatus(active) {
            return active
                ? '<span class="badge badge-green">Actif</span>'
                : '<span class="badge badge-red">Inactif</span>';
        }

        function renderCats(cats) {
            if (!cats || !cats.length) return '-';
            return `<span class="categories-list">${cats.map(c => c.name).join(', ')}</span>`;
        }

        async function loadProducts(page = 1) {
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '20' });
                if (currentQuery) params.set('q', currentQuery);
                if (currentCategoryId) params.set('category_id', currentCategoryId);

                showMessage('Chargement...', 'info');
                const res = await fetch(`${apiBaseUrl}/products/list.php?` + params.toString());
                const data = await res.json();

                if (!data.success) { showMessage('Erreur chargement.', 'error'); return; }

                renderProducts(data.data.items);

                const p = data.data.pagination;
                currentPage = p.page;
                totalPages = p.total_pages;
                paginationInfo.textContent = `Page ${p.page} / ${p.total_pages} · ${p.total_items} produit(s)`;
                prevPageButton.disabled = currentPage <= 1;
                nextPageButton.disabled = currentPage >= totalPages;

                showMessage('', 'info');
            } catch (error) {
                console.error(error);
                showMessage('Erreur technique.', 'error');
            }
        }

        searchButton.addEventListener('click', () => {
            currentQuery = searchInput.value.trim();
            currentCategoryId = categoryFilter.value;
            currentPage = 1;
            loadProducts(currentPage);
        });
        searchInput.addEventListener('keydown', (e) => { if(e.key === 'Enter') searchButton.click(); });
        categoryFilter.addEventListener('change', () => {
            currentCategoryId = categoryFilter.value;
            currentQuery = searchInput.value.trim();
            currentPage = 1;
            loadProducts(currentPage);
        });
        prevPageButton.addEventListener('click', () => { if(currentPage > 1) loadProducts(currentPage - 1); });
        nextPageButton.addEventListener('click', () => { if(currentPage < totalPages) loadProducts(currentPage + 1); });
        addProductButton.addEventListener('click', () => window.location.href = '/admin/product_form.php');

        (async function init() {
            await ensureAuthenticated();
            await loadCategories();
            await loadProducts(1);
        })();
    </script>
<?php
require __DIR__ . '/_footer.php';