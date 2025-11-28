<?php
declare(strict_types=1);
$pageTitle = 'Éditer une page';
$activeMenu = 'pages';
require __DIR__ . '/_header.php';
?>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
    <script type="importmap">
        {
            "imports": {
                "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
                "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
            }
        }
    </script>
    <script type="module">
        import {
            ClassicEditor, Essentials, Bold, Italic, Link, Paragraph, List, BlockQuote,
            Table, TableToolbar, SourceEditing, Heading
        } from 'ckeditor5';

        function initCkEditor(elementOrSelector) {
            const element = typeof elementOrSelector === 'string'
                ? document.querySelector(elementOrSelector)
                : elementOrSelector;

            if (!element) return Promise.reject("Élément non trouvé");

            return ClassicEditor.create(element, {
                plugins: [
                    Essentials, Bold, Italic, Link, Paragraph, List, BlockQuote,
                    Table, TableToolbar, SourceEditing, Heading
                ],
                toolbar: {
                    items: [
                        'heading', '|', 'bold', 'italic', 'link', '|',
                        'bulletedList', 'numberedList', '|',
                        'insertTable', 'blockQuote', '|', 'undo', 'redo', '|', 'sourceEditing'
                    ]
                }
            });
        }
        window.initCkEditor = initCkEditor;
    </script>

    <div class="page">
        <div class="page-header">
            <div class="title"><span class="brand">NANOOK</span> · Page</div>
            <div style="display:flex; gap:10px;">
                <a href="#" id="viewBtn" target="_blank" class="btn-secondary" style="display:none;">👁️ Visualiser</a>
                <a href="/admin/pages.php" class="back-link">Retour liste</a>
            </div>
        </div>

        <div id="message" class="message" style="display:none;"></div>

        <div class="card">
            <form id="pageForm">
                <input type="hidden" id="pageId">

                <div class="form-group">
                    <label>Titre de la page</label>
                    <input type="text" id="titleInput" required>
                </div>

                <div class="grid-two" style="display:grid; grid-template-columns: 1fr 200px; gap:20px;">
                    <div class="form-group">
                        <label>Slug (URL)</label>
                        <input type="text" id="slugInput" required>
                        <div class="help-text" style="font-size:11px; color:#666;">https://nanook.paris/i/<span id="slugPreview">...</span></div>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <div class="checkbox-wrapper" style="margin-top:8px;">
                            <input type="checkbox" id="isActiveInput">
                            <label for="isActiveInput" style="display:inline;">En ligne</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Chapeau (Introduction)</label>
                    <textarea id="chapeauInput" rows="3" style="width:100%; border:1px solid #ddd; padding:10px;"></textarea>
                </div>

                <div class="form-group">
                    <label>Contenu</label>
                    <textarea id="contentInput" class="ck" style="min-height:300px;"></textarea>
                </div>

                <div id="gallerySection" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px; display:none;">
                    <h3 style="font-size:15px; margin-bottom:10px;">Galerie d'images</h3>
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:15px;">
                        <input type="file" id="imageUpload" accept="image/*">
                        <button type="button" class="btn-secondary" id="uploadBtn">Ajouter</button>
                    </div>
                    <div id="imagesGrid" style="display:flex; flex-wrap:wrap; gap:10px;"></div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn-primary" id="saveBtn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api';
        let editorInstance = null;
        let currentPageId = null;

        
        function slugify(text) {
            return text.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');
        }

        document.getElementById('titleInput').addEventListener('input', (e) => {
            if(!currentPageId) {
                const s = slugify(e.target.value);
                document.getElementById('slugInput').value = s;
                document.getElementById('slugPreview').textContent = s;
            }
        });
        document.getElementById('slugInput').addEventListener('input', (e) => {
            document.getElementById('slugPreview').textContent = e.target.value;
        });

        
        window.addEventListener('DOMContentLoaded', async () => {
            
            try {
                editorInstance = await window.initCkEditor('#contentInput');
            } catch(e) { console.error(e); }

            
            const params = new URLSearchParams(window.location.search);
            if(params.get('id')) {
                currentPageId = params.get('id');
                document.getElementById('pageId').value = currentPageId;
                document.getElementById('gallerySection').style.display = 'block';
                await loadPage(currentPageId);
                await loadImages(currentPageId);
            }
        });

        async function loadPage(id) {
            const res = await fetch(`${apiBaseUrl}/pages/get.php?id=${id}`);
            const data = await res.json();
            if(data.success) {
                const p = data.data;
                document.getElementById('titleInput').value = p.title;
                document.getElementById('slugInput').value = p.slug;
                document.getElementById('slugPreview').textContent = p.slug;
                document.getElementById('chapeauInput').value = p.chapeau || '';
                document.getElementById('isActiveInput').checked = (p.is_active == 1);
                if(editorInstance) editorInstance.setData(p.content || '');

                
                const viewBtn = document.getElementById('viewBtn');
                viewBtn.href = `/i/${p.slug}`;
                viewBtn.style.display = 'inline-flex';
            }
        }

        
        document.getElementById('pageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.disabled = true; btn.textContent = 'Enregistrement...';

            const payload = {
                id: currentPageId,
                title: document.getElementById('titleInput').value,
                slug: document.getElementById('slugInput').value,
                chapeau: document.getElementById('chapeauInput').value,
                content: editorInstance ? editorInstance.getData() : '',
                is_active: document.getElementById('isActiveInput').checked ? 1 : 0
            };

            try {
                const res = await fetch(`${apiBaseUrl}/pages/save.php`, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.success) {
                    if(!currentPageId) window.location.href = `/admin/page_form.php?id=${data.data.id}`;
                    else {
                        
                        loadPage(currentPageId);
                    }
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch(e) { console.error(e); }
            finally { btn.disabled = false; btn.textContent = 'Enregistrer'; }
        });

        
        async function loadImages(pid) {
            const res = await fetch(`${apiBaseUrl}/page_images/list.php?page_id=${pid}`);
            const data = await res.json();
            const grid = document.getElementById('imagesGrid');
            grid.innerHTML = '';
            if(data.success) {
                data.data.forEach(img => {
                    const div = document.createElement('div');
                    div.style.cssText = "width:100px; height:100px; position:relative; border:1px solid #ddd;";
                    div.innerHTML = `
                    <img src="/storage/page_images/${img.file_path}" style="width:100%; height:100%; object-fit:cover;">
                    <button onclick="deleteImage(${img.id})" style="position:absolute; top:0; right:0; background:red; color:white; border:none; padding:2px 6px; cursor:pointer;">×</button>
                `;
                    grid.appendChild(div);
                });
            }
        }

        document.getElementById('uploadBtn').addEventListener('click', async () => {
            const file = document.getElementById('imageUpload').files[0];
            if(!file || !currentPageId) return;
            const fd = new FormData();
            fd.append('page_id', currentPageId);
            fd.append('image', file);
            await fetch(`${apiBaseUrl}/page_images/upload.php`, { method:'POST', body:fd });
            document.getElementById('imageUpload').value = '';
            loadImages(currentPageId);
        });

        window.deleteImage = async (id) => {
            if(!confirm('Supprimer ?')) return;
            await fetch(`${apiBaseUrl}/page_images/delete.php`, { method:'POST', body:JSON.stringify({id}) });
            loadImages(currentPageId);
        };
    </script>
<?php require __DIR__ . '/_footer.php'; ?>