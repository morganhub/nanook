<?php
// admin/product_form.php
declare(strict_types=1);

$pageTitle = 'Produit form';
$activeMenu = 'product_form';
require __DIR__ . '/_header.php';
?>
    <style>
        .card { background: #ffffff; border-radius: 10px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.06); margin-bottom: 16px; }
        .form-grid { display: grid; grid-template-columns: minmax(180px, 220px) minmax(0, 1fr); column-gap: 18px; row-gap: 10px; align-items: flex-start; }
        .label-cell { text-align: right; padding-top: 8px; font-size: 13px; color: #374151; white-space: nowrap; }
        .field-cell { font-size: 13px; }
        label { font-weight: 500; }
        input[type="text"], input[type="number"], input[type="date"], textarea, select { width: 100%; padding: 7px 9px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 13px; font-family: inherit; }
        textarea { min-height: 80px; resize: vertical; }
        .field-inline { display: flex; align-items: center; gap: 6px; font-size: 13px; }
        .section-title { font-size: 14px; font-weight: 600; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .badge { display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 999px; font-size: 11px; font-weight: 500; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .btn-primary, .btn-secondary, .btn-danger, .btn-soft { padding: 4px 5px; border-radius: 4px; border: none; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #111827; color: #ffffff; }
        .btn-secondary { background: #ffffff; color: #111827; border: 1px solid #d1d5db; }
        .btn-danger { background: #b91c1c; color: #ffffff; }
        .btn-soft { background: #f3f4f6; color: #111827; border: 1px solid #e5e7eb; }
        .btn-primary[disabled], .btn-secondary[disabled], .btn-danger[disabled], .btn-soft[disabled] { opacity: .6; cursor: default; }
        .message { font-size: 13px; margin-bottom: 8px; }
        .message.error { color: #b91c1c; }
        .message.success { color: #15803d; }
        .actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }
        .images-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .image-card { width: 100px; border-radius: 6px; border: 1px solid #e5e7eb; overflow: hidden; background: #f9fafb; display: flex; flex-direction: column; font-size: 10px; }
        .image-thumb { width: 100%; height: 80px; object-fit: cover; background: #e5e7eb; }
        .image-meta { padding: 4px; text-align: center; }
        .image-actions { display: flex; gap: 2px; padding: 2px; }
        .image-actions button { flex: 1; font-size: 10px; padding: 3px; }
        .table-mini { width: 100%; border-collapse: collapse; font-size: 12px; }
        .table-mini td.flex { display: flex; gap:20px; white-space: nowrap; flex-flow: nowrap}
        .table-mini th, .table-mini td { padding: 6px 6px; border-bottom: 1px solid #f3f4f6; }
        .table-mini th { text-align: left; font-weight: 600; background: #f9fafb; }
        .pill-status { display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 999px; font-size: 11px; }
        .pill-status.green { background: #dcfce7; color: #15803d; }
        .pill-status.red { background: #fee2e2; color: #b91c1c; }
        .parent-masked-info { font-size: 13px; color: #6b7280; background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; grid-column: 1 / -1; display: none; }

        /* Styles pour l'upload variante */
        .variant-images-section { border-top: 1px solid #eee; margin-top: 15px; padding-top: 15px; }
        .variant-images-header { font-weight: 600; margin-bottom: 8px; font-size: 13px; }
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

                    <!-- Message Masqué si Variantes -->
                    <div class="parent-masked-info" id="parentMaskedInfo">
                        Ce produit possède des déclinaisons. Le prix, le stock et la disponibilité sont gérés individuellement sur chaque variante ci-dessous.
                    </div>

                    <!-- Champs Masquables (js-parent-field) -->
                    <div class="label-cell js-parent-field">
                        <label for="priceInput">Prix (en euros)</label>
                    </div>
                    <div class="field-cell js-parent-field">
                        <input type="number" id="priceInput" min="0" step="0.01" inputmode="decimal">
                    </div>

                    <div class="label-cell js-parent-field">
                        <label for="stockInput">Stock global</label>
                    </div>
                    <div class="field-cell js-parent-field">
                        <input type="number" id="stockInput" min="0" step="1">
                    </div>

                    <div class="label-cell js-parent-field">
                        <label for="displayOrderInput">Ordre d’affichage</label>
                    </div>
                    <div class="field-cell js-parent-field">
                        <input type="number" id="displayOrderInput" step="1" value="0">
                    </div>

                    <div class="label-cell js-parent-field">
                        Statut & Stock
                    </div>
                    <div class="field-cell js-parent-field">
                        <div class="field-inline">
                            <input type="checkbox" id="isActiveInput" checked>
                            <span>Produit actif</span>
                        </div>
                        <div class="field-inline">
                            <input type="checkbox" id="allowPreorderInput" checked>
                            <span>Autoriser la précommande en cas de rupture</span>
                        </div>
                    </div>

                    <div class="label-cell js-parent-field">
                        <label for="availabilityDateInput">Disponibilité</label>
                    </div>
                    <div class="field-cell js-parent-field">
                        <input type="date" id="availabilityDateInput">
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                            Date prévisionnelle d'expédition si en précommande.
                        </div>
                    </div>
                    <!-- Fin Champs Masquables -->

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
                                <label for="variantPriceInput">Prix (vide = prix produit)</label>
                                <input type="number" id="variantPriceInput" min="0" step="0.01" inputmode="decimal">
                            </div>
                            <div>
                                <label for="variantStockInput">Stock</label>
                                <input type="number" id="variantStockInput" min="0" step="1">
                            </div>
                            <div>
                                <label for="variantOrderInput">Ordre</label>
                                <input type="number" id="variantOrderInput" step="1" value="0">
                            </div>
                            <div>
                                <label for="variantAvailabilityDateInput">Disponibilité</label>
                                <input type="date" id="variantAvailabilityDateInput">
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

                        <!-- GESTION IMAGES VARIANTE -->
                        <div class="variant-images-section">
                            <div class="variant-images-header">Images spécifiques à cette variante</div>
                            <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                                <input type="file" id="variantImageUploadInput" accept="image/*" style="font-size:12px;">
                                <button type="button" class="btn-soft" id="uploadVariantImageButton">Ajouter à la variante</button>
                            </div>
                            <div class="images-grid" id="variantImagesGrid"></div>
                            <div style="font-size:11px;color:#999;margin-top:5px;">Si vide, les images générales seront utilisées.</div>
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
        const availabilityDateInput = document.getElementById('availabilityDateInput'); // NEW
        const shortDescriptionInput = document.getElementById('shortDescriptionInput');
        const longDescriptionInput = document.getElementById('longDescriptionInput');
        const categoriesContainer = document.getElementById('categoriesContainer');
        const cancelButton = document.getElementById('cancelButton');
        const saveButton = document.getElementById('saveButton');

        // Masquage champs parent
        const parentMaskedInfo = document.getElementById('parentMaskedInfo');
        const parentFields = document.querySelectorAll('.js-parent-field');

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
        const variantAvailabilityDateInput = document.getElementById('variantAvailabilityDateInput'); // NEW
        const variantIsActiveInput = document.getElementById('variantIsActiveInput');
        const variantAllowPreorderInput = document.getElementById('variantAllowPreorderInput');
        const variantCancelButton = document.getElementById('variantCancelButton');
        const variantSaveButton = document.getElementById('variantSaveButton');

        // Customization constants...
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
            if (value === null || value === undefined) return 0;
            let str = String(value).trim();
            if (!str) return 0;
            str = str.replace(',', '.');
            let num = Number(str);
            if (Number.isNaN(num)) return 0;
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
                const res = await fetch(`${apiBaseUrl}/me.php`, { method: 'GET', credentials: 'include' });
                const data = await res.json();
                if (!data.authenticated) window.location.href = '/admin/index.php';
            } catch (error) {
                console.error(error);
                window.location.href = '/admin/index.php';
            }
        }

        function slugify(value) {
            let text = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            text = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
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

        // --- LOGIQUE AFFICHAGE PARENT ---
        function toggleParentFields() {
            const hasVariants = variants.length > 0;
            if (hasVariants) {
                parentMaskedInfo.style.display = 'block';
                parentFields.forEach(el => el.style.display = 'none');
            } else {
                parentMaskedInfo.style.display = 'none';
                parentFields.forEach(el => el.style.display = 'block');
            }
        }

        async function loadCategories() {
            try {
                const res = await fetch(`${apiBaseUrl}/categories/list.php`, { method: 'GET', credentials: 'include' });
                const data = await res.json();
                if (!data.success) return;
                allCategories = data.data || [];
                renderProductCategories();
            } catch (error) { console.error(error); }
        }

        function renderProductCategories() {
            categoriesContainer.innerHTML = '';
            if (!allCategories.length) {
                let span = document.createElement('span');
                span.style.fontSize = '12px'; span.style.color = '#6b7280'; span.textContent = 'Aucune catégorie définie.';
                categoriesContainer.appendChild(span); return;
            }
            let selectedIds = loadedProduct && loadedProduct.category_ids ? loadedProduct.category_ids : [];
            for (let cat of allCategories) {
                let label = document.createElement('label');
                label.style.fontSize = '13px'; label.style.display = 'flex'; label.style.alignItems = 'center'; label.style.gap = '4px';
                let input = document.createElement('input'); input.type = 'checkbox'; input.value = String(cat.id);
                if (selectedIds.includes(cat.id)) input.checked = true;
                let span = document.createElement('span'); span.textContent = cat.name;
                label.appendChild(input); label.appendChild(span); categoriesContainer.appendChild(label);
            }
        }

        async function loadProductIfEditing() {
            const idParam = getQueryParam('id');
            if (!idParam) return;
            const id = parseInt(idParam, 10);
            if (!id || id <= 0) return;

            try {
                const res = await fetch(`${apiBaseUrl}/products/get.php?id=` + encodeURIComponent(id), { method: 'GET', credentials: 'include' });
                const data = await res.json();
                if (!data.success) { showMessage('Erreur lors du chargement du produit.', 'error'); return; }
                loadedProduct = data.data;
                productIdInput.value = loadedProduct.id;
                nameInput.value = loadedProduct.name;
                slugInput.value = loadedProduct.slug;
                priceInput.value = loadedProduct.price;
                stockInput.value = loadedProduct.stock_quantity;
                displayOrderInput.value = loadedProduct.display_order;
                isActiveInput.checked = !!loadedProduct.is_active;
                allowPreorderInput.checked = !!loadedProduct.allow_preorder_when_oos;
                availabilityDateInput.value = loadedProduct.availability_date || ''; // DATE
                shortDescriptionInput.value = loadedProduct.short_description ?? '';
                longDescriptionInput.value = loadedProduct.long_description ?? '';
                renderProductCategories();
                await loadImages('parent'); // Charge images parent
                await loadVariants();
                await loadCustomizations();
            } catch (error) {
                console.error(error);
                showMessage('Erreur de communication avec le serveur.', 'error');
            }
        }

        // --- GESTION IMAGES (Parent & Variante) ---
        async function loadImages(context, variantId = null) {
            if (!productIdInput.value) return;
            let url = `${apiBaseUrl}/product_images/list.php?product_id=${productIdInput.value}`;

            // Si contexte parent, on veut que celles sans variant_id
            if (context === 'parent') url += '&variant_id=null';
            // Si contexte variante, on veut celles de la variante
            if (context === 'variant' && variantId) url += `&variant_id=${variantId}`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                const container = (context === 'parent') ? document.getElementById('imagesGrid') : document.getElementById('variantImagesGrid');
                container.innerHTML = '';

                if (data.success && data.data.length > 0) {
                    data.data.forEach(img => {
                        const div = document.createElement('div');
                        div.className = 'image-card';
                        div.innerHTML = `
                            <img src="/storage/product_images/${img.file_path}" class="image-thumb">
                            <div class="image-actions">
                                ${context === 'parent' ? `<button type="button" class="btn-soft" onclick="setMainImage(${img.id})">★</button>` : ''}
                                <button type="button" class="btn-danger" onclick="deleteImage(${img.id}, '${context}', ${variantId})">×</button>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<span style="font-size:11px;color:#999;">Aucune image.</span>';
                }
            } catch (e) { console.error(e); }
        }

        async function uploadImage(file, context, variantId = null) {
            const fd = new FormData();
            fd.append('product_id', productIdInput.value);
            fd.append('image', file);
            if (variantId) fd.append('variant_id', variantId);

            try {
                const res = await fetch(`${apiBaseUrl}/product_images/upload.php`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    loadImages(context, variantId);
                } else {
                    showMessage('Erreur upload', 'error');
                }
            } catch (e) { console.error(e); }
        }

        // Bouton Upload Parent
        document.getElementById('uploadImageButton').addEventListener('click', () => {
            const inp = document.getElementById('imageInput');
            if(inp.files[0]) { uploadImage(inp.files[0], 'parent'); inp.value=''; }
        });

        // Bouton Upload Variante
        document.getElementById('uploadVariantImageButton').addEventListener('click', () => {
            const inp = document.getElementById('variantImageUploadInput');
            const vid = document.getElementById('variantId').value;
            if(inp.files[0] && vid) { uploadImage(inp.files[0], 'variant', vid); inp.value=''; }
        });

        window.deleteImage = async (id, context, variantId) => {
            if(!confirm('Supprimer ?')) return;
            await fetch(`${apiBaseUrl}/product_images/delete.php`, { method:'POST', body: JSON.stringify({id}) });
            loadImages(context, variantId);
        };

        window.setMainImage = async (id) => {
            await fetch(`${apiBaseUrl}/product_images/set_main.php`, { method:'POST', body: JSON.stringify({id}) });
            loadImages('parent');
        };

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
                    if (cid > 0) selectedCategoryIds.push(cid);
                }
            }

            const payload = {
                id: idValue > 0 ? idValue : undefined,
                name: nameInput.value.trim(),
                slug: slugInput.value.trim(),
                short_description: shortDescriptionInput.value.trim(),
                long_description: longDescriptionInput.value.trim(),
                price: parsePrice(priceInput.value),
                stock_quantity: parseInt(stockInput.value, 10) || 0,
                allow_preorder_when_oos: allowPreorderInput.checked ? 1 : 0,
                availability_date: availabilityDateInput.value || null, // DATE
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
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'include', body: JSON.stringify(payload)
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
                if (!loadedProduct) loadedProduct = {id: pid, category_ids: selectedCategoryIds};
                setTimeout(() => { window.location.href = '/admin/products.php'; }, 800);
            } catch (error) {
                console.error(error);
                showMessage('Erreur de communication avec le serveur.', 'error');
                saveButton.disabled = false;
            }
        }

        productForm.addEventListener('submit', saveProduct);

        // --- VARIANTES ---
        async function loadVariants() {
            if (!productIdInput.value) { variants = []; renderVariants(); toggleParentFields(); return; }
            try {
                const res = await fetch(`${apiBaseUrl}/variants/list.php?product_id=` + encodeURIComponent(productIdInput.value), { method: 'GET', credentials: 'include' });
                const data = await res.json();
                if (!data.success) return;
                variants = data.data || [];
                renderVariants();
                toggleParentFields(); // Mise à jour de l'affichage
            } catch (error) { console.error(error); }
        }

        function renderVariants() {
            variantsTableBody.innerHTML = '';
            if (!variants.length) {
                let tr = document.createElement('tr'); let td = document.createElement('td'); td.colSpan = 6; td.textContent = 'Aucune déclinaison.'; td.style.fontSize = '12px'; td.style.color = '#6b7280'; tr.appendChild(td); variantsTableBody.appendChild(tr); return;
            }
            for (let v of variants) {
                let tr = document.createElement('tr');
                let tdName = document.createElement('td'); tdName.textContent = v.name; tr.appendChild(tdName);
                let tdSku = document.createElement('td'); tdSku.textContent = v.sku || '-'; tr.appendChild(tdSku);
                let tdPrice = document.createElement('td');
                if (v.price === null || typeof v.price === 'undefined') tdPrice.textContent = '—';
                else tdPrice.textContent = parsePrice(v.price).toFixed(2) + ' €';
                tr.appendChild(tdPrice);
                let tdStock = document.createElement('td'); tdStock.textContent = v.stock_quantity;
                if (v.stock_quantity <= 0) {
                    if (v.allow_preorder_when_oos) {
                        let span = document.createElement('span'); span.className = 'badge'; span.style.background = '#fef3c7'; span.style.color = '#92400e'; span.textContent = 'Précommande'; span.style.marginLeft = '4px'; tdStock.appendChild(span);
                    } else {
                        let span = document.createElement('span'); span.className = 'badge badge-red'; span.style.background = '#fee2e2'; span.style.color = '#b91c1c'; span.textContent = 'Rupture'; span.style.marginLeft = '4px'; tdStock.appendChild(span);
                    }
                }
                tr.appendChild(tdStock);
                let tdStatus = document.createElement('td'); let pill = document.createElement('span'); pill.className = 'pill-status ' + (v.is_active ? 'green' : 'red'); pill.textContent = v.is_active ? 'Active' : 'Inactive'; tdStatus.appendChild(pill); tr.appendChild(tdStatus);
                let tdActions = document.createElement('td');
                tdActions.className = 'flex';
                let editBtn = document.createElement('button'); editBtn.type = 'button'; editBtn.className = 'btn-soft'; editBtn.textContent = 'Éditer'; editBtn.addEventListener('click', () => openVariantEditor(v));
                let delBtn = document.createElement('button'); delBtn.type = 'button'; delBtn.className = 'btn-danger'; delBtn.textContent = 'Supprimer'; delBtn.style.marginLeft = '4px'; delBtn.addEventListener('click', () => deleteVariant(v.id));
                tdActions.appendChild(editBtn); tdActions.appendChild(delBtn); tr.appendChild(tdActions);
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
                variantAvailabilityDateInput.value = variant.availability_date || ''; // DATE
                variantIsActiveInput.checked = !!variant.is_active;
                variantAllowPreorderInput.checked = !!variant.allow_preorder_when_oos;

                // Charger images variante
                const vGrid = document.getElementById('variantImagesGrid');
                vGrid.innerHTML = '';
                document.querySelector('.variant-images-section').style.display = 'block';
                loadImages('variant', variant.id);
            } else {
                variantIdInput.value = '';
                variantNameInput.value = '';
                variantSkuInput.value = '';
                variantMaterialInput.value = '';
                variantColorInput.value = '';
                variantPriceInput.value = '';
                variantStockInput.value = '0';
                variantOrderInput.value = '0';
                variantAvailabilityDateInput.value = '';
                variantIsActiveInput.checked = true;
                variantAllowPreorderInput.checked = true;

                // Pas d'image si nouvelle variante
                document.querySelector('.variant-images-section').style.display = 'none';
            }
        }

        function closeVariantEditor() { variantEditor.style.display = 'none'; variantEmptyHint.style.display = 'block'; }
        addVariantButton.addEventListener('click', () => openVariantEditor(null));
        variantCancelButton.addEventListener('click', () => closeVariantEditor());

        async function saveVariant() {
            if (!productIdInput.value) { showMessage('Enregistrez le produit avant les déclinaisons.', 'error'); return; }
            const productId = parseInt(productIdInput.value, 10);
            const id = variantIdInput.value ? parseInt(variantIdInput.value, 10) : 0;
            const payload = {
                product_id: productId,
                id: id || undefined,
                name: variantNameInput.value.trim(),
                sku: variantSkuInput.value.trim(),
                material: variantMaterialInput.value.trim(),
                color: variantColorInput.value.trim(),
                price: variantPriceInput.value === '' ? null : parsePrice(variantPriceInput.value),
                stock_quantity: parseInt(variantStockInput.value, 10) || 0,
                allow_preorder_when_oos: variantAllowPreorderInput.checked ? 1 : 0,
                availability_date: variantAvailabilityDateInput.value || null, // DATE
                is_active: variantIsActiveInput.checked ? 1 : 0,
                display_order: parseInt(variantOrderInput.value, 10) || 0
            };
            if (!payload.name) { showMessage('Nom obligatoire.', 'error'); return; }
            variantSaveButton.disabled = true;
            try {
                const res = await fetch(`${apiBaseUrl}/variants/save.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify(payload) });
                const data = await res.json();
                if (!res.ok || !data.success) { showMessage('Erreur enregistrement.', 'error'); variantSaveButton.disabled = false; return; }
                await loadVariants(); closeVariantEditor();
            } catch (error) { console.error(error); showMessage('Erreur serveur.', 'error'); } finally { variantSaveButton.disabled = false; }
        }
        variantSaveButton.addEventListener('click', saveVariant);

        async function deleteVariant(id) {
            if (!confirm('Supprimer ?')) return;
            try {
                const res = await fetch(`${apiBaseUrl}/variants/delete.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify({id}) });
                const data = await res.json();
                if (data.success) await loadVariants();
            } catch (e) { console.error(e); }
        }

        // Customizations (Identique)
        async function loadCustomizations() {
            if (!productIdInput.value) { customizations = []; renderCustomizations(); return; }
            try {
                const res = await fetch(`${apiBaseUrl}/customizations/list.php?product_id=` + encodeURIComponent(productIdInput.value), { method: 'GET', credentials: 'include' });
                const data = await res.json(); if (data.success) { customizations = data.data || []; renderCustomizations(); }
            } catch (e) { console.error(e); }
        }
        function renderCustomizations() {
            customizationsTableBody.innerHTML = '';
            if (!customizations.length) {
                let tr = document.createElement('tr'); let td = document.createElement('td'); td.colSpan = 5; td.textContent = 'Aucune personnalisation.'; td.style.fontSize = '12px'; td.style.color = '#6b7280'; tr.appendChild(td); customizationsTableBody.appendChild(tr); return;
            }
            for (let c of customizations) {
                let tr = document.createElement('tr');
                let tdLabel = document.createElement('td'); tdLabel.textContent = c.label; tr.appendChild(tdLabel);
                let tdType = document.createElement('td'); tdType.textContent = c.field_type; tr.appendChild(tdType);
                let tdRequired = document.createElement('td'); tdRequired.textContent = c.is_required ? 'Oui' : 'Non'; tr.appendChild(tdRequired);
                let tdFreeText = document.createElement('td'); tdFreeText.textContent = c.allow_free_text ? 'Oui' : 'Non'; tr.appendChild(tdFreeText);
                let tdActions = document.createElement('td');
                let editBtn = document.createElement('button'); editBtn.type = 'button'; editBtn.className = 'btn-soft'; editBtn.textContent = 'Éditer'; editBtn.addEventListener('click', () => openCustomizationEditor(c));
                let delBtn = document.createElement('button'); delBtn.type = 'button'; delBtn.className = 'btn-danger'; delBtn.textContent = 'Supprimer'; delBtn.style.marginLeft = '4px'; delBtn.addEventListener('click', () => deleteCustomization(c.id));
                tdActions.appendChild(editBtn); tdActions.appendChild(delBtn); tr.appendChild(tdActions); customizationsTableBody.appendChild(tr);
            }
        }
        function openCustomizationEditor(c = null) {
            customizationEmptyHint.style.display = 'none'; customizationEditor.style.display = 'block';
            if (c) {
                customizationIdInput.value = c.id; customizationLabelInput.value = c.label; customizationFieldNameInput.value = c.field_name; customizationFieldTypeInput.value = c.field_type; customizationOrderInput.value = c.display_order;
                customizationAllowFreeTextInput.checked = !!c.allow_free_text; customizationFreeTextLabelInput.value = c.free_text_label || ''; customizationFreeTextMaxLengthInput.value = c.free_text_max_length || ''; customizationIsRequiredInput.checked = !!c.is_required; currentCustomizationOptions = c.options || [];
            } else {
                customizationIdInput.value = ''; customizationLabelInput.value = ''; customizationFieldNameInput.value = ''; customizationFieldTypeInput.value = 'text'; customizationOrderInput.value = '0'; customizationAllowFreeTextInput.checked = false; customizationFreeTextLabelInput.value = ''; customizationFreeTextMaxLengthInput.value = ''; customizationIsRequiredInput.checked = false; currentCustomizationOptions = [];
            }
            renderOptions();
        }
        function closeCustomizationEditor() { customizationEditor.style.display = 'none'; customizationEmptyHint.style.display = 'block'; }
        function renderOptions() {
            optionsTableBody.innerHTML = '';
            if (!currentCustomizationOptions.length) {
                let tr = document.createElement('tr'); let td = document.createElement('td'); td.colSpan = 3; td.textContent = 'Aucune option.'; td.style.fontSize = '12px'; td.style.color = '#6b7280'; tr.appendChild(td); optionsTableBody.appendChild(tr); return;
            }
            for (let opt of currentCustomizationOptions) {
                let tr = document.createElement('tr'); let tdLabel = document.createElement('td'); tdLabel.textContent = opt.label; tr.appendChild(tdLabel);
                let tdPrice = document.createElement('td'); let euros = parsePrice(opt.price_delta).toFixed(2); tdPrice.textContent = euros === '0.00' ? '—' : ('+' + euros + ' €'); tr.appendChild(tdPrice);
                let tdActions = document.createElement('td'); let delBtn = document.createElement('button'); delBtn.type = 'button'; delBtn.className = 'btn-danger'; delBtn.textContent = 'Supprimer';
                delBtn.addEventListener('click', async () => { if(opt.id) await deleteOption(opt.id); else { currentCustomizationOptions = currentCustomizationOptions.filter(o => o !== opt); renderOptions(); } });
                tdActions.appendChild(delBtn); tr.appendChild(tdActions); optionsTableBody.appendChild(tr);
            }
        }
        addOptionButton.addEventListener('click', () => {
            let label = window.prompt('Label ?'); if (!label) return;
            let priceStr = window.prompt('Supplément € :', ''); let price = parsePrice(priceStr);
            currentCustomizationOptions.push({ id: null, label, description: null, price_delta: price, display_order: currentCustomizationOptions.length }); renderOptions();
        });
        async function saveCustomization() {
            if (!productIdInput.value) { showMessage('Enregistrez le produit d’abord.', 'error'); return; }
            const pid = parseInt(productIdInput.value, 10); const id = customizationIdInput.value ? parseInt(customizationIdInput.value, 10) : 0;
            const payload = {
                product_id: pid, id: id || undefined, label: customizationLabelInput.value.trim(), field_name: customizationFieldNameInput.value.trim(), field_type: customizationFieldTypeInput.value, is_required: customizationIsRequiredInput.checked ? 1 : 0,
                allow_free_text: customizationAllowFreeTextInput.checked ? 1 : 0, free_text_label: customizationFreeTextLabelInput.value.trim(), free_text_max_length: parseInt(customizationFreeTextMaxLengthInput.value, 10) || null, display_order: parseInt(customizationOrderInput.value, 10) || 0
            };
            if (!payload.label || !payload.field_name) { showMessage('Label/Nom requis.', 'error'); return; }
            customizationSaveButton.disabled = true;
            try {
                const res = await fetch(`${apiBaseUrl}/customizations/save.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify(payload) });
                const data = await res.json(); if (!res.ok || !data.success) { showMessage('Erreur enregistrement.', 'error'); return; }
                await syncOptions(data.data.id); await loadCustomizations(); closeCustomizationEditor();
            } catch (e) { console.error(e); } finally { customizationSaveButton.disabled = false; }
        }
        customizationSaveButton.addEventListener('click', saveCustomization); customizationCancelButton.addEventListener('click', closeCustomizationEditor); addCustomizationButton.addEventListener('click', () => openCustomizationEditor(null));
        async function syncOptions(cid) {
            for (let opt of currentCustomizationOptions) {
                const payload = { customization_id: cid, id: opt.id || undefined, label: opt.label, description: opt.description, price_delta: opt.price_delta, display_order: opt.display_order };
                try { await fetch(`${apiBaseUrl}/customizations/options_save.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify(payload) }); } catch (e) {}
            }
        }
        async function deleteOption(id) { try { const res = await fetch(`${apiBaseUrl}/customizations/options_delete.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify({id}) }); const data = await res.json(); if (data.success) { currentCustomizationOptions = currentCustomizationOptions.filter(o => o.id !== id); renderOptions(); } } catch (e) {} }
        async function deleteCustomization(id) { if(!confirm('Supprimer ?')) return; try { const res = await fetch(`${apiBaseUrl}/customizations/delete.php`, { method: 'POST', headers: {'Content-Type': 'application/json'}, credentials: 'include', body: JSON.stringify({id}) }); const data = await res.json(); if (data.success) { await loadCustomizations(); closeCustomizationEditor(); } } catch (e) {} }

        (async function init() {
            await ensureAuthenticated();
            await loadCategories();
            await loadProductIfEditing();
        })();
    </script>
<?php
require __DIR__ . '/_footer.php';
?>