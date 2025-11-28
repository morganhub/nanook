<?php

declare(strict_types=1);

$pageTitle = 'Édition Utilisateur';
require __DIR__ . '/_header.php';
?>


    <div class="page">
        <div class="card">
            <h2 style="margin-top:0; margin-bottom:20px; font-size:18px;">Profil Administrateur</h2>

            <div id="message" style="display:none; padding:10px; border-radius:6px; margin-bottom:20px; font-size:14px;"></div>

            <form id="userForm">
                <input type="hidden" id="userId">

                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" id="username" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" required>
                </div>

                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" id="password" placeholder="Laisser vide pour ne pas changer">
                    <div class="help-text">Uniquement si vous souhaitez le modifier.</div>
                </div>

                <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">
                <h3 style="font-size:16px; margin-bottom:15px;">Accès & Rapports</h3>

                <div class="checkbox-wrapper">
                    <input type="checkbox" id="isActive" checked>
                    <label for="isActive" style="margin:0; font-weight:400;">Compte actif (peut se connecter)</label>
                </div>

                <div class="form-group">
                    <label>Fréquence du rapport d'activité</label>
                    <select id="reportFrequency">
                        <option value="daily">Quotidien (Tous les jours)</option>
                        <option value="weekly">Hebdomadaire (Le Lundi)</option>
                        <option value="monthly">Mensuel (Le 1er du mois)</option>
                        <option value="never">Jamais</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Heure d'envoi du rapport</label>
                    <select id="reportHour">
                        <?php for($i=0; $i<24; $i++): ?>
                            <option value="<?= $i ?>"><?= sprintf('%02d:00', $i) ?></option>
                        <?php endfor; ?>
                    </select>
                    <div class="help-text">Heure du serveur (actuellement <?= date('H:i') ?>)</div>
                </div>

                <div class="actions">
                    <a href="/admin/users.php" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary" id="saveBtn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const apiBaseUrl = '/admin/api';
        const messageEl = document.getElementById('message');
        const userIdInput = document.getElementById('userId');

        function showMessage(text, type) {
            messageEl.textContent = text;
            messageEl.style.display = 'block';
            messageEl.style.background = (type === 'success') ? '#dcfce7' : '#fee2e2';
            messageEl.style.color = (type === 'success') ? '#166534' : '#b91c1c';
        }

        async function loadUser() {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');
            if (!id) return;

            try {
                const res = await fetch(apiBaseUrl + '/users/get.php?id=' + id);
                const data = await res.json();

                if (data.success) {
                    const u = data.data;
                    userIdInput.value = u.id;
                    document.getElementById('username').value = u.username;
                    document.getElementById('email').value = u.email;
                    document.getElementById('isActive').checked = (u.is_active == 1);
                    document.getElementById('reportFrequency').value = u.report_frequency;
                    document.getElementById('reportHour').value = u.report_hour;
                }
            } catch (e) {
                console.error(e);
                showMessage('Erreur chargement utilisateur', 'error');
            }
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.textContent = 'Enregistrement...';

            const payload = {
                id: userIdInput.value || null,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value || null,
                is_active: document.getElementById('isActive').checked ? 1 : 0,
                report_frequency: document.getElementById('reportFrequency').value,
                report_hour: parseInt(document.getElementById('reportHour').value)
            };

            try {
                const res = await fetch(apiBaseUrl + '/users/save.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    showMessage('Utilisateur enregistré avec succès.', 'success');
                    if (!payload.id) {
                        setTimeout(() => window.location.href = '/admin/users.php', 1000);
                    }
                } else {
                    showMessage(data.error || 'Erreur enregistrement', 'error');
                }
            } catch (err) {
                console.error(err);
                showMessage('Erreur technique', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enregistrer';
            }
        });

        loadUser();
    </script>
<?php require __DIR__ . '/_footer.php'; ?>