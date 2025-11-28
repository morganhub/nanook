<?php

declare(strict_types=1);


require_once __DIR__ . '/api/_bootstrap.php';



if (isset($_SESSION['nanook_admin_id'])) {
    header('Location: /admin/orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nanook Admin - Connexion</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .brand {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.1em;
            margin-bottom: 30px;
            display: block;
            color: #111827;
            text-transform: uppercase;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #000;
        }
        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <span class="brand">Nanook</span>

    <div id="errorMsg" class="error"></div>

    <form id="loginForm">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" id="btnSubmit">Se connecter</button>
    </form>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSubmit');
        const err = document.getElementById('errorMsg');
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        btn.disabled = true;
        btn.textContent = 'Connexion...';
        err.style.display = 'none';

        try {
            const response = await fetch('/admin/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                
                window.location.href = '/admin/orders.php';
            } else {
                throw new Error(data.error || 'Identifiants incorrects');
            }
        } catch (error) {
            console.error(error);
            err.textContent = 'Erreur : ' + error.message;
            err.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Se connecter';
        }
    });
</script>

</body>
</html>