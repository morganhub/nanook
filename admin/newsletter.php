<?php

declare(strict_types=1);
$pageTitle = 'Abonnés Newsletter';
$activeMenu = 'newsletter'; 
require __DIR__ . '/_header.php';
?>

    <div class="page">
        <div class="page-header">
            <div class="title"><span class="brand">NANOOK</span> · Newsletter</div>
        </div>

        <div id="message" class="message" style="display:none;"></div>

        <div class="card">
            <table>
                <thead>
                <tr>
                    <th>Inscrit le</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Désabo. le</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody id="subscribersTableBody"></tbody>
            </table>
        </div>
    </div>

    <div class="overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Éditer l'abonné</div>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm">
                <input type="hidden" id="editId">
                <label>Email</label>
                <input type="email" id="editEmail" required>

                <div class="checkbox-wrapper" style="margin-top:15px;">
                    <input type="checkbox" id="editIsActive">
                    <label for="editIsActive" style="display:inline;">Abonnement Actif</label>
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api/newsletter';
        const tbody = document.getElementById('subscribersTableBody');
        const editModal = document.getElementById('editModal');

        
        async function loadSubscribers() {
            try {
                const res = await fetch(`${apiBaseUrl}/list.php`);
                const data = await res.json();
                if (data.success) renderTable(data.data);
            } catch (e) { console.error(e); }
        }

        function renderTable(subs) {
            if (!subs.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">Aucun abonné.</td></tr>';
                return;
            }
            tbody.innerHTML = subs.map(s => {
                const dateIn = s.created_at.split(' ')[0];
                const dateOut = s.unsubscribed_at ? s.unsubscribed_at.split(' ')[0] : '-';
                const statusBadge = s.is_active
                    ? '<span class="badge badge-green">Actif</span>'
                    : '<span class="badge badge-red">Désabonné</span>';

                
                const json = JSON.stringify(s).replace(/"/g, '&quot;');

                return `
            <tr>
                <td>${dateIn}</td>
                <td style="font-weight:500;">${s.email}</td>
                <td>${statusBadge}</td>
                <td style="color:#666; font-size:12px;">${dateOut}</td>
                <td style="text-align:right;">
                    <button class="action-btn" onclick="openEditModal(${json})">Éditer</button>
                    <button class="btn-danger" style="padding:4px 8px;" onclick="deleteSub(${s.id})">×</button>
                </td>
            </tr>
            `;
            }).join('');
        }

        
        window.openEditModal = function(sub) {
            document.getElementById('editId').value = sub.id;
            document.getElementById('editEmail').value = sub.email;
            document.getElementById('editIsActive').checked = (sub.is_active === 1);
            editModal.classList.add('active');
        };

        window.closeEditModal = function() {
            editModal.classList.remove('active');
        };

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: document.getElementById('editId').value,
                email: document.getElementById('editEmail').value,
                is_active: document.getElementById('editIsActive').checked ? 1 : 0
            };

            await fetch(`${apiBaseUrl}/save.php`, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            closeEditModal();
            loadSubscribers();
        });

        
        window.deleteSub = async function(id) {
            if(!confirm("Supprimer définitivement cet abonné ?")) return;
            await fetch(`${apiBaseUrl}/delete.php`, {
                method: 'POST',
                body: JSON.stringify({id})
            });
            loadSubscribers();
        };

        loadSubscribers();
    </script>

<?php require __DIR__ . '/_footer.php'; ?>