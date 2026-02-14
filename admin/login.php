<?php
require_once __DIR__ . '/../config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - VMDestek Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a1a;
            overflow: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(102, 126, 234, 0.08) 0%, transparent 50%);
            animation: bgFloat 15s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes bgFloat {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(2%, -2%) rotate(1deg);
            }

            66% {
                transform: translate(-1%, 1%) rotate(-0.5deg);
            }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
        }

        .login-header h1 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 15px;
            transition: color 0.3s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.08);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .input-wrapper input:focus+i,
        .input-wrapper input:focus~i {
            color: #667eea;
        }

        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login .spinner {
            display: none;
        }

        .btn-login.loading .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .error-message.show {
            display: flex;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.25);
            font-size: 12px;
        }

        /* Floating particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-10vh) scale(1);
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <div class="particles" id="particles"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h1>VMDestek</h1>
                <p>Admin paneline giriş yapın</p>
            </div>

            <div class="error-message" id="errorMsg">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText"></span>
            </div>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" placeholder="Kullanıcı adınızı girin" required autofocus>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Şifre</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" placeholder="Şifrenizi girin" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="spinner"></span>
                    <span class="btn-text">Giriş Yap</span>
                </button>
            </form>

            <div class="footer">
                VMDestek &copy;
                <?= date('Y') ?>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particle.style.animationDelay = (Math.random() * 10) + 's';
            particle.style.width = (Math.random() * 4 + 2) + 'px';
            particle.style.height = particle.style.width;
            particlesContainer.appendChild(particle);
        }

        async function handleLogin(e) {
            e.preventDefault();

            const btn = document.getElementById('btnLogin');
            const errorMsg = document.getElementById('errorMsg');
            const errorText = document.getElementById('errorText');

            btn.classList.add('loading');
            errorMsg.classList.remove('show');

            try {
                const res = await fetch('../api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value
                    })
                });

                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check" style="margin-right:8px"></i> Giriş Başarılı';
                    setTimeout(() => window.location.href = 'index.php', 500);
                } else {
                    throw new Error(data.error || 'Giriş başarısız');
                }
            } catch (err) {
                errorText.textContent = err.message;
                errorMsg.classList.add('show');
                btn.classList.remove('loading');
            }
        }
    </script>
</body>

</html>