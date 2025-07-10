<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin Kost</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(80, 36, 180, 0.15);
            padding: 40px 32px 32px 32px;
            width: 100%;
            max-width: 370px;
            text-align: center;
            position: relative;
        }
        .login-header {
            margin-bottom: 28px;
        }
        .login-header .fa-user-shield {
            font-size: 2.5rem;
            color: #7c3aed;
            margin-bottom: 10px;
        }
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d176a;
            margin-bottom: 6px;
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            padding: 12px 14px;
            border: 1.5px solid #cfc6f7;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border 0.2s;
            background: #f7f5ff;
        }
        .login-form input:focus {
            border-color: #7c3aed;
        }
        .login-form button {
            background: linear-gradient(90deg, #7c3aed 0%, #8b5cf6 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .login-form button:hover {
            background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
            box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13);
        }
        .error {
            color: #e53e3e;
            background: #fff0f0;
            border: 1px solid #ffc2c2;
            border-radius: 6px;
            padding: 8px 0;
            margin-bottom: 12px;
        }
        @media (max-width: 480px) {
            .login-container {
                padding: 24px 8px 18px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <div class="login-title">Login Admin</div>
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Username atau password salah!</div>
        <?php endif; ?>
        <form class="login-form" method="post" action="proses_login.php">
            <input type="text" name="username" placeholder="Username" required autofocus autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html> 