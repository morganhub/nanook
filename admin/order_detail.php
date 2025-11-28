<?php


declare(strict_types=1);

$pageTitle = 'Détails commandes';
$activeMenu = 'order_detail';
require __DIR__ . '/_header.php';
?>
    <meta charset="UTF-8">
    <title>Nanook · Détail commande</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            margin: 0 auto 40px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
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
            margin-bottom: 14px;
        }
        .card-header-line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 8px;
        }
        .card-title {
            font-size: 15px;
            font-weight: 600;
        }
        .message {
            font-size: 13px;
            margin-bottom: 8px;
        }
        .message.error { color: #b91c1c; }
        .message.info { color: #4b5563; }
        .grid-two {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            column-gap: 20px;
            row-gap: 8px;
            font-size: 13px;
        }
        .label {
            font-weight: 500;
            color: #374151;
        }
        .value {
            color: #111827;
        }
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
        .badge-status-delivered {
            background: #def7ec;
            color: #0f5132;
        }
        .badge-status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
        .badge-pref-christmas {
            background: #e0f2fe;
            color: #0369a1;
        }
        .btn-status {
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 7px 11px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-status:hover { opacity: .92; }
        .tracking-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 8px;
            font-size: 13px;
        }
        .tracking-box strong { color: #111827; }
        .badge-pref-no {
            background: #f3f4f6;
            color: #374151;
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
            padding: 7px 8px;
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
        .badge-small {
            font-size: 10px;
            padding: 1px 5px;
        }
        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
        }
        @media (max-width: 768px) {
            .grid-two {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="page-header">
        <div class="title"><span class="brand">NANOOK</span> · Détail commande</div>
        <a href="/admin/orders.php" class="back-link">&larr; Retour aux commandes</a>
    </div>

    <div id="message" class="message" style="display:none;"></div>

    <div class="card">
        <div class="card-header-line">
            <div class="card-title" id="orderTitle">Commande</div>
            <div>
                <span class="badge" id="statusBadge"></span>
                <button type="button" class="btn-status" id="changeStatusButton">Changer le statut</button>
            </div>
        </div>
        <div class="grid-two">
            <div>
                <div><span class="label">Numéro :</span> <span class="value" id="orderNumber"></span></div>
                <div><span class="label">Date :</span> <span class="value" id="orderDate"></span></div>
                <div><span class="label">Total :</span> <span class="value" id="orderTotal"></span></div>
                <div><span class="label">Préférence livraison :</span>
                    <span class="value" id="orderShippingPref"></span>
                </div>
                <div id="trackingInfo" class="tracking-box" style="display:none;"></div>
            </div>
            <div>
                <div><span class="label">Client :</span> <span class="value" id="customerName"></span></div>
                <div><span class="label">E-mail :</span> <span class="value" id="customerEmail"></span></div>
                <div><span class="label">Ville :</span> <span class="value" id="customerCity"></span></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header-line">
            <div class="card-title">Adresse de livraison</div>
        </div>
        <div style="font-size:13px;">
            <div id="shippingAddressLine1"></div>
            <div id="shippingAddressLine2"></div>
            <div id="shippingAddressCity"></div>
            <div id="shippingAddressCountry"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header-line">
            <div class="card-title">Articles</div>
        </div>
        <table>
            <thead>
            <tr>
                <th>Produit</th>
                <th>Déclinaison</th>
                <th>Qté</th>
                <th>Prix unitaire</th>
                <th>Total ligne</th>
                <th>Précommande</th>
                <th>Personnalisation</th>
            </tr>
            </thead>
            <tbody id="itemsTableBody"></tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header-line">
            <div class="card-title">Commentaires client</div>
        </div>
        <div id="customerComment" style="font-size:13px;"></div>
    </div>

    <div class="card">
        <div class="card-header-line">
            <div class="card-title">Logs e-mail</div>
        </div>
        <table>
            <thead>
            <tr>
                <th>Envoyé le</th>
                <th>Destinataire</th>
                <th>Sujet</th>
            </tr>
            </thead>
            <tbody id="emailLogsTableBody"></tbody>
        </table>
    </div>
</div>
<script src="/assets/js/admin-order-status.js"></script>
<script>
    let apiBaseUrl = '/admin/api';

    let messageEl = document.getElementById('message');

    let orderTitle = document.getElementById('orderTitle');
    let statusBadge = document.getElementById('statusBadge');
    let changeStatusButton = document.getElementById('changeStatusButton');

    let orderNumber = document.getElementById('orderNumber');
    let orderDate = document.getElementById('orderDate');
    let orderTotal = document.getElementById('orderTotal');
    let orderShippingPref = document.getElementById('orderShippingPref');
    let trackingInfo = document.getElementById('trackingInfo');

    let customerName = document.getElementById('customerName');
    let customerEmail = document.getElementById('customerEmail');
    let customerCity = document.getElementById('customerCity');

    let shippingAddressLine1 = document.getElementById('shippingAddressLine1');
    let shippingAddressLine2 = document.getElementById('shippingAddressLine2');
    let shippingAddressCity = document.getElementById('shippingAddressCity');
    let shippingAddressCountry = document.getElementById('shippingAddressCountry');

    let itemsTableBody = document.getElementById('itemsTableBody');
    let customerComment = document.getElementById('customerComment');
    let emailLogsTableBody = document.getElementById('emailLogsTableBody');

    const statusBadgeClass = AdminOrders.statusBadgeClass;
    const statusLabel = AdminOrders.statusLabel;

    let currentOrderId = null;
    let loadedOrder = null;

    const statusModal = AdminOrders.createStatusModal({
        apiBaseUrl,
        onStatusUpdated: () => loadOrder(),
    });

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

    function getQueryParam(name) {
        let params = new URLSearchParams(window.location.search);
        return params.get(name);
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
        let euros = price
        return euros + ' €';
    }

    function renderTracking(order) {
        if (!trackingInfo) {
            return;
        }

        if (order.tracking_number && order.tracking_carrier) {
            trackingInfo.style.display = 'block';
            trackingInfo.innerHTML =
                '<div><strong>Transporteur :</strong> ' + order.tracking_carrier.toUpperCase() + '</div>' +
                '<div><strong>Numéro de suivi :</strong> ' + order.tracking_number + '</div>';
            return;
        }

        if (order.status === 'shipped') {
            trackingInfo.style.display = 'block';
            trackingInfo.textContent = 'Commande en cours de livraison. Aucun numéro de suivi renseigné.';
            return;
        }
        if (order.status === 'delivered') {
            trackingInfo.style.display = 'block';
            trackingInfo.textContent = 'Commande livrée.';
            return;
        }
        trackingInfo.style.display = 'none';
        trackingInfo.textContent = '';
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

    function renderOrder(order) {
        loadedOrder = order;
        currentOrderId = order.id;
        orderTitle.textContent = 'Commande #' + order.order_number;
        statusBadge.className = statusBadgeClass(order.status);
        statusBadge.textContent = statusLabel(order.status);

        orderNumber.textContent = order.order_number;
        orderDate.textContent = order.created_at || '';
        orderTotal.textContent = formatPrice(order.total_amount);
        orderShippingPref.textContent = shippingPrefLabel(order.shipping_preference);

        let fullName = order.customer_first_name + ' ' + order.customer_last_name;
        customerName.textContent = fullName;
        customerEmail.textContent = order.customer_email;
        customerCity.textContent = order.shipping_city + (order.shipping_country ? ' (' + order.shipping_country + ')' : '');

        shippingAddressLine1.textContent = order.shipping_address_line1;
        shippingAddressLine2.textContent = order.shipping_address_line2 || '';
        shippingAddressCity.textContent =
            (order.shipping_postal_code ? order.shipping_postal_code + ' ' : '') +
            (order.shipping_city || '');
        shippingAddressCountry.textContent = order.shipping_country || '';
        renderTracking(order);
        if (order.customer_comment && order.customer_comment.trim() !== '') {
            customerComment.textContent = order.customer_comment;
        } else {
            customerComment.textContent = 'Aucun commentaire.';
        }
    }

    function renderItems(items) {
        itemsTableBody.innerHTML = '';
        if (!items.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 7;
            td.textContent = 'Aucun article.';
            td.style.fontSize = '13px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            itemsTableBody.appendChild(tr);
            return;
        }

        for (let item of items) {
            let tr = document.createElement('tr');

            let tdProduct = document.createElement('td');
            tdProduct.textContent = item.product_name;
            tr.appendChild(tdProduct);

            let tdVariant = document.createElement('td');
            tdVariant.textContent = item.variant_name || '-';
            if (item.variant_sku) {
                let spanSku = document.createElement('span');
                spanSku.className = 'mono';
                spanSku.style.marginLeft = '4px';
                spanSku.textContent = '(' + item.variant_sku + ')';
                tdVariant.appendChild(spanSku);
            }
            tr.appendChild(tdVariant);

            let tdQty = document.createElement('td');
            tdQty.textContent = item.quantity;
            tr.appendChild(tdQty);

            let tdUnit = document.createElement('td');
            tdUnit.textContent = formatPrice(item.unit_price);
            tr.appendChild(tdUnit);

            let tdLine = document.createElement('td');
            tdLine.textContent = formatPrice(item.line_total);
            tr.appendChild(tdLine);

            let tdPreorder = document.createElement('td');
            if (item.is_preorder) {
                let badge = document.createElement('span');
                badge.className = 'badge badge-small';
                badge.style.background = '#fef3c7';
                badge.style.color = '#92400e';
                badge.textContent = 'Précommande';
                tdPreorder.appendChild(badge);
            } else {
                tdPreorder.textContent = '—';
            }
            tr.appendChild(tdPreorder);

            let tdCustom = document.createElement('td');
            if (item.customizations && Object.keys(item.customizations).length > 0) {
                let ul = document.createElement('ul');
                ul.style.margin = '0';
                ul.style.paddingLeft = '18px';
                ul.style.fontSize = '12px';
                for (let key in item.customizations) {
                    if (!Object.prototype.hasOwnProperty.call(item.customizations, key)) {
                        continue;
                    }
                    let li = document.createElement('li');
                    li.textContent = key + ' : ' + item.customizations[key];
                    ul.appendChild(li);
                }
                tdCustom.appendChild(ul);
            } else {
                tdCustom.textContent = '—';
            }
            tr.appendChild(tdCustom);

            itemsTableBody.appendChild(tr);
        }
    }

    function renderEmailLogs(logs) {
        emailLogsTableBody.innerHTML = '';
        if (!logs || !logs.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 3;
            td.textContent = 'Aucun e-mail enregistré.';
            td.style.fontSize = '13px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            emailLogsTableBody.appendChild(tr);
            return;
        }

        for (let log of logs) {
            let tr = document.createElement('tr');

            let tdDate = document.createElement('td');
            tdDate.textContent = log.sent_at || '';
            tr.appendChild(tdDate);

            let tdRecipient = document.createElement('td');
            tdRecipient.textContent = log.recipient_email;
            tr.appendChild(tdRecipient);

            let tdSubject = document.createElement('td');
            tdSubject.textContent = log.subject;
            tr.appendChild(tdSubject);

            emailLogsTableBody.appendChild(tr);
        }
    }

    async function loadOrder() {
        let idParam = getQueryParam('id');
        if (!idParam) {
            showMessage('Identifiant de commande manquant.', 'error');
            return;
        }
        let id = parseInt(idParam, 10);
        if (!id || id <= 0) {
            showMessage('Identifiant de commande invalide.', 'error');
            return;
        }

        showMessage('Chargement de la commande…', 'info');

        try {
            let res = await fetch(apiBaseUrl + '/orders/get.php?id=' + encodeURIComponent(id), {
                method: 'GET',
                credentials: 'include'
            });
            let data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Commande introuvable.', 'error');
                return;
            }

            renderOrder(data.data.order);
            renderItems(data.data.items);
            await loadEmailLogs(id);
            showMessage('', 'info');
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function loadEmailLogs(orderId) {
        try {
            let res = await fetch(apiBaseUrl + '/orders/email_logs.php?order_id=' + encodeURIComponent(orderId), {
                method: 'GET',
                credentials: 'include'
            });
            let data = await res.json();
            if (!data.success) {
                renderEmailLogs([]);
                return;
            }
            renderEmailLogs(data.data);
        } catch (error) {
            console.error(error);
            renderEmailLogs([]);
        }
    }

    changeStatusButton.addEventListener('click', () => {
        if (!currentOrderId) {
            return;
        }
        statusModal.openModal(currentOrderId, loadedOrder ? loadedOrder.status : null);
    });

    (async function init() {
        await ensureAuthenticated();
        await loadOrder();
    })();
</script>
</body>
<?php
require __DIR__ . '/_footer.php';