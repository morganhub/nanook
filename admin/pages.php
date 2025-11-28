<?php
declare(strict_types=1);
$pageTitle = 'Pages CMS';
$activeMenu = 'pages'; // Ajoutez ce cas dans admin/_header.php pour l'highlight
require __DIR__ . '/_header.php';
?>
    <div class="page">
        <div class="page-header">
            <div class="title"><span class="brand">NANOOK</span> · Pages</div>
            <a href="/admin/page_form.php" class="btn-primary">Créer une page</a>
        </div>

        <div id="message" class="message" style="display:none;"></div>

        <div class="card">
            <table>
                <thead>
                <tr>
                    <th>Titre</th>
                    <th>URL (Slug)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="pagesTableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api';
        const tbody = document.getElementById('pagesTableBody');

        async function loadPages() {
            const res = await fetch(`${apiBaseUrl}/pages/list.php`);
            const data = await res.json();
            if(data.success) renderPages(data.data);
        }

        function renderPages(pages) {
            if(!pages.length) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#888;">Aucune page.</td></tr>'; return; }

            tbody.innerHTML = pages.map(p => `
        <tr>
            <td style="font-weight:600;">${p.title}</td>
            <td style="color:#666;">/i/${p.slug}</td>
            <td>
                <span class="badge ${p.is_active ? 'badge-green' : 'badge-red'}">
                    ${p.is_active ? 'Active' : 'Brouillon'}
                </span>
            </td>
            <td style="display:flex; gap:5px;">
                <a href="/i/${p.slug}" target="_blank" class="btn-secondary" title="Visualiser">👁️</a>
                <a href="/admin/page_form.php?id=${p.id}" class="btn-primary">Éditer</a>
                <button onclick="deletePage(${p.id})" class="btn-danger">×</button>
            </td>
        </tr>
    `).join('');
        }

        async function deletePage(id) {
            if(!confirm('Supprimer cette page ?')) return;
            await fetch(`${apiBaseUrl}/pages/delete.php`, { method:'POST', body:JSON.stringify({id}) });
            loadPages();
        }

        loadPages();
    </script>
<?php require __DIR__ . '/_footer.php'; ?>