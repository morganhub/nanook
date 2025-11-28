<?php
// admin/attributes.php
declare(strict_types=1);

$pageTitle = 'Dictionnaire des Attributs';
$activeMenu = 'attributes';
require __DIR__ . '/_header.php';
?>

    <style>
        /* Styles Grille */
        .attr-section { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .attr-header { background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .attr-title { font-weight: 600; font-size: 1.1rem; color: #111827; }
        .attr-type-badge { font-size: 0.75rem; background: #e5e7eb; padding: 2px 8px; border-radius: 12px; color: #666; margin-left: 10px; text-transform: uppercase; }

        .options-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 20px; }

        .opt-card { border: 1px solid #eee; border-radius: 6px; padding: 10px; display: flex; align-items: center; gap: 10px; background: #fff; transition: 0.2s; cursor: pointer; }
        .opt-card:hover { border-color: #111827; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-1px); }

        .visual-preview { width: 40px; height: 40px; border-radius: 6px; border: 1px solid #ddd; flex-shrink: 0; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6; position: relative;}
        /* Damier pour transparence PNG */
        .visual-preview.is-image { background-image: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 10px 10px; background-position: 0 0, 0 5px, 5px -5px, -5px 0px; }
        .visual-preview-img { width:100%; height:100%; object-fit:cover; border-radius:5px; }

        .opt-info { flex: 1; overflow: hidden; }
        .opt-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .opt-val-text { font-size: 0.75rem; color: #888; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* MODALE */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 999; backdrop-filter: blur(2px); }
        .modal-box { background: #fff; width: 100%; max-width: 450px; border-radius: 12px; padding: 25px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideUp 0.2s ease-out; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: #111827; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #374151; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }

        .visual-editor { display: flex; align-items: center; gap: 15px; margin-top: 5px; }
        .visual-large { width: 80px; height: 80px; border-radius: 8px; border: 1px solid #e5e7eb; background: #f9fafb; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .visual-large img { width: 100%; height: 100%; object-fit: cover; }

        .btn-row { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .btn-cancel { background: #fff; border: 1px solid #d1d5db; color: #374151; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        .btn-save { background: #111827; border: none; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        .btn-icon-action { padding: 6px; border: 1px solid #eee; background: #fff; border-radius: 4px; cursor: pointer; color: #555; font-size: 12px; display: flex; align-items: center; gap:5px; }
        .btn-icon-action:hover { background: #f3f4f6; }
        .btn-danger-text { color: #dc2626; border-color: #fee2e2; background: #fef2f2; }
    </style>

    <div class="page">
        <div class="page-header">
            <div class="title"><span class="brand">NANOOK</span> · Dictionnaire des Attributs</div>
            <button class="btn-primary" onclick="alert('Pour ajouter un attribut, passez par le SQL pour le moment, ou demandez le code complet CRUD.')">Gérer les types</button>
        </div>

        <div id="attributesContainer">Chargement...</div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-title">Modifier l'option</div>
            <input type="hidden" id="modalOptId">
            <input type="hidden" id="modalAttrId">
            <input type="hidden" id="modalType">

            <div class="form-group">
                <label class="form-label">Nom de l'option</label>
                <input type="text" id="modalName" class="form-input" placeholder="Ex: Rouge, Cuir...">
            </div>

            <div id="modalVisualSection" class="form-group" style="display:none;">
                <label class="form-label">Apparence</label>

                <div id="modalColorUI" style="display:none;">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" id="modalColorInput" style="height:40px; width:60px; border:none; background:none; cursor:pointer;">
                        <span id="modalColorHex" style="font-family:monospace; color:#666;"></span>
                    </div>
                </div>

                <div id="modalImageUI" style="display:none;">
                    <div class="visual-editor">
                        <div class="visual-large is-image" id="modalImagePreview">
                            <span style="color:#ccc; font-size:24px;">📷</span>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <button type="button" class="btn-icon-action" onclick="document.getElementById('modalFileUpload').click()">
                                <span>📤 Changer l'image</span>
                            </button>
                            <button type="button" class="btn-icon-action btn-danger-text" onclick="removeImage()">
                                <span>× Retirer l'image</span>
                            </button>
                            <input type="file" id="modalFileUpload" accept="image/*" style="display:none;" onchange="handleFileSelect(this)">
                        </div>
                    </div>
                    <input type="hidden" id="modalImagePath">
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button type="button" class="btn-save" onclick="saveOption()">Enregistrer</button>
            </div>

            <div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px; text-align:center;">
                <button type="button" style="color:#dc2626; background:none; border:none; font-size:12px; cursor:pointer; text-decoration:underline;" onclick="disableOption()">Désactiver cette option (Poubelle)</button>
            </div>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api';
        let currentAttributes = [];

        async function loadAttributes() {
            const container = document.getElementById('attributesContainer');
            try {
                const res = await fetch(`${apiBaseUrl}/attributes/list_full.php`);
                const data = await res.json();

                if(!data.success) { container.innerHTML = "Erreur."; return; }
                currentAttributes = data.data;
                render();

            } catch(e) { console.error(e); }
        }

        function render() {
            const container = document.getElementById('attributesContainer');
            container.innerHTML = '';

            currentAttributes.forEach(attr => {
                const section = document.createElement('div');
                section.className = 'attr-section';

                let typeLabel = attr.type;
                if(attr.type==='color') typeLabel='Couleur';
                if(attr.type==='image') typeLabel='Texture';

                section.innerHTML = `
            <div class="attr-header">
                <div><span class="attr-title">${attr.name}</span> <span class="attr-type-badge">${typeLabel}</span></div>
                <button class="btn-icon-action" onclick="openNewModal(${attr.id}, '${attr.type}')">+ Ajouter</button>
            </div>
            <div class="options-grid" id="grid-${attr.id}"></div>
        `;
                container.appendChild(section);

                const grid = section.querySelector(`#grid-${attr.id}`);
                // Filter active only (or show disabled differently? here we show active)
                attr.options.forEach(opt => {
                    if(opt.is_active == 0) return;
                    grid.appendChild(createCard(attr, opt));
                });
            });
        }

        function createCard(attr, opt) {
            const el = document.createElement('div');
            el.className = 'opt-card';
            el.onclick = () => openEditModal(attr, opt); // LE CLIC OUVRE LA MODALE

            let visual = '';
            if(attr.type === 'color') {
                const c = opt.value || '#ffffff';
                visual = `<div class="visual-preview" style="background-color:${c}"></div>`;
            } else if(attr.type === 'image') {
                if(opt.value) {
                    visual = `<div class="visual-preview is-image"><img src="/storage/${opt.value}" class="visual-preview-img"></div>`;
                } else {
                    visual = `<div class="visual-preview is-image"><span style="color:#ccc">📷</span></div>`;
                }
            } else {
                visual = `<div class="visual-preview"><span style="font-size:10px; color:#666">Aa</span></div>`;
            }

            el.innerHTML = `
        ${visual}
        <div class="opt-info">
            <div class="opt-name">${opt.name}</div>
            <div class="opt-val-text">${opt.value || ''}</div>
        </div>
        <div style="color:#ccc;">✎</div>
    `;
            return el;
        }

        // --- LOGIQUE MODALE ---
        const modal = document.getElementById('editModal');
        const mId = document.getElementById('modalOptId');
        const mAttrId = document.getElementById('modalAttrId');
        const mName = document.getElementById('modalName');
        const mType = document.getElementById('modalType');

        const mVisSection = document.getElementById('modalVisualSection');
        const mColorUI = document.getElementById('modalColorUI');
        const mImageUI = document.getElementById('modalImageUI');

        const mColorInp = document.getElementById('modalColorInput');
        const mColorHex = document.getElementById('modalColorHex');
        const mImgPreview = document.getElementById('modalImagePreview');
        const mImgPath = document.getElementById('modalImagePath');

        function openEditModal(attr, opt) {
            mId.value = opt.id;
            mAttrId.value = attr.id;
            mName.value = opt.name;
            mType.value = attr.type;

            setupVisualUI(attr.type, opt.value);

            modal.style.display = 'flex';
        }

        function openNewModal(attrId, type) {
            mId.value = 0; // Nouveau
            mAttrId.value = attrId;
            mName.value = '';
            mType.value = type;

            setupVisualUI(type, null);

            modal.style.display = 'flex';
            mName.focus();
        }

        function setupVisualUI(type, value) {
            mVisSection.style.display = 'none';
            mColorUI.style.display = 'none';
            mImageUI.style.display = 'none';

            if(type === 'select') return; // Pas de visuel

            mVisSection.style.display = 'block';

            if(type === 'color') {
                mColorUI.style.display = 'block';
                mColorInp.value = value || '#000000';
                mColorHex.textContent = mColorInp.value;
                mColorInp.oninput = () => mColorHex.textContent = mColorInp.value;
            }

            if(type === 'image') {
                mImageUI.style.display = 'block';
                mImgPath.value = value || ''; // Chemin actuel
                renderModalImage(value);
            }
        }

        function renderModalImage(path) {
            if(path) {
                mImgPreview.innerHTML = `<img src="/storage/${path}" style="width:100%; height:100%; object-fit:cover;">`;
            } else {
                mImgPreview.innerHTML = '<span style="color:#ccc; font-size:24px;">📷</span>';
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // --- LOGIQUE UPLOAD IMAGE (DANS MODALE) ---
        async function handleFileSelect(input) {
            const file = input.files[0];
            if(!file) return;

            // On upload tout de suite pour avoir le path (plus simple)
            // Astuce : on peut utiliser un ID temporaire 0 si c'est une création,
            // mais upload_texture.php a besoin d'un ID pour nommer le fichier.
            // -> On va générer un nom basé sur timestamp dans le PHP, l'ID est moins critique pour le nommage désormais.

            // Hack: Si c'est une création (ID=0), on envoie ID=0 au PHP.
            // Assurez-vous que upload_texture.php accepte ID=0 ou génère un nom sans ID.
            const optId = mId.value || 0;

            const fd = new FormData();
            fd.append('texture', file);
            fd.append('option_id', optId);

            mImgPreview.style.opacity = 0.5;
            try {
                const res = await fetch(`${apiBaseUrl}/attributes/upload_texture.php`, { method:'POST', body:fd });
                const data = await res.json();
                if(data.success) {
                    mImgPath.value = data.data.file_path; // On stocke le nouveau chemin
                    renderModalImage(data.data.file_path);
                } else {
                    alert('Erreur upload');
                }
            } catch(e) { console.error(e); }
            mImgPreview.style.opacity = 1;
        }

        function removeImage() {
            mImgPath.value = ''; // Vide la valeur
            renderModalImage(null);
        }

        // --- SAUVEGARDE FINALE ---
        async function saveOption() {
            const type = mType.value;
            let finalValue = null;

            if(type === 'color') finalValue = mColorInp.value;
            if(type === 'image') finalValue = mImgPath.value; // Peut être vide si retirée

            const payload = {
                id: mId.value,
                attribute_id: mAttrId.value,
                name: mName.value,
                value: finalValue
            };

            try {
                const res = await fetch(`${apiBaseUrl}/attributes/option_save.php`, {
                    method: 'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
                });
                const data = await res.json();

                if(data.success) {
                    closeModal();
                    loadAttributes(); // Reload grid
                } else {
                    alert('Erreur: ' + (data.error||'Inconnue'));
                }
            } catch(e) { console.error(e); }
        }

        async function disableOption() {
            if(!mId.value || mId.value == 0) return;
            if(!confirm('Êtes-vous sûr de vouloir retirer cette option ?')) return;

            try {
                await fetch(`${apiBaseUrl}/attributes/option_save.php`, {
                    method: 'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ action:'disable', id: mId.value })
                });
                closeModal();
                loadAttributes();
            } catch(e) {}
        }

        loadAttributes();
    </script>

<?php require __DIR__ . '/_footer.php'; ?>