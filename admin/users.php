<?php

declare(strict_types=1);

$pageTitle = 'Utilisateurs Admin';
$activeMenu = 'users'; 
require __DIR__ . '/_header.php';
?>

    <div class="page">
        <div class="page-header">
            <div class="title">Gestion des Administrateurs</div>
            <a href="/admin/user_form.php" class="btn-primary">Ajouter un utilisateur</a>
        </div>

        <div class="card">
            <div id="message" style="display:none; margin-bottom:15px; padding:10px; border-radius:4px;"></div>

            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Rapport</th>
                    <th>Statut</th>
                    <th>Dernière activité</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="usersTableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api';
        const tableBody = document.getElementById('usersTableBody');

        function renderUsers(users) {
            if (!users.length) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">Aucun utilisateur.</td></tr>';
                return;
            }

            tableBody.innerHTML = users.map(u => {
                const statusBadge = u.is_active
                    ? '<span class="badge badge-green">Actif</span>'
                    : '<span class="badge badge-red">Bloqué</span>';

                const roleLabel = u.is_super_admin ? 'Super Admin' : 'Admin';

                
                let reportInfo = '<span class="badge badge-gray">Jamais</span>';
                if (u.report_frequency !== 'never') {
                    const freqLabels = { daily: 'Quotidien', weekly: 'Hebdo', monthly: 'Mensuel' };
                    reportInfo = `<span class="badge badge-gray">${freqLabels[u.report_frequency] || u.report_frequency} à ${u.report_hour}h</span>`;
                }

                return `
                <tr>
                    <td style="font-weight:500;">${u.username}</td>
                    <td>${u.email}</td>
                    <td>${roleLabel}</td>
                    <td>${reportInfo}</td>
                    <td>${statusBadge}</td>
                    <td style="color:#666; font-size:13px;">${u.updated_at || '-'}</td>
                    <td>
                        <a href="/admin/user_form.php?id=${u.id}" class="action-btn">Modifier</a>
                    </td>
                </tr>
            `;
            }).join('');
        }

        async function loadUsers() {
            try {
                const res = await fetch(apiBaseUrl + '/users/list.php');
                const data = await res.json();
                if (data.success) {
                    renderUsers(data.data);
                }
            } catch (e) {
                console.error(e);
            }
        }

        loadUsers();
    </script>
<?php require __DIR__ . '/_footer.php'; ?>