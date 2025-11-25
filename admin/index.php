<?php
// EXEMPLE d’en-tête de page admin (products.php, orders.php, etc.)

declare(strict_types=1);

$pageTitle = 'Nanook - admin';
$activeMenu = 'index';
require __DIR__ . '/_header.php';
?>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 420px;
            margin: 80px auto;
            background: #ffffff;
            padding: 24px 28px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 12px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #111827;
        }
        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 4px;
            border: none;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }
        button[disabled] {
            opacity: 0.6;
            cursor: default;
        }
        .error {
            color: #b91c1c;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .success {
            color: #15803d;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .hidden {
            display: none;
        }
        .admin-area {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .admin-email {
            font-weight: 500;
        }
        .logout-btn {
            background: #b91c1c;
        }
        .brand {
            font-weight: 600;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><span class="brand">NANOOK</span> · Admin</h1>
    <div class="subtitle">Connexion à l’espace de gestion.</div>

    <div id="message" class="hidden"></div>

    <form id="loginForm">
        <label for="loginEmail">E-mail</label>
        <input type="email" id="loginEmail" name="email" autocomplete="username" required>

        <label for="loginPassword">Mot de passe</label>
        <input type="password" id="loginPassword" name="password" autocomplete="current-password" required>

        <button type="submit" id="loginButton">Se connecter</button>
    </form>

    <div id="adminArea" class="admin-area hidden">
        <div class="admin-header">
            <span class="admin-email" id="adminEmail"></span>
            <button type="button" class="logout-btn" id="logoutButton">Se déconnecter</button>
        </div>
        <div>
            Zone protégée admin (à remplacer par la gestion produits / commandes / etc.).
        </div>
    </div>
</div>

<script>
    const apiBaseUrl = '/admin/api';
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const messageDiv = document.getElementById('message');
    const adminArea = document.getElementById('adminArea');
    const adminEmailSpan = document.getElementById('adminEmail');
    const logoutButton = document.getElementById('logoutButton');

    function showMessage(text, type) {
        if (!text) {
            messageDiv.textContent = '';
            messageDiv.className = 'hidden';
            return;
        }
        messageDiv.textContent = text;
        messageDiv.className = type === 'error' ? 'error' : 'success';
    }

    async function fetchCurrentAdmin() {
        try {
            const res = await fetch(`${apiBaseUrl}/me.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = await res.json();
            if (data.authenticated) {
                adminEmailSpan.textContent = data.admin.email;
                adminArea.classList.remove('hidden');
                loginForm.classList.add('hidden');
            } else {
                adminArea.classList.add('hidden');
                loginForm.classList.remove('hidden');
            }
        } catch (error) {
            console.error(error);
        }
    }

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage('', '');
        loginButton.disabled = true;

        const emailInput = document.getElementById('loginEmail');
        const passwordInput = document.getElementById('loginPassword');

        let payload = {
            email: emailInput.value,
            password: passwordInput.value
        };

        try {
            const res = await fetch(`${apiBaseUrl}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showMessage('Identifiants invalides.', 'error');
                loginButton.disabled = false;
                return;
            }

            showMessage('Connexion réussie.', 'success');
            emailInput.value = '';
            passwordInput.value = '';

            await fetchCurrentAdmin();
        } catch (error) {
            console.error(error);
            showMessage('Erreur de connexion au serveur.', 'error');
        } finally {
            loginButton.disabled = false;
        }
    });

    logoutButton.addEventListener('click', async () => {
        try {
            const res = await fetch(`${apiBaseUrl}/logout.php`, {
                method: 'POST',
                credentials: 'include'
            });
            const data = await res.json();
            if (data.success) {
                showMessage('Déconnexion effectuée.', 'success');
                adminArea.classList.add('hidden');
                loginForm.classList.remove('hidden');
            }
        } catch (error) {
            console.error(error);
            showMessage('Erreur lors de la déconnexion.', 'error');
        }
    });

    fetchCurrentAdmin();
</script>
<?php
require __DIR__ . '/_footer.php';