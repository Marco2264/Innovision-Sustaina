<?php
/**
 * Food Saver - Login Page
 */
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Saver</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --bg-surface: #fafafa;
            --bg-card: #ffffff;
            --primary: #064e3b;
            --primary-hover: #022c22;
            --border-color: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 20px;
            --radius-xl: 24px;
            --glass-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-surface);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-container {
            background: var(--bg-card);
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-icon {
            background: var(--primary);
            width: 48px;
            height: 48px;
            color: #faf9f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .input-group svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 18px;
            height: 18px;
        }

        input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: #fbfbf9;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition-fast);
        }

        input:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(6, 78, 59, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition-fast);
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(6, 78, 59, 0.15);
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(6, 78, 59, 0.2);
        }

        .auth-links {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .auth-links a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .toast {
            background: white;
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid var(--primary);
            width: 320px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.danger {
            border-left-color: #dc2626;
        }

        .toast.success {
            border-left-color: #16a34a;
        }
        
        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast.danger .toast-icon {
            color: #dc2626;
        }

        .toast.success .toast-icon {
            color: #16a34a;
        }

        .toast-content {
            display: flex;
            flex-direction: column;
        }

        .toast-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .toast-message {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-icon">
            <i data-lucide="leaf"></i>
        </div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to manage your food inventory.</p>
        
        <form method="POST" action="actions.php">
            <input type="hidden" name="action" value="login">
            
            <div class="input-group">
                <i data-lucide="mail"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            
            <div class="input-group">
                <i data-lucide="lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn-submit">Log In</button>
        </form>
        
        <div class="auth-links">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        lucide.createIcons();

        function showToast(title, message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const iconName = type === 'success' ? 'check-circle' : 'alert-circle';
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i data-lucide="${iconName}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;
            
            container.appendChild(toast);
            lucide.createIcons({ root: toast });
            
            // Trigger animation
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
            
            // Auto dismiss
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    </script>

    <?php if (isset($_SESSION['toast'])): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                showToast(
                    "<?= addslashes($_SESSION['toast']['title']) ?>",
                    "<?= addslashes($_SESSION['toast']['message']) ?>",
                    "<?= $_SESSION['toast']['type'] ?>"
                );
            });
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
</body>
</html>
