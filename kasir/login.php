<?php
require '../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login sebagai kasir, redirect ke dashboard
if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'kasir') {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Login Kasir - Walini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../assets/img/login.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #198754;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #198754;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-login:hover {
            background: #146c43;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .form-group input {
                font-size: 16px; /* Mencegah zoom di iOS */
            }
        }
        /* ============================================
   LOGIN PAGE - MOBILE OPTIMIZATION
   ============================================ */
@media (max-width: 768px) {
    
    /* Body padding lebih kecil */
    body {
        padding: 10px !important;
    }
    
    /* Card login lebih compact */
    .login-container {
        padding: 25px 20px !important;
        max-width: 100% !important;
        width: 100% !important;
        border-radius: 12px !important;
        margin: 0 !important;
    }
    
    /* Header lebih kecil */
    .login-header {
        margin-bottom: 20px !important;
    }
    
    .login-header h1 {
        font-size: 24px !important;
        margin-bottom: 5px !important;
    }
    
    .login-header p {
        font-size: 12px !important;
    }
    
    /* Form group lebih rapat */
    .form-group {
        margin-bottom: 15px !important;
    }
    
    .form-group label {
        font-size: 13px !important;
        margin-bottom: 6px !important;
    }
    
    .form-group input {
        padding: 10px 12px !important;
        font-size: 14px !important;
        border-radius: 6px !important;
    }
    
    /* Tombol masuk lebih kecil */
    .btn-login {
        padding: 12px !important;
        font-size: 15px !important;
        border-radius: 6px !important;
        min-height: 44px !important;
    }
    
    /* Error message lebih kecil */
    .error-message {
        padding: 10px !important;
        font-size: 12px !important;
        margin-bottom: 15px !important;
    }
}

/* HP KECIL (< 375px) */
@media (max-width: 374px) {
    .login-container {
        padding: 20px 15px !important;
    }
    
    .login-header h1 {
        font-size: 22px !important;
    }
    
    .form-group input {
        padding: 10px !important;
        font-size: 14px !important;
    }
}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>WALINI</h1>
            <p>Login Kasir</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label><i class="bi bi-envelope"></i> Email</label>
                <input type="email" name="email" placeholder="Masukkan email kasir" required>
            </div>
            
            <div class="form-group">
                <label><i class="bi bi-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
        </form>
    </div>
</body>
</html>