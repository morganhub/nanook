<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Produit form';
$activeMenu = 'product_form';
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
            padding: 18px 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            margin-bottom: 16px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
            column-gap: 18px;
            row-gap: 10px;
            align-items: flex-start;
        }
        .label-cell {
            text-align: right;
            padding-top: 8px;
            font-size: 13px;
            color: #374151;
            white-space: nowrap;
        }
        .field-cell {
            font-size: 13px;
        }
        label {
            font-weight: 500;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 7px 9px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            font-family: inherit;
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        .field-inline {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }
        .btn-primary,
        .btn-secondary,
        .btn-danger,
        .btn-soft {
            padding: 7px 11px;
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
        .btn-danger {
            background: #b91c1c;
            color: #ffffff;
        }
        .btn-soft {
            background: #f3f4f6;
            color: #111827;
            border: 1px solid #e5e7eb;
        }
        .btn-primary[disabled],
        .btn-secondary[disabled],
        .btn-danger[disabled],
        .btn-soft[disabled] {
            opacity: .6;
            cursor: default;
        }
        .message {
            font-size: 13px;
            margin-bottom: 8px;
        }
        .message.error { color: #b91c1c; }
        .message.success { color: #15803d; }
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f9fafb;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            border: 1px solid #e5e7eb;
        }
        .pill span {
            color: #374151;
        }
        .pill small {
            color: #6b7280;
        }
        .images-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-card {
            width: 120px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            font-size: 11px;
        }
        .image-thumb {
            width: 100%;
            height: 90px;
            object-fit: cover;
            background: #e5e7eb;
        }
        .image-meta {
            padding: 4px 6px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .image-actions {
            display: flex;
            gap: 4px;
        }
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            font-size: 11px;
            color: #6b7280;
        }
        .table-mini {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .table-mini th,
        .table-mini td {
            padding: 6px 6px;
            border-bottom: 1px solid #f3f4f6;
        }
        .table-mini th {
            text-align: left;
            font-weight: 600;
            background: #f9fafb;
        }
        .table-mini tr:last-child td {
            border-bottom: none;
        }
        .pill-status {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
        }
        .pill-status.green {
            background: #dcfce7;
            color: #15803d;
        }
        .pill-status.red {
            background: #fee2e2;
            color: #b91c1c;
        }
        @media (max-width: 800px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .label-cell {
                text-align: left;
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="page-header">
        <div class="title"><span class="brand">NANOOK</span> · Produit</div>
        <a href="/admin/products.php" class="back-link">&larr; Retour à la liste</a>
    </div>

    <div id="message" class="message" style="display:none;"></div>

    <div class="card">
        <div class="section-title">Informations principales</div>
        <form id="productForm">
            <input type="hidden" id="productId">

            <div class="form-grid">
                <div class="label-cell">
                    <label for="nameInput">Nom du produit</label>
                </div>
                <div class="field-cell">
                    <input type="text" id="nameInput" required>
                </div>

                <div class="label-cell">
                    <label for="slugInput">Slug (URL)</label>
                </div>
                <div class="field-cell">
                    <input type="text" id="slugInput" required>
                </div>

                <div class="label-cell">
                    <label for="priceInput">Prix (en euros)</label>
                </div>
                <div class="field-cell">
                    <input
                            type="number"
                            id="priceInput"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            required
                    >
                </div>

                <div class="label-cell">
                    <label for="stockInput">Stock global</label>
                </div>
                <div class="field-cell">
                    <input type="number" id="stockInput" min="0" step="1" required>
                    <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                        Si vous utilisez surtout les déclinaisons, ce stock peut rester à 0.
                    </div>
                </div>

                <div class="label-cell">
                    <label for="displayOrderInput">Ordre d’affichage</label>
                </div>
                <div class="field-cell">
                    <input type="number" id="displayOrderInput" step="1" value="0">
                </div>

                <div class="label-cell">
                    Statut
                </div>
                <div class="field-cell">
                    <div class="field-inline">
                        <input type="checkbox" id="isActiveInput" checked>
                        <span>Produit actif</span>
                    </div>
                    <div class="field-inline">
                        <input type="checkbox" id="allowPreorderInput" checked>
                        <span>Autoriser la précommande en cas de rupture</span>
                    </div>
                </div>

                <div class="label-cell">
                    <label for="shortDescriptionInput">Description courte</label>
                </div>
                <div class="field-cell">
                    <textarea id="shortDescriptionInput"></textarea>
                </div>

                <div class="label-cell">
                    <label for="longDescriptionInput">Description longue</label>
                </div>
                <div class="field-cell">
                    <textarea id="longDescriptionInput"></textarea>
                </div>

                <div class="label-cell">
                    Catégories
                </div>
                <div class="field-cell">
                    <div id="categoriesContainer" style="display:flex;flex-wrap:wrap;gap:8px 16px;"></div>
                    <div style="margin-top:4px;font-size:11px;color:#6b7280;">
                        Gérer les catégories dans <a href="/admin/categories.php" style="color:#111827;text-decoration:underline;">l’écran dédié</a>.
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="btn-secondary" id="cancelButton">Annuler</button>
                <button type="submit" class="btn-primary" id="saveButton">Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-title">
            Images du produit
            <span class="badge badge-info" style="margin-left:6px;">Sharding hexadécimal</span>
        </div>
        <div class="form-grid">
            <div class="label-cell">
                Image principale & galerie
            </div>
            <div class="field-cell">
                <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <input type="file" id="imageInput" accept="image/*" style="font-size:12px;">
                    <button type="button" class="btn-soft" id="uploadImageButton">Ajouter</button>
                </div>
                <div class="images-grid" id="imagesGrid"></div>
                <div style="font-size:11px;color:#6b7280;margin-top:6px;">
                    Les fichiers sont rangés automatiquement dans <code>/storage/product_images/aa/bb/...</code>.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Déclinaisons (variantes)</div>
        <div class="form-grid">
            <div class="label-cell">
                Variantes
            </div>
            <div class="field-cell">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;align-items:center;">
                    <div class="chips">
                        <span>Ex : couleur, matériau, format…</span>
                    </div>
                    <button type="button" class="btn-soft" id="addVariantButton">Ajouter une déclinaison</button>
                </div>
                <table class="table-mini" id="variantsTable">
                    <thead>
                    <tr>
                        <th>Nom</th>
                        <th>SKU</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="variantsTableBody"></tbody>
                </table>
            </div>

            <div class="label-cell">
                Édition rapide
            </div>
            <div class="field-cell">
                <div id="variantEditor" style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;display:none;">
                    <input type="hidden" id="variantId">
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                        <div>
                            <label for="variantNameInput">Nom</label>
                            <input type="text" id="variantNameInput">
                        </div>
                        <div>
                            <label for="variantSkuInput">SKU</label>
                            <input type="text" id="variantSkuInput">
                        </div>
                        <div>
                            <label for="variantMaterialInput">Matériau</label>
                            <input type="text" id="variantMaterialInput">
                        </div>
                        <div>
                            <label for="variantColorInput">Couleur</label>
                            <input type="text" id="variantColorInput">
                        </div>
                        <div>
                            <label for="variantPriceInput">Prix (centimes, vide = prix produit)</label>
                            <input
                                    type="number"
                                    id="variantPriceInput"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                            >
                        </div>
                        <div>
                            <label for="variantStockInput">Stock</label>
                            <input type="number" id="variantStockInput" min="0" step="1">
                        </div>
                        <div>
                            <label for="variantOrderInput">Ordre</label>
                            <input type="number" id="variantOrderInput" step="1" value="0">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:4px;margin-top:18px;">
                            <div class="field-inline">
                                <input type="checkbox" id="variantIsActiveInput">
                                <span>Active</span>
                            </div>
                            <div class="field-inline">
                                <input type="checkbox" id="variantAllowPreorderInput">
                                <span>Précommande si stock 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions" style="margin-top:8px;">
                        <button type="button" class="btn-secondary" id="variantCancelButton">Annuler</button>
                        <button type="button" class="btn-primary" id="variantSaveButton">Enregistrer la déclinaison</button>
                    </div>
                </div>
                <div id="variantEmptyHint" style="font-size:12px;color:#6b7280;">
                    Sélectionnez une déclinaison dans la liste pour l’éditer ou cliquez sur “Ajouter une déclinaison”.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Personnalisations</div>
        <div class="form-grid">
            <div class="label-cell">
                Règles
            </div>
            <div class="field-cell">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;align-items:center;">
                    <div class="chips">
                        <span>Texte, options (motif, couleur), etc.</span>
                    </div>
                    <button type="button" class="btn-soft" id="addCustomizationButton">Ajouter une personnalisation</button>
                </div>
                <table class="table-mini" id="customizationsTable">
                    <thead>
                    <tr>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Obligatoire</th>
                        <th>Texte libre</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="customizationsTableBody"></tbody>
                </table>
            </div>

            <div class="label-cell">
                Détail
            </div>
            <div class="field-cell">
                <div id="customizationEditor" style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;display:none;">
                    <input type="hidden" id="customizationId">
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                        <div>
                            <label for="customizationLabelInput">Label affiché</label>
                            <input type="text" id="customizationLabelInput">
                        </div>
                        <div>
                            <label for="customizationFieldNameInput">Nom de champ (tech.)</label>
                            <input type="text" id="customizationFieldNameInput">
                        </div>
                        <div>
                            <label for="customizationFieldTypeInput">Type</label>
                            <select id="customizationFieldTypeInput">
                                <option value="text">Texte</option>
                                <option value="textarea">Texte long</option>
                                <option value="select">Liste de choix</option>
                                <option value="checkbox">Case à cocher</option>
                            </select>
                        </div>
                        <div>
                            <label for="customizationOrderInput">Ordre</label>
                            <input type="number" id="customizationOrderInput" step="1" value="0">
                        </div>
                        <div>
                            <label>Options</label>
                            <div style="font-size:11px;color:#6b7280;margin-bottom:3px;">
                                Utilisé pour le type “select”.
                            </div>
                            <button type="button" class="btn-soft" id="addOptionButton">Ajouter une option</button>
                        </div>
                        <div>
                            <label>Texte libre associé</label>
                            <div class="field-inline" style="margin-bottom:4px;">
                                <input type="checkbox" id="customizationAllowFreeTextInput">
                                <span>Autoriser un texte libre</span>
                            </div>
                            <input type="text" id="customizationFreeTextLabelInput" placeholder="Label du champ texte">
                            <input type="number" id="customizationFreeTextMaxLengthInput" placeholder="Longueur maximale" min="1" style="margin-top:4px;">
                        </div>
                        <div>
                            <div class="field-inline" style="margin-top:18px;">
                                <input type="checkbox" id="customizationIsRequiredInput">
                                <span>Champ obligatoire</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:8px;">
                        <table class="table-mini" id="optionsTable">
                            <thead>
                            <tr>
                                <th>Label</th>
                                <th>Supplément</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="optionsTableBody"></tbody>
                        </table>
                    </div>

                    <div class="actions" style="margin-top:8px;">
                        <button type="button" class="btn-secondary" id="customizationCancelButton">Annuler</button>
                        <button type="button" class="btn-primary" id="customizationSaveButton">Enregistrer la personnalisation</button>
                    </div>
                </div>
                <div id="customizationEmptyHint" style="font-size:12px;color:#6b7280;">
                    Sélectionnez une personnalisation dans la liste pour l’éditer ou cliquez sur “Ajouter une personnalisation”.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const apiBaseUrl = '/admin/api';

    const messageEl = document.getElementById('message');

    const productForm = document.getElementById('productForm');
    const productIdInput = document.getElementById('productId');
    const nameInput = document.getElementById('nameInput');
    const slugInput = document.getElementById('slugInput');
    const priceInput = document.getElementById('priceInput');
    const stockInput = document.getElementById('stockInput');
    const displayOrderInput = document.getElementById('displayOrderInput');
    const isActiveInput = document.getElementById('isActiveInput');
    const allowPreorderInput = document.getElementById('allowPreorderInput');
    const shortDescriptionInput = document.getElementById('shortDescriptionInput');
    const longDescriptionInput = document.getElementById('longDescriptionInput');
    const categoriesContainer = document.getElementById('categoriesContainer');
    const cancelButton = document.getElementById('cancelButton');
    const saveButton = document.getElementById('saveButton');

    const imageInput = document.getElementById('imageInput');
    const uploadImageButton = document.getElementById('uploadImageButton');
    const imagesGrid = document.getElementById('imagesGrid');

    const variantsTableBody = document.getElementById('variantsTableBody');
    const addVariantButton = document.getElementById('addVariantButton');
    const variantEditor = document.getElementById('variantEditor');
    const variantEmptyHint = document.getElementById('variantEmptyHint');
    const variantIdInput = document.getElementById('variantId');
    const variantNameInput = document.getElementById('variantNameInput');
    const variantSkuInput = document.getElementById('variantSkuInput');
    const variantMaterialInput = document.getElementById('variantMaterialInput');
    const variantColorInput = document.getElementById('variantColorInput');
    const variantPriceInput = document.getElementById('variantPriceInput');
    const variantStockInput = document.getElementById('variantStockInput');
    const variantOrderInput = document.getElementById('variantOrderInput');
    const variantIsActiveInput = document.getElementById('variantIsActiveInput');
    const variantAllowPreorderInput = document.getElementById('variantAllowPreorderInput');
    const variantCancelButton = document.getElementById('variantCancelButton');
    const variantSaveButton = document.getElementById('variantSaveButton');

    const customizationsTableBody = document.getElementById('customizationsTableBody');
    const addCustomizationButton = document.getElementById('addCustomizationButton');
    const customizationEditor = document.getElementById('customizationEditor');
    const customizationEmptyHint = document.getElementById('customizationEmptyHint');
    const customizationIdInput = document.getElementById('customizationId');
    const customizationLabelInput = document.getElementById('customizationLabelInput');
    const customizationFieldNameInput = document.getElementById('customizationFieldNameInput');
    const customizationFieldTypeInput = document.getElementById('customizationFieldTypeInput');
    const customizationOrderInput = document.getElementById('customizationOrderInput');
    const customizationAllowFreeTextInput = document.getElementById('customizationAllowFreeTextInput');
    const customizationFreeTextLabelInput = document.getElementById('customizationFreeTextLabelInput');
    const customizationFreeTextMaxLengthInput = document.getElementById('customizationFreeTextMaxLengthInput');
    const customizationIsRequiredInput = document.getElementById('customizationIsRequiredInput');
    const optionsTableBody = document.getElementById('optionsTableBody');
    const addOptionButton = document.getElementById('addOptionButton');
    const customizationCancelButton = document.getElementById('customizationCancelButton');
    const customizationSaveButton = document.getElementById('customizationSaveButton');

    let allCategories = [];
    let loadedProduct = null;
    let images = [];
    let variants = [];
    let customizations = [];
    let currentCustomizationOptions = [];

    function parsePrice(value) {
        if (value === null || value === undefined) {
            return 0;
        }
        let str = String(value).trim();
        if (!str) {
            return 0;
        }
        // accepter "12,34" et "12.34"
        str = str.replace(',', '.');

        let num = Number(str);
        if (Number.isNaN(num)) {
            return 0;
        }

        // on limite à 2 décimales
        return Math.round(num * 100) / 100;
    }

    function showMessage(text, type = 'error') {
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
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
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

    function slugify(value) {
        let text = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        text = text.toLowerCase();
        text = text.replace(/[^a-z0-9]+/g, '-');
        text = text.replace(/^-+|-+$/g, '');
        return text;
    }

    nameInput.addEventListener('input', () => {
        if (!slugInput.value || (loadedProduct && slugInput.value === loadedProduct.slug)) {
            slugInput.value = slugify(nameInput.value);
        }
    });

    cancelButton.addEventListener('click', (event) => {
        event.preventDefault();
        window.location.href = '/admin/products.php';
    });

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
            allCategories = data.data || [];
            renderProductCategories();
        } catch (error) {
            console.error(error);
        }
    }

    function renderProductCategories() {
        categoriesContainer.innerHTML = '';
        if (!allCategories.length) {
            let span = document.createElement('span');
            span.style.fontSize = '12px';
            span.style.color = '#6b7280';
            span.textContent = 'Aucune catégorie définie.';
            categoriesContainer.appendChild(span);
            return;
        }

        let selectedIds = loadedProduct && loadedProduct.category_ids ? loadedProduct.category_ids : [];

        for (let cat of allCategories) {
            let label = document.createElement('label');
            label.style.fontSize = '13px';
            label.style.display = 'flex';
            label.style.alignItems = 'center';
            label.style.gap = '4px';

            let input = document.createElement('input');
            input.type = 'checkbox';
            input.value = String(cat.id);
            if (selectedIds.includes(cat.id)) {
                input.checked = true;
            }

            let span = document.createElement('span');
            span.textContent = cat.name;

            label.appendChild(input);
            label.appendChild(span);
            categoriesContainer.appendChild(label);
        }
    }

    async function loadProductIfEditing() {
        const idParam = getQueryParam('id');
        if (!idParam) {
            return;
        }
        const id = parseInt(idParam, 10);
        if (!id || id <= 0) {
            return;
        }

        try {
            const res = await fetch(`${apiBaseUrl}/products/get.php?id=` + encodeURIComponent(id), {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                showMessage('Erreur lors du chargement du produit.', 'error');
                return;
            }
            loadedProduct = data.data;
            productIdInput.value = loadedProduct.id;
            nameInput.value = loadedProduct.name;
            slugInput.value = loadedProduct.slug;
            priceInput.value = loadedProduct.price;
            stockInput.value = loadedProduct.stock_quantity;
            displayOrderInput.value = loadedProduct.display_order;
            isActiveInput.checked = !!loadedProduct.is_active;
            allowPreorderInput.checked = !!loadedProduct.allow_preorder_when_oos;
            shortDescriptionInput.value = loadedProduct.short_description ?? '';
            longDescriptionInput.value = loadedProduct.long_description ?? '';
            renderProductCategories();
            await loadImages();
            await loadVariants();
            await loadCustomizations();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function saveProduct(event) {
        event.preventDefault();
        showMessage('', 'success');
        saveButton.disabled = true;

        const idValue = productIdInput.value ? parseInt(productIdInput.value, 10) : 0;

        let selectedCategoryIds = [];
        const catCheckboxes = categoriesContainer.querySelectorAll('input[type="checkbox"]');
        for (let input of catCheckboxes) {
            if (input.checked) {
                let cid = parseInt(input.value, 10);
                if (cid > 0) {
                    selectedCategoryIds.push(cid);
                }
            }
        }

        const payload = {
            id: idValue > 0 ? idValue : undefined,
            name: nameInput.value.trim(),
            slug: slugInput.value.trim(),
            short_description: shortDescriptionInput.value.trim(),
            long_description: longDescriptionInput.value.trim(),
            price: parsePrice(priceInput.value), // <—
            stock_quantity: parseInt(stockInput.value, 10) || 0,
            allow_preorder_when_oos: allowPreorderInput.checked ? 1 : 0,
            is_active: isActiveInput.checked ? 1 : 0,
            display_order: parseInt(displayOrderInput.value, 10) || 0,
            category_ids: selectedCategoryIds
        };

        if (!payload.name || !payload.slug) {
            showMessage('Le nom et le slug sont obligatoires.', 'error');
            saveButton.disabled = false;
            return;
        }

        try {
            const res = await fetch(`${apiBaseUrl}/products/save.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de l’enregistrement du produit.', 'error');
                saveButton.disabled = false;
                return;
            }
            const pid = data.data.id;
            productIdInput.value = pid;
            showMessage('Produit enregistré.', 'success');

            if (!loadedProduct) {
                loadedProduct = {id: pid, category_ids: selectedCategoryIds};
            }
            setTimeout(() => {
                window.location.href = '/admin/products.php';
            }, 800);
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
            saveButton.disabled = false;
        }
    }

    productForm.addEventListener('submit', saveProduct);

    async function loadImages() {
        if (!productIdInput.value) {
            images = [];
            renderImages();
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/product_images/list.php?product_id=` + encodeURIComponent(productIdInput.value), {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                return;
            }
            images = data.data || [];
            renderImages();
        } catch (error) {
            console.error(error);
        }
    }

    function renderImages() {
        imagesGrid.innerHTML = '';
        if (!images.length) {
            let span = document.createElement('span');
            span.style.fontSize = '12px';
            span.style.color = '#6b7280';
            span.textContent = 'Aucune image pour le moment.';
            imagesGrid.appendChild(span);
            return;
        }
        for (let img of images) {
            let card = document.createElement('div');
            card.className = 'image-card';

            let thumb = document.createElement('img');
            thumb.className = 'image-thumb';
            thumb.src = '/storage/product_images/' + img.file_path;
            thumb.alt = '';
            card.appendChild(thumb);

            let meta = document.createElement('div');
            meta.className = 'image-meta';

            let row1 = document.createElement('div');
            row1.textContent = img.is_main ? 'Image principale' : 'Image';
            row1.style.fontWeight = '500';
            row1.style.fontSize = '11px';
            meta.appendChild(row1);

            let row2 = document.createElement('div');
            row2.className = 'image-actions';

            let mainBtn = document.createElement('button');
            mainBtn.type = 'button';
            mainBtn.className = 'btn-soft';
            mainBtn.textContent = img.is_main ? 'Principale' : 'Définir principale';
            mainBtn.disabled = !!img.is_main;
            mainBtn.style.flex = '1';
            mainBtn.addEventListener('click', () => setMainImage(img.id));

            let delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-danger';
            delBtn.textContent = 'Supprimer';
            delBtn.style.flex = '1';
            delBtn.addEventListener('click', () => deleteImage(img.id));

            row2.appendChild(mainBtn);
            row2.appendChild(delBtn);
            meta.appendChild(row2);

            card.appendChild(meta);
            imagesGrid.appendChild(card);
        }
    }

    uploadImageButton.addEventListener('click', async () => {
        if (!productIdInput.value) {
            showMessage('Enregistrez d’abord le produit avant d’ajouter des images.', 'error');
            return;
        }
        if (!imageInput.files || !imageInput.files[0]) {
            showMessage('Sélectionnez un fichier image.', 'error');
            return;
        }
        let formData = new FormData();
        formData.append('product_id', productIdInput.value);
        formData.append('image', imageInput.files[0]);

        uploadImageButton.disabled = true;
        try {
            const res = await fetch(`${apiBaseUrl}/product_images/upload.php`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de l’upload de l’image.', 'error');
                uploadImageButton.disabled = false;
                return;
            }
            imageInput.value = '';
            await loadImages();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        } finally {
            uploadImageButton.disabled = false;
        }
    });

    async function deleteImage(id) {
        if (!window.confirm('Supprimer cette image ?')) {
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/product_images/delete.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la suppression de l’image.', 'error');
                return;
            }
            await loadImages();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function setMainImage(id) {
        try {
            const res = await fetch(`${apiBaseUrl}/product_images/set_main.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la mise à jour de l’image principale.', 'error');
                return;
            }
            await loadImages();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function loadVariants() {
        if (!productIdInput.value) {
            variants = [];
            renderVariants();
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/variants/list.php?product_id=` + encodeURIComponent(productIdInput.value), {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                return;
            }
            variants = data.data || [];
            renderVariants();
        } catch (error) {
            console.error(error);
        }
    }

    function renderVariants() {
        variantsTableBody.innerHTML = '';
        if (!variants.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 6;
            td.textContent = 'Aucune déclinaison.';
            td.style.fontSize = '12px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            variantsTableBody.appendChild(tr);
            return;
        }
        for (let v of variants) {
            let tr = document.createElement('tr');

            let tdName = document.createElement('td');
            tdName.textContent = v.name;
            tr.appendChild(tdName);

            let tdSku = document.createElement('td');
            tdSku.textContent = v.sku || '-';
            tr.appendChild(tdSku);

            let tdPrice = document.createElement('td');
            if (v.price === null || typeof v.price === 'undefined') {
                tdPrice.textContent = '—';
            } else {
                let euros = parsePrice(v.price).toFixed(2);
                tdPrice.textContent = euros + ' €';
            }
            tr.appendChild(tdPrice);

            let tdStock = document.createElement('td');
            tdStock.textContent = v.stock_quantity;
            if (v.stock_quantity === 0 && v.allow_preorder_when_oos) {
                let span = document.createElement('span');
                span.className = 'badge';
                span.style.background = '#fef3c7';
                span.style.color = '#92400e';
                span.textContent = 'Précommande';
                span.style.marginLeft = '4px';
                tdStock.appendChild(span);
            }
            tr.appendChild(tdStock);

            let tdStatus = document.createElement('td');
            let pill = document.createElement('span');
            pill.className = 'pill-status ' + (v.is_active ? 'green' : 'red');
            pill.textContent = v.is_active ? 'Active' : 'Inactive';
            tdStatus.appendChild(pill);
            tr.appendChild(tdStatus);

            let tdActions = document.createElement('td');
            let editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn-soft';
            editBtn.textContent = 'Éditer';
            editBtn.addEventListener('click', () => openVariantEditor(v));

            let delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-danger';
            delBtn.textContent = 'Supprimer';
            delBtn.style.marginLeft = '4px';
            delBtn.addEventListener('click', () => deleteVariant(v.id));

            tdActions.appendChild(editBtn);
            tdActions.appendChild(delBtn);
            tr.appendChild(tdActions);

            variantsTableBody.appendChild(tr);
        }
    }

    function openVariantEditor(variant = null) {
        variantEmptyHint.style.display = 'none';
        variantEditor.style.display = 'block';

        if (variant) {
            variantIdInput.value = variant.id;
            variantNameInput.value = variant.name;
            variantSkuInput.value = variant.sku || '';
            variantMaterialInput.value = variant.material || '';
            variantColorInput.value = variant.color || '';
            variantPriceInput.value = variant.price !== null ? variant.price : '';
            variantStockInput.value = variant.stock_quantity;
            variantOrderInput.value = variant.display_order;
            variantIsActiveInput.checked = !!variant.is_active;
            variantAllowPreorderInput.checked = !!variant.allow_preorder_when_oos;
        } else {
            variantIdInput.value = '';
            variantNameInput.value = '';
            variantSkuInput.value = '';
            variantMaterialInput.value = '';
            variantColorInput.value = '';
            variantPriceInput.value = '';
            variantStockInput.value = '0';
            variantOrderInput.value = '0';
            variantIsActiveInput.checked = true;
            variantAllowPreorderInput.checked = true;
        }
    }

    function closeVariantEditor() {
        variantEditor.style.display = 'none';
        variantEmptyHint.style.display = 'block';
    }

    addVariantButton.addEventListener('click', () => openVariantEditor(null));
    variantCancelButton.addEventListener('click', () => closeVariantEditor());

    async function saveVariant() {
        if (!productIdInput.value) {
            showMessage('Enregistrez le produit avant les déclinaisons.', 'error');
            return;
        }
        const productId = parseInt(productIdInput.value, 10);
        const id = variantIdInput.value ? parseInt(variantIdInput.value, 10) : 0;

        const payload = {
            product_id: productId,
            id: id || undefined,
            name: variantNameInput.value.trim(),
            sku: variantSkuInput.value.trim(),
            material: variantMaterialInput.value.trim(),
            color: variantColorInput.value.trim(),
            price: variantPriceInput.value === ''
                ? null
                : parsePrice(variantPriceInput.value),
            stock_quantity: parseInt(variantStockInput.value, 10) || 0,
            allow_preorder_when_oos: variantAllowPreorderInput.checked ? 1 : 0,
            is_active: variantIsActiveInput.checked ? 1 : 0,
            display_order: parseInt(variantOrderInput.value, 10) || 0
        };

        if (!payload.name) {
            showMessage('Le nom de la déclinaison est obligatoire.', 'error');
            return;
        }

        variantSaveButton.disabled = true;
        try {
            const res = await fetch(`${apiBaseUrl}/variants/save.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de l’enregistrement de la déclinaison.', 'error');
                variantSaveButton.disabled = false;
                return;
            }
            await loadVariants();
            closeVariantEditor();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        } finally {
            variantSaveButton.disabled = false;
        }
    }

    variantSaveButton.addEventListener('click', saveVariant);

    async function deleteVariant(id) {
        if (!window.confirm('Supprimer cette déclinaison ?')) {
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/variants/delete.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la suppression de la déclinaison.', 'error');
                return;
            }
            await loadVariants();
            closeVariantEditor();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function loadCustomizations() {
        if (!productIdInput.value) {
            customizations = [];
            renderCustomizations();
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/customizations/list.php?product_id=` + encodeURIComponent(productIdInput.value), {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (!data.success) {
                return;
            }
            customizations = data.data || [];
            renderCustomizations();
        } catch (error) {
            console.error(error);
        }
    }

    function renderCustomizations() {
        customizationsTableBody.innerHTML = '';
        if (!customizations.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 5;
            td.textContent = 'Aucune personnalisation.';
            td.style.fontSize = '12px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            customizationsTableBody.appendChild(tr);
            return;
        }
        for (let c of customizations) {
            let tr = document.createElement('tr');

            let tdLabel = document.createElement('td');
            tdLabel.textContent = c.label;
            tr.appendChild(tdLabel);

            let tdType = document.createElement('td');
            tdType.textContent = c.field_type;
            tr.appendChild(tdType);

            let tdRequired = document.createElement('td');
            tdRequired.textContent = c.is_required ? 'Oui' : 'Non';
            tr.appendChild(tdRequired);

            let tdFreeText = document.createElement('td');
            tdFreeText.textContent = c.allow_free_text ? 'Oui' : 'Non';
            tr.appendChild(tdFreeText);

            let tdActions = document.createElement('td');
            let editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn-soft';
            editBtn.textContent = 'Éditer';
            editBtn.addEventListener('click', () => openCustomizationEditor(c));

            let delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-danger';
            delBtn.textContent = 'Supprimer';
            delBtn.style.marginLeft = '4px';
            delBtn.addEventListener('click', () => deleteCustomization(c.id));

            tdActions.appendChild(editBtn);
            tdActions.appendChild(delBtn);
            tr.appendChild(tdActions);

            customizationsTableBody.appendChild(tr);
        }
    }

    function openCustomizationEditor(customization = null) {
        customizationEmptyHint.style.display = 'none';
        customizationEditor.style.display = 'block';

        if (customization) {
            customizationIdInput.value = customization.id;
            customizationLabelInput.value = customization.label;
            customizationFieldNameInput.value = customization.field_name;
            customizationFieldTypeInput.value = customization.field_type;
            customizationOrderInput.value = customization.display_order;
            customizationAllowFreeTextInput.checked = !!customization.allow_free_text;
            customizationFreeTextLabelInput.value = customization.free_text_label || '';
            customizationFreeTextMaxLengthInput.value = customization.free_text_max_length || '';
            customizationIsRequiredInput.checked = !!customization.is_required;
            currentCustomizationOptions = customization.options || [];
        } else {
            customizationIdInput.value = '';
            customizationLabelInput.value = '';
            customizationFieldNameInput.value = '';
            customizationFieldTypeInput.value = 'text';
            customizationOrderInput.value = '0';
            customizationAllowFreeTextInput.checked = false;
            customizationFreeTextLabelInput.value = '';
            customizationFreeTextMaxLengthInput.value = '';
            customizationIsRequiredInput.checked = false;
            currentCustomizationOptions = [];
        }
        renderOptions();
    }

    function closeCustomizationEditor() {
        customizationEditor.style.display = 'none';
        customizationEmptyHint.style.display = 'block';
    }

    function renderOptions() {
        optionsTableBody.innerHTML = '';
        if (!currentCustomizationOptions.length) {
            let tr = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = 3;
            td.textContent = 'Aucune option.';
            td.style.fontSize = '12px';
            td.style.color = '#6b7280';
            tr.appendChild(td);
            optionsTableBody.appendChild(tr);
            return;
        }
        for (let opt of currentCustomizationOptions) {
            let tr = document.createElement('tr');

            let tdLabel = document.createElement('td');
            tdLabel.textContent = opt.label;
            tr.appendChild(tdLabel);

            let tdPrice = document.createElement('td');
            let euros = parsePrice(opt.price_delta).toFixed(2);
            tdPrice.textContent = euros === '0.00' ? '—' : ('+' + euros + ' €');
            tr.appendChild(tdPrice);

            let tdActions = document.createElement('td');
            let delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-danger';
            delBtn.textContent = 'Supprimer';
            delBtn.addEventListener('click', async () => {
                if (opt.id) {
                    await deleteOption(opt.id);
                } else {
                    currentCustomizationOptions = currentCustomizationOptions.filter(o => o !== opt);
                    renderOptions();
                }
            });
            tdActions.appendChild(delBtn);
            tr.appendChild(tdActions);

            optionsTableBody.appendChild(tr);
        }
    }

    addOptionButton.addEventListener('click', () => {
        let label = window.prompt('Label de l’option ?');
        if (!label) {
            return;
        }
        let priceStr = window.prompt('Supplément (en euros, ex: 4.50 ou 4,50) :', '');
        let price = priceStr === null || priceStr.trim() === ''
            ? 0
            : parsePrice(priceStr);
        currentCustomizationOptions.push({
            id: null,
            label: label,
            description: null,
            price_delta: price,
            display_order: currentCustomizationOptions.length
        });
        renderOptions();
    });

    async function saveCustomization() {
        if (!productIdInput.value) {
            showMessage('Enregistrez le produit avant les personnalisations.', 'error');
            return;
        }
        const productId = parseInt(productIdInput.value, 10);
        const id = customizationIdInput.value ? parseInt(customizationIdInput.value, 10) : 0;

        const payload = {
            product_id: productId,
            id: id || undefined,
            label: customizationLabelInput.value.trim(),
            field_name: customizationFieldNameInput.value.trim(),
            field_type: customizationFieldTypeInput.value,
            is_required: customizationIsRequiredInput.checked ? 1 : 0,
            allow_free_text: customizationAllowFreeTextInput.checked ? 1 : 0,
            free_text_label: customizationFreeTextLabelInput.value.trim(),
            free_text_max_length: customizationFreeTextMaxLengthInput.value === ''
                ? null
                : (parseInt(customizationFreeTextMaxLengthInput.value, 10) || null),
            display_order: parseInt(customizationOrderInput.value, 10) || 0
        };

        if (!payload.label || !payload.field_name) {
            showMessage('Label et nom de champ sont obligatoires.', 'error');
            return;
        }

        customizationSaveButton.disabled = true;
        try {
            const res = await fetch(`${apiBaseUrl}/customizations/save.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de l’enregistrement de la personnalisation.', 'error');
                customizationSaveButton.disabled = false;
                return;
            }
            const customizationId = data.data.id;
            await syncOptions(customizationId);
            await loadCustomizations();
            closeCustomizationEditor();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        } finally {
            customizationSaveButton.disabled = false;
        }
    }

    customizationSaveButton.addEventListener('click', saveCustomization);
    customizationCancelButton.addEventListener('click', () => closeCustomizationEditor());
    addCustomizationButton.addEventListener('click', () => openCustomizationEditor(null));

    async function syncOptions(customizationId) {
        for (let opt of currentCustomizationOptions) {
            const payload = {
                customization_id: customizationId,
                id: opt.id || undefined,
                label: opt.label,
                description: opt.description || null,
                price_delta: opt.price_delta || 0,
                display_order: opt.display_order || 0
            };
            try {
                const res = await fetch(`${apiBaseUrl}/customizations/options_save.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success && !opt.id) {
                    opt.id = data.data.id;
                }
            } catch (error) {
                console.error(error);
            }
        }
    }

    async function deleteOption(id) {
        try {
            const res = await fetch(`${apiBaseUrl}/customizations/options_delete.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la suppression de l’option.', 'error');
                return;
            }
            currentCustomizationOptions = currentCustomizationOptions.filter(o => o.id !== id);
            renderOptions();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    async function deleteCustomization(id) {
        if (!window.confirm('Supprimer cette personnalisation ?')) {
            return;
        }
        try {
            const res = await fetch(`${apiBaseUrl}/customizations/delete.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showMessage('Erreur lors de la suppression de la personnalisation.', 'error');
                return;
            }
            await loadCustomizations();
            closeCustomizationEditor();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de communication avec le serveur.', 'error');
        }
    }

    (async function init() {
        await ensureAuthenticated();
        await loadCategories();
        await loadProductIfEditing();
    })();
</script>
<?php
require __DIR__ . '/_footer.php';
