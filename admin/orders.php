<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Commandes';
$activeMenu = 'orders';
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
            letter-spacing: .04em;
        }
        .back-link {
            font-size: 13px;
            color: #111827;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 16px 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        .filters input[type="text"],
        .filters input[type="date"],
        .filters select {
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }
        .btn-primary,
        .btn-secondary {
            padding: 8px 12px;
            border-radius: 4px;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-primary {
            background: #111827;
            color: #ffffff;
        }
        .btn-secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
        }
        .btn-primary[disabled],
        .btn-secondary[disabled] {
            opacity: .6;
            cursor: default;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        thead {
            background: #f9fafb;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
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
        .message {
            margin-bottom: 8px;
            font-size: 13px;
        }
        .message.error { color: #b91c1c; }
        .message.info { color: #4b5563; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-status-confirmed {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-status-shipped {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge-pref-christmas {
            background: #e0f2fe;
            color: #0369a1;
        }
        .badge-pref-no {
            background: #f3f4f6;
            color: #374151;
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
        a.link {
            color: #111827;
            text-decoration: none;
        }
        a.link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
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


    <div class="card">
        <div id="message" class="message" style="display:none;"></div>

        <div class="filters">
            <input type="text" id="searchInput" placeholder="Recherche (#commande, client, e-mail)…">
            <select id="statusFilter">
                <option value="">Tous statuts</option>
                <option value="pending">En attente</option>
                <option value="confirmed">Confirmée</option>
                <option value="shipped">Expédiée</option>
                <option value="cancelled">Annulée</option>
            </select>
            <select id="shippingPrefFilter">
                <option value="">Toutes livraisons</option>
                <option value="christmas">Pour Noël</option>
                <option value="no_preference">Sans préférence</option>
            </select>
            <input type="date" id="dateFromInput">
            <input type="date" id="dateToInput">
            <button type="button" class="btn-primary" id="applyFiltersButton">Filtrer</button>
        </div>

        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Client</th>
                <th>E-mail</th>
                <th>Ville</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Livraison</th>
                <th>Articles</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="ordersTableBody"></tbody>
        </table>

        <div class="pagination">
            <div id="paginationInfo"></div>
            <div class="pagination-controls">
                <button type="button" class="btn-secondary" id="prevPageButton">&larr; Préc.</button>
                <button type="button" class="btn-secondary" id="nextPageButton">Suiv. &rarr;</button>
            </div>
        </div>
    </div>
</div>

<script>
    let apiBaseUrl = '/admin/api';

    let messageEl = document.getElementById('message');
    let ordersTableBody = document.getElementById('ordersTableBody');
    let paginationInfo = document.getElementById('paginationInfo');
    let prevPageButton = document.getElementById('prevPageButton');
    let nextPageButton = document.getElementById('nextPageButton');

    let searchInput = document.getElementById('searchInput');
    let statusFilter = document.getElementById('statusFilter');
    let shippingPrefFilter = document.getElementById('shippingPrefFilter');
    let dateFromInput = document.getElementById('dateFromInput');
    let dateToInput = document.getElementById('dateToInput');
    let applyFiltersButton = document.getElementById('applyFiltersButton');

    let currentPage = 1;
    let totalPages = 1;
    let currentQuery = '';
    let currentStatus = '';
    let currentShippingPref = '';
    let currentDateFrom = '';
    let currentDateTo = '';

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
            let res = await fetch(apiBaseUrl + '/me.php', {
                method: 'GET',
                credentials: 'include'
            });
            let data = await res.json();
            if (!data.authenticated) {
                window.location.href = '/admin/index.php';
            }
        } catch (error) {
            console.error(error);
            window.location.href = '/admin/index.php';
        }
    }

    function formatPrice(price) {
        let euros = (price).toFixed(2);
        return euros + ' €';
    }

    function statusBadgeClass(status) {
        if (status === 'pending') {
            return 'badge badge-status-pending';
        }
        if (status === 'confirmed') {
            return 'badge badge-status-confirmed';
        }
        if (status === 'shipped') {
            return 'badge badge-status-shipped';
        }
        if (status === 'cancelled') {
            return 'badge badge-status-cancelled';
        }
        return 'badge';
    }

    function statusLabel(status) {
        if (status === 'pending') {
            return 'En attente';
        }
        if (status === 'confirmed') {
            return 'Confirmée';
        }
        if (status === 'shipped') {
            return 'Expédiée';
        }
        if (status === 'cancelled') {
            return 'Annulée';
        }
        return status;
    }

    function shippingPrefLabel(pref) {
        if (pref === 'christmas') {
            return 'Pour Noël';
        }
        if (pref === 'no_preference') {
            return 'Sans préférence';
        }
        return pref;
    }

    function shippingPrefBadgeClass(pref) {
        if (pref === 'christmas') {
            return 'badge badge-pref-christmas';
        }
        if (pref === 'no_preference') {
            return 'badge badge-pref-no';
        }
        return 'badge';
    }

    function renderOrders(items) {
        ordersTableBody.innerHTML = '';
        if (!items || !items.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 10;
            td.textContent = 'Aucune commande trouvée.';
            td.style.fontSize = '13px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            ordersTableBody.appendChild(tr);
            return;
        }

        for (let o of items) {
            let tr = document.createElement('tr');

            let tdNumber = document.createElement('td');
            tdNumber.textContent = o.order_number;
            tr.appendChild(tdNumber);

            let tdDate = document.createElement('td');
            tdDate.textContent = o.created_at || '';
            tr.appendChild(tdDate);

            let tdCustomer = document.createElement('td');
            tdCustomer.textContent = o.customer_first_name + ' ' + o.customer_last_name;
            tr.appendChild(tdCustomer);

            let tdEmail = document.createElement('td');
            tdEmail.textContent = o.customer_email;
            tr.appendChild(tdEmail);

            let tdCity = document.createElement('td');
            tdCity.textContent = (o.shipping_city || '') + (o.shipping_country ? ' (' + o.shipping_country + ')' : '');
            tr.appendChild(tdCity);

            let tdTotal = document.createElement('td');
            tdTotal.textContent = formatPrice(o.total_amount);
            tr.appendChild(tdTotal);

            let tdStatus = document.createElement('td');
            let badgeStatus = document.createElement('span');
            badgeStatus.className = statusBadgeClass(o.status);
            badgeStatus.textContent = statusLabel(o.status);
            tdStatus.appendChild(badgeStatus);
            tr.appendChild(tdStatus);

            let tdPref = document.createElement('td');
            let badgePref = document.createElement('span');
            badgePref.className = shippingPrefBadgeClass(o.shipping_preference);
            badgePref.textContent = shippingPrefLabel(o.shipping_preference);
            tdPref.appendChild(badgePref);
            tr.appendChild(tdPref);

            let tdItems = document.createElement('td');
            tdItems.textContent = o.items_count;
            tr.appendChild(tdItems);

            let tdActions = document.createElement('td');
            let link = document.createElement('a');
            link.href = '/admin/order_detail.php?id=' + encodeURIComponent(o.id);
            link.textContent = 'Détails';
            link.className = 'link';
            tdActions.appendChild(link);
            tr.appendChild(tdActions);

            ordersTableBody.appendChild(tr);
        }
    }

    async function loadOrders(page = 1) {
        try {
            let params = new URLSearchParams();
            params.set('page', String(page));
            params.set('per_page', '20');
            if (currentQuery) {
                params.set('q', currentQuery);
            }
            if (currentStatus) {
                params.set('status', currentStatus);
            }
            if (currentShippingPref) {
                params.set('shipping_preference', currentShippingPref);
            }
            if (currentDateFrom) {
                params.set('date_from', currentDateFrom);
            }
            if (currentDateTo) {
                params.set('date_to', currentDateTo);
            }

            showMessage('Chargement des commandes…', 'info');

            let res = await fetch(apiBaseUrl + '/orders/list.php?' + params.toString(), {
                method: 'GET',
                credentials: 'include'
            });
            let data = await res.json();

            if (!data.success) {
                showMessage('Erreur lors du chargement des commandes.', 'error');
                return;
            }

            let payload = data.data;
            renderOrders(payload.items);

            currentPage = payload.pagination.page;
            totalPages = payload.pagination.total_pages;

            paginationInfo.textContent =
                'Page ' + payload.pagination.page + ' / ' + payload.pagination.total_pages +
                ' · ' + payload.pagination.total_items + ' commande(s)';

            prevPageButton.disabled = currentPage <= 1;
            nextPageButton.disabled = currentPage >= totalPages;

            if (!payload.items.length) {
                showMessage('Aucune commande ne correspond aux filtres.', 'info');
            } else {
                showMessage('', 'info');
            }
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    applyFiltersButton.addEventListener('click', () => {
        currentQuery = searchInput.value.trim();
        currentStatus = statusFilter.value;
        currentShippingPref = shippingPrefFilter.value;
        currentDateFrom = dateFromInput.value;
        currentDateTo = dateToInput.value;
        currentPage = 1;
        loadOrders(currentPage);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyFiltersButton.click();
        }
    });

    prevPageButton.addEventListener('click', () => {
        if (currentPage > 1) {
            loadOrders(currentPage - 1);
        }
    });

    nextPageButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            loadOrders(currentPage + 1);
        }
    });

    (async function init() {
        await ensureAuthenticated();
        await loadOrders(1);
    })();
</script>
<?php
require __DIR__ . '/_footer.php';