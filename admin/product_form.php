<?php
// admin/product_form.php
declare(strict_types=1);

$pageTitle = 'Produit form';
$activeMenu = 'product_form';
require __DIR__ . '/_header.php';
?>
    <style>
        /* ... (Gardez vos styles CSS précédents, j'ajoute juste ceux pour les boutons d'options) ... */
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

        /* Nouveaux Styles Générateur */
        .attributes-selector { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; }
        .attr-group { margin-bottom: 15px; }
        .attr-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .attr-group-title { font-weight: 600; font-size: 13px; text-transform: uppercase; color: #374151; }
        .btn-add-opt { font-size: 11px; padding: 2px 6px; background: #e5e7eb; color: #374151; border-radius: 4px; cursor: pointer; border:none; }
        .btn-add-opt:hover { background: #d1d5db; }

        .attr-options-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .attr-option-label { display: inline-flex; align-items: center; gap: 5px; background: #fff; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; cursor: pointer; user-select: none; position: relative; padding-right: 24px; }
        .attr-option-label:hover { border-color: #9ca3af; }
        .attr-option-label input:checked + span { font-weight: 600; color: #111827; }

        .btn-del-opt {
            position: absolute; right: 2px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: transparent; color: #9ca3af; font-size: 14px; line-height: 1;
            opacity: 0.5; transition: opacity 0.2s;
        }
        .btn-del-opt:hover { background: #fee2e2; color: #b91c1c; opacity: 1; }
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
                    <div class="label-cell"><label for="nameInput">Nom du produit</label></div>
                    <div class="field-cell"><input type="text" id="nameInput" required></div>

                    <div class="label-cell"><label for="slugInput">Slug (URL)</label></div>
                    <div class="field-cell"><input type="text" id="slugInput" required></div>

                    <div class="parent-masked-info" id="parentMaskedInfo">
                        Ce produit possède des déclinaisons. Le prix, le stock et la disponibilité sont gérés individuellement ci-dessous.
                    </div>

                    <div class="label-cell js-parent-field"><label for="priceInput">Prix (en euros)</label></div>
                    <div class="field-cell js-parent-field"><input type="number" id="priceInput" min="0" step="0.01" inputmode="decimal"></div>

                    <div class="label-cell js-parent-field"><label for="stockInput">Stock global</label></div>
                    <div class="field-cell js-parent-field"><input type="number" id="stockInput" min="0" step="1"></div>

                    <div class="label-cell js-parent-field"><label for="displayOrderInput">Ordre d’affichage</label></div>
                    <div class="field-cell js-parent-field"><input type="number" id="displayOrderInput" step="1" value="0"></div>

                    <div class="label-cell js-parent-field">Statut & Stock</div>
                    <div class="field-cell js-parent-field">
                        <div class="field-inline">
                            <input type="checkbox" id="isActiveInput" checked><span>Produit actif</span>
                        </div>
                        <div class="field-inline">
                            <input type="checkbox" id="allowPreorderInput" checked><span>Autoriser la précommande en cas de rupture</span>
                        </div>
                    </div>

                    <div class="label-cell js-parent-field"><label for="availabilityDateInput">Disponibilité</label></div>
                    <div class="field-cell js-parent-field">
                        <input type="date" id="availabilityDateInput">
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">Date prévisionnelle d'expédition si en précommande.</div>
                    </div>

                    <div class="label-cell"><label for="shortDescriptionInput">Description courte</label></div>
                    <div class="field-cell"><textarea id="shortDescriptionInput"></textarea></div>

                    <div class="label-cell"><label for="longDescriptionInput">Description longue</label></div>
                    <div class="field-cell"><textarea id="longDescriptionInput"></textarea></div>

                    <div class="label-cell">Catégories</div>
                    <div class="field-cell">
                        <div id="categoriesContainer" style="display:flex;flex-wrap:wrap;gap:8px 16px;"></div>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="btn-secondary" id="cancelButton">Annuler</button>
                    <button type="submit" class="btn-primary" id="saveButton">Enregistrer le produit</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="section-title">Images du produit <span class="badge badge-info" style="margin-left:6px;">Globales</span></div>
            <div class="form-grid">
                <div class="label-cell">Galerie</div>
                <div class="field-cell">
                    <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                        <input type="file" id="imageInput" accept="image/*" style="font-size:12px;">
                        <button type="button" class="btn-soft" id="uploadImageButton">Ajouter</button>
                    </div>
                    <div class="images-grid" id="imagesGrid"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="section-title">Déclinaisons & Combinaisons</div>

            <div class="form-grid">
                <div class="label-cell">1. Configuration</div>
                <div class="field-cell">
                    <div class="attributes-selector">
                        <p style="font-size:12px; color:#666; margin-top:0; margin-bottom:10px;">
                            Ajoutez des options si besoin, puis cochez celles à utiliser pour ce produit. Les options désactivées n'apparaissent pas ici sauf si vous les rajoutez.
                        </p>
                        <div id="attributesListContainer">Chargement des attributs...</div>
                        <hr style="border:0; border-top:1px solid #e5e7eb; margin:15px 0;">
                        <button type="button" class="btn-primary" id="btnGenerate">Générer les combinaisons</button>
                    </div>
                </div>

                <div class="label-cell">2. Liste générée</div>
                <div class="field-cell">
                    <div id="variantsContainer">
                        <p style="font-size:13px; color:#999; font-style:italic;">Aucune variante. Sélectionnez des attributs ci-dessus et cliquez sur Générer.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="section-title">Personnalisations (Gravure, etc.)</div>
            <div class="form-grid">
                <div class="label-cell">Règles</div>
                <div class="field-cell">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;align-items:center;">
                        <div class="chips"><span>Texte, options (motif), etc.</span></div>
                        <button type="button" class="btn-soft" id="addCustomizationButton">Ajouter une règle</button>
                    </div>
                    <table class="table-mini" id="customizationsTable">
                        <thead><tr><th>Label</th><th>Type</th><th>Obligatoire</th><th>Texte libre</th><th></th></tr></thead>
                        <tbody id="customizationsTableBody"></tbody>
                    </table>
                </div>
                <div class="label-cell">Détail</div>
                <div class="field-cell">
                    <div id="customizationEditor" style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;display:none;">
                        <input type="hidden" id="customizationId">
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                            <div><label>Label affiché</label><input type="text" id="customizationLabelInput"></div>
                            <div><label>Nom technique</label><input type="text" id="customizationFieldNameInput"></div>
                            <div><label>Type</label><select id="customizationFieldTypeInput"><option value="text">Texte</option><option value="textarea">Texte long</option><option value="select">Liste</option><option value="checkbox">Case</option></select></div>
                            <div><label>Ordre</label><input type="number" id="customizationOrderInput" step="1" value="0"></div>
                            <div><label>Options (Select)</label><button type="button" class="btn-soft" id="addOptionButton">Ajouter option</button></div>
                            <div>
                                <label>Texte libre ?</label>
                                <div class="field-inline" style="margin-bottom:4px;"><input type="checkbox" id="customizationAllowFreeTextInput"><span>Autoriser</span></div>
                                <input type="text" id="customizationFreeTextLabelInput" placeholder="Label champ texte"><input type="number" id="customizationFreeTextMaxLengthInput" placeholder="Max chars" min="1" style="margin-top:4px;">
                            </div>
                            <div><div class="field-inline" style="margin-top:18px;"><input type="checkbox" id="customizationIsRequiredInput"><span>Obligatoire</span></div></div>
                        </div>
                        <div style="margin-top:8px;"><table class="table-mini" id="optionsTable"><thead><tr><th>Label</th><th>Supplément</th><th></th></tr></thead><tbody id="optionsTableBody"></tbody></table></div>
                        <div class="actions" style="margin-top:8px;"><button type="button" class="btn-secondary" id="customizationCancelButton">Annuler</button><button type="button" class="btn-primary" id="customizationSaveButton">OK</button></div>
                    </div>
                    <div id="customizationEmptyHint" style="font-size:12px;color:#6b7280;">Sélectionnez ou ajoutez une personnalisation.</div>
                </div>
            </div>
        </div>
    </div>

    <template id="variantRowTemplate">
        <tr class="variant-row">
            <td class="v-combo" style="font-weight:600; color:#111827; font-size:12px;"></td>
            <td><input type="text" class="v-sku" placeholder="SKU" style="width:100%;"></td>
            <td><input type="number" class="v-price" step="0.01" placeholder="Prix" style="width:100%;"></td>
            <td><input type="number" class="v-stock" step="1" placeholder="Qté" style="width:100%;"></td>
            <td style="text-align:center;">
                <input type="hidden" class="v-id">
                <input type="hidden" class="v-option-ids">
                <button type="button" class="btn-soft v-edit-btn" title="Détails">+</button>
                <button type="button" class="btn-danger v-delete-btn" title="Supprimer">&times;</button>
            </td>
        </tr>
        <tr class="variant-details-row" style="display:none; background:#f9fafb;">
            <td colspan="5" style="padding:10px 15px; border-bottom:2px solid #e5e7eb;">
                <div class="form-grid" style="grid-template-columns: 100px 1fr; row-gap:5px;">
                    <div class="label-cell">Desc. courte</div>
                    <div class="field-cell"><textarea class="v-short-desc" rows="2" placeholder="Surcharge la description..."></textarea></div>
                    <div class="label-cell">Options</div>
                    <div class="field-cell field-inline">
                        <input type="checkbox" class="v-preorder"> Autoriser précommande
                        <input type="checkbox" class="v-active" checked style="margin-left:15px;"> Actif
                    </div>
                    <div class="label-cell">Disponibilité</div>
                    <div class="field-cell"><input type="date" class="v-date" style="width:auto;"></div>
                    <div class="label-cell">Images var.</div>
                    <div class="field-cell">
                        <div class="v-images-container" style="display:flex; gap:5px; flex-wrap:wrap; margin-bottom:5px;"></div>
                        <div style="font-size:11px; color:#999;">Enregistrez le produit pour gérer les images spécifiques.</div>
                    </div>
                </div>
            </td>
        </tr>
    </template>

    <script>
        const apiBaseUrl = '/admin/api';
        const messageEl = document.getElementById('message');

        // Main Form Elements
        const productForm = document.getElementById('productForm');
        const productIdInput = document.getElementById('productId');
        const nameInput = document.getElementById('nameInput');
        const slugInput = document.getElementById('slugInput');
        const priceInput = document.getElementById('priceInput');
        const stockInput = document.getElementById('stockInput');
        const displayOrderInput = document.getElementById('displayOrderInput');
        const isActiveInput = document.getElementById('isActiveInput');
        const allowPreorderInput = document.getElementById('allowPreorderInput');
        const availabilityDateInput = document.getElementById('availabilityDateInput');
        const shortDescriptionInput = document.getElementById('shortDescriptionInput');
        const longDescriptionInput = document.getElementById('longDescriptionInput');
        const categoriesContainer = document.getElementById('categoriesContainer');
        const parentMaskedInfo = document.getElementById('parentMaskedInfo');
        const parentFields = document.querySelectorAll('.js-parent-field');

        // Customization Elements
        const customizationsTableBody = document.getElementById('customizationsTableBody');
        const customizationEditor = document.getElementById('customizationEditor');
        const customizationEmptyHint = document.getElementById('customizationEmptyHint');

        let allCategories = [];
        let allAttributes = [];
        let loadedProduct = null;
        let variantsData = []; // Stockage des objets variantes
        let customizations = [];
        let currentCustomizationOptions = [];

        // --- UTILS ---
        // La fonction qui manquait :
        function getQueryParam(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name);
        }

        function showMessage(text, type='error') {
            if(!text) { messageEl.style.display='none'; return; }
            messageEl.textContent = text;
            messageEl.className = 'message '+type;
            messageEl.style.display = 'block';
        }
        function parsePrice(val) {
            if(!val) return 0;
            return Math.round(parseFloat(String(val).replace(',','.')) * 100) / 100;
        }
        function slugify(text) {
            return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }
        nameInput.addEventListener('input', () => {
            if(!slugInput.value || (loadedProduct && slugInput.value === loadedProduct.slug)) slugInput.value = slugify(nameInput.value);
        });

        // --- INIT ---
        async function init() {
            await ensureAuth();
            await Promise.all([loadCategories(), loadAttributesDefinition()]);
            await loadProductIfEditing();
        }
        async function ensureAuth() {
            const res = await fetch(`${apiBaseUrl}/me.php`);
            const data = await res.json();
            if(!data.authenticated) window.location.href='/admin/index.php';
        }

        // --- CATEGORIES ---
        async function loadCategories() {
            const res = await fetch(`${apiBaseUrl}/categories/list.php`);
            const data = await res.json();
            allCategories = data.data || [];
            renderCategories();
        }
        function renderCategories() {
            categoriesContainer.innerHTML = '';
            allCategories.forEach(cat => {
                const lbl = document.createElement('label');
                lbl.style.cssText = 'font-size:13px; display:flex; align-items:center; gap:4px;';
                const chk = document.createElement('input'); chk.type='checkbox'; chk.value=cat.id;
                if(loadedProduct && loadedProduct.category_ids.includes(cat.id)) chk.checked = true;
                lbl.append(chk, document.createTextNode(cat.name));
                categoriesContainer.appendChild(lbl);
            });
        }

        // --- ATTRIBUTES & GENERATOR & MANAGEMENT ---
        async function loadAttributesDefinition() {
            try {
                const res = await fetch(`${apiBaseUrl}/attributes/list_full.php`);
                const data = await res.json();
                if(data.success) {
                    allAttributes = data.data;
                    renderAttributesSelector();
                }
            } catch(e) { console.log('Attributs non chargés'); }
        }

        function renderAttributesSelector() {
            const container = document.getElementById('attributesListContainer');
            container.innerHTML = '';

            allAttributes.forEach(attr => {
                const grp = document.createElement('div'); grp.className='attr-group';

                // Header: Titre + Bouton Ajout
                const header = document.createElement('div'); header.className='attr-header';
                const title = document.createElement('div'); title.className='attr-group-title'; title.textContent = attr.name;
                const btnAdd = document.createElement('button'); btnAdd.type='button'; btnAdd.className='btn-add-opt'; btnAdd.textContent = '+ Ajouter';
                btnAdd.onclick = () => addNewOption(attr.id);
                header.append(title, btnAdd);

                const grid = document.createElement('div'); grid.className='attr-options-grid';

                attr.options.forEach(opt => {
                    // Filtre: On n'affiche que les actifs (sauf si on voulait un mode "voir archives")
                    if(opt.is_active == 0) return;

                    const lbl = document.createElement('label'); lbl.className='attr-option-label';
                    const chk = document.createElement('input');
                    chk.type='checkbox'; chk.className='attr-opt-check';
                    chk.dataset.attrId = attr.id;
                    chk.dataset.optId = opt.id;
                    chk.dataset.optName = opt.name;

                    const span = document.createElement('span'); span.textContent = opt.name;

                    // Bouton suppression (désactivation)
                    const btnDel = document.createElement('button'); btnDel.type='button'; btnDel.className='btn-del-opt'; btnDel.innerHTML = '&times;';
                    btnDel.title = "Désactiver (mettre à la poubelle)";
                    btnDel.onclick = (e) => {
                        e.preventDefault(); // Empêche clic checkbox
                        disableOption(opt.id, opt.name);
                    };

                    lbl.append(chk, span, btnDel);
                    grid.appendChild(lbl);
                });
                grp.append(header, grid);
                container.appendChild(grp);
            });
        }

        async function addNewOption(attrId) {
            const name = prompt("Nom de la nouvelle option (ex: Rouge, XL) :");
            if (!name) return;
            // Valeur optionnelle (ex: hex code), on peut faire simple pour l'instant
            const value = "";

            try {
                const res = await fetch(`${apiBaseUrl}/attributes/option_save.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ attribute_id: attrId, name: name, value: value })
                });
                const data = await res.json();
                if(data.success) {
                    await loadAttributesDefinition(); // Recharge la liste
                } else {
                    alert("Erreur: " + (data.error || "Impossible d'ajouter"));
                }
            } catch(e) { console.error(e); }
        }

        async function disableOption(optId, optName) {
            if(!confirm(`Retirer l'option "${optName}" des propositions pour les nouveaux produits ?\n(Les produits existants la conserveront)`)) return;

            try {
                const res = await fetch(`${apiBaseUrl}/attributes/option_save.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'disable', id: optId })
                });
                const data = await res.json();
                if(data.success) {
                    await loadAttributesDefinition(); // Recharge la liste (l'option disparaîtra)
                }
            } catch(e) { console.error(e); }
        }

        document.getElementById('btnGenerate').addEventListener('click', () => {
            const selected = {};
            document.querySelectorAll('.attr-opt-check:checked').forEach(chk => {
                const aId = chk.dataset.attrId;
                if(!selected[aId]) selected[aId] = [];
                selected[aId].push({ id: parseInt(chk.dataset.optId), name: chk.dataset.optName });
            });
            const keys = Object.keys(selected);
            if(keys.length === 0) { alert('Sélectionnez au moins un attribut.'); return; }

            const arrays = keys.map(k => selected[k]);
            const combos = cartesian(arrays);

            const newVariants = combos.map(c => ({
                id: null,
                option_ids: c.map(o => o.id),
                combo_name: c.map(o => o.name).join(' - '),
                sku: '', price: '', stock: 0, short_description: '',
                allow_preorder: true, is_active: true, availability_date: ''
            }));

            if(confirm('Cela va remplacer la liste ci-dessous (les variantes existantes non compatibles seront perdues à la sauvegarde). Continuer ?')) {
                variantsData = newVariants;
                renderVariantsTable();
            }
        });

        function cartesian(args) {
            var r = [], max = args.length-1;
            function helper(arr, i) {
                for (var j=0, l=args[i].length; j<l; j++) {
                    var a = arr.slice(0); a.push(args[i][j]);
                    if (i==max) r.push(a); else helper(a, i+1);
                }
            }
            helper([], 0);
            return r;
        }

        function renderVariantsTable() {
            const container = document.getElementById('variantsContainer');
            if(variantsData.length === 0) {
                container.innerHTML = '<p style="font-size:13px; color:#999;">Aucune variante.</p>';
                toggleParentFields(false);
                return;
            }
            toggleParentFields(true);

            let html = `
            <table class="table-mini" style="border:1px solid #e5e7eb;">
                <thead><tr><th>Combinaison</th><th>SKU</th><th>Prix</th><th>Stock</th><th style="width:80px;">Actions</th></tr></thead>
                <tbody id="genVariantsBody"></tbody>
            </table>
            <div style="margin-top:5px; font-size:11px; color:#666;">Prix vide = Prix du produit parent.</div>
            `;
            container.innerHTML = html;
            const tbody = document.getElementById('genVariantsBody');
            const tpl = document.getElementById('variantRowTemplate');

            variantsData.forEach((v, idx) => {
                const clone = tpl.content.cloneNode(true);
                const tr = clone.querySelector('.variant-row');
                const det = clone.querySelector('.variant-details-row');

                tr.querySelector('.v-combo').textContent = v.combo_name;
                const inpSku = tr.querySelector('.v-sku'); inpSku.value = v.sku || '';
                const inpPrice = tr.querySelector('.v-price'); inpPrice.value = v.price || '';
                const inpStock = tr.querySelector('.v-stock'); inpStock.value = v.stock;

                // Bind inputs to data array
                inpSku.onchange = e => variantsData[idx].sku = e.target.value;
                inpPrice.onchange = e => variantsData[idx].price = e.target.value;
                inpStock.onchange = e => variantsData[idx].stock = e.target.value;

                // Details inputs
                const txtDesc = det.querySelector('.v-short-desc'); txtDesc.value = v.short_description || '';
                txtDesc.onchange = e => variantsData[idx].short_description = e.target.value;

                const chkPre = det.querySelector('.v-preorder'); chkPre.checked = !!v.allow_preorder;
                chkPre.onchange = e => variantsData[idx].allow_preorder = e.target.checked;

                const chkAct = det.querySelector('.v-active'); chkAct.checked = !!v.is_active;
                chkAct.onchange = e => variantsData[idx].is_active = e.target.checked;

                const inpDate = det.querySelector('.v-date'); inpDate.value = v.availability_date || '';
                inpDate.onchange = e => variantsData[idx].availability_date = e.target.value;

                // Actions
                tr.querySelector('.v-delete-btn').onclick = () => {
                    variantsData.splice(idx, 1);
                    renderVariantsTable();
                };
                tr.querySelector('.v-edit-btn').onclick = () => {
                    det.style.display = (det.style.display === 'none' ? 'table-row' : 'none');
                };

                if(v.id) {
                    loadVariantImages(v.id, det.querySelector('.v-images-container'));
                }

                tbody.append(tr, det);
            });
        }

        function toggleParentFields(hasVariants) {
            parentMaskedInfo.style.display = hasVariants ? 'block' : 'none';
            parentFields.forEach(el => el.style.display = hasVariants ? 'none' : 'block');
        }

        // --- LOAD PRODUCT ---
        async function loadProductIfEditing() {
            const id = getQueryParam('id');
            if(!id) return;

            const res = await fetch(`${apiBaseUrl}/products/get.php?id=${id}`);
            const data = await res.json();
            if(!data.success) return;

            loadedProduct = data.data;
            productIdInput.value = loadedProduct.id;
            nameInput.value = loadedProduct.name;
            slugInput.value = loadedProduct.slug;
            priceInput.value = loadedProduct.price;
            stockInput.value = loadedProduct.stock_quantity;
            displayOrderInput.value = loadedProduct.display_order;
            isActiveInput.checked = !!loadedProduct.is_active;
            allowPreorderInput.checked = !!loadedProduct.allow_preorder_when_oos;
            availabilityDateInput.value = loadedProduct.availability_date || '';
            shortDescriptionInput.value = loadedProduct.short_description || '';
            longDescriptionInput.value = loadedProduct.long_description || '';

            renderCategories();
            loadImages('parent');

            await loadExistingVariants(id);
            loadCustomizations();
        }

        async function loadExistingVariants(pid) {
            try {
                const res = await fetch(`${apiBaseUrl}/variants/list_full.php?product_id=${pid}`);
                const data = await res.json();
                if(data.success && data.data.length > 0) {
                    variantsData = data.data.map(v => ({
                        id: v.id,
                        option_ids: v.option_ids,
                        combo_name: v.name,
                        sku: v.sku,
                        price: v.price,
                        stock: v.stock_quantity,
                        short_description: v.short_description,
                        allow_preorder: v.allow_preorder_when_oos,
                        is_active: v.is_active,
                        availability_date: v.availability_date
                    }));
                    renderVariantsTable();

                    // On coche les options utilisées (même si elles sont désactivées, il faudrait idéalement les voir,
                    // mais ici on ne voit que les actives. Si une option est utilisée mais inactive, elle ne sera pas cochée
                    // dans le générateur, mais la variante existe bien dans le tableau du bas. C'est le comportement voulu.)
                    const usedOptIds = new Set();
                    variantsData.forEach(v => v.option_ids.forEach(oid => usedOptIds.add(oid)));
                    document.querySelectorAll('.attr-opt-check').forEach(chk => {
                        if(usedOptIds.has(parseInt(chk.dataset.optId))) chk.checked = true;
                    });
                }
            } catch(e) { console.log('No variants'); }
        }

        // --- IMAGES (Parent & Variant) ---
        async function loadImages(context, container=null) {
            const pid = productIdInput.value;
            if(!pid) return;
            let url = `${apiBaseUrl}/product_images/list.php?product_id=${pid}&variant_id=null`;
            const res = await fetch(url);
            const data = await res.json();
            const grid = document.getElementById('imagesGrid');
            grid.innerHTML = '';
            if(data.data) {
                data.data.forEach(img => {
                    const div = document.createElement('div'); div.className='image-card';
                    div.innerHTML = `<img src="/storage/product_images/${img.file_path}" class="image-thumb">
                        <div class="image-actions">
                            <button type="button" class="btn-soft" onclick="setMainImage(${img.id})">★</button>
                            <button type="button" class="btn-danger" onclick="deleteImage(${img.id})">×</button>
                        </div>`;
                    grid.appendChild(div);
                });
            }
        }
        document.getElementById('uploadImageButton').addEventListener('click', async () => {
            const f = document.getElementById('imageInput').files[0];
            if(!f || !productIdInput.value) return;
            const fd = new FormData(); fd.append('product_id', productIdInput.value); fd.append('image', f);
            await fetch(`${apiBaseUrl}/product_images/upload.php`, {method:'POST', body:fd});
            loadImages('parent');
        });
        window.deleteImage = async (id) => {
            if(!confirm('Supprimer?')) return;
            await fetch(`${apiBaseUrl}/product_images/delete.php`, {method:'POST', body:JSON.stringify({id})});
            loadImages('parent');
        }
        window.setMainImage = async (id) => {
            await fetch(`${apiBaseUrl}/product_images/set_main.php`, {method:'POST', body:JSON.stringify({id})});
            loadImages('parent');
        }

        async function loadVariantImages(vid, container) {
            container.innerHTML = 'Chargement...';
            const res = await fetch(`${apiBaseUrl}/product_images/list.php?product_id=${productIdInput.value}&variant_id=${vid}`);
            const data = await res.json();
            container.innerHTML = '';

            const upDiv = document.createElement('div');
            upDiv.innerHTML = `<label class="btn-soft" style="font-size:10px; cursor:pointer;">+ Img <input type="file" style="display:none" onchange="uploadVariantImage(this, ${vid})"></label>`;
            container.appendChild(upDiv);

            if(data.data) {
                data.data.forEach(img => {
                    const imgEl = document.createElement('div');
                    imgEl.style.cssText = "position:relative; width:40px; height:40px;";
                    imgEl.innerHTML = `<img src="/storage/product_images/${img.file_path}" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                    <button onclick="deleteImageVar(${img.id}, ${vid})" style="position:absolute;top:-5px;right:-5px;background:red;color:white;border-radius:50%;width:15px;height:15px;font-size:10px;line-height:1;">&times;</button>`;
                    container.appendChild(imgEl);
                });
            }
        }
        window.uploadVariantImage = async (inp, vid) => {
            const f = inp.files[0];
            const fd = new FormData();
            fd.append('product_id', productIdInput.value);
            fd.append('variant_id', vid);
            fd.append('image', f);
            await fetch(`${apiBaseUrl}/product_images/upload.php`, {method:'POST', body:fd});
            const container = inp.closest('.v-images-container');
            loadVariantImages(vid, container);
        }
        window.deleteImageVar = async (iid, vid) => {
            if(!confirm('Supprimer?')) return;
            await fetch(`${apiBaseUrl}/product_images/delete.php`, {method:'POST', body:JSON.stringify({id:iid})});
            const btn = document.querySelector(`button[onclick="deleteImageVar(${iid}, ${vid})"]`);
            if(btn) loadVariantImages(vid, btn.closest('.v-images-container'));
        }

        // --- SAVE PRODUCT ---
        productForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveButton.disabled = true;
            saveButton.textContent = 'Enregistrement...'; // Feedback visuel
            showMessage('Enregistrement en cours...', 'info');

            const payload = {
                id: productIdInput.value || undefined,
                name: nameInput.value,
                slug: slugInput.value,
                price: parsePrice(priceInput.value),
                stock_quantity: parseInt(stockInput.value)||0,
                allow_preorder_when_oos: allowPreorderInput.checked?1:0,
                availability_date: availabilityDateInput.value || null,
                is_active: isActiveInput.checked?1:0,
                display_order: parseInt(displayOrderInput.value)||0,
                short_description: shortDescriptionInput.value,
                long_description: longDescriptionInput.value,
                category_ids: Array.from(categoriesContainer.querySelectorAll('input:checked')).map(c=>parseInt(c.value)),

                // On envoie bien le tableau complet des variantes générées
                variants: variantsData
            };

            try {
                const res = await fetch(`${apiBaseUrl}/products/save.php`, {
                    method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
                });
                const data = await res.json();

                if(data.success) {
                    showMessage('Produit sauvegardé avec succès !', 'success');

                    // Si c'est un NOUVEAU produit (pas d'ID au départ), on redirige vers sa page d'édition pour rester dessus
                    if(!payload.id) {
                        setTimeout(() => window.location.href=`/admin/product_form.php?id=${data.data.id}`, 500);
                    }
                    else {
                        // Si on est DÉJÀ en édition, on ne bouge pas, on recharge juste les données (pour rafraîchir les IDs des variantes)
                        await loadProductIfEditing();

                        // On remet le bouton à la normale
                        saveButton.disabled = false;
                        saveButton.textContent = 'Enregistrer le produit';
                    }
                } else {
                    showMessage('Erreur sauvegarde: '+ (data.message||'Erreur inconnue'), 'error');
                    saveButton.disabled = false;
                    saveButton.textContent = 'Enregistrer le produit';
                }
            } catch(err) {
                console.error(err);
                showMessage('Erreur technique lors de la sauvegarde.', 'error');
                saveButton.disabled = false;
                saveButton.textContent = 'Enregistrer le produit';
            }
        });

        // --- CUSTOMIZATIONS ---
        async function loadCustomizations() {
            if(!productIdInput.value) return;
            const res = await fetch(`${apiBaseUrl}/customizations/list.php?product_id=${productIdInput.value}`);
            const data = await res.json();
            customizations = data.data||[]; renderCustomizations();
        }
        function renderCustomizations() {
            customizationsTableBody.innerHTML = customizations.length ? '' : '<tr><td colspan="5" style="color:#999">Aucune.</td></tr>';
            customizations.forEach(c => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${c.label}</td><td>${c.field_type}</td><td>${c.is_required?'Oui':'Non'}</td><td>${c.allow_free_text?'Oui':'Non'}</td>
                <td><button type="button" class="btn-soft" onclick="editCust(${c.id})">Edit</button> <button type="button" class="btn-danger" onclick="delCust(${c.id})">×</button></td>`;
                customizationsTableBody.appendChild(tr);
            });
        }
        document.getElementById('addCustomizationButton').onclick = () => openCustEditor(null);
        document.getElementById('customizationCancelButton').onclick = () => { customizationEditor.style.display='none'; customizationEmptyHint.style.display='block'; };

        window.editCust = (id) => {
            const c = customizations.find(x=>x.id===id);
            openCustEditor(c);
        };
        window.delCust = async (id) => {
            if(!confirm('Supprimer?')) return;
            await fetch(`${apiBaseUrl}/customizations/delete.php`, {method:'POST', body:JSON.stringify({id})});
            loadCustomizations();
        };
        function openCustEditor(c) {
            customizationEditor.style.display='block'; customizationEmptyHint.style.display='none';
            const idInp = document.getElementById('customizationId');
            if(c) {
                idInp.value=c.id;
                document.getElementById('customizationLabelInput').value=c.label;
                document.getElementById('customizationFieldNameInput').value=c.field_name;
                document.getElementById('customizationFieldTypeInput').value=c.field_type;
                document.getElementById('customizationOrderInput').value=c.display_order;
                document.getElementById('customizationIsRequiredInput').checked=!!c.is_required;
                document.getElementById('customizationAllowFreeTextInput').checked=!!c.allow_free_text;
                document.getElementById('customizationFreeTextLabelInput').value=c.free_text_label||'';
                document.getElementById('customizationFreeTextMaxLengthInput').value=c.free_text_max_length||'';
                currentCustomizationOptions = c.options||[];
            } else {
                idInp.value=''; document.getElementById('customizationLabelInput').value='';
                currentCustomizationOptions=[];
            }
            renderOptions();
        }
        function renderOptions() {
            optionsTableBody.innerHTML = currentCustomizationOptions.map(o => `<tr><td>${o.label}</td><td>${o.price_delta}</td><td><button onclick="removeOpt(${o.id})" class="btn-danger">×</button></td></tr>`).join('');
        }
        document.getElementById('addOptionButton').onclick = () => {
            const l = prompt('Label?'); if(l) { currentCustomizationOptions.push({id:null, label:l, price_delta:0}); renderOptions(); }
        }
        document.getElementById('customizationSaveButton').onclick = async () => {
            const payload = {
                product_id: parseInt(productIdInput.value),
                id: document.getElementById('customizationId').value || undefined,
                label: document.getElementById('customizationLabelInput').value,
                field_name: document.getElementById('customizationFieldNameInput').value,
                field_type: document.getElementById('customizationFieldTypeInput').value,
                is_required: document.getElementById('customizationIsRequiredInput').checked?1:0,
                allow_free_text: document.getElementById('customizationAllowFreeTextInput').checked?1:0,
                free_text_label: document.getElementById('customizationFreeTextLabelInput').value,
                free_text_max_length: parseInt(document.getElementById('customizationFreeTextMaxLengthInput').value)||null,
                display_order: parseInt(document.getElementById('customizationOrderInput').value)||0
            };
            await fetch(`${apiBaseUrl}/customizations/save.php`, {method:'POST', body:JSON.stringify(payload)});
            loadCustomizations();
            customizationEditor.style.display='none';
        };

        init();
    </script>
<?php require __DIR__ . '/_footer.php'; ?>